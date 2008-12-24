<?php
/**********************************************************************************
* ProjectAdmin.php                                                                *
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

/*
	!!!
*/

function ProjectsAdmin()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	require_once($sourcedir . '/Project.php');
	require_once($sourcedir . '/ManageServer.php');

	isAllowedTo('project_admin');
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = &$txt['project_tools_admin'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['project_tools_admin_desc'];

	$context['page_title'] = $txt['project_tools_admin'];

	$subActions = array(
		'main' => array('ProjectsAdminMain'),
		'settings' => array('ProjectsAdminSettings'),
		'maintenance' => array('ProjectsMaintenance'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$subActions[$_REQUEST['sa']][0]();
}

function ProjectsAdminMain()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$context['sub_template'] = 'project_admin_main';
}

function ProjectsAdminSettings($return_config = false)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$config_vars = array(
			array('check', 'projectEnabled'),
			array('check', 'projectAttachments'),
		'',
			array('int', 'issuesPerPage'),
			array('int', 'commentsPerPage'),
		'',
			array('permissions', 'project_access', 0, $txt['setting_project_access'], 'subtext' => $txt['setting_project_access_subtext']),
			array('permissions', 'project_admin', 0, $txt['setting_project_admin']),
	);

	if ($return_config)
		return $config_vars;

	if (isset($_GET['save']))
	{
		checkSession('post');
		saveDBSettings($config_vars);

		writeLog();

		redirectexit('action=admin;area=projectsadmin;sa=settings');
	}

	$context['post_url'] = $scripturl . '?action=admin;area=projectsadmin;sa=settings;save';
	$context['page_title'] = $txt['project_settings_title'];
	$context['settings_title'] = $txt['project_settings'];
	$context['sub_template'] = 'show_settings';

	prepareDBSettingContext($config_vars);
}

function ProjectsMaintenance()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$maintenaceActions = array(
		'repair' => 'ProjectsMaintenanceRepair',
	);

	$context['sub_template'] = 'project_maintenance';

	if (isset($_REQUEST['activity']) && isset($maintenaceActions[$_REQUEST['activity']]))
	{
		$context['maintenance_action'] = $txt['project_maintenance_' . $_REQUEST['activity']];
		$maintenaceActions[$_REQUEST['activity']]();
	}
}

function ProjectsMaintenanceRepair()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	if (!isset($_REQUEST['fix']))
	{
		$context['project_errors'] = array();

		// Comments not linked to events
		$request = $smcFunc['db_query']('', '
			SELECT id_comment
			FROM {db_prefix}issue_comments
			WHERE id_event = 0');

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_errors'][] = sprintf($txt['error_comment_not_linked'], $row['id_comment']);

		if (!empty($context['project_errors']))
			$context['sub_template'] = 'project_maintenance_repair_list';
		else
			$context['maintenance_message'] = $txt['repair_no_errors'];
	}
	else
	{

	}
}

?>