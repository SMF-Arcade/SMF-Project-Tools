<?php
/**********************************************************************************
* ProjectPermissions.php                                                          *
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

function ManageProjectPermissions()
{
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_permissions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_permissions_description'];

	$context['page_title'] = $txt['manage_project_permissions'];

	$subActions = array(
		'main' => array('ManageProjectPermissions'),
		'new' => array('NewProjectProfile'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

?>