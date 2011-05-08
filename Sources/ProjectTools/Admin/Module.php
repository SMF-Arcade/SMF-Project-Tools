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
	

}

?>