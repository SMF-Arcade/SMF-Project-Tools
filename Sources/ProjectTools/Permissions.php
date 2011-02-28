<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

/**
 * Permissions Profile
 * 
 * @todo Deny support
 */
class ProjectTools_Permissions
{
	/**
	 * Contains permissions profile instances
	 */
	private static $_instances = array();
	
	/**
	 *
	 * @return ProjectTools_Permissions Profile instance
	 */
	static function getProfile($id)
	{
		if (!isset(self::$_instances[$id]))
			self::$_instances[$id] = new self($id);
			
		return self::$_instances[$id];
	}
	
	/**
	 * Project ID
	 * 
	 * @var boolean|int ID Of Permission profile. False if not found
	 */
	public $id;
	
	/**
	 *
	 */
	private $permissions;
	
	/**
	 *
	 */
	function __construct($id)
	{
		global $user_info, $modSettings;
		
		$this->id = $id;
		
		// Admins are allowed to do everything!
		if ($user_info['is_admin'])  // allowedTo('project_admin')?
			return;
		
		if (!empty($modSettings['cache_enable']))
		{
			$cache_groups = $user_info['groups'];
			asort($cache_groups);
			$cache_groups = implode(',', $cache_groups);
			// If it's a spider then cache it different.
			if ($user_info['possibly_robot'])
				$cache_groups .= '-spider';
				
			if (($temp = cache_get_data('project_profile:' . $this->id . ':' . $cache_groups, 240)) != null && time() - 240 > $modSettings['settings_updated'])
				list ($this->permissions) = $temp;
		}
		
		if (empty($this->permissions))
		{
			$request = $smcFunc['db_query']('', '
				SELECT permission
				FROM {db_prefix}project_permissions
				WHERE id_group IN({array_int:groups})
					AND id_profile = {int:profile}',
				array(
					'profile' => $context['project']['profile'],
					'groups' => $user_info['groups'],
				)
			);
	
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$user_info['project_permissions'][$row['permission']] = true;	
			$smcFunc['db_free_result']($request);
	
			// User can see private issues
			//if (!empty($user_info['project_permissions']['issue_view_private']))
			//	$user_info['query_see_issue_project'] = $user_info['query_see_version_issue'];
			
			if (!empty($modSettings['cache_enable']))
				cache_put_data('project_profile:' . $this->id . ':' . $cache_groups, array($user_info['project_permissions'], null), 240);			
		}
	}
	
	/**
	 * Checks whatever permission is allowed in this profile
	 */
	function allowedTo($permission)
	{
		global $context, $user_info;

		// Admins can do anything
		if (allowedTo('project_admin'))
			return true;
	
		if (isset($this->permissions[$permission]) && $this->permissions[$permission])
			return true;
	
		return false;
	}
}

?>