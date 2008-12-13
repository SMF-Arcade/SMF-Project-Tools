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

?>