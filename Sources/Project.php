<?php
/**********************************************************************************
* Project.php                                                                       *
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
	!!!
*/

function Projects()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $project;

	$project = 0;

	loadProjectTools();

	$subActions = array(
		// Project
		'list' => array('ProjectList.php', 'ProjectList'),
		'viewProject' => array('ProjectView.php', 'ProjectView'),
		// Issues
		'issues' => array('IssueList.php', 'IssueList'),
		'viewIssue' => array('IssueView.php', 'IssueView'),
		// Report Issue
		'reportIssue' => array('IssueReport.php', 'ReportIssue'),
		'reportIssue2' => array('IssueReport.php', 'ReportIssue2'),
	);

	// Load Issue if needed
	if (!empty($_REQUEST['issue']))
	{
		if (!loadIssue((int) $_REQUEST['issue']))
			fatal_lang_error('issue_not_found');

		$_REQUEST['project'] = $context['current_issue']['project']['id'];

		if (!isset($_REQUEST['sa']))
			$_REQUEST['sa'] = 'viewIssue';
	}

	// Load Project if needed
	if (!empty($_REQUEST['project']))
	{
		if (!($context['project'] = loadProject((int) $_REQUEST['project'], true)))
			fatal_lang_error('project_not_found');

		$project = $context['project']['id'];

		if (!isset($_REQUEST['sa']))
			$_REQUEST['sa'] = 'viewProject';
	}

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if ($_REQUEST['sa'] == 'list' && !empty($project))
		$_REQUEST['sa'] = 'viewProject';

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][2]))
		isAllowedTo($subActions[$_REQUEST['sa']][2]);

	// Template if Project selected
	if (!empty($project))
	{
		$context['project_tabs'] = array(
			'title' => $context['project']['name'],
			'text' => parse_bbc($context['project']['description']),
			'tabs' => array(
				array(
					'href' => $scripturl . '?project=' . $project,
					'title' => $txt['project'],
					'is_selected' => in_array($_REQUEST['sa'], array('viewProject')) || $project_page === true,
				),
				'issues' => array(
					'href' => $scripturl . '?project=' . $project . ';sa=issues',
					'title' => $txt['issues'],
					'is_selected' => in_array($_REQUEST['sa'], array('issues', 'viewIssue', 'reportIssue', 'reportIssue2')),
				)
			),
		);

		// Linktree
		$context['linktree'][] = array(
			'name' => $txt['linktree_projects'],
			'url' => $scripturl . '?action=projects'
		);
		$context['linktree'][] = array(
			'name' => $context['project']['name'],
			'url' => $scripturl . '?project=' . $project
		);

		if ($context['project_tabs']['tabs']['issues']['is_selected'])
			$context['linktree'][] = array(
				'name' => $txt['linktree_issues'],
				'url' => $scripturl . '?project=' . $project . ';sa=issues',
			);

		$context['template_layers'][] = 'project';
	}

	require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
	$subActions[$_REQUEST['sa']][1]();
}

function loadProjectTools($mode = '')
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $project_version;

	if (!empty($project_version))
		return;

	require_once($sourcedir . '/Subs-Project.php');
	require_once($sourcedir . '/Subs-Issue.php');

	// Which version this is?
	$project_version = '0.1 Alpha';

	$context['project_tools'] = array();
	$context['issue_tracker'] = array();

	if (empty($mode))
	{
		// Can see project?
		if ($user_info['is_guest'])
			$see_project = 'FIND_IN_SET(-1, p.member_groups)';

		// Administrators can see all projects.
		elseif ($user_info['is_admin'])
			$see_project = '1 = 1';
		// Registered user.... just the groups in $user_info['groups'].
		else
			$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';

		// Can see version?
		if ($user_info['is_guest'])
			$see_version = 'FIND_IN_SET(-1, ver.member_groups)';
		// Administrators can see all versions.
		elseif ($user_info['is_admin'])
			$see_version = '1 = 1';
		// Registered user.... just the groups in $user_info['groups'].
		else
			$see_version = '(ISNULL(ver.member_groups) OR (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups)))';

		$user_info['query_see_project'] = $see_project;
		$user_info['query_see_version'] = $see_version;

		// Show everything?
		if (allowedTo('issue_view_any'))
			$user_info['query_see_issue'] = "($see_project AND $see_version)";
		// Show only own?
		elseif (allowedTo('issue_view_own'))
			$user_info['query_see_issue'] = "($see_project AND $see_version AND i.reporter = $user_info[id])";
		// if not then we can't show anything
		else
			$user_info['query_see_issue'] = "(0 = 1)";
	}
	elseif ($mode == 'admin')
	{
		$user_info['query_see_project'] = '1 = 1';
		$user_info['query_see_version'] = '1 = 1';
	}

	loadLanguage('Project+Issue');
	loadIssueTypes();
	loadTemplate('Project', array('forum', 'project'));
}

?>