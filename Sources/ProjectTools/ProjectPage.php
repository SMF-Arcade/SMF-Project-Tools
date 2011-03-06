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
	/**
	 *
	 */
	static protected $areas;
	
	/**
	 *
	 */
	static protected $current_area;
	
	/**
	 *
	 */
	static public function Main()
	{
		global $context, $txt, $settings;
			
		$context['active_project_modules'] = array();
			
		// Load Modules
		foreach (ProjectTools_Project::getCurrent()->modules as $id)
		{
			$module = ProjectTools_Extensions::getModule($id);
			
			if ($module)
				$context['active_project_modules'][$id] = new $module['class_name'](ProjectTools_Project::getCurrent());
		}
		
		//
		$project_areas = array(
			'main' => array(
				'title' => $txt['project'],
				'href' => project_get_url(array('project' => ProjectTools_Project::getCurrent()->id)),
				'callback' => array(get_class(), 'ViewPage'),
				'hide_linktree' => true,
				'order' => 'first',
			),
		);
		
		//
		foreach ($context['active_project_modules'] as $id => $module)
		{
			$area = $module->RegisterArea();
			$project_areas[$area['id']] = $area;
		}
		
		self::CreateAreas($project_areas);
		unset($project_areas);
		
		// Tabs
		$context['project_tabs'] = array(
			'title' => ProjectTools_Project::getCurrent()->name,
			'description' => ProjectTools_Project::getCurrent()->description,
			'tabs' => array(),
		);
		
		if (empty($_REQUEST['area']) || !isset(self::$areas[$_REQUEST['area']]))
			$_REQUEST['area'] = 'main';
			
		if (empty($_REQUEST['sa']))
			$_REQUEST['sa'] = 'main';
			
		self::$current_area = &self::$areas[$_REQUEST['area']];
		
		// Create Tabs
		foreach (self::$areas as $id => &$area)
		{
			$area['href'] = isset($area['href']) ? $area['href'] : project_get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => $id));
			
			$context['project_tabs']['tabs'][$id] = array(
				'title' => $area['title'],
				'href' => $area['href'],
				'is_selected' => $area === self::$current_area,
				'hide_linktree' => !empty($area['hide_linktree']),
				'order' => $area['order'],
			);
		}
	
		// Sort tabs to correct order
		uksort($context['project_tabs']['tabs'], 'projectTabSort');
			
		// Call Initialize View function
		//if (isset($context['current_project_module']) && method_exists($context['current_project_module'], 'beforeSubaction'))
		//	$context['current_project_module']->beforeSubaction($_REQUEST['sa']);
			
		// Linktree
		$context['linktree'][] = array(
			'name' => strip_tags(ProjectTools_Project::getCurrent()->name),
			'url' => project_get_url(array('project' => ProjectTools_Project::getCurrent()->id)),
		);
		
		if (empty(self::$current_area['hide_linktree']))
			$context['linktree'][] = array(
				'name' => self::$current_area['title'],
				'url' => self::$current_area['href'],
			);
		
		/*
		if (isset($context['current_project_module']->subTabs[$_REQUEST['sa']]))
		{
			$context['current_project_module']->subTabs[$_REQUEST['sa']]['is_selected'] = true;
			
			if (empty($context['current_project_module']->subTabs[$_REQUEST['sa']]['hide_linktree']))
				$context['linktree'][] = array(
					'name' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['title'],
					'url' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['href'],
				);
			
			$context['project_sub_tabs'] = $context['current_project_module']->subTabs;
		}*/
		
		// Template
		loadTemplate('Project', array('project'));
		
		if (!isset($_REQUEST['xml']))
		{
			$context['template_layers'][] = 'project';
			
			$context['html_headers'] .= '
			<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
		}
		
		call_user_func(self::$current_area['callback'], array($_REQUEST['sa']));
	}
	
	/**
	 *
	 */
	static private function CreateAreas($areas)
	{
		foreach ($areas as $id => $area)
		{
			if (!empty($area['project_permission']) && !ProjectTools_Project::getCurrent()->allowedTo($area['project_permission']))
				continue;
			elseif (!empty($area['permission']) && !allowedTo($area['permission']))
				continue;
		
			self::$areas[$id] = $area;
		}
	}
}

?>