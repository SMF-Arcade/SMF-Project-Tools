<?php
/**
 * Admin pages for Projects 
 *
 * @package ProjectTools
 * @subpackage Admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Project Module Admin
 */
class ProjectTools_Admin_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	public function Main()
	{
		global $sourcedir, $context, $project, $txt;
		
		require_once($sourcedir . '/Subs-ProjectAdmin.php');
		
		loadTemplate('ProjectModule-Admin');
		loadLanguage('ProjectAdmin');
		loadLanguage('ProjectTools/UserAdmin');

		$context['project_sub_tabs'] = array(
			'main' => array(
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin')),
				'title' => $txt['project'],
				'is_selected' => false,
				'order' => 'first',
				'hide_linktree' => true,
			),
			'versions' => array(
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')),
				'title' => $txt['manage_versions'],
				'is_selected' => false,
				'order' => 10,
			),
			'category' => array(
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
				'title' => $txt['manage_project_category'],
				'is_selected' => false,
				'order' => 10,
			),
		);
		
		$subActions = array(
			'main' => array($this, 'ProjectAdminMain'),
			'versions' => array($this, 'ProjectAdminVersions'),
			'category' => array($this, 'ProjectAdminCategory'),
		);
		
		//
		foreach ($context['active_project_modules'] as $id => $module)
		{
			if (method_exists($module, 'RegisterAdminSubactions'))
			{
				$area = $module->RegisterAdminSubactions();
				
				foreach ($area as $id => $a)
				{
					$context['project_sub_tabs'][$id] = array(
						'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => $id)),
						'title' => $a['title'],
						'is_selected' => false,
						'order' => !isset($a['order']) ? 10 : (int) $a['order'],						
					);
					$subActions[$id] = $a['callback'];
				}
			}
		}
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		$context['project_sub_tabs'][$_REQUEST['sa']]['is_selected'] = true;
			
		call_user_func($subActions[$_REQUEST['sa']], $this->project);
	}
	
	/**
	 *
	 */
	public function RegisterArea()
	{
		global $txt;
		
		return array(
			'id' => 'admin',
			'title' => $txt['project_admin'],
			'callback' => 'Main',
			'hide_linktree' => true,
			'order' => 50,
			'project_permission' => 'admin',
		);
	}
	
	/**
	 *
	 */
	public function ProjectAdminMain()
	{
		global $context, $txt;
		
		$context['page_title'] = $txt['title_project_admin'];
	}
	
	/**
	 *
	 */
	public function ProjectAdminCategory()
	{
		global $txt;
		
		if (!isset($_REQUEST['category']))
			$this->ProjectAdminCategoryList();
		elseif (isset($_REQUEST['save']))
			$this->ProjectAdminCategoryEdit2();
		else
			$this->ProjectAdminCategoryEdit();
			
		$context['project_tabs']['description'] = $txt['project_admin_category_description'];
	}
	
	/**
	 *
	 */
	public function ProjectAdminCategoryList()
	{
		global $sourcedir, $context, $txt, $project;

		$listOptions = array(
			'id' => 'categories_list',
			'base_href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
			'get_items' => array(
				'function' => 'list_getCategories',
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
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
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
						<a href="' . ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category', 'category' => 'new')) . '">
							' . $txt['new_category'] . '
						</a>',
					'class' => 'catbg',
					'align' => 'right',
				),
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
	function ProjectAdminCategoryEdit()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		if ($_REQUEST['category'] == 'new')
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
	function ProjectAdminCategoryEdit2()
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
	
		redirectexit('action=admin;area=manageprojects;section=categories');
	}
}

?>