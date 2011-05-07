<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

/**
 *
 * @todo Cache queries
 * @todo fix version load
 */
class ProjectTools_ProjectView
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
	
	/**
	 *
	 */
	static public function Main()
	{
		global $context, $txt, $settings;
		
		loadLanguage('ProjectTools/ProjectView');
			
		$context['active_project_modules'] = array();

		// Load Modules
		foreach (ProjectTools_Project::getCurrent()->extensions as $id => $ext)
		{
			if (!$ext)
				continue;
			$module = $ext->getModule();
			$context['active_project_modules'][$module] = new $module(ProjectTools_Project::getCurrent());
		}
		
		if (empty($context['active_project_modules']))
			fatal_lang_error('pt_no_modules', false);
		
		//
		$project_areas = array();
		
		//
		foreach ($context['active_project_modules'] as $id => $module)
		{
			if ($area = $module->RegisterArea())
			{
				$area['module'] = $module;
				$project_areas[$area['id']] = $area;
			}
		}
		
		// No possible areas?
		if (empty($project_areas))
			fatal_lang_error('pt_no_modules', false);
			
		self::CreateAreas($project_areas);
		unset($project_areas);
		
		// Tabs
		$context['project_tabs'] = array(
			'title' => ProjectTools_Project::getCurrent()->name,
			'description' => ProjectTools_Project::getCurrent()->description,
			'tabs' => array(),
		);
		
		//
		if (empty($_REQUEST['area']) || !isset(self::$areas[$_REQUEST['area']]))
			$_REQUEST['area'] = 'main';
			
		if (empty($_REQUEST['sa']))
			$_REQUEST['sa'] = 'main';
			
		self::$current_area = &self::$areas[$_REQUEST['area']];
			
		
		// Create Tabs
		foreach (self::$areas as $id => &$area)
		{
			$area['href'] = $area['id'] !== 'main' ? ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => $id))
				: ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id));
			
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
			
		// Linktree
		$context['linktree'][] = array(
			'name' => strip_tags(ProjectTools_Project::getCurrent()->name),
			'url' => ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id)),
		);
		
		// Add area to linktree
		if (empty(self::$current_area['hide_linktree']))
			$context['linktree'][] = array(
				'name' => self::$current_area['title'],
				'url' => self::$current_area['href'],
			);
		
		//
		/*if (isset(self::$current_area['module']->subTabs[$_REQUEST['sa']]))
		{
			self::$current_area['module']->subTabs[$_REQUEST['sa']]['is_selected'] = true;
			
			if (empty(self::$current_area['module']->subTabs[$_REQUEST['sa']]['hide_linktree']))
				$context['linktree'][] = array(
					'name' => self::$current_area['module']->subTabs[$_REQUEST['sa']]['title'],
					'url' => self::$current_area['module']->subTabs[$_REQUEST['sa']]['href'],
				);
			
			$context['project_sub_tabs'] = self::$current_area['module']->subTabs;
		}*/
		
		// Template
		loadTemplate('Project', array('project'));
		
		if (!isset($_REQUEST['xml']))
		{
			$context['template_layers'][] = 'project';
			
			$context['html_headers'] .= '
			<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
		}
		
		call_user_func(array(self::$current_area['module'], self::$current_area['callback']), array($_REQUEST['sa']));
	}
	
}

?