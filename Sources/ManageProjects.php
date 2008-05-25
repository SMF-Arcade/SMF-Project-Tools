<?php
/**********************************************************************************
* ManageProjects.php                                                              *
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

function ManageProjects()
{
	global $context, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');
	loadLanguage('ProjectAdmin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = &$txt['manage_projects'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = &$txt['manage_projects_description'];

	$context['page_title'] = &$txt['manage_projects'];

	$subActions = array(
		'list' => array('ManageProjectsList'),
		// Project
		'newproject' => array('EditProject'),
		'project' => array('EditProject'),
		'project2' => array('EditProject2'),
		// Version
		'newversion' => array('EditVersion'),
		'version' => array('EditVersion'),
		'version2' => array('EditVersion2'),
		// Category
		'newcategory' => array('EditCategory'),
		'category' => array('EditCategory'),
		'category2' => array('EditCategory2'),
		// Permissions
		'permissions' => array('EditPermissions'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageProjectsList()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT p.id_project, p.name, p.description
		FROM {db_prefix}projects AS p');

	$context['projects'] = array();
	$projects = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'link' => $scripturl . '?action=admin;area=manageprojects;sa=project;project=' . $row['id_project'],
			'name' => $row['name'],
			'description' => $row['description'],
			'versions' => array(),
			'categories' => array(),
		);

		$projects[] = $row['id_project'];
	}
	$smcFunc['db_free_result']($request);

	if (!empty($projects))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_version, id_project, version_name, id_parent
			FROM {db_prefix}project_versions
			WHERE id_project IN ({array_int:projects})
			ORDER BY id_project, id_parent',
			array(
				'projects' => $projects
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if ($row['id_parent'] == 0)
			{
				$context['projects'][$row['id_project']]['versions'][$row['id_version']] = array(
					'id' => $row['id_version'],
					'name' => $row['version_name'],
					'sub_versions' => array(),
				);
			}
			else
			{
				$context['projects'][$row['id_project']]['versions'][$row['id_parent']]['sub_versions'][] = array(
					'id' => $row['id_version'],
					'name' => $row['version_name'],
				);
			}
		}
		$smcFunc['db_free_result']($request);

		$request = $smcFunc['db_query']('', "
			SELECT id_category, id_project, category_name
			FROM {db_prefix}issue_category
			WHERE id_project IN ({array_int:projects})",
			array(
				'projects' => $projects
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['projects'][$row['id_project']]['categories'][] = array(
				'id' => $row['id_category'],
				'name' => $row['category_name']
			);
		}
		$smcFunc['db_free_result']($request);
	}

	$context['sub_template'] = 'projects_list';
}

function EditProject()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;
	if (!isset($context['project']) && empty($_REQUEST['project']) || !$project = loadProject($_REQUEST['project']))
		$_REQUEST['sa'] = 'newproject';

	if ($_REQUEST['sa'] == 'newproject')
	{
		$curProject = array(
			'member_groups' => array(-1, 0),
		);

		$context['project'] = array(
			'is_new' => true,
			'id' => 0,
			'name' => '',
			'description' => '',
			'long_description' => '',
			'trackers' => array_keys($context['project_tools']['issue_types']),
			'developers' => array(),
		);
	}
	else
	{
		$curProject = array(
			'member_groups' => explode(',', $project['member_groups']),
		);

		$context['project'] = array(
			'id' => $project['id'],
			'name' => htmlspecialchars($project['name']),
			'description' => htmlspecialchars($project['description']),
			'long_description' => htmlspecialchars($project['long_description']),
			'trackers' => array_keys($project['trackers']),
			'developers' => $project['developers'],
		);
	}

	// Default membergroups.
	$context['groups'] = array(
		-1 => array(
			'id' => '-1',
			'name' => $txt['guests'],
			'checked' => in_array('-1', $curProject['member_groups']),
			'is_post_group' => false,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'checked' => in_array('0', $curProject['member_groups']),
			'is_post_group' => false,
		)
	);

	// Load membergroups.
	$request = $smcFunc['db_query']('', '
		SELECT group_name, id_group, min_posts
		FROM {db_prefix}membergroups
		WHERE id_group > 3 OR id_group = 2
		ORDER BY min_posts, id_group != 2, group_name');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($_REQUEST['sa'] == 'newproject' && $row['min_posts'] == -1)
			$curProject['member_groups'][] = $row['id_group'];

		$context['groups'][(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'checked' => in_array($row['id_group'], $curProject['member_groups']),
			'is_post_group' => $row['min_posts'] != -1,
		);
	}
	$smcFunc['db_free_result']($request);

	require_once($sourcedir . '/Subs-Editor.php');

	// Developer suggester
	$suggestOptions = array(
		'id' => 'developer',
		'search_type' => 'member',
		'width' => '130px',
		'value' => '',
		'button' => $txt['developer_add'],
	);
	create_control_autosuggest($suggestOptions);

	if (!isset($_REQUEST['delete']))
	{
		$context['sub_template'] = 'edit_project';

		if (!empty($context['project']['is_new']))
			$context['page_title'] = $txt['new_project'];
		else
			$context['page_title'] = $txt['edit_project'];
	}
	else
	{
		$context['sub_template'] = 'confirm_project_delete';
		$context['page_title'] = $txt['confirm_project_delete'];
	}
}

function EditProject2()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];

	if (!empty($_POST['project']) && !loadProject($_POST['project'], false))
		fatal_lang_error('project_not_found');

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$projectOptions = array();

		$projectOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['project_name']);
		$projectOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);
		$projectOptions['long_description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['long_desc']);

		$projectOptions['member_groups'] = array();
		if (!empty($_POST['groups']))
			foreach ($_POST['groups'] as $group)
				$projectOptions['member_groups'][] = (int) $group;

		$projectOptions['trackers'] = array();
		if (!empty($_POST['trackers']))
			foreach ($_POST['trackers'] as $tracker)
				if (isset($context['project_tools']['issue_types'][$tracker]))
					$projectOptions['trackers'][] = $tracker;

		if (count($projectOptions['trackers']) == 0)
			fatal_lang_error('no_issue_types');

		if (isset($_POST['add']))
			$_POST['project'] = createProject($projectOptions);
		else
			updateProject($_POST['project'], $projectOptions);

		$developers = array();

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_developer
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project'],
			)
		);

		$rows = array();

		if (!empty($_POST['developer']))
		{
			foreach ($_POST['developer'] as $id_member)
				if (is_numeric($id_member))
					$rows[] = array($_POST['project'], (int) $id_member);

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
	elseif (isset($_POST['delete']) && !isset($_POST['confirmation']))
	{
		EditProject();
		return;
	}
	elseif (isset($_POST['delete']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}projects
			WHERE id_project = {int:project}
			LIMIT 1',
			array(
				'project' => $_POST['project']
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_versions
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project']
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issues
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project']
			)
		);
	}

	redirectexit('action=admin;area=manageprojects');
}

function EditVersion()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$_REQUEST['version'] = isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : 0;
	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;

	if ($_REQUEST['sa'] == 'newversion')
	{
		if (!$context['project'] = loadProject((int) $_REQUEST['project'], true))
			fatal_lang_error('project_not_found');

		$curVersion = array(
			'member_groups' => array(-1, 0),
		);

		$context['version'] = array(
			'is_new' => true,
			'id' => 0,
			'project' => $context['project']['id'],
			'name' => '',
			'description' => '',
			'parent' => !empty($_REQUEST['parent']) && isset($context['project']['versions'][$_REQUEST['parent']]) ? $_REQUEST['parent'] : 0,
			'status' => 0,
			'release_date' => array('day' => 0, 'month' => 0, 'year' => 0),
		);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT
				v.id_version, v.id_project, v.id_parent, v.version_name,
				v.status, v.member_groups, v.description, v.release_date
			FROM {db_prefix}project_versions AS v
			WHERE id_version = {int:version}',
			array(
				'version' => $_REQUEST['version']
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('version_not_found');

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$context['project'] = loadProject((int) $row['id_project'], true))
			fatal_lang_error('project_not_found');

		$curVersion = array(
			'member_groups' => explode(',', $row['member_groups']),
		);

		$context['version'] = array(
			'id' => $row['id_version'],
			'project' => $row['id_project'],
			'name' => htmlspecialchars($row['version_name']),
			'description' => htmlspecialchars($row['description']),
			'parent' => isset($context['project']['versions'][$row['id_parent']]) ? $row['id_parent'] : 0,
			'status' => $row['status'],
			'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array('day' => 0, 'month' => 0, 'year' => 0),
		);
	}

	// Default membergroups.
	$context['groups'] = array(
		-1 => array(
			'id' => '-1',
			'name' => $txt['guests'],
			'checked' => in_array('-1', $curVersion['member_groups']),
			'is_post_group' => false,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'checked' => in_array('0', $curVersion['member_groups']),
			'is_post_group' => false,
		)
	);

	// Load membergroups.
	$request = $smcFunc['db_query']('', '
		SELECT group_name, id_group, min_posts
		FROM {db_prefix}membergroups
		WHERE id_group > 3 OR id_group = 2
		ORDER BY min_posts, id_group != 2, group_name');
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($_REQUEST['sa'] == 'newversion' && $row['min_posts'] == -1)
			$curVersion['member_groups'][] = $row['id_group'];

		$context['groups'][(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'checked' => in_array($row['id_group'], $curVersion['member_groups']),
			'is_post_group' => $row['min_posts'] != -1,
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'edit_version';
}

function EditVersion2()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];
	$_POST['version'] = (int) $_POST['version'];

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$versionOptions = array();

		$versionOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['version_name']);
		$versionOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);

		if (!empty($_POST['parent']))
		{
			$versionOptions['parent'] = $_POST['parent'];

			$versionOptions['release_date'] = serialize(array(
				'day' => !empty($_POST['release_date'][0]) ? $_POST['release_date'][0] : 0,
				'month' => !empty($_POST['release_date'][1]) ? $_POST['release_date'][1] : 0,
				'year' => !empty($_POST['release_date'][2]) ? $_POST['release_date'][2] : 0
			));

			$versionOptions['status'] = (int) $_POST['status'];

			if ($versionOptions['status'] < 0 || $versionOptions['status'] > 6)
				$versionOptions['status'] = 0;
		}

		$versionOptions['member_groups'] = array();
		if (!empty($_POST['groups']))
			foreach ($_POST['groups'] as $group)
				$versionOptions['member_groups'][] = (int) $group;

		if (isset($_POST['add']))
			createVersion($_POST['project'], $versionOptions);
		else
			updateVersion($_POST['version'], $versionOptions);
	}
	elseif (isset($_POST['delete']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_versions
			WHERE id_version = {int:version}',
			array(
				'version' => $_POST['version']
			)
		);
	}

	redirectexit('action=admin;area=manageprojects');
}

function EditCategory()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	if ($_REQUEST['sa'] == 'newcategory')
	{
		$context['category'] = array(
			'is_new' => true,
			'id' => 0,
			'project' => (int) $_REQUEST['project'],
			'name' => '',
		);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_category, id_project, category_name
			FROM {db_prefix}issue_category
			WHERE id_category = {int:category}',
			array(
				'category' => $_REQUEST['category']
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		$context['category'] = array(
			'id' => $row['id_category'],
			'project' => $row['id_project'],
			'name' => htmlspecialchars($row['category_name']),
		);

		unset($row);
	}

	if (!isset($_REQUEST['delete']))
	{
		$context['sub_template'] = 'edit_category';

		if (!empty($context['category']['is_new']))
			$context['page_title'] = $txt['new_category'];
		else
			$context['page_title'] = $txt['edit_category'];
	}
	else
	{
		$context['sub_template'] = 'confirm_category_delete';
		$context['page_title'] = $txt['confirm_category_delete'];
	}
}

function EditCategory2()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['category'] = (int) $_POST['category'];

	if (isset($_POST['category']) || isset($_POST['add']))
	{
		$categoryOptions = array();

		$categoryOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['category_name']);

		if (isset($_POST['add']))
			createCategory($_POST['project'], $categoryOptions);
		else
			updateCategory($_POST['category'], $categoryOptions);
	}

	redirectexit('action=admin;area=manageprojects');
}
?>