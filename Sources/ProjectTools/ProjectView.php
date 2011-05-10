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
		
		// Language
		loadLanguage('ProjectTools/ProjectView');
			
		//
		$project_areas = array();
		
		//
		ProjectTools_Extensions::runProjectHooks('RegisterAreas', array(&$project_areas));
		
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
		
		// Default area
		if (empty($_REQUEST['area']) || !isset(self::$areas[$_REQUEST['area']]))
			$_REQUEST['area'] = 'main';
			
		// Default subaction
		if (empty($_REQUEST['sa']))
			$_REQUEST['sa'] = 'main';
			
		self::$current_area = &self::$areas[$_REQUEST['area']];
		
		// Create Tabs
		foreach (self::$areas as $id => &$area)
		{
			$area['href'] = $id !== 'main' ? ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => $id))
				: ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id));
			
			// Add links for sub buttons
			if (isset($area['sub_buttons']))
			{
				foreach ($area['sub_buttons'] as $sid => &$sub_btn)
				{
					$link = array('project' => ProjectTools_Project::getCurrent()->id);
					
					if ($id !== 'main')
						$link['area'] = $id;
					if ($sid !== 'main')
						$link['sa'] = $sid;
						
					$sub_btn['href'] = ProjectTools::get_url($link);
				}
			}
			
			$context['project_tabs']['tabs'][$id] = array(
				'title' => $area['title'],
				'href' => $area['href'],
				'is_selected' => $area === self::$current_area,
				'hide_linktree' => !empty($area['hide_linktree']),
				'order' => $area['order'],
				'sub_buttons' => isset($area['sub_buttons']) ? $area['sub_buttons'] : array(),
			);
		}
	
		// Sort tabs to correct order
		uasort($context['project_tabs']['tabs'], array('ProjectTools', 'projectTabSort'));
		
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
			
		// Add submenu if there is any
		if (!empty(self::$current_area['sub_buttons']))
			$context['project_sub_tabs'] = self::$current_area['sub_buttons'];
		
		// Template
		loadTemplate('Project', array('project'));
		
		if (!isset($_REQUEST['xml']))
		{
			$context['template_layers'][] = 'project';
			
			$context['html_headers'] .= '
			<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
		}
		
		call_user_func(self::$current_area['callback'], array($_REQUEST['sa']));
		
		// Todo: fix?
		if (isset($context['project_sub_tabs'][$_REQUEST['sa']]))
			$context['project_sub_tabs'][$_REQUEST['sa']]['is_selected'] = true;
	}
	
}

?>