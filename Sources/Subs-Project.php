<?php
/**********************************************************************************
* Subs-Project.php                                                                *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.1 Alpha                         *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007 by:          Niko Pahajoki (http://www.madjoki.com)              *
* Support, News, Updates at:  http://www.madjoki.com                              *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	!!!
*/

function loadProjectTools($mode = '', $force = false)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $project_version;

	if (!empty($project_version) && !$force)
		return;

	require_once($sourcedir . '/Subs-Issue.php');

	// Which version this is?
	$project_version = '0.1 Alpha';

	// Can see project?
	if ($user_info['is_guest'])
		$see_project = 'FIND_IN_SET(-1, p.member_groups)';

	// Administrators can see all projects.
	elseif ($user_info['is_admin'])
		$see_project = '1 = 1';
	// Registered user.... just the groups in $user_info['groups'].
	else
		$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';

	// Can see version?
	if ($user_info['is_guest'])
		$see_version = 'FIND_IN_SET(-1, ver.member_groups)';
	// Administrators can see all versions.
	elseif ($user_info['is_admin'])
		$see_version = '1 = 1';
	// Registered user.... just the groups in $user_info['groups'].
	else
		$see_version = '(ISNULL(ver.member_groups) OR (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups)))';

	$user_info['query_see_project'] = $see_project;
	$user_info['query_see_version'] = $see_version;

	// Show everything?
	if (allowedTo('issue_view_any'))
		$user_info['query_see_issue'] = "($see_project AND $see_version)";
	// Show only own?
	elseif (allowedTo('issue_view_own'))
		$user_info['query_see_issue'] = "($see_project AND $see_version AND i.reporter = $user_info[id])";
	// if not then we can't show anything
	else
		$user_info['query_see_issue'] = "(0 = 1)";

	$context['project_tools'] = array();
	$context['issue_tracker'] = array();

	loadLanguage('Project+Issue');
	loadIssueTypes();
	loadTemplate('Project', array('forum', 'project'));
}

function loadProject($id_project, $detailed = true)
{
	global $context, $smcFunc, $db_prefix, $scripturl, $user_info, $txt;

	$columns = implode(', p.', $context['type_columns']);

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.long_description, p.member_groups, p.trackers,
			p.' . $columns . '
		FROM {db_prefix}projects AS p
		WHERE {query_see_project}
			AND p.id_project = {int:project}
		LIMIT 1',
		array(
			'project' => $id_project,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$project = array(
		'id' => $row['id_project'],
		'link' => $scripturl . '?project=' . $row['id_project'],
		'name' => $row['name'],
		'description' => $row['description'],
		'long_description' => $row['long_description'],
		'member_groups' => $row['member_groups'],
		'versions' => array(),
		'parents' => array(),
		'category' => array(),
		'issues' => array(),
		'trackers' => explode(',', $row['trackers']),
	);

	if (!$detailed)
		return $project;

	foreach ($project['trackers'] as $key)
	{
		$project['issues'][$key] = array(
			'info' => &$context['project_tools']['issue_types'][$key],
			'open' => $row['open_' . $key],
			'closed' => $row['closed_' . $key],
			'total' => $row['open_' . $key] + $row['closed_' . $key],
			'link' => $scripturl . '?project='. $project['id'] . ';sa=issues;type=' . $key
		);
	}

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT
			id_version, id_parent, version_name, release_date, status, ' .  implode(', ', $context['type_columns']) . '
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}
			AND {query_see_version}
		ORDER BY id_parent',
		array(
			'project' => $id_project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$issues = array();
		foreach ($project['trackers'] as $key)
		{
			$issues[$key] = array(
				'info' => &$context['project_tools']['issue_types'][$key],
				'open' => $row['open_' . $key],
				'closed' => $row['closed_' . $key],
				'total' => $row['open_' . $key] + $row['closed_' . $key],
				'link' => $scripturl . '?project='. $project['id'] . ';sa=issues;version=' . $row['id_version'] . ';type=' . $key
			);
		}

		if ($row['id_parent'] == 0)
		{
			$project['versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'issues' => $issues,
				'sub_versions' => array(),
			);
		}
		else
		{
			$project['versions'][$row['id_parent']]['sub_versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
				'released' => $row['status'] >= 4,
				'issues' => $issues,
			);
		}

		$project['parents'][$row['id_version']] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT id_category, category_name
		FROM {db_prefix}issue_category AS cat
		WHERE id_project = {int:project}',
		array(
			'project' => $id_project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$project['category'][$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name']
		);
	$smcFunc['db_free_result']($request);

	return $project;
}

function projectAllowed($permission)
{
	global $context;

	if ($project === null)
	{
	}
}

function createProject($projectOptions)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (empty($projectOptions['name']) || !isset($projectOptions['member_groups']) || !is_array($projectOptions['member_groups']))
		trigger_error('createProject(): required parameters missing or invalid');

	$projectOptions['member_groups'] = implode(',', $projectOptions['member_groups']);

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}projects',
		array(
			'name' => 'string',
			'member_groups' => 'string',
			'description' => 'string'
		),
		array(
			$projectOptions['name'],
			$projectOptions['member_groups'],
			$projectOptions['description']
		),
		array()
	);

	$id_project = $smcFunc['db_insert_id']('{db_prefix}projects', 'id_project');

	unset($projectOptions['name'], $projectOptions['description'], $projectOptions['member_groups']);

	// Anything left?
	if (!empty($projectOptions))
		updateProject($id_project, $projectOptions);

	return $id_project;
}

function updateProject($id_project, $projectOptions)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Boards.php');

	$projectUpdates = array();

	if (isset($projectOptions['name']))
		$projectUpdates[] = 'name = {string:name}';
	if (isset($projectOptions['description']))
		$projectUpdates[] = 'description = {string:description}';

	if (isset($projectOptions['long_description']))
		$projectUpdates[] = 'long_description = {string:long_description}';

	if (isset($projectOptions['trackers']))
	{
		$projectUpdates[] = 'trackers = {string:trackers}';
		$projectOptions['trackers'] = implode(',', $projectOptions['trackers']);
	}

	if (isset($projectOptions['member_groups']))
	{
		$projectUpdates[] = 'member_groups = {string:member_groups}';
		$projectOptions['member_groups'] = implode(',', $projectOptions['member_groups']);
	}

	if (!empty($projectUpdates))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}projects
			SET
				' . implode(',
				', $projectUpdates) . '
			WHERE id_project = {int:project}',
			array_merge($projectOptions, array(
				'project' => $id_project,
			))
		);

	return true;
}

function createVersion($id_project, $versionOptions)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (empty($versionOptions['name']) || !isset($versionOptions['member_groups']) || !is_array($versionOptions['member_groups']))
		trigger_error('createVersion(): required parameters missing or invalid');

	if (empty($versionOptions['release_date']) || empty($versionOptions['parent']))
		$versionOptions['release_date'] = serialize(array('day' => 0, 'month' => 0, 'year' => 0));

	if (empty($versionOptions['description']))
		$versionOptions['description'] = '';

	if (empty($versionOptions['parent']))
	{
		$versionOptions['parent'] = 0;
		$versionOptions['status'] = 0;
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND id_version = {int:version}',
			array(
				'project' => $id_project,
				'version' => $versionOptions['parent'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			trigger_error('createVersion(): invalid parent');
		$smcFunc['db_free_result']($request);
	}

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}project_versions',
		array(
			'id_project' => 'int',
			'id_parent' => 'int',
			'version_name' => 'string',
			'description' => 'string',
		),
		array(
			$id_project,
			$versionOptions['parent'],
			$versionOptions['name'],
			$versionOptions['description']
		),
		array('id_version')
	);

	$id_version = $smcFunc['db_insert_id']('{db_prefix}project_versions', 'id_version');

	unset($versionOptions['parent'], $versionOptions['name'], $versionOptions['description']);

	updateVersion($id_version, $versionOptions);

	return $id_version;
}

function updateVersion($id_version, $versionOptions)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$versionUpdates = array();

	if (isset($versionOptions['name']))
		$versionUpdates[] = 'version_name = {string:name}';

	if (isset($versionOptions['description']))
		$versionUpdates[] = 'description = {string:description}';

	if (isset($versionOptions['release_date']))
		$versionUpdates[] = 'release_date = {string:release_date}';

	if (isset($versionOptions['member_groups']))
	{
		$versionUpdates[] = 'member_groups = {string:member_groups}';
		$versionOptions['member_groups'] = implode(',', $versionOptions['member_groups']);
	}

	if (isset($versionOptions['status']))
		$versionUpdates[] = 'status = {int:status}';

	if (!empty($versionUpdates))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET
				' . implode(',
				', $versionUpdates) . '
			WHERE id_version = {int:version}',
			array_merge($versionOptions, array(
				'version' => $id_version,
			))
		);

	return true;
}

function createCategory($id_project, $categoryOptions)
{
	global $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}issue_category',
		array(
			'id_project' => 'int',
			'category_name' => 'string'
		),
		array(
			$id_project,
			$categoryOptions['name']
		),
		array('id_category')
	);

	return true;
}

function updateCategory($id_category, $categoryOptions)
{
	global $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$categoryUpdates = array();

	if (isset($categoryOptions['name']))
		$categoryUpdates[] = 'category_name = {string:name}';

	if (isset($categoryOptions['project']))
		$categoryUpdates[] = 'id_project = {int:project}';

	if (!empty($categoryOptions))
		$request = $smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_category
		SET
			' . implode(',
			', $categoryUpdates) . '
		WHERE id_category = {int:category}',
		array_merge($categoryOptions, array(
			'category' => $id_category,
		))
	);

	return true;
}
?>