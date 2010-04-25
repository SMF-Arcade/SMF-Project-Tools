<?php
/**********************************************************************************
* ProjectModule-General.php                                                       *
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
	'title' => 'General',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('general', 'ProjectModule_General');

class ProjectModule_Base
{
	// Define subactions this handles by default
	public $subActions = array();
	
	// Default constructor
	function __construct()
	{
		$this->subActions = array('main' => array('callback' => array($this, 'mainView')));
	}
	
	public function registersubAction($sa, $data)
	{
		$this->subActions[$sa] = $data;
	}
	
	function beforeSubaction(&$subaction)
	{
		global $sourcedir;
		
		// Check that subaction exists, if not use "main"
		if (!isset($this->subActions[$subaction]))
			$subaction = 'main';
		
		// No main subaction? Use first then
		if (!isset($this->subActions[$subaction]) && count($this->subActions) > 0)
			list ($subaction, ) = array_keys($this->subActions);
		elseif (!isset($this->subActions[$subaction]))
			trigger_error(__CLASS__ . ' doesn\'t contain any subaction', E_USER_ERROR);
			
		// Check permission to subaction?
		if (isset($this->subActions[$subaction]['permission']))
			isAllowedTo($this->subActions[$subaction]['permission']);
		if (isset($this->subActions[$subaction]['project_permission']))
			projectIsAllowedTo($this->subActions[$subaction]['project_permission']);
		
		// File required?
		if (isset($this->subActions[$subaction]['file']))
			require_once($sourcedir . '/' . $this->subActions[$subaction]['file']);
	}
	
	final function main($subaction)
	{
		call_user_func($this->subActions[$subaction]['callback']);
	}
}

class ProjectModule_General extends ProjectModule_Base
{
	function __construct()
	{
		$this->subActions = array(
			'main' => array(
				'file' => 'ProjectView.php',
				'callback' => 'ProjectView',
			),
			'subscribe' => array(
				'file' => 'ProjectView.php',
				'callback' => 'ProjectSubscribe',
			),			
		);
	}
	
	function RegisterProjectArea()
	{
		return array(
			'area' => 'main', 'tab' => 'main',
		);
	}
	
	function RegisterProjectFrontpageBlocks(&$frontpage_blocks)
	{
		global $context;
		
		$this->__register_issue_blocks($frontpage_blocks);
	}
		
	private function __register_issue_blocks(&$frontpage_blocks)
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