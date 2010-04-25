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
}

?>