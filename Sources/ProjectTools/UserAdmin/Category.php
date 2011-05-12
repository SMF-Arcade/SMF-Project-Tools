<?php
/**
 * Main handler for Project Tools User Admin
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Project Admin
 */
class ProjectTools_UserAdmin_Category
{
	/**
	 *
	 */
	public static function Main()
	{
		global $context, $txt;
			
		$subActions = array(
			'main' => array('ProjectTools_UserAdmin_Category', 'ListCategory'),
			'edit' => array('ProjectTools_UserAdmin_Category', 'Edit'),
			'edit2' => array('ProjectTools_UserAdmin_Category', 'Edit2'),
			'new' => array('ProjectTools_UserAdmin_Category', 'Edit'),
			'new2' => array('ProjectTools_UserAdmin_Category', 'Edit2'),
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		//
		//loadTemplate('ProjectTools/UserAdmin');
			
		call_user_func($subActions[$_REQUEST['sa']]);
			
		$context['project_tabs']['description'] = $txt['pt_ua_category_desc'];
	}
	
	/**
	 *
	 */
	public static function ListCategory()
	{
		global $sourcedir, $context, $txt, $project;

		$listOptions = array(
			'id' => 'categories_list',
			'base_href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'categories')),
			'get_items' => array(
				'function' => array('ProjectTools_Admin', 'list_getCategories'),
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
							'format' => '<input type="checkbox" name="categories[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['header_category'],
					),
					'data' => array(
						'db' => 'link',
					),
					'sort' => array(
						'default' => 'cat.category_name',
						'reverse' => 'cat.category_name DESC',
					),
				),
			),
			'form' => array(
				'href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'categories')),
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'additional_rows' => array(
			),
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['page_title'] = sprintf($txt['title_category_list'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'categories_list';
	}
	
	/**
	 *
	 */
	public static function Edit()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		$context['category_form'] = new ProjectTools_Form_Category(
			isset($_REQUEST['category']) ? (int) $_REQUEST['category'] : null, null, null, array('project' => $project)
		);
		
		if ($context['category_form']->is_post && $context['category_form']->Save())
			redirectexit(ProjectTools::get_admin_url(array('project' => $project, 'area' => 'categories')));
			
		// Template
		$context['sub_template'] = 'edit_category';
	}
}

?>