<?php
/**
 * Hooks for SMF Project Hooks
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
	 *
	 */
	public static function admin_areas(&$admin_areas)
	{
		global $txt, $modSettings;
		
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
}

?>