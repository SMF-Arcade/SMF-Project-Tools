<?php
/**********************************************************************************
* ProjectAdmin.php                                                                *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.2                               *
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

	$context['sub_template'] = 'project_admin_maintenance';

	if (isset($_REQUEST['activity']) && isset($maintenaceActions[$_REQUEST['activity']]))
	{
		$context['maintenance_action'] = $txt['project_maintenance_' . $_REQUEST['activity']];
		$maintenaceActions[$_REQUEST['activity']]();
	}
}

function ProjectsMaintenanceRepair()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	// Check for errors
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
		$smcFunc['db_free_result']($request);

		/*// Events without issues
		$request = $smcFunc['db_query']('', '
			SELECT id_event
			FROM {db_prefix}project_timeline AS tl
				LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
			WHERE ISNULL(i.id_issue)');

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_errors'][] = sprintf($txt['error_issue_info_event'], $row['id_event']);
		$smcFunc['db_free_result']($request);*/

		// Events without poster info
		$request = $smcFunc['db_query']('', '
			SELECT id_event
			FROM {db_prefix}project_timeline
			WHERE poster_name = {string:empty} OR poster_email = {string:empty} OR poster_ip = {string:empty}',
			array(
				'empty' => '',
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_errors'][] = sprintf($txt['error_missing_poster_info_event'], $row['id_event']);
		$smcFunc['db_free_result']($request);

		// Unnecessary events
		$request = $smcFunc['db_query']('', '
			SELECT id_event
			FROM {db_prefix}project_timeline
			WHERE event = {string:edit_comment} OR event = {string:delete_comment}',
			array(
				'edit_comment' => 'edit_comment',
				'delete_comment' => 'delete_comment',
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_errors'][] = sprintf($txt['error_unnecessary_event'], $row['id_event']);
		$smcFunc['db_free_result']($request);

		// Show list if there were errors
		if (!empty($context['project_errors']))
			$context['sub_template'] = 'project_admin_maintenance_repair_list';
		else
		{
			$context['maintenance_message'] = $txt['repair_no_errors'];
			$context['maintenance_finished'] = true;
		}
	}
	// Fix errors
	else
	{
		// Fix comments without id_event
		$request = $smcFunc['db_query']('', '
			SELECT id_comment
			FROM {db_prefix}issue_comments
			WHERE id_event = 0');

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$event_req = $smcFunc['db_query']('', '
				SELECT id_event
				FROM {db_prefix}project_timeline AS tl
				WHERE tl.event = {string:new_comment}
					AND INSTR(tl.event_data , {string:comment})',
				array(
					'new_comment' => 'new_comment',
					'comment' => 's:7:"comment";i:' . $row['id_comment'] . ''
				)
			);

			list ($id_event) = $smcFunc['db_fetch_row']($event_req);
			$smcFunc['db_free_result']($event_req);

			if (!$id_event)
			{
				$event_req = $smcFunc['db_query']('', '
					SELECT id_event
					FROM {db_prefix}issues AS i
						LEFT JOIN {db_prefix}project_timeline AS tl ON (tl.id_issue = i.id_issue)
					WHERE i.id_comment_first = {int:comment}
						AND tl.event = {string:new_issue}',
					array(
						'new_issue' => 'new_issue',
						'comment' => $row['id_comment'],
					)
				);
				list ($id_event) = $smcFunc['db_fetch_row']($event_req);
				$smcFunc['db_free_result']($event_req);
			}

			if ($id_event)
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}issue_comments
					SET id_event = {int:event}
					WHERE id_comment = {int:comment}',
					array(
						'event' => $id_event,
						'comment' => $row['id_comment'],
					)
				);
		}
		$smcFunc['db_free_result']($request);

		// Events without poster info
		$request = $smcFunc['db_query']('', '
			SELECT tl.id_event, com.poster_name, com.poster_email, com.poster_ip
			FROM {db_prefix}project_timeline AS tl
				INNER JOIN {db_prefix}issue_comments AS com ON (com.id_event = tl.id_event)
			WHERE tl.poster_name = {string:empty} OR tl.poster_email = {string:empty} OR tl.poster_ip = {string:empty}',
			array(
				'empty' => '',
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_timeline
				SET poster_name = {string:poster_name}, poster_email = {string:poster_email}, poster_ip = {string:poster_ip}
				WHERE id_event = {int:event}', array(
					'event' => $row['id_event'],
					'poster_name' => $row['poster_name'],
					'poster_email' => $row['poster_email'],
					'poster_ip' => $row['poster_ip'],
				)
			);

		// Unnecessary events
		$request = $smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_timeline
			WHERE event = {string:edit_comment} OR event = {string:delete_comment}',
			array(
				'edit_comment' => 'edit_comment',
				'delete_comment' => 'delete_comment',
			)
		);

		$context['maintenance_finished'] = true;
	}
}

?>