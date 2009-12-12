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
		
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
		call_user_func($subActions[$_REQUEST['sa']][1]);
		
		return;
	}
	
	$subActions = array(
		// Project
		'main' => array('ProjectView.php', 'ProjectView'),
		'subscribe' => array('ProjectView.php', 'ProjectSubscribe'),
		// Issues
		'issues' => array('IssueList.php', 'IssueList'),
		'viewIssue' => array('IssueView.php', 'IssueView'),
		'tags' => array('IssueView.php', 'IssueTag'),
		'update' => array('IssueReport.php', 'IssueUpdate'),
		'upload' => array('IssueReport.php', 'IssueUpload'),
		'delete' => array('IssueView.php', 'IssueDelete'),
		'move' => array('IssueView.php', 'IssueMove'),
		// Reply
		'reply' => array('IssueComment.php', 'IssueReply'),
		'reply2' => array('IssueComment.php', 'IssueReply2'),
		// Edit
		'edit' => array('IssueComment.php', 'IssueReply'),
		'edit2' => array('IssueComment.php', 'IssueReply2'),
		// Remove comment
		'removeComment' => array('IssueComment.php', 'IssueDeleteComment'),
		// Report Issue
		'reportIssue' => array('IssueReport.php', 'ReportIssue'),
		'reportIssue2' => array('IssueReport.php', 'ReportIssue2'),
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

	if (empty($project) && !empty($subActions[$_REQUEST['sa']][2]))
		fatal_lang_error('project_not_found', false);

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][3]))
		isAllowedTo($subActions[$_REQUEST['sa']][3]);

	$context['project_tabs'] = array(
		'title' => $context['project']['name'],
		'text' => $context['project']['description'],
		'tabs' => array(
			'main' => array(
				'href' => project_get_url(array('project' => $project)),
				'title' => $txt['project'],
				'is_selected' => in_array($_REQUEST['sa'], array('viewProject')),
				'order' => 'first',
			),
			'issues' => array(
				'href' => project_get_url(array('project' => $project, 'sa' => 'issues')),
				'title' => $txt['issues'],
				'is_selected' => in_array($_REQUEST['sa'], array('issues', 'viewIssue', 'reportIssue', 'reportIssue2', 'reply', 'reply2', 'edit', 'edit2', 'update', 'delete')),
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
	
	foreach ($context['project_tabs']['tabs'] as $id => $tab)
		if (!empty($tab['is_selected']) && !empty($tab['linktree']))
			$context['linktree'][] = $tab['linktree'];

	if (isset($context['current_issue']))
		$context['linktree'][] = array(
			'name' => $context['current_issue']['name'],
			'url' => $context['current_issue']['href'],
		);

	loadTemplate('ProjectView');

	if (!isset($_REQUEST['xml']))
		$context['template_layers'][] = 'project_view';
		
	require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
	call_user_func($subActions[$_REQUEST['sa']][1]);
}

?>