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

function loadProject($id_project)
{
	global $context, $smcFunc, $db_prefix, $scripturl, $user_info, $txt, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.long_description, p.trackers, p.project_groups,
			p.id_comment_mod, p.' . implode(', p.', $context['type_columns']) . ',
			dev.id_member AS is_dev
		FROM {db_prefix}projects AS p
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:member})
		WHERE {query_see_project}
			AND p.id_project = {int:project}
		LIMIT 1',
		array(
			'project' => $id_project,
			'member' => $user_info['id'],
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$project = array(
		'id' => $row['id_project'],
		'link' => '<a href="' . $scripturl . '?project=' . $row['id_project'] . '">' . $row['name'] . '</a>',
		'href' => $scripturl . '?project=' . $row['id_project'],
		'name' => $row['name'],
		'description' => $row['description'],
		'long_description' => $row['long_description'],
		'category' => array(),
		'groups' => explode('', $row['project_groups']),
		'trackers' => array(),
		'developers' => array(),
		'is_developer' => !empty($row['is_dev']),
		'comment_mod' => $row['id_comment_mod'],
	);

	$trackers = explode(',', $row['trackers']);

	foreach ($trackers as $key)
	{
		$project['trackers'][$key] = array(
			'info' => &$context['project_tools']['issue_types'][$key],
			'open' => $row['open_' . $key],
			'closed' => $row['closed_' . $key],
			'total' => $row['open_' . $key] + $row['closed_' . $key],
			'link' => $scripturl . '?project='. $project['id'] . ';sa=issues;type=' . $key,
		);
	}

	// Developers
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name
		FROM {db_prefix}project_developer AS dev
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
		WHERE id_project = {int:project}',
		array(
			'project' => $id_project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$project['developers'][$row['id_member']] = array(
			'id' => $row['id_member'],
			'name' => $row['real_name'],
		);
	}
	$smcFunc['db_free_result']($request);

	// Category
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

function loadVersions($project)
{
	global $context, $smcFunc, $db_prefix, $scripturl, $user_info, $txt;

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT
			id_version, id_parent, version_name, release_date, status
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}
			AND {query_see_version}
		ORDER BY id_parent',
		array(
			'project' => $project['id'],
		)
	);

	$versions = array();
	$version_ids = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_parent'] == 0)
		{
			$versions[$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'sub_versions' => array(),
			);
		}
		else
		{
			$versions[$row['id_parent']]['sub_versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
				'released' => $row['status'] >= 4,
			);
		}

		$version_ids[$row['id_version']] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);

	return array($versions, $version_ids);
}

function projectAllowedTo($permission)
{
	global $context, $project;

	if (empty($project))
		fatal_error('projectAllowedTo(): Project not loaded');

	// Admins can do anything
	if (allowedTo('project_admin'))
		return true;

	$permissions = array(
		'admin' => 50,
		'issue_resolve' => 35,
		'issue_moderate' => 35,
		'delete_comment_any' => 35,
		'delete_comment_own' => 6,
		'issue_attach' => 5,
		'issue_update' => 5,
		'issue_report' => 5,
		'issue_comment' => 4,
		'issue_view' => 1,
		'view' => 1,
	);

	if (isset($permissions[$permission]) && $context['project_levels'][$project] >= $permissions[$permission])
		return true;

	return false;
}

function projectIsAllowedTo($permission)
{
	global $context, $project, $user_info;

	if ($project === null)
		fatal_error('projectAllowed(): Project not loaded');

	if (!projectAllowedTo($permission))
	{
		if ($user_info['is_guest'])
			is_not_guest($txt['cannot_project_' . $permission]);

		fatal_lang_error('cannot_project_' . $permission, false);

		// Getting this far is a really big problem, but let's try our best to prevent any cases...
		trigger_error('Hacking attempt...', E_USER_ERROR);
	}
}

function createProject($projectOptions)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (empty($projectOptions['name']) || !isset($projectOptions['description']))
		trigger_error('createProject(): required parameters missing or invalid', E_USER_ERROR);

	$smcFunc['db_insert']('insert',
		'{db_prefix}projects',
		array(
			'name' => 'string',
			'description' => 'string'
		),
		array(
			$projectOptions['name'],
			$projectOptions['description']
		),
		array()
	);

	$id_project = $smcFunc['db_insert_id']('{db_prefix}projects', 'id_project');

	unset($projectOptions['name'], $projectOptions['description']);

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

	if (isset($projectOptions['project_groups']))
	{
		$projectUpdates[] = 'project_groups = {string:project_groups}';
		$projectOptions['project_groups'] = implode(',', $projectOptions['project_groups']);
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

	if (empty($versionOptions['name']))
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

	$smcFunc['db_insert']('insert',
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

	if (isset($versionOptions['project_groups']))
	{
		$versionUpdates[] = 'project_groups = {string:project_groups}';
		$versionOptions['project_groups'] = implode(',', $versionOptions['project_groups']);
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

	$smcFunc['db_insert']('insert',
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