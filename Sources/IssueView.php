<?php
/**********************************************************************************
* IssueList.php                                                                   *
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

/*
	View and Edit issue

*/

function IssueView()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$context['show_update'] = false;

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_my_issue'] ? 'own' : 'any';

	if (projectAllowedTo('issue_update_' . $type))
	{
		$context['show_update'] = true;

		if (allowedTo('issue_assign'))
		{
			$context['show_assign'] = true;
			$context['assign_members'] = &$context['project']['developers'];
		}
	}

	// Template
	$context['sub_template'] = 'issue_view';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
}

function IssueUpdate()
{
	global $context;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	checkSession();

	print_r($_POST);
}
?>