<?php
/**********************************************************************************
* ProjectModule-Admin.php                                                         *
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

class ProjectModule_Admin
{
	public $title = 'Admin';
	public $version = '0.5';
	
	public function RegisterProjectSubactions()
	{	
		return array(
			'admin' => array(
				'callback' => array($this, 'ProjectAdminMain'),
				'tab' => 'admin',
				'project_permission' => 'admin',
			),
			'adminVersions' => array(
				'callback' => array($this, 'ProjectAdminVersions'),
				'tab' => 'admin',
				'project_permission' => 'admin',
			),
		);
	}
	
	public function RegisterProjectTabs(&$tabs)
	{
		global $project, $context, $txt;
		
		$tabs['admin'] = array(
			'href' => project_get_url(array('project' => $project, 'sa' => 'admin')),
			'title' => $txt['project_admin'],
			'is_selected' => false,
			'order' => 'last',
			'project_permission' => 'admin',
			'linktree' => array(
				'name' => $txt['project_admin'],
				'url' => project_get_url(array('project' => $project, 'sa' => 'admin')),
			),
		);
	}
	
	// Callback before any subaction routine is called
	public function beforeSubaction($subaction)
	{
		
	}
	
	public function ProjectAdminMain()
	{
		
	}
	
	public function ProjectAdminVersions()
	{
		
	}
}

?>