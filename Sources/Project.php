<?php
/**********************************************************************************
* Project.php                                                                     *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.5                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2010 by:     Niko Pahajoki (http://www.madjoki.com)              *
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
	
	// Array for fixing old < 0.5 urls
	$saToArea = array(
		'main' => 'main',
		'subscribe' => array('main', 'subscribe'),
		// issues
		'issues' => 'issues',
		'viewIssue' => array('issues', 'view'),
		'tags' => array('issues', 'tags'),
		'update' => array('issues', 'update'),
		'upload' => array('issues', 'upload'),
		'move' => array('issues', 'move'),
		'reply' => array('issues', 'reply'),
		'reply2' => array('issues', 'reply2'),
		'edit' => array('issues', 'edit'),
		'edit2' => array('issues', 'edit2'),
		'removeComment' => array('issues', 'removeComment'),
		'reportIssue' => array('issues', 'report'),
		'reportIssue2' => array('issues', 'report2'),
	);
	
	if (empty($_REQUEST['area']) && !empty($_REQUEST['sa']) && isset($saToArea[$_REQUEST['sa']]))
	{
		if (is_array($saToArea[$_REQUEST['sa']]))
			list ($_REQUEST['area'], $_REQUEST['sa']) = $saToArea[$_REQUEST['sa']];
		else
		{
			$_REQUEST['area'] = $saToArea[$_REQUEST['sa']];
			unset($_REQUEST['sa']);
		}
	}
	elseif (!isset($_REQUEST['sa']) && !empty($issue))
	{
		$_REQUEST['area'] = 'issues';
		$_REQUEST['sa'] = 'view';
	}
	
	// Areas are sets of subactions (registered by modules)
	$subAreas = array(
		'main' => array(
			'area' => 'main',
			'module' => 'general',
			'tab' => 'main',
			'subactions' => array(
				'main' => array(
					'file' => 'ProjectView.php',
					'callback' => 'ProjectView',
				),
				'subscribe' => array(
					'file' => 'ProjectView.php',
					'callback' => 'ProjectSubscribe',
				),			
			),
		),
		'issues' => array(
			'area' => 'issues',
			'module' => 'issues',
			'tab' => 'issues',
			'subactions' => array(
				'main' => array(
					'file' => 'IssueList.php',
					'callback' => 'IssueList',
				),
				'view' => array(
					'file' => 'IssueView.php',
					'callback' => 'IssueView',
				),
				'tags' => array(
					'file' => 'IssueView.php',
					'callback' => 'IssueTag',
				),
				'update' => array(
					'file' => 'IssueReport.php',
					'callback' => 'IssueUpdate',
				),
				'upload' => array(
					'file' => 'IssueReport.php',
					'callback' => 'IssueUpload',
				),
				'move' => array(
					'file' => 'IssueView.php',
					'callback' => 'IssueMove',
				),
				// Reply
				'reply' => array(
					'file' => 'IssueComment.php',
					'callback' => 'IssueReply',
				),
				'reply2' => array(
					'file' => 'IssueComment.php',
					'callback' => 'IssueReply2',
				),
				// Edit
				'edit' => array(
					'file' => 'IssueComment.php',
					'callback' => 'IssueReply',
				),
				'edit2' => array(
					'file' => 'IssueComment.php',
					'callback' => 'IssueReply2',
				),
				// Remove comment
				'removeComment' => array(
					'file' => 'IssueComment.php',
					'callback' => 'IssueDeleteComment',
				),
				// Report Issue
				'report' => array(
					'file' => 'IssueReport.php',
					'callback' => 'ReportIssue',
				),
				'report2' => array(
					'file' => 'IssueReport.php',
					'callback' => 'ReportIssue2',
				),				
			),
		),
	);
	
	// Tabs
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
				'href' => project_get_url(array('project' => $project, 'area' => 'issues')),
				'title' => $txt['issues'],
				'is_selected' => false,
				'linktree' => array(
					'name' => $txt['linktree_issues'],
					'url' => project_get_url(array('project' => $project, 'area' => 'issues')),
				),
				'order' => 10,
			)
		),
	);

	// Let Modules register subAreas
	if (!empty($context['project_modules']))
	{
		foreach ($context['project_modules'] as $id => $module)
		{
			if (method_exists($module, 'RegisterProjectArea'))
			{
				$area = $module->RegisterProjectArea();
				
				$subAreas[$area['area']] = array(
					'area' => $area['area'],
					'module' => $id,
					'tab' => !empty($area['tab']) ? $area['tab'] : $area['area'],
				);
			}
			if (method_exists($module, 'RegisterProjectTabs'))
				$module->RegisterProjectTabs($context['project_tabs']['tabs']);
		}
	}
	
	// Let Modules register subactions to areas
	if (!empty($context['project_modules']))
	{
		foreach ($context['project_modules'] as $id => $module)
		{
			if (method_exists($module, 'RegisterProjectSubactions'))
			{
				$addActions = $module->RegisterProjectSubactions(array_keys($subAreas));
				
				foreach ($addActions as $id => $data)
					$subAreas[$data['area']]['subactions'][$id] = $data + array('module' => $id);
			}
		}
	}

	// Remove tabs which user has no permission to see 
	foreach ($context['project_tabs']['tabs'] as $id => $tab)
	{
		if (!empty($tab['permission']) && !allowedTo($tab['permission']))
			unset($context['project_tabs']['tabs'][$id]);
		elseif (!empty($tab['project_permission']) && !projectAllowedTo($tab['project_permission']))
			unset($context['project_tabs']['tabs'][$id]);
	}

	// Sort tabs to correct order
	uksort($context['project_tabs']['tabs'], 'projectTabSort');

	// Linktree
	$context['linktree'][] = array(
		'name' => strip_tags($context['project']['name']),
		'url' => project_get_url(array('project' => $project)),
	);
	
	if (isset($context['current_issue']))
		$context['linktree'][] = array(
			'name' => $context['current_issue']['name'],
			'url' => $context['current_issue']['href'],
		);

	loadTemplate('ProjectView');

	if (!isset($_REQUEST['xml']))
		$context['template_layers'][] = 'project_view';
		
	if (empty($_REQUEST['area']) || !isset($subAreas[$_REQUEST['area']]))
		$_REQUEST['area'] = 'main';
		
	$subActions = &$subAreas[$_REQUEST['area']]['subactions'];
	
	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';
	
	$current_area = &$subAreas[$_REQUEST['area']];
	$context['current_project_module'] = &$context['project_modules'][$current_area['module']];
	
	if (isset($context['project_tabs']['tabs'][$current_area['tab']]))
		$context['project_tabs']['tabs'][$current_area['tab']]['is_selected'] = true;
	else
		$context['project_tabs']['tabs']['main']['is_selected'] = true;

	// Can access this area?
	if (isset($current_area['permission']))
		isAllowedTo($current_area['permission']);
	if (isset($current_area['project_permission']))
		projectIsAllowedTo($current_area['project_permission']);
			
	// Check permission to subaction?
	if (isset($subActions[$_REQUEST['sa']]['permission']))
		isAllowedTo($subActions[$_REQUEST['sa']]['permission']);
	if (isset($subActions[$_REQUEST['sa']]['project_permission']))
		projectIsAllowedTo($subActions[$_REQUEST['sa']]['project_permission']);
			
	// Call Initialize View function
	if (isset($context['current_project_module']) && method_exists($context['current_project_module'], 'beforeSubaction'))
		$context['current_project_module']->beforeSubaction($_REQUEST['sa']);
			
	// Load Additional file if required
	if (isset($subActions[$_REQUEST['sa']]['file']))
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']]['file']);
			
	call_user_func($subActions[$_REQUEST['sa']]['callback']);
}

?>