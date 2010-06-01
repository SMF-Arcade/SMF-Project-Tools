<?php
/**********************************************************************************
* ProjectModule-Issues.php                                                        *
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

global $extensionInformation;

$extensionInformation = array(
	'title' => 'Issue Tracker',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('issues', 'ProjectModule_Issues');

class ProjectModule_Issues extends ProjectModule_Base
{
	function __construct()
	{
		$this->subActions = array(
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
		);	
	}
	
	function RegisterProjectArea()
	{
		return array(
			'area' => 'issues', 'tab' => 'issues',
		);
	}
	
	function RegisterProjectTabs(&$tabs)
	{
		$tabs['issues'] = array(
			'href' => project_get_url(array('project' => $project, 'area' => 'issues')),
			'title' => $txt['issues'],
			'is_selected' => false,
			'linktree' => array(
				'name' => $txt['linktree_issues'],
				'url' => project_get_url(array('project' => $project, 'area' => 'issues')),
			),
			'order' => 10,
		);
	}
	
	function RegisterProjectFrontpageBlocks(&$frontpage_blocks)
	{
		global $context, $project, $user_info;
		
		$issues_num = 5;

		$issue_list = array(
			'recent_issues' => array(
				'title' => 'recent_issues',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues')),
				'order' => 'i.updated DESC',
				'where' => '1 = 1',
				'show' => projectAllowedTo('issue_view'),
			),
			'my_reports' => array(
				'title' => 'reported_by_me',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'reporter' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_reporter = {int:current_member}',
				'show' => projectAllowedTo('issue_report'),
			),
			'assigned' => array(
				'title' => 'assigned_to_me',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'assignee' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_assigned = {int:current_member} AND NOT (i.status IN ({array_int:closed_status}))',
				'show' => projectAllowedTo('issue_resolve'),
			),
			'new_issues' => array(
				'title' => 'new_issues',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'status' => 1,)),
				'order' => 'i.created DESC',
				'where' => 'i.status = 1',
				'show' => projectAllowedTo('issue_view'),
			),
		);
	
		foreach ($issue_list as $block_id => $information)
			$frontpage_blocks[$block_id] = array(
				'title' => $information['title'],
				'href' => $information['href'],
				'data_function' => 'getIssueList',
				'data_parameters' => array(0, $issues_num, $information['order'], $information['where']),
				'template' => 'issue_list_block',
				'show' => $information['show'],
			);
	}
}

?>