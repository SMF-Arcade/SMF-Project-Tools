<?php
/**
 * Main handler for Project Tools
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_ProjectPage
{
	static public function Main()
	{
		global $context;
		
		// Areas are sets of subactions (registered by modules)
		$subAreas = array();
		
		// Tabs
		$context['project_tabs'] = array(
			'title' => ProjectTools_Project::getCurrent()->name,
			'description' => ProjectTools_Project::getCurrent()->description,
			'tabs' => array(
				'main' => array(
					'href' => project_get_url(array('project' => $project)),
					'title' => $txt['project'],
					'is_selected' => false,
					'hide_linktree' => true,
					'order' => 'first',
				),
			),
		);
		
		$context['active_project_modules'] = array('ProjectTools_IssueTracker_Extension');
	
		// Let Modules register subAreas
		if (!empty($context['active_project_modules']))
		{
			foreach ($context['active_project_modules'] as $id => $module)
			{
				if (method_exists($module, 'RegisterProjectArea'))
				{
					$area = $module->RegisterProjectArea();
					
					$subAreas[$area['area']] = array(
						'area' => $area['area'],
						'module' => $id,
						'tab' => !empty($area['tab']) ? $area['tab'] : $area['area'],
					);
				}
				if (method_exists($module, 'RegisterProjectTabs'))
					$module->RegisterProjectTabs($context['project_tabs']['tabs']);
			}
		}
		
		// Remove tabs which user has no permission to see 
		foreach ($context['project_tabs']['tabs'] as $id => $tab)
		{
			if (!empty($tab['permission']) && !allowedTo($tab['permission']))
				unset($context['project_tabs']['tabs'][$id]);
			elseif (!empty($tab['project_permission']) && !projectAllowedTo($tab['project_permission']))
				unset($context['project_tabs']['tabs'][$id]);
		}
	
		// Sort tabs to correct order
		uksort($context['project_tabs']['tabs'], 'projectTabSort');
	
		if (empty($_REQUEST['area']) || !isset($subAreas[$_REQUEST['area']]))
			$_REQUEST['area'] = 'main';
			
		if (empty($_REQUEST['sa']))
			$_REQUEST['sa'] = 'main';
			
		$current_area = &$subAreas[$_REQUEST['area']];
		$context['current_project_module'] = &$context['active_project_modules'][$current_area['module']];
		
		if (isset($context['project_tabs']['tabs'][$current_area['tab']]))
			$context['project_tabs']['tabs'][$current_area['tab']]['is_selected'] = true;
		else
			$context['project_tabs']['tabs']['main']['is_selected'] = true;
			
		// Can access this area?
		if (isset($current_area['permission']))
			isAllowedTo($current_area['permission']);
		if (isset($current_area['project_permission']))
			projectIsAllowedTo($current_area['project_permission']);
			
		// Call Initialize View function
		if (isset($context['current_project_module']) && method_exists($context['current_project_module'], 'beforeSubaction'))
			$context['current_project_module']->beforeSubaction($_REQUEST['sa']);
			
		// Linktree
		$context['linktree'][] = array(
			'name' => strip_tags(ProjectTools_Project::getCurrent()->name),
			'url' => project_get_url(array('project' => $project)),
		);
		
		if (empty($context['project_tabs']['tabs'][$current_area['tab']]['hide_linktree']))
			$context['linktree'][] = array(
				'name' => $context['project_tabs']['tabs'][$current_area['tab']]['title'],
				'url' => $context['project_tabs']['tabs'][$current_area['tab']]['href'],
			);
			
		if (isset($context['current_project_module']->subTabs[$_REQUEST['sa']]))
		{
			$context['current_project_module']->subTabs[$_REQUEST['sa']]['is_selected'] = true;
			
			if (empty($context['current_project_module']->subTabs[$_REQUEST['sa']]['hide_linktree']))
				$context['linktree'][] = array(
					'name' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['title'],
					'url' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['href'],
				);
			
			$context['project_sub_tabs'] = $context['current_project_module']->subTabs;
		}
		
		$context['current_project_module']->main($_REQUEST['sa']);
	}
}

?>