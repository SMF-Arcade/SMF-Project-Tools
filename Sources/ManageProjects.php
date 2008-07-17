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
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = &$txt['manage_projects'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = &$txt['manage_projects_description'];

	$context['page_title'] = &$txt['manage_projects'];

	$subActions = array(
		'list' => array('ManageProjectsList'),
		// Project
		'newproject' => array('EditProject'),
		'project' => array('EditProject'),
		'project2' => array('EditProject2'),
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
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

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

	// Template
	$context['sub_template'] = 'projects_list';
}

function EditProject()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;
	if (!isset($context['project']) && empty($_REQUEST['project']) || !$project = loadProject($_REQUEST['project']))
		$_REQUEST['sa'] = 'newproject';

	if ($_REQUEST['sa'] == 'newproject')
	{
		$curProject = array(
		);

		$context['project'] = array(
			'is_new' => true,
			'id' => 0,
			'name' => '',
			'description' => '',
			'long_description' => '',
			'trackers' => array_keys($context['project_tools']['issue_types']),
			'developers' => array(),
			'public_access' => 0,
		);

		$context['project_groups'] = array();

		$request = $smcFunc['db_query']('', '
			SELECT id_group, id_project, group_name, member_groups
			FROM {db_prefix}project_groups
			WHERE id_project = 0',
			array(
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['project_groups'][$row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => $row['group_name'],
				'member_groups' => explode(',', $row['member_groups']),
				'global' => true,
			);
		}
		$smcFunc['db_free_result']($request);
	}
	else
	{
		$curProject = array(
		);

		$context['project'] = array(
			'id' => $project['id'],
			'name' => htmlspecialchars($project['name']),
			'description' => htmlspecialchars($project['description']),
			'long_description' => htmlspecialchars($project['long_description']),
			'trackers' => array_keys($project['trackers']),
			'groups' => $project['groups'],
			'developers' => $project['developers'],
		);

		$context['project_groups'] = array();

		$request = $smcFunc['db_query']('', '
			SELECT id_group, id_project, group_name, member_groups
			FROM {db_prefix}project_groups
			WHERE id_project = {int:project} OR id_project = 0',
			array(
				'project' => $project['id'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['project_groups'][$row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => $row['group_name'],
				'member_groups' => explode(',', $row['member_groups']),
				'global' => $row['id_project'] == 0,
				'selected' => in_array($row['id_group'], $project['groups']),
			);
		}
		$smcFunc['db_free_result']($request);
	}

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
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];

	if (!empty($_POST['project']) && !loadProject($_POST['project']))
		fatal_lang_error('project_not_found', false);

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$projectOptions = array();

		$projectOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['project_name']);
		$projectOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);
		$projectOptions['long_description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['long_desc']);

		$projectOptions['trackers'] = array();
		if (!empty($_POST['trackers']))
			foreach ($_POST['trackers'] as $tracker)
				if (isset($context['project_tools']['issue_types'][$tracker]))
					$projectOptions['trackers'][] = $tracker;

		$projectOptions['project_groups'] = array();
		if (!empty($_POST['project_groups']))
			foreach ($_POST['project_groups'] as $group)
				$projectOptions['project_groups'][] = $group;

		if (count($projectOptions['trackers']) == 0)
			fatal_lang_error('no_issue_types', false);

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
			foreach ($_POST['developer'] as $id_member => $i)
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
			DELETE FROM {db_prefix}project_groups
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

function EditCategory()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

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
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

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