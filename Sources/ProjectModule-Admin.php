<?php
/**********************************************************************************
* ProjectModule-Admin.php                                                         *
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
	'title' => 'Admin',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('admin', 'ProjectModule_Admin');

class ProjectModule_Admin extends ProjectModule_Base
{
	public $title = 'Admin';
	
	function __construct()
	{
		$this->subActions = array(
			'main' => array(
				'area' => 'admin',
				'callback' => array($this, 'ProjectAdminMain'),
				'tab' => 'admin',
			),
			'version' => array(
				'area' => 'admin',
				'callback' => array($this, 'ProjectAdminVersions'),
				'tab' => 'admin',
			)
		);	
	}
	
	public function RegisterProjectArea()
	{
		return array('area' => 'admin', 'tab' => 'admin', 'project_permission' => 'admin');
	}
	
	public function RegisterProjectTabs(&$tabs)
	{
		global $project, $context, $txt;
		
		$tabs['admin'] = array(
			'href' => project_get_url(array('project' => $project, 'area' => 'admin')),
			'title' => $txt['project_admin'],
			'is_selected' => false,
			'order' => 'last',
			'project_permission' => 'admin',
			'linktree' => array(
				'name' => $txt['project_admin'],
				'url' => project_get_url(array('project' => $project, 'area' => 'admin')),
			),
		);
	}
	
	// Callback before any subaction routine is called
	public function beforeSubaction(&$subaction)
	{	
		global $sourcedir;
		
		require_once($sourcedir . '/Subs-ProjectAdmin.php');
		
		loadTemplate('ManageProjects');
		
		projectIsAllowedTo('admin');

		parent::beforeSubaction($subaction);
	}
	
	public function ProjectAdminMain()
	{
		
	}
	
	public function ProjectAdminVersions()
	{
		global $scripturl, $sourcedir, $context, $txt, $project;
		
		$listOptions = array(
			'id' => 'versions_list',
			'base_href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions_list')),
			'get_items' => array(
				'function' => 'list_getVersions',
				'params' => array(
					$project,
				),
			),
			'columns' => array(
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
						'style' => 'width: 4%;',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="versions[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['header_version'],
					),
					'data' => array(
						'function' => create_function('$list_item', '
							return str_repeat(\'&nbsp;\', $list_item[\'level\'] * 5) . $list_item[\'link\'];
						'),
					),
					'sort' => array(
						'default' => 'ver.version_name',
						'reverse' => 'ver.version_name DESC',
					),
				),
				'actions' => array(
					'header' => array(
						'value' => $txt['new_version'],
						'style' => 'width: 16%; text-align: right;',
					),
					'data' => array(
						'function' => create_function('$list_item', '
							global $txt, $scripturl;
							return (empty($list_item[\'level\']) ? \'<a href="\' .  $scripturl . \'?action=admin;area=manageprojects;section=versions;sa=new;project=' . $id_project . ';parent=\' . $list_item[\'id\'] . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
						'),
						'style' => 'text-align: right;',
					),
					'sort' => array(
						'default' => 'ver.version_name',
						'reverse' => 'ver.version_name DESC',
					),
				),
			),
			'form' => array(
				'href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions_list')),
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '
						<a href="' . $scripturl . '?action=admin;area=manageprojects;section=versions;sa=new;project=' . $id_project . '">
							' . $txt['new_version_group'] . '
						</a>',
					'class' => 'catbg',
					'align' => 'right',
				),
			),
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['sub_template'] = 'versions_list';
	}
}

?>