<?php
/**
 * Hooks for SMF Project
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Hooks
{
	/**
	 * Autoload function
	 * 
	 * @param string $class_name Class Name
	 */
	static public function autoload($class_name)
	{
		global $sourcedir;
		
		if ($class_name == 'ProjectTools')
			require_once($sourcedir . '/ProjectTools/ProjectTools.php');	
		elseif (substr($class_name, 0, 12) == 'ProjectTools')
		{
			$class_file = str_replace('_', '/', $class_name);
	
			if (file_exists($sourcedir . '/' . $class_file . '.php'))
				require_once($sourcedir . '/' . $class_file . '.php');
			else
				return false;
				
			return true;
		}
		
		return false;
	}
	
	/**
	 * Registers autoload function
	 *
	 * @todo Move to somewhere else?
	 */
	static public function registerAutoload()
	{
		spl_autoload_register(array(__CLASS__, 'autoload'));
	}
	
	/**
	 * Inserts array in array after key
	 *
	 * @param array $input Input array
	 * @param string $key Key to search
	 * @param array $insert Array of values to insert after
	 * @param string $where Relation to insert to key: 'before' or 'after'.
	 * @param bool $strict Strict parameter for array_search.
	 */
	public static function array_insert(&$input, $key, $insert, $where = 'after', $strict = false)
	{
		$position = array_search($key, array_keys($input), $strict);
		
		// Key not found -> insert as last
		if ($position === false)
		{
			$input = array_merge($input, $insert);
			return;
		}
		
		if ($where === 'after')
			$position += 1;

		// Insert as first
		if ($position === 0)
			$input = array_merge($insert, $input);
		else
			$input = array_merge(
				array_slice($input, 0, $position, true),
				$insert,
				array_slice($input, $position, null, true)
			);
	}
	
	/**
	 * SMF Hook integrate_pre_load
	 */
	public static function pre_load()
	{
		self::registerAutoload();
	}
	
	/**
	 * SMF Hook integrate_actions
	 *
	 * Adds actions in $actionArray of index.php
	 */
	public static function actions(&$actionArray)
	{
		global $modSettings;
		
		if (empty($modSettings['projectEnabled']))
			return;
		
		$actionArray['projects'] = array('ProjectTools/Main.php', array('ProjectTools_Main', 'Main'));
		$actionArray['projectadmin'] = array('ProjectTools/Admin/Main.php', array('ProjectTools_Admin_Main', 'Main'));
	}
	
	/**
	 * SMF Hook integrate_menu_buttons
	 */
	public static function menu_buttons(&$menu_buttons)
	{
		global $modSettings, $context, $txt, $scripturl;
		
		$context['allow_project'] = !empty($modSettings['projectEnabled']) && allowedTo('project_access');
		
		if (empty($modSettings['projectEnabled']))
			return;
		
		if ($context['current_action'] == 'projectadmin')
			$context['current_action'] = 'projects';
		
		self::array_insert($menu_buttons, 'search', array(
			'projects' => array(
				'title' => $txt['projects'],
				'href' => project_get_url(),
				'show' => $context['allow_project'],
				'sub_buttons' => array(
					'admin' => array(
						'title' => $txt['projects_admin'],
						'href' => project_admin_get_url(),
						'show' => allowedTo('project_admin'), // TODO: Allow is users is project admin
					),
				),
			)), 'before'
		);
	}
	
	/**
	 *
	 */
	static public function load_theme()
	{
		global $modSettings, $context, $txt;
		
		if (empty($modSettings['projectEnabled']))
			return;
		
		loadLanguage('Project');
	
		// Load status texts
		foreach ($context['issue_status'] as $id => $status)
		{
			if (isset($txt['issue_status_' . $status['name']]))
				$status['text'] = $txt['issue_status_' . $status['name']];
	
			$context['issue_status'][$id] = $status;
		}
	
		// Apply translated names to trackers
		foreach ($context['issue_trackers'] as $id => $tracker)
		{
			if (!isset($txt['issue_type_' . $tracker['short']]) || !isset($txt['issue_type_plural_' . $tracker['short']]))
				continue;
			
			$tracker['name'] = $txt['issue_type_' . $tracker['short']];
			$tracker['plural'] = $txt['issue_type_plural_' . $tracker['short']];
			
			$context['issue_trackers'][$id] = $tracker;
		}
		
		$context['issues_per_page'] = !empty($modSettings['issuesPerPage']) ? $modSettings['issuesPerPage'] : 25;
		$context['comments_per_page'] = !empty($modSettings['commentsPerPage']) ? $modSettings['commentsPerPage'] : 20;	

		loadIssue();
	}
	
	/**
	 * SMF Hook integrate_admin_areas
	 *
	 * Adds Project Tools group in admin.
	 */
	public static function admin_areas(&$admin_areas)
	{
		global $txt, $modSettings;
		
		if (empty($modSettings['projectEnabled']))
			return;
		
		self::array_insert($admin_areas, 'forum',
			array(
				'project' => array(
					'title' => $txt['project_tools'],
					'permission' => array('project_admin'),
					'areas' => array(
						'projectsadmin' => array(
							'label' => $txt['project_general'],
							'file' => 'ProjectAdmin.php',
							'function' => 'ProjectsAdmin',
							'enabled' => !empty($modSettings['projectEnabled']),
							'permission' => array('project_admin'),
							'subsections' => array(
								'main' => array($txt['project_general_main']),
								'settings' => array($txt['project_general_settings']),
								'maintenance' => array($txt['project_general_maintenance']),
								'extensions' => array($txt['project_general_extensions'])
							),
						),
						'manageprojects' => array(
							'label' => $txt['manage_projects'],
							'file' => 'ManageProjects.php',
							'function' => 'ManageProjects',
							'enabled' => !empty($modSettings['projectEnabled']),
							'permission' => array('project_admin'),
							'subsections' => array(
								'list' => array($txt['modify_projects']),
								'new' => array($txt['new_project']),
							),
						),
						'projectpermissions' => array(
							'label' => $txt['manage_project_permissions'],
							'file' => 'ProjectPermissions.php',
							'function' => 'ManageProjectPermissions',
							'enabled' => !empty($modSettings['projectEnabled']),
							'permission' => array('project_admin'),
							'subsections' => array(),
						),
					),
				),
			)
		);
	}
	
	/**
	 *
	 */
	public static function profile_areas(&$profile_areas)
	{
		global $txt, $modSettings;
		
		if (empty($modSettings['projectEnabled']))
			return;
		
		self::array_insert($profile_areas['info']['areas'], 'showposts',
			array(
				'project' => array(
					'label' => $txt['project_profile'],
					'file' => 'Profile-Project.php',
					'function' => 'projectProfile',
					'enabled' => !empty($modSettings['projectEnabled']),
					'subsections' => array(
						'main' => array($txt['project_profile_main']),
						'reported' => array($txt['project_profile_reported']),
						'assigned' => array($txt['project_profile_assigned']),
					),
					'permission' => array(
						'own' => 'project_profile_own',
						'any' => 'project_profile_any',
					),
				),
			)
		);
	}
	
	/**
	 *
	 */
	public static function core_features(&$core_features)
	{
		$core_features['pj'] = array(
			'url' => 'action=admin;area=projectsadmin',
			'settings' => array(
				'projectEnabled' => 1,
			),
		);
	}
}

?>