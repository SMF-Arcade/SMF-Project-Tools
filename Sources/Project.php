<?php
/**********************************************************************************
* Project.php                                                                     *
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

function Projects($standalone = false)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;

	// Check that user can access Project Tools
	isAllowedTo('project_access');
	
	loadProjectToolsPage();

	// Admin made mistake on manual edits? (for safety reasons!!)
	if (isset($context['project_error']))
		fatal_lang_error($context['project_error'], false);

	// Add "Projects" to Linktree
	$context['linktree'][] = array(
		'name' => $txt['linktree_projects'],
		'url' => project_get_url(),
	);
	
	// Project was not selected
	if (empty($project))
	{
		$subActions = array(
			'list' => array('ProjectList.php', 'ProjectList'),
		);
		
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
		
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
		call_user_func($subActions[$_REQUEST['sa']][1]);
		
		return;
	}
	
	$subActions = array(
		// Project
		'main' => array(
			'file' => 'ProjectView.php',
			'callback' => 'ProjectView',
			'tab' => 'main',
		),
		'subscribe' => array(
			'file' => 'ProjectView.php',
			'callback' => 'ProjectSubscribe',
			'tab' => 'main',
		),
		// Issues
		'issues' => array(
			'file' => 'IssueList.php',
			'callback' => 'IssueList',
			'tab' => 'issues',
		),
		'viewIssue' => array(
			'file' => 'IssueView.php',
			'callback' => 'IssueView',
			'tab' => 'issues',
		),
		'tags' => array(
			'file' => 'IssueView.php',
			'callback' => 'IssueTag',
			'tab' => 'issues',
		),
		'update' => array(
			'file' => 'IssueReport.php',
			'callback' => 'IssueUpdate',
			'tab' => 'issues',
		),
		'upload' => array(
			'file' => 'IssueReport.php',
			'callback' => 'IssueUpload',
			'tab' => 'issues',
		),
		'move' => array(
			'file' => 'IssueView.php',
			'callback' => 'IssueMove',
			'tab' => 'issues',
		),
		// Reply
		'reply' => array(
			'file' => 'IssueComment.php',
			'callback' => 'IssueReply',
			'tab' => 'issues',
		),
		'reply2' => array(
			'file' => 'IssueComment.php',
			'callback' => 'IssueReply2',
			'tab' => 'issues',
		),
		// Edit
		'edit' => array(
			'file' => 'IssueComment.php',
			'callback' => 'IssueReply',
			'tab' => 'issues',
		),
		'edit2' => array(
			'file' => 'IssueComment.php',
			'callback' => 'IssueReply2',
			'tab' => 'issues',
		),
		// Remove comment
		'removeComment' => array(
			'file' => 'IssueComment.php',
			'callback' => 'IssueDeleteComment',
			'tab' => 'issues',
		),
		// Report Issue
		'reportIssue' => array(
			'file' => 'IssueReport.php',
			'callback' => 'ReportIssue',
			'tab' => 'issues',
		),
		'reportIssue2' => array(
			'file' => 'IssueReport.php',
			'callback' => 'ReportIssue2',
			'tab' => 'issues',
		),
	);
	
	// Let Modules register subactions
	if (!empty($context['project_modules']))
	{
		foreach ($context['project_modules'] as $id => $module)
			if (method_exists($module, 'RegisterProjectSubactions'))
			{
				$addActions = $module->RegisterProjectSubactions();
				
				foreach ($addActions as $id => $data)
					$subActions[$id] = $data + array('module' => $id);
			}
	}

	if (!isset($_REQUEST['sa']) && !empty($issue))
		$_REQUEST['sa'] = 'viewIssue';
	elseif (!isset($_REQUEST['sa']))
		$_REQUEST['sa'] = 'main';

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']]['permission']))
		isAllowedTo($subActions[$_REQUEST['sa']]['permission']);
	if (isset($subActions[$_REQUEST['sa']]['project_permission']))
		projectIsAllowedTo($subActions[$_REQUEST['sa']]['project_permission']);

	$context['project_tabs'] = array(
		'title' => $context['project']['name'],
		'text' => $context['project']['description'],
		'tabs' => array(
			'main' => array(
				'href' => project_get_url(array('project' => $project)),
				'title' => $txt['project'],
				'is_selected' => false,
				'order' => 'first',
			),
			'issues' => array(
				'href' => project_get_url(array('project' => $project, 'sa' => 'issues')),
				'title' => $txt['issues'],
				'is_selected' => false,
				'linktree' => array(
					'name' => $txt['linktree_issues'],
					'url' => project_get_url(array('project' => $project, 'sa' => 'issues')),
				),
				'order' => 10,
			)
		),
	);

	// Let Modules register project tabs
	if (!empty($context['project_modules']))
	{
		foreach ($context['project_modules'] as $module)
			if (method_exists($module, 'RegisterProjectTabs'))
				$module->RegisterProjectTabs($context['project_tabs']['tabs']);
	}
	
	// Sort tabs to correct order
	uksort($context['project_tabs']['tabs'], 'projectTabSort');

	// Linktree
	$context['linktree'][] = array(
		'name' => strip_tags($context['project']['name']),
		'url' => project_get_url(array('project' => $project)),
	);
	
	if (isset($subActions[$_REQUEST['sa']]['tab']) && isset($context['project_tabs']['tabs'][$subActions[$_REQUEST['sa']]['tab']]))
		$context['project_tabs']['tabs'][$subActions[$_REQUEST['sa']]['tab']]['is_selected'] = true;
	else
		$context['project_tabs']['tabs']['main']['is_selected'] = true;

	if (isset($context['current_issue']))
		$context['linktree'][] = array(
			'name' => $context['current_issue']['name'],
			'url' => $context['current_issue']['href'],
		);

	loadTemplate('ProjectView');

	if (!isset($_REQUEST['xml']))
		$context['template_layers'][] = 'project_view';
		
	// Load Additional file if required
	if (isset($subActions[$_REQUEST['sa']]['file']))
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']]['file']);
		
	call_user_func($subActions[$_REQUEST['sa']]['callback']);
}

?>