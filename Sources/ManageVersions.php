<?php
/**********************************************************************************
* ManageVersions.php                                                              *
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

function ManageVersions()
{
	global $context, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = &$txt['manage_versions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = &$txt['manage_projects_description'];

	$context['page_title'] = &$txt['manage_versions'];

	$subActions = array(
		'list' => array('ManageVersionsList'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageVersions');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageVersionsList()
{
	global $context, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

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
	if (!isset($_REQUEST['project']) && !in_array((int) $_REQUEST['project'], $projects))
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
		'id' => 'version_list',
		'base_href' => $scripturl . '?action=admin;area=manageversions;sa=edit',
		'default_sort_col' => 'name',
		'get_items' => array(
			'function' => 'list_getVersions',
			'params' => array(
				$id_project,
			),
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['header_version'],
				),
				'data' => array(
					'db' => 'name',
				),
				'sort' => array(
					'default' => 'ver.version_name',
					'reverse' => 'ver.version_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=manageversions',
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
			),
		),
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'versions_list';
}

function list_getVersions($project)
{
	global $context, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT id_version, version_name
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}',
		array(
			'project' => $project
		)
	);

	$versions = array();
	while ($row = $smcFunc['db_fetch_row']($request))
	{
		$versions[] = array(
			'id' => $row['id_version'],
			'name' => $row['version_name'],
		);
	}

	$smcFunc['db_free_result']($request);

	return $versions;
}

?>