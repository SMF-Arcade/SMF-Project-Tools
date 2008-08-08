<?php
/**********************************************************************************
* ProjectPermissions.php                                                          *
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

function ManageProjectPermissions()
{
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_permissions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_permissions_description'];

	$context['page_title'] = $txt['manage_project_permissions'];

	$subActions = array(
		'main' => array('ManageProjectPermissionsMain'),
		'new' => array('NewProjectProfile'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageProjectPermissionsMain()
{
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	$listOptions = array(
		'id' => 'profiles_list',
		'base_href' => $scripturl . '?action=admin;area=projectpermissions',
		'get_items' => array(
			'function' => 'list_getProfiles',
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="profiles[]" value="%1$d" class="check" />',
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
					'default' => 'pr.profile_name',
					'reverse' => 'pr.profile_name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=projectpermissions',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				'sc' => $context['session_id'],
			),
		),
		'no_items_label' => '', // Not possible
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'profiles_list';
}

function list_getProfiles($start, $items_per_page, $sort)
{
	global $smcFunc, $scripturl;

	$profiles = array();

	$request = $smcFunc['db_query']('', '
		SELECT pr.id_profile, pr.profile_name, COUNT(*) AS num_project
		FROM {db_prefix}project_profiles AS pr
			LEFT JOIN {db_prefix}projects AS p ON (p.id_profile = pr.id_profile)
		GROUP BY pr.id_profile');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$projects[] = array(
			'id' => $row['id_project'],
			'link' => '<a href="' . $scripturl . '?action=admin;area=projectpermissions;sa=edit;project=' . $row['id_project'] . '">' . $row['profile_name'] . '</a>',
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=edit;project=' . $row['id_project'],
			'name' => $row['profile_name'],
			'projects' => comma_format($row['num_project']),
		);
	}
	$smcFunc['db_free_result']($request);

	return $profiles;
}

?>