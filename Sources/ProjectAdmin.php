<?php
/**********************************************************************************
* ProjectAdmin.php                                                                *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.5                               *
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

/*
	!!!
*/

function ProjectsAdmin()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	require_once($sourcedir . '/Subs-Project.php');
	require_once($sourcedir . '/ManageServer.php');

	isAllowedTo('project_admin');
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['project_tools_admin'];
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

	require_once($sourcedir . '/Subs-ProjectMaintenance.php');

	$maintenaceActions = array(
		'repair' => 'ProjectsMaintenanceRepair',
		'upgrade' => 'ProjectsMaintenanceUpgrade',
	);

	$context['sub_template'] = 'project_admin_maintenance';

	if (isset($_REQUEST['activity']) && isset($maintenaceActions[$_REQUEST['activity']]))
	{
		$context['maintenance_action'] = $txt['project_maintenance_' . $_REQUEST['activity']];
		$repairFunctions = $maintenaceActions[$_REQUEST['activity']]();

		$context['total_steps'] = count($repairFunctions);

		if (!isset($_GET['step']))
			$_GET['step'] = 0;

		if (!isset($_SESSION['maintenance']) || $_SESSION['maintenance']['activity'] != $_REQUEST['activity'])
		{
			$_SESSION['maintenance'] = array(
				'activity' => $_REQUEST['activity'],
				'needed_actions' => array(),
			);

			foreach ($repairFunctions as $id => $act)
			{
				if ($act['function'](true))
					$_SESSION['maintenance']['needed_actions'][] = $id;
			}

			if (!empty($_SESSION['maintenance']['needed_actions']))
				redirectexit('action=admin;area=projectsadmin;sa=maintenance;step=0;activity=' . $_REQUEST['activity'] . ';' . $context['session_var'] . '=' . $context['session_id']);
		}
		else
		{
			$current_step = -1;
			foreach ($repairFunctions as $id => $act)
			{
				$current_step++;

				if ($_GET['step'] > $current_step)
					continue;

				if (!in_array($id, $_SESSION['maintenance']['needed_actions']))
				{
					$_GET['step']++;
					continue;
				}

				$act['function']();

				$_GET['step']++;

				pauseProjectMaintenance(true);
			}
		}

		unset($_SESSION['maintenance']);
		$context['maintenance_finished'] = true;
	}
}

function pauseProjectMaintenance($force)
{
	global $context, $txt, $time_start;

	// Errr, wait.  How much time has this taken already?
	if (!$force && time() - array_sum(explode(' ', $time_start)) < 3)
		return;

	$context['continue_get_data'] = '?action=admin;area=projectsadmin;sa=maintenance;step=' . $_GET['step'] . ';activity=' . $_REQUEST['activity'] . ';' . $context['session_var'] . '=' . $context['session_id'];
	$context['page_title'] = $txt['not_done_title'];
	$context['continue_post_data'] = '';
	$context['continue_countdown'] = '2';
	$context['sub_template'] = 'not_done';

	// Change these two if more steps are added!
	if (empty($max_substep))
		$context['continue_percent'] = round(($_GET['step'] * 100) / $context['total_steps']);
	else
		$context['continue_percent'] = round((($_GET['step'] + ($_GET['substep'] / $max_substep)) * 100) / $context['total_steps']);

	// Never more than 100%!
	$context['continue_percent'] = min($context['continue_percent'], 100);

	obExit();
}

function ProjectsMaintenanceRepair()
{
	global $txt;

	$repairFunctions = array(
		array(
			'name' => $txt['repair_step_general_maintenance'],
			'function' => 'ptMaintenanceGeneral',
		),
		array(
			'name' => $txt['repair_step_comments_not_linked'],
			'function' => 'ptMaintenanceEvents1',
		),
		array(
			'name' => $txt['repair_step_events_without_poster'],
			'function' => 'ptMaintenanceEvents2',
		),
		array(
			'name' => $txt['repair_step_not_needed_events'],
			'function' => 'ptMaintenanceEvents3',
		),
		array(
			'name' => '',
			'function' => 'ptMaintenanceIssues1',
		),
		array(
			'name' => '',
			'function' => 'ptMaintenanceIssues2',
		),
		array(
			'name' => '',
			'function' => 'ptMaintenanceIssueCounts',
		)
	);

	return $repairFunctions;
}

function ProjectsMaintenanceUpgrade()
{
	global $txt;

	$repairFunctions = array(
		array(
			'function' => 'ptUpgrade_log_issues',
		),
		array(
			'function' => 'ptUpgrade_trackers',
		),
		array(
			'function' => 'ptUpgrade_versionFields',
		),
		
		// These maintenance actions are needed for proper upgrade
		array(
			'name' => '',
			'function' => 'ptMaintenanceIssues2',
		),
		array(
			'name' => '',
			'function' => 'ptMaintenanceIssueCounts',
		),
	);

	return $repairFunctions;
}

?>