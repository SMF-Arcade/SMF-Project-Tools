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

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_assign'] = false;
	$context['can_edit'] = false;

	if (projectAllowedTo('issue_update_' . $type))
	{
		if (projectAllowedTo('issue_assign'))
		{
			$context['can_assign'] = true;
			$context['assign_members'] = &$context['project']['developers'];
		}

		$context['set_target'] = projectAllowedTo('issue_set_taget');
		$context['change_status'] = projectAllowedTo('issue_change_status');

		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	// Template
	$context['sub_template'] = 'issue_view';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
}

function IssueUpdate()
{
	global $context, $user_info;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	checkSession();

	$posterOptions = array(
		'id' => $user_info['id']
	);

	$issueOptions = array();

	if (projectAllowedTo('issue_update_' . $type))
	{
		// Assigning
		if (projectAllowedTo('issue_assign') && isset($_POST['assign']))
		{
			if ((int) $_POST['assign'] != $context['current_issue']['assignee']['id'])
			{
				if (!isset($context['project']['developers'][(int) $_POST['assign']]))
					$_POST['assign'] = 0;

				$issueOptions['assignee'] = (int) $_POST['assign'];
			}
		}

		// Version
		if (isset($_POST['version']) && $context['current_issue']['version']['id'] != (int) $_POST['version'])
		{
			if (!isset($context['project']['parents'][(int) $_POST['version']]))
				$_POST['version'] = 0;

			$issueOptions['version'] = (int) $_POST['version'];
		}

		// Version fixed
		if (projectAllowedTo('issue_set_target') && isset($_POST['version_fixed']) && $context['current_issue']['version_fixed']['id'] != (int) $_POST['version_fixed'])
		{
			if (!isset($context['project']['parents'][(int) $_POST['version_fixed']]))
				$_POST['version_fixed'] = 0;

			$issueOptions['version_fixed'] = (int) $_POST['version_fixed'];
		}

		// Category
		if (isset($_POST['category']) && $context['current_issue']['category']['id'] != (int) $_POST['category'])
		{
			if (!isset($context['project']['category'][(int) $_POST['category']]))
				$_POST['category'] = 0;

			$issueOptions['category'] = (int) $_POST['category'];
		}

		// Status
		if (projectAllowedTo('issue_change_status') && isset($_POST['status']) && $context['current_issue']['status']['id'] != (int) $_POST['status'])
		{
			if (isset($context['issue']['status'][(int) $_POST['status']]))
				$issueOptions['status'] = (int) $_POST['status'];
		}
	}

	// DEBUG
	print_r(array($_POST, $issueOptions));
	die();

	$id_event = updateIssue($issue, $issueOptions, $posterOptions);

	redirectexit('issue=' . $_REQUEST['issue']);
}
?>