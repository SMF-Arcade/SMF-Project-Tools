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
class ProjectTools_Install
{
	/**
	 *
	 */
	static protected $adminFeature = 'pj';
	
	/**
	 *
	 */
	static protected $settings = array(
		'issuesPerPage' => array(25, false),
		'commentsPerPage' => array(20, false),
		'projectEnabled' => array(1, false),
		'projectExtensions' => array('Frontpage,Roadmap,IssueTracker', true),
		'projectAttachments' => array(1, false),
		'linkIssues' => array(1, false),
	);
	
	/**
	 *
	 */
	static protected $permissions = array(
		'project_access' => array(-1, 0, 2),
		'project_admin' => array(),
	);

	/**
	 *
	 */
	static protected $hooks = array(
		'integrate_pre_include' => '$sourcedir/ProjectTools/Hooks.php',
		'integrate_pre_load' => 'ProjectTools_Hooks::pre_load',
		'integrate_actions' => 'ProjectTools_Hooks::actions',
		'integrate_admin_areas' => 'ProjectTools_Hooks::admin_areas',
		'integrate_core_features' => 'ProjectTools_Hooks::core_features',
		'integrate_load_theme' => 'ProjectTools_Hooks::load_theme',
		'integrate_profile_areas' => 'ProjectTools_Hooks::profile_areas',
		'integrate_menu_buttons' => 'ProjectTools_Hooks::menu_buttons',
	);
	
	/**
	 *
	 */
	static public function install()
	{
		Madjoki_Install_Helper::updateAdminFeatures(self::$adminFeature, true);
		Madjoki_Install_Helper::doSettings(self::$settings);
		Madjoki_Install_Helper::doPermission(self::$permissions);
		
		self::installDefaultData();
		
		foreach (self::$hooks as $hook => $func)
			add_integration_function($hook, $func);
	}
	
	/**
	 *
	 */
	static public function installDefaultData()
	{
		global $smcFunc;
		
		//
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}project_profiles
			WHERE id_profile = 1');
		
		list ($count) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		if ($count == 0)
		{
			$smcFunc['db_insert']('insert',
				'{db_prefix}project_profiles',
				array('id_profile' => 'int', 'profile_name' => 'string',),
				array(1, 'Default',),
				array('id_profile')
			);
			$smcFunc['db_insert']('insert',
				'{db_prefix}project_permissions',
				array('id_profile' => 'int', 'id_group' => 'int', 'permission' => 'string'),
				array(
					// Guest
					array(1, -1, 'issue_view'),
					// Regular members
					array(1, 0, 'issue_view'),
					array(1, 0, 'issue_report'),
					array(1, 0, 'issue_comment'),
					array(1, 0, 'issue_update_own'),
					array(1, 0, 'issue_attach'),
					array(1, 0, 'edit_comment_own'),
					// Global Moderators
					array(1, 2, 'issue_view'),
					array(1, 2, 'issue_report'),
					array(1, 2, 'issue_comment'),
					array(1, 2, 'issue_update_own'),
					array(1, 2, 'issue_update_any'),
					array(1, 2, 'issue_attach'),
					array(1, 2, 'issue_moderate'),
					array(1, 2, 'edit_comment_own'),
					array(1, 2, 'edit_comment_any'),
					array(1, 2, 'delete_comment_own'),
					array(1, 2, 'delete_comment_any'),
				),
				array('id_profile', 'id_group')
			);
		}
		
		//
		$smcFunc['db_insert']('ignore',
			'{db_prefix}project_trackers',
			array('id_tracker' => 'int', 'short_name' => 'string', 'tracker_name' => 'string',  'plural_name' => 'string'),
			array(
				array(1, 'bug', 'Bug', 'Bugs'),
				array(2, 'feature', 'Feature', 'Features'),
			),
			array('id_tracker')
		);
		
		//
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}package_servers
			WHERE name = {string:name}',
			array(
				'name' => 'SMF Project Tools Package Server',
			)
		);
		
		list ($count) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		
		if ($count == 0)
		{
			$smcFunc['db_insert']('insert',
				'{db_prefix}package_servers',
				array(
					'name' => 'string',
					'url' => 'string',
				),
				array(
					'SMF Project Tools Package Server',
					'http://download.smfproject.net',
				),
				array()
			);
		}
	}
	
	/**
	 *
	 */
	static public function uninstall($full_remove = false)
	{
		if ($full_remove)
		{
			/*Madjoki_Install_Helper::updateAdminFeatures(self::$adminFeature, true);
			Madjoki_Install_Helper::doSettings(self::$settings);
			Madjoki_Install_Helper::doPermission(self::$permissions);*/
		}
		
		foreach (self::$hooks as $hook => $func)
			remove_integration_function($hook, $func);
	}
	
}

?>