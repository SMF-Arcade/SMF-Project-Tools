<?php
/**********************************************************************************
* Subs-ProjectAdmin.php                                                           *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.3                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2009 by:     Niko Pahajoki (http://www.madjoki.com)              *
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

function loadAdminProjects()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT p.id_project, p.name, p.description
		FROM {db_prefix}projects AS p
		ORDER BY p.name');

	$context['projects'] = array();
	$projects = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'href' => $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $row['id_project'],
			'name' => $row['name'],
			'description' => $row['description'],
			'versions' => array(),
			'categories' => array(),
		);

		$projects[] = $row['id_project'];
	}
	$smcFunc['db_free_result']($request);

	if (empty($projects))
		fatal_lang_error('admin_no_projects', false);

	// Current project
	if (isset($_REQUEST['project']) && in_array($_REQUEST['project'], $projects))
		$id_project = (int) $_REQUEST['project'];
	elseif (isset($_SESSION['admin_project']) && in_array($_SESSION['admin_project'], $projects))
		$id_project = $_SESSION['admin_project'];
	else
		$id_project = $projects[0];

	$_SESSION['admin_project'] = $id_project;

	$projectsHtml = '';

	foreach ($context['projects'] as $project)
	{
		$projectsHtml .= '
		<option value="' . $project['id'] . '"' . ($project['id'] == $id_project ? ' selected="selected"' : '') . '>' . $project['name']. '</option>';
	}

	return array($id_project, $projectsHtml);
}

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

function updateProject($id_project, $projectOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

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
		// Update versions with permission inherited
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND permission_inherit = {int:inherit}
				AND id_parent = {int:no_parent}',
			array(
				'project' => $id_project,
				'inherit' => 1,
				'no_parent' => 0,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			updateVersion($id_project, $row['id_version'], array('member_groups' => $projectOptions['member_groups']));

		$smcFunc['db_free_result']($request);

		$projectUpdates[] = 'member_groups = {string:member_groups}';
		$projectOptions['member_groups'] = implode(',', $projectOptions['member_groups']);
	}

	if (isset($projectOptions['theme']))
		$projectUpdates[] = 'project_theme = {int:theme}';
	if (isset($projectOptions['override_theme']))
	{
		$projectUpdates[] = 'override_theme = {int:override_theme}';
		$projectOptions['override_theme'] = $projectOptions['override_theme'] ? 1 : 0;
	}

	if (isset($projectOptions['profile']))
		$projectUpdates[] = 'id_profile = {int:profile}';

	if (isset($projectOptions['category']))
		$projectUpdates[] = 'id_category = {int:category}';
	if (isset($projectOptions['category_position']))
		$projectUpdates[] = 'cat_position = {string:category_position}';

	if (!empty($projectUpdates))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}projects
			SET
				' . implode(',
				', $projectUpdates) . '
			WHERE id_project = {int:project}',
			array_merge($projectOptions, array(
				'project' => $id_project,
			))
		);

	if (isset($projectOptions['developers']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_member
			FROM {db_prefix}project_developer
			WHERE id_project = {int:project}',
			array(
				'project' => $id_project,
			)
		);

		$developers = array();

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$developers[] = $row['id_member'];
		$smcFunc['db_free_result']($request);

		$toRemove = array_diff($developers, $projectOptions['developers']);
		$toAdd = array_diff($projectOptions['developers'], $developers);

		if (!empty($toRemove))
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}project_developer
				WHERE id_member IN({array_int:remove})
					AND id_project = {int:project}',
				array(
					'remove' => $toRemove,
					'project' => $id_project,
				)
			);

		if (!empty($toAdd))
		{
			$rows = array();

			foreach ($toAdd as $id_member)
				if (!empty($id_member))
					$rows[] = array($id_project, (int) $id_member);

			$smcFunc['db_insert']('insert',
				'{db_prefix}project_developer',
				array(
					'id_project' => 'int',
					'id_member' => 'int',
				),
				$rows,
				array('id_project', 'id_member')
			);
		}
	}

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

function createVersion($id_project, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	if (empty($versionOptions['name']))
		trigger_error('createVersion(): required parameters missing or invalid');

	if (empty($versionOptions['release_date']))
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

	updateVersion($id_project, $id_version, $versionOptions);

	return $id_version;
}

function updateVersion($id_project, $id_version, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;
	
	$request = $smcFunc['db_query']('', '
		SELECT id_parent, permission_inherit
		FROM {db_prefix}project_versions
		WHERE id_project = {int:project}
			AND id_version = {int:version}',
		array(
			'project' => $id_project,
			'version' => $id_version,
		)
	);
	
	$versionRow = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	
	if (!$versionRow)
		return false;
	
	$inherited = !empty($versionRow['permission_inherit']);
	
	// Will it change?
	if (isset($versionOptions['permission_inherit']))
		$inherited = !empty($versionOptions['permission_inherit']);
	
	// Don't allow changing member_groups when inherited
	if (isset($versionOptions['member_groups']) && !$inherited)
		unset($versionOptions['member_groups']);
			
	$versionUpdates = array();

	if (isset($versionOptions['name']))
		$versionUpdates[] = 'version_name = {string:name}';

	if (isset($versionOptions['description']))
		$versionUpdates[] = 'description = {string:description}';

	if (isset($versionOptions['release_date']))
		$versionUpdates[] = 'release_date = {string:release_date}';
		
	if (isset($versionOptions['permission_inherit']))
	{
		// Make sure it's not overwritten
		if (isset($versionOptions['member_groups']) && !empty($versionOptions['permission_inherit']))
			unset($versionOptions['member_groups']);
			
		$versionUpdates[] = 'permission_inherit = {int:permission_inherit}';
		$versionOptions['permission_inherit'] = !empty($versionOptions['permission_inherit']) ? 1 : 0;
		$versionRow = $versionOptions['permission_inherit'];
		
		// Inherit from parent version
		if (!empty($versionRow['id_parent']))
			$request = $smcFunc['db_query']('', '
				SELECT member_groups
				FROM {db_prefix}project_versions
				WHERE id_project = {int:project}
					AND id_version = {int:version}',
				array(
					'project' => $id_project,
					'version' => $versionRow['id_parent'],
				)
			);
		// or from project
		else
			$request = $smcFunc['db_query']('', '
				SELECT member_groups
				FROM {db_prefix}projects
				WHERE id_project = {int:project}',
				array(
					'project' => $id_project,
				)
			);
			
		$versionUpdates[] = 'member_groups = {string:member_groups}';
		list ($versionOptions['member_groups']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	}

	if (isset($versionOptions['member_groups']) && !$inherited)
	{
		// Update versions with permission inherited
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND permission_inherit = {int:inherit}
				AND id_parent = {int:parent}',
			array(
				'project' => $id_project,
				'inherit' => 1,
				'parent' => $id_version,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			updateVersion($id_project, $row['id_version'], array('member_groups' => $versionOptions['member_groups']));
		$smcFunc['db_free_result']($request);
		
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

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

function createPTCategory($id_project, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_category',
		array('id_project' => 'int', 'category_name' => 'string'),
		array($id_project, $categoryOptions['name']),
		array('id_category')
	);

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

function updatePTCategory($id_project, $id_category, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

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

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

function loadProjectAdmin($id_project)
{
	global $context, $smcFunc, $user_info, $txt, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.long_description, p.trackers, p.member_groups,
			p.id_category, p.cat_position, p.' . implode(', p.', $context['tracker_columns']) . ',
			p.project_theme, p.override_theme, p.id_profile
		FROM {db_prefix}projects AS p
		WHERE p.id_project = {int:project}
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
		'profile' => $row['id_profile'],
		'theme' => $row['project_theme'],
		'override_theme' => !empty($row['override_theme']),
		'id_category' => $row['id_category'],
		'category_position' => $row['cat_position'],
	);

	$trackers = explode(',', $row['trackers']);

	foreach ($trackers as $id)
	{
		$tracker = $context['issue_trackers'][$id];
		$project['trackers'][$id] = array(
			'info' => &$context['issue_trackers'][$id],
			'open' => $row['open_' . $tracker['short']],
			'closed' => $row['closed_' . $tracker['short']],
			'total' => $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']],
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

	$last = 0;

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$project['developers'][$row['id_member']] = array(
			'id' => (int) $row['id_member'],
			'name' => $row['real_name'],
			'last' => false,
		);
		$last = $row['id_member'];
	}
	$smcFunc['db_free_result']($request);

	// Set last developer
	if (!empty($last))
		$project['developers'][$last]['last'] = true;

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
			'link' => '<a href="' . $scripturl . '?action=admin;area=manageprojects;section=categories;sa=edit;category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>',
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
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageprojects;section=versions;sa=edit;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
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
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageprojects;section=versions;sa=edit;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>',
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