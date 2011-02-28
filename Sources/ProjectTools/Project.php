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
class ProjectTools_Project
{
	/**
	 * Contains project instances
	 */
	private static $_instances = array();
	
	/**
	 *
	 * @return ProjectTools_Project Project Instance
	 */
	static function getProject($id)
	{
		if (!isset(self::$_instances[$id]))
			self::$_instances[$id] = new self($id);
			
		return self::$_instances[$id];
	}
	
	/**
	 * Project ID
	 * 
	 * @var boolean|int ID Of Project. False if not found
	 */
	public $id;
	
	/**
	 * @var ProjectTools_Permissions
	 */
	private $permissions;
	
	/**
	 * Name of project
	 */
	public $name;
	
	/**
	 *
	 */
	public function __construct($id)
	{
		global $smcFunc, $context;
		
		$request = $smcFunc['db_query']('', '
			SELECT
				p.id_project, p.id_profile, p.name, p.description, p.long_description, p.trackers, p.modules, p.member_groups,
				p.id_event_mod, p.' . implode(', p.', $context['tracker_columns']) . ', p.project_theme
			FROM {db_prefix}projects AS p
			WHERE p.id_project = {int:project}
			LIMIT 1',
			array(
				'project' => $project,
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		
		if (!$row)
		{
			$this->id = false;
		}
		
		$this->id = $row['id_project'];
		$this->name = $row['name'];
		
		$this->permissions = ProjectTools_Permissions::getProfile($row['id_profile']);
	}
	
	/**
	 * Checks whatever permission is allowed in this project
	 */
	function allowedTo($permission)
	{
		global $context, $user_info, $project;

		// Admins and developers can do anything
		if (allowedTo('project_admin')/* || $context['project']['is_developer']*/)
			return true;
	
		return $this->permissions->allowedTo($permission);
	}
	
	/**
	 * Checks if permission is allowed in this project and shows error page if not
	 */
	function isAllowedTo($permission)
	{
		global $txt, $user_info;

		if (!$this->allowedTo($permission))
		{
			if ($user_info['is_guest'])
				is_not_guest($txt['cannot_project_' . $permission]);
	
			fatal_lang_error('cannot_project_' . $permission, false);
	
			// Getting this far is a really big problem, but let's try our best to prevent any cases...
			trigger_error('Hacking attempt...', E_USER_ERROR);
		}
	}
}

?>