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
class ProjectTools_UserAdmin
{
	/**
	 * Main Project Tools functions, handles calling correct module and action
	 */
	static public function Main()
	{
		global $context, $smcFunc, $user_info, $txt;
		
		loadLanguage('ProjectTools/UserAdmin');
		loadTemplate('ProjectTools/UserAdmin');
		
		is_not_guest($txt['pt_ua_no_guest']);
	
		// Check that user can access Project Tools
		isAllowedTo('project_access');
		
		if (!ProjectTools_Project::getCurrent())
			return self::SelectProject();
		else
			return self::AdminProject();
	}
	
	/**
	 *
	 */
	static public function SelectProject()
	{
		global $context, $smcFunc, $user_info, $txt;
		
		// 
		$context['admin_projects'] = array();

		// Which projects I can admin?
		$request = $smcFunc['db_query']('', '
			SELECT p.id_project, p.name
			FROM {db_prefix}projects AS p' . (!allowedTo('project_admin') ? '
				INNER JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})' : ''),
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['admin_projects'][$row['id_project']] = array(
				'id' => $row['id_project'],
				'name' => $row['name'],
				'href' => ProjectTools::get_admin_url(array('project' => $row['id_project'])),
			);
		$smcFunc['db_free_result']($request);
		
		if (count($context['admin_projects']) == 1)
		{
			$project = array_pop($context['admin_projects']);
			
			redirectexit(ProjectTools::get_admin_url(array('project' => $project['id'])));
		}
		elseif (count($context['admin_projects']) == 0)
			fatal_lang_error('pt_admin_not_allowed', false);
			
		$context['sub_template'] = 'select_project';
	}
	
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
	static public function AdminProject()
	{
		global $context, $txt, $settings;
		
		$project_areas = array(
			'main' => array(
				'id' => 'main',
				'module' => 'ProjectTools_UserAdmin',
				'callback' => 'Frontpage',
				'title' => $txt['pt_ua_tab_main'],
				'order' => 'first',
			),
			'members' => array(
				'id' => 'members',
				'module' => 'ProjectTools_UserAdmin_Members',
				'callback' => 'Main',
				'title' => $txt['pt_ua_tab_members'],
				'order' => 1,				
			),
			'versions' => array(
				'id' => 'versions',
				'module' => 'ProjectTools_UserAdmin_Versions',
				'callback' => 'Main',
				'title' => $txt['pt_ua_tab_versions'],
				'order' => 2,
			),
		);
		//
		foreach (ProjectTools_Project::getCurrent()->getModules() as $id => $module)
		{
			/*if ($area = $module->RegisterAdminArea())
			{
				$area['module'] = $module;
				$project_areas[$area['id']] = $area;
			}*/
		}
			
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
			$area['href'] = $area['id'] !== 'main' ? ProjectTools::get_admin_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => $id))
				: ProjectTools::get_admin_url(array('project' => ProjectTools_Project::getCurrent()->id));
			
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
		$context['linktree'][] = array(
			'name' => $txt['pt_admin'],
			'url' => ProjectTools::get_admin_url(array('project' => ProjectTools_Project::getCurrent()->id)),
		);
		
		// Add area to linktree
		if (empty(self::$current_area['hide_linktree']))
			$context['linktree'][] = array(
				'name' => self::$current_area['title'],
				'url' => self::$current_area['href'],
			);
		
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
	
	/**
	 *
	 */
	static public function Frontpage()
	{
		global $context;
		
		$context['project_form'] = new ProjectTools_Form_ProjectEdit(ProjectTools_Project::getCurrent()->id);
		if ($context['project_form']->is_post && $context['project_form']->Save())
			redirectexit(ProjectTools::get_admin_url(array('project' => ProjectTools_Project::getCurrent()->id)));
		
		$context['sub_template'] = 'admin_frontpage';
	}
}

?>