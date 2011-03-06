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
		
		if (substr($class_name, 0, 12) == 'ProjectTools')
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
		
		$actionArray['projects'] = array('Project.php', array('ProjectTools', 'Projects'));
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