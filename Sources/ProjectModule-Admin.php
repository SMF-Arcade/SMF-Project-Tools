<?php
/**
 * Admin pages for Projects 
 *
 * @package project-admin
 * @version 0.5
 * @license htttp://download.smfproject.net/license.php New-BSD
 * @since 0.5
 */

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
		parent::__construct();
		
		$this->subActions = array(
			'main' => array(
				'area' => 'admin',
				'callback' => array($this, 'ProjectAdminMain'),
				'tab' => 'admin',
			),
			'versions' => array(
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
		global $sourcedir, $context, $project, $txt;
		
		require_once($sourcedir . '/Subs-ProjectAdmin.php');
		
		loadTemplate('ProjectModule-Admin');
		loadTemplate('ManageProjects');
		
		loadLanguage('ProjectAdmin');
		
		projectIsAllowedTo('admin');
		
		// Template layers for Admin pages
		$context['template_layers'][] = 'ProjectModuleAdmin';
		
		// Tabs
		$context['project_admin_tabs'] = array(
			'tabs' => array(
				'main' => array(
					'href' => project_get_url(array('project' => $project, 'area' => 'admin')),
					'title' => $txt['project'],
					'is_selected' => false,
					'order' => 'first',
				),
				'versions' => array(
					'href' => project_get_url(array('project' => $project, 'area' => 'versions')),
					'title' => $txt['manage_versions'],
					'is_selected' => false,
					'order' => 10,
				)
			),
		);

		parent::beforeSubaction($subaction);
	}
	
	public function ProjectAdminMain()
	{
		
	}
	
	public function ProjectAdminVersions()
	{
		if (empty($_REQUEST['version']))
			$this->ProjectAdminVersionsList();
		else
			$this->ProjectAdminVersionEdit();
	}
	
	public function ProjectAdminVersionsList()
	{	
		global $scripturl, $sourcedir, $context, $txt, $project;
		
		$listOptions = array(
			'id' => 'versions_list',
			'base_href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')),
			'get_items' => array(
				'function' => 'list_getVersions2',
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
							return (empty($list_item[\'level\']) ? \'<a href="\' .  project_get_url(array(\'project\' => $project, \'area\' => \'admin\', \'sa\' => \'versions\', \'version\' => \'new\', \'parent\' => $list_item[\'id\'])) . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
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
						<a href="' . project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions', 'version' => 'new')) . '">
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