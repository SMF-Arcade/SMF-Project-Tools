<?php
/**
 * Manage Project
 *
 * @package admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class ProjectTools_ManageProjects
{
	/**
	 *
	 */
	public static function Main()
	{
		global $context, $sourcedir, $user_info, $txt;
	
		require_once($sourcedir . '/Subs-Project.php');
	
		isAllowedTo('project_admin');
		ProjectTools_Main::loadPage('admin');
	
		$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_projects'];
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_projects_description'];
	
		$context['page_title'] = $txt['manage_projects'];
	
		$subActions = array(
			'list' => array('ListProjects', 'list'),
			'new' => array('Edit', 'new'),
			'edit' => array('Edit', 'list'),
		);
	
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
	
		if (isset($subActions[$_REQUEST['sa']][1]))
			$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];
		else
			$context[$context['admin_menu_name']]['current_subsection'] = $section['id'];
			
		// Load template if needed
		loadTemplate('ManageProjects');
	
		// Call action
		self::$subActions[$_REQUEST['sa']][0]();
	}
	
	/**
	 *
	 */
	public static function ListProjects()
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
		$listOptions = array(
			'id' => 'projects_list',
			'base_href' => $scripturl . '?action=admin;area=manageprojects',
			'get_items' => array(
				'function' => array('ProjectTools_Admin', 'list_getProjects'),
			),
			'default_sort_col' => 'name',
			'columns' => array(
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
						'style' => 'width: 4%;',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="projects[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['header_project'],
					),
					'data' => array(
						'db' => 'link',
					),
					'sort' => array(
						'default' => 'p.name',
						'reverse' => 'p.name DESC',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=manageprojects',
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'no_items_label' => $txt['no_projects'],
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['sub_template'] = 'projects_list';
	}
	
	/**
	 *
	 */
	public static function Edit()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt;
	
		$context['project_form'] = new ProjectTools_Form_ProjectEdit(isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : null);
		
		if ($context['project_form']->is_post && $context['project_form']->Save())
			redirectexit('action=admin;area=manageprojects');
		
		$context['sub_template'] = 'edit_project';
	}
}

?>