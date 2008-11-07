<?php
/**********************************************************************************
* Profile-Project.php                                                             *
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

function projectProfile($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
	global $user_info, $smcFunc, $sourcedir;

	require_once($sourcedir . '/Project.php');
	loadProjectTools('profile');

	$subActions = array(
		'main' => array('projectProfileMain'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$context[$context['profile_menu_name']]['tab_data']['title'] = $txt['project_tools_profile'];
	$context[$context['profile_menu_name']]['tab_data']['description'] = $txt['project_tools_profile_desc'];

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][1]))
		isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$subActions[$_REQUEST['sa']][0]($memID);
}

function projectProfileMain($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
	global $user_info, $smcFunc, $sourcedir;

	$context['statistics'] = array();

	// Reported Issues
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues
		WHERE id_reporter = {int:member}',
		array(
			'member' => $memID,
		)
	);

	list ($context['statistics']['reported_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Assigned Issues
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues
		WHERE id_assigned = {int:member}',
		array(
			'member' => $memID,
		)
	);

	list ($context['statistics']['assigned_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Format
	$context['statistics']['reported_issues'] = comma_format($context['statistics']['reported_issues']);
	$context['statistics']['assigned_issues'] = comma_format($context['statistics']['assigned_issues']);

	// Template
	$context['sub_template'] = 'project_profile_main';
}

?>