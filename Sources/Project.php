<?php
/**********************************************************************************
* Project.php                                                                     *
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

function Projects($standalone = false)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;

	$project = 0;

	isAllowedTo('project_access');
	loadProjectTools();

	$subActions = array(
		// Project
		'list' => array('ProjectList.php', 'ProjectList'),
		'viewProject' => array('ProjectView.php', 'ProjectView', true),
		'roadmap' => array('ProjectRoadmap.php', 'ProjectRoadmap', true),
		// Issues
		'issues' => array('IssueList.php', 'IssueList', true),
		'viewIssue' => array('IssueView.php', 'IssueView', true),
		'tags' => array('IssueView.php', 'IssueTag', true),
		'update' => array('IssueReport.php', 'IssueUpdate', true),
		'upload' => array('IssueReport.php', 'IssueUpload', true),
		'delete' => array('IssueView.php', 'IssueDelete', true),
		// Reply
		'reply' => array('IssueComment.php', 'IssueReply', true),
		'reply2' => array('IssueComment.php', 'IssueReply2', true),
		// Edit
		'edit' => array('IssueComment.php', 'IssueReply', true),
		'edit2' => array('IssueComment.php', 'IssueReply2', true),
		// Remove comment
		'delete' => array('IssueComment.php', 'IssueDeleteComment', true),
		// Report Issue
		'reportIssue' => array('IssueReport.php', 'ReportIssue', true),
		'reportIssue2' => array('IssueReport.php', 'ReportIssue2', true),
	);

	// Linktree
	$context['linktree'][] = array(
		'name' => $txt['linktree_projects'],
		'url' => project_get_url(array('action' => 'projects')),
	);

	if (isset($_REQUEST['issue']) && strpos($_REQUEST['issue'], '.') !== false)
		list ($issue, $_REQUEST['start']) = explode('.', $_REQUEST['issue'], 2);

	// Load Project
	loadProject();

	// Load Issue if needed
	if (isset($issue))
	{
		loadIssue();

		if (!isset($_REQUEST['sa']))
			$_REQUEST['sa'] = 'viewIssue';
	}
	elseif (!isset($_REQUEST['sa']) && !empty($project))
		$_REQUEST['sa'] = 'viewProject';

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (!empty($project) && empty($subActions[$_REQUEST['sa']][2]))
		$_REQUEST['sa'] = 'viewProject';
	elseif (empty($project) && !empty($subActions[$_REQUEST['sa']][2]))
		fatal_lang_error('project_not_found', false);

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][3]))
		isAllowedTo($subActions[$_REQUEST['sa']][3]);

	// Template if Project selected
	if (!empty($project))
	{
		$context['project_tabs'] = array(
			'title' => $context['project']['name'],
			'text' => parse_bbc($context['project']['description']),
			'tabs' => array(
				array(
					'href' => project_get_url(array('project' => $project)),
					'title' => $txt['project'],
					'is_selected' => in_array($_REQUEST['sa'], array('viewProject')),
				),
				'roadmap' => array(
					'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap')),
					'title' => $txt['roadmap'],
					'is_selected' => in_array($_REQUEST['sa'], array('roadmap')),
				),
				'issues' => array(
					'href' => project_get_url(array('project' => $project, 'sa' => 'issues')),
					'title' => $txt['issues'],
					'is_selected' => in_array($_REQUEST['sa'], array('issues', 'viewIssue', 'reportIssue', 'reportIssue2', 'reply', 'reply2', 'edit', 'edit2', 'update', 'delete')),
				)
			),
		);

		// Linktree
		$context['linktree'][] = array(
			'name' => $context['project']['name'],
			'url' => project_get_url(array('project' => $project)),
		);

		if ($context['project_tabs']['tabs']['issues']['is_selected'])
			$context['linktree'][] = array(
				'name' => $txt['linktree_issues'],
				'url' => project_get_url(array('project' => $project, 'sa' => 'issues')),
			);
		elseif ($context['project_tabs']['tabs']['roadmap']['is_selected'])
			$context['linktree'][] = array(
				'name' => $txt['linktree_roadmap'],
				'url' => project_get_url(array('project' => $project, 'sa' => 'roadmap')),
			);

		if (isset($context['current_issue']))
			$context['linktree'][] = array(
				'name' => $context['current_issue']['name'],
				'url' => project_get_url(array('issue' => $context['current_issue']['id'])),
			);

		loadTemplate('ProjectView');

		if (!isset($_REQUEST['xml']))
			$context['template_layers'][] = 'project_view';
	}

	require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
	$subActions[$_REQUEST['sa']][1]();
}

function loadProjectTools($mode = '')
{
	global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $project_version, $settings;

	if (!empty($project_version))
		return;

	// Which version this is?
	$project_version = '0.2';

	$context['issues_per_page'] = !empty($modSettings['issuesPerPage']) ? $modSettings['issuesPerPage'] : 25;
	$context['comments_per_page'] = !empty($modSettings['commentsPerPage']) ? $modSettings['commentsPerPage'] : 20;

	loadTemplate('Project', array('forum', 'project'));

	if (empty($mode))
	{
		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';

		if (!isset($_REQUEST['xml']))
			$context['template_layers'][] = 'project';
	}
	elseif ($mode == 'profile')
	{
		loadTemplate('ProjectProfile');

		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
	}
	elseif ($mode == 'admin')
	{
		require_once($sourcedir . '/Subs-ProjectAdmin.php');

		$user_info['query_see_project'] = '1 = 1';
		$user_info['query_see_version'] = '1 = 1';

		if (loadLanguage('ProjectAdmin') == false)
			loadLanguage('ProjectAdmin', 'english');

		loadTemplate('ProjectAdmin');

		if (!isset($_REQUEST['xml']))
			$context['template_layers'][] = 'project_admin';
	}
}

?>