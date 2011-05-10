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
	
		if ($_REQUEST['sa'] == 'new')
		{
			$context['category'] = array(
				'is_new' => true,
				'id' => 0,
				'name' => '',
			);
			
			$context['page_title'] = sprintf($txt['title_category_new'], ProjectTools_Project::getCurrent()->name);
		}
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_category, id_project, category_name
				FROM {db_prefix}issue_category
				WHERE id_category = {int:category}
					AND id_project = {int:project}',
				array(
					'category' => (int) $_REQUEST['category'],
					'project' => $project,
				)
			);
			$row = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);
	
			if (!$row)
				fatal_lang_error('category_not_found');
	
			$context['category'] = array(
				'id' => $row['id_category'],
				'name' => htmlspecialchars($row['category_name']),
			);
			
			$context['page_title'] = sprintf($txt['title_category_edit'], ProjectTools_Project::getCurrent()->name, htmlspecialchars($row['category_name']));
	
			unset($row);
		}
	
		if (!isset($_REQUEST['delete']))
		{
			$context['sub_template'] = 'edit_category';
	
			if (!empty($context['category']['is_new']))
				$context['page_title'] = $txt['new_category'];
			else
				$context['page_title'] = $txt['edit_category'];
		}
		else
		{
			$context['sub_template'] = 'confirm_category_delete';
			$context['page_title'] = $txt['confirm_category_delete'];
		}
	}
	
	/**
	 *
	 */
	public static function Edit2()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		checkSession();
	
		$_POST['category'] = (int) $_POST['category'];
	
		if (isset($_POST['edit']) || isset($_POST['add']))
		{
			$categoryOptions = array();
	
			$categoryOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['category_name']);
	
			if (isset($_POST['add']))
				createPTCategory($project, $categoryOptions);
			else
				updatePTCategory($project, $_POST['category'], $categoryOptions);
		}
		elseif (isset($_POST['delete']))
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}issue_category
				WHERE id_category = {int:category}
					AND id_project = {int:project}',
				array(
					'category' => $_POST['category'],
					'project' => $project,
				)
			);
		}
		
		redirectexit(ProjectTools::get_admin_url(array('project' => $project, 'area' => 'categories')));
	}
}

?>