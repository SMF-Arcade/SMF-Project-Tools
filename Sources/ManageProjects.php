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

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_projects'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_projects_description'];

	$context['page_title'] = $txt['manage_projects'];

	$subActions = array(
		'list' => array('ManageProjectsList'),
		'new' => array('EditProject'),
		'edit' => array('EditProject'),
		'edit2' => array('EditProject2'),
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

	$listOptions = array(
		'id' => 'projects_list',
		'base_href' => $scripturl . '?action=admin;area=manageprojects',
		'get_items' => array(
			'function' => 'list_getProjects',
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="projects[]" value="%1$d" class="check" />',
						'params' => array(
							'id' => false,
						),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['header_project'],
				),
				'data' => array(
					'db' => 'link',
				),
				'sort' => array(
					'default' => 'p.name',
					'reverse' => 'p.name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=manageprojects',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				'sc' => $context['session_id'],
			),
		),
		'no_items_label' => $txt['no_projects'],
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'projects_list';
}

function EditProject()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;
	if (!isset($context['project']) && empty($_REQUEST['project']) || !$project = loadProjectAdmin($_REQUEST['project']))
		$_REQUEST['sa'] = 'new';

	if ($_REQUEST['sa'] == 'new')
	{
		$curProject = array(
			'member_groups' => array('-1', '0'),
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
	}
	else
	{
		$curProject = array(
			'member_groups' => $project['groups'],
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
		if ($_REQUEST['sa'] == 'new' && $row['min_posts'] == -1)
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
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];

	if (!empty($_POST['project']) && !loadProjectAdmin($_POST['project']))
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

		$projectOptions['member_groups'] = array();
		if (!empty($_POST['groups']))
			foreach ($_POST['groups'] as $group)
				$projectOptions['member_groups'][] = $group;

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
			DELETE FROM {db_prefix}issues
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project']
			)
		);
	}

	redirectexit('action=admin;area=manageprojects');
}

function ManageCategories()
{
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_category'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_category_description'];

	$context['page_title'] = $txt['manage_project_category'];

	$subActions = array(
		'list' => array('ManageCategoriesList'),
		'new' => array('EditCategory'),
		'edit' => array('EditCategory'),
		'edit2' => array('EditCategory2'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageCategoriesList()
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
			'href' => $scripturl . '?action=admin;area=manageprojects;sa=project;project=' . $row['id_project'],
			'name' => $row['name'],
			'description' => $row['description'],
			'versions' => array(),
			'categories' => array(),
		);

		$projects[] = $row['id_project'];
	}
	$smcFunc['db_free_result']($request);

	// Current project
	if (!isset($_REQUEST['project']) || !in_array((int) $_REQUEST['project'], $projects))
		$id_project = $projects[0];
	else
		$id_project = (int) $_REQUEST['project'];

	$projectsHtml = '';

	foreach ($context['projects'] as $project)
	{
		$projectsHtml .= '
		<option value="' . $project['id'] . '"' . ($project['id'] == $id_project ? ' selected="selected"' : '') . '>' . $project['name']. '</option>';
	}

	$listOptions = array(
		'id' => 'categories_list',
		'base_href' => $scripturl . '?action=admin;area=managecategories',
		'get_items' => array(
			'function' => 'list_getCategories',
			'params' => array(
				$id_project,
			),
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="categories[]" value="%1$d" class="check" />',
						'params' => array(
							'id' => false,
						),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['header_category'],
				),
				'data' => array(
					'db' => 'link',
				),
				'sort' => array(
					'default' => 'cat.category_name',
					'reverse' => 'cat.category_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=managecategories',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				'sc' => $context['session_id'],
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'top_of_list',
				'value' => '
					<select name="project">' . $projectsHtml . '</select>
					<input type="submit" name="go" value="' . $txt['go'] . '" />',
				'class' => 'catbg',
				'align' => 'right',
			),
			array(
				'position' => 'bottom_of_list',
				'value' => '
					<a href="' . $scripturl . '?action=admin;area=managecategories;sa=new;project=' . $id_project . '">
						' . $txt['new_category'] . '
					</a>',
				'class' => 'catbg',
				'align' => 'right',
			),
		),
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'categories_list';
}

function EditCategory()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	if ($_REQUEST['sa'] == 'new')
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
		if (empty($_REQUEST['category']) || !is_numeric($_REQUEST['category']))
			fatal_lang_error('category_not_found');

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

		if (!$row)
			fatal_lang_error('category_not_found');

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
			createPTCategory($_POST['project'], $categoryOptions);
		else
			updatePTCategory($_POST['category'], $categoryOptions);
	}

	redirectexit('action=admin;area=managecategories;project=' . $_POST['project']);
}

?>