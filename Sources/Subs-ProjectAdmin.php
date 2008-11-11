<?php
/**********************************************************************************
* Subs-ProjectAdmin.php                                                           *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.2                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2008 by:     Niko Pahajoki (http://www.madjoki.com)              *
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

function createProject($projectOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	if (empty($projectOptions['name']) || !isset($projectOptions['description']) || !isset($projectOptions['member_groups']) || !isset($projectOptions['trackers']))
		trigger_error('createProject(): required parameters missing or invalid', E_USER_ERROR);

	$smcFunc['db_insert']('insert',
		'{db_prefix}projects',
		array(
			'name' => 'string',
			'description' => 'string',
			'long_description' => 'string',
			'trackers' => 'string',
			'member_groups' => 'string',
			'id_profile' => 'int',
		),
		array(
			$projectOptions['name'],
			$projectOptions['description'],
			isset($projectOptions['long_description']) ? $projectOptions['long_description'] : '',
			implode(',', $projectOptions['trackers']),
			implode(',', $projectOptions['member_groups']),
			empty($projectOptions['profile']) ? 1 : $projectOptions['profile'],
		),
		array('id_project')
	);

	$id_project = $smcFunc['db_insert_id']('{db_prefix}projects', 'id_project');

	unset($projectOptions['name'], $projectOptions['description'], $projectOptions['trackers'], $projectOptions['member_groups'], $projectOptions['profile']);

	// Anything left?
	if (!empty($projectOptions))
		updateProject($id_project, $projectOptions);

	return $id_project;
}

function createVersion($id_project, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

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
			'member_groups' => 'string',
		),
		array(
			$id_project,
			$versionOptions['parent'],
			$versionOptions['name'],
			$versionOptions['description'],
			implode(',', $versionOptions['member_groups']),
		),
		array('id_version')
	);

	$id_version = $smcFunc['db_insert_id']('{db_prefix}project_versions', 'id_version');

	unset($versionOptions['parent'], $versionOptions['name'], $versionOptions['description'], $versionOptions['member_groups']);

	updateVersion($id_version, $versionOptions);

	return $id_version;
}

function createPTCategory($id_project, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

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

function loadProjectAdmin($id_project)
{
	global $context, $smcFunc, $user_info, $txt, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.long_description, p.trackers, p.member_groups,
			p.id_category, p.cat_position, p.' . implode(', p.', $context['type_columns']) . ',
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
		'name' => $row['name'],
		'description' => $row['description'],
		'long_description' => $row['long_description'],
		'category' => array(),
		'groups' => explode(',', $row['member_groups']),
		'trackers' => array(),
		'developers' => array(),
		'is_developer' => !empty($row['is_dev']),
		'id_category' => $row['id_category'],
		'category_position' => $row['cat_position'],
	);

	$trackers = explode(',', $row['trackers']);

	foreach ($trackers as $key)
	{
		$project['trackers'][$key] = array(
			'info' => &$context['issue_types'][$key],
			'open' => $row['open_' . $key],
			'closed' => $row['closed_' . $key],
			'total' => $row['open_' . $key] + $row['closed_' . $key],
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
	global $context, $smcFunc, $user_info, $txt;

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
			if (!isset($versions[$row['id_parent']]))
				continue;

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

function list_getProjects($start, $items_per_page, $sort)
{
	global $smcFunc, $scripturl;

	$projects = array();

	$request = $smcFunc['db_query']('', '
		SELECT p.id_project, p.name
		FROM {db_prefix}projects AS p
		ORDER BY ' . $sort);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$projects[] = array(
			'id' => $row['id_project'],
			'link' => '<a href="' . $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $row['id_project'] . '">' . $row['name'] . '</a>',
			'href' => $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $row['id_project'],
			'name' => $row['name'],
		);
	}
	$smcFunc['db_free_result']($request);

	return $projects;
}

function list_getCategories($start, $items_per_page, $sort, $project)
{
	global $smcFunc, $scripturl;

	$request = $smcFunc['db_query']('', '
		SELECT cat.id_category, cat.category_name
		FROM {db_prefix}issue_category AS cat
		WHERE cat.id_project = {int:project}
		ORDER BY cat.category_name',
		array(
			'project' => $project
		)
	);

	$categories = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$categories[] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'link' => '<a href="' . $scripturl . '?action=admin;area=managecategories;sa=edit;category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>',
		);
	}
	$smcFunc['db_free_result']($request);

	return $categories;
}

function list_getVersions($start, $items_per_page, $sort, $project)
{
	global $smcFunc, $scripturl;

	$request = $smcFunc['db_query']('', '
		SELECT ver.id_version, ver.version_name, ver.id_parent
		FROM {db_prefix}project_versions AS ver
		WHERE ver.id_project = {int:project}
		ORDER BY ver.id_parent, ver.version_name',
		array(
			'project' => $project
		)
	);

	$versionsTemp = array();
	$children = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (empty($row['id_parent']))
		{
			$versionsTemp[] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageversions;sa=edit;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
				'level' => 0,
			);
		}
		else
		{
			if (!isset($children[$row['id_parent']]))
				$children[$row['id_parent']] = array();

			$children[$row['id_parent']][] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageversions;sa=edit;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
				'level' => 1,
			);
		}
	}
	$smcFunc['db_free_result']($request);

	$versions = array();

	foreach ($versionsTemp as $ver)
	{
		$versions[] = $ver;

		if (isset($children[$ver['id']]))
			$versions = array_merge($versions, $children[$ver['id']]);
	}

	return $versions;
}

function list_getProfiles($start = 0, $items_per_page = -1, $sort = '')
{
	global $smcFunc, $scripturl;

	$profiles = array();

	$request = $smcFunc['db_query']('', '
		SELECT pr.id_profile, pr.profile_name, COUNT(p.id_project) AS num_project
		FROM {db_prefix}project_profiles AS pr
			LEFT JOIN {db_prefix}projects AS p ON (p.id_profile = pr.id_profile)
		GROUP BY pr.id_profile');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$profiles[] = array(
			'id' => $row['id_profile'],
			'link' => '<a href="' . $scripturl . '?action=admin;area=projectpermissions;sa=edit;profile=' . $row['id_profile'] . '">' . $row['profile_name'] . '</a>',
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=edit;profile=' . $row['id_profile'],
			'name' => $row['profile_name'],
			'projects' => comma_format($row['num_project']),
			'disabled' => ($row['num_project'] > 0 || $row['id_profile'] == 1) ? 'disabled="disabled" ' : '',
		);
	}
	$smcFunc['db_free_result']($request);

	return $profiles;
}

function getAllPTPermissions()
{
	// List of all possible permissions
	// 'perm' => array(own/any, [guest = true])

	return array(
		'issue_view' => array(false),
		'issue_view_private' => array(false),
		'issue_report' => array(false),
		'issue_comment' => array(false),
		'issue_update' => array(true, false),
		'issue_attach' => array(false),
		'issue_moderate' => array(false, false),
		// Comments
		'edit_comment' => array(true, false),
		'delete_comment' => array(true, false),
	);
}

?>