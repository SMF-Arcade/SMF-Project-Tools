<?php
/**
 * Contains code for profile pages.
 *
 * @package profile
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * @todo Implement extension support
 */
class ProjectTools_UserProfile
{
	/**
	 * Project profile
	 * @todo fix this
	 */
	public static function Main($memID)
	{
		global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
		global $user_info, $smcFunc, $sourcedir;
	
		//
		ProjectTools_Main::loadPage('profile');
	
		$subActions = array(
			'main' => array(array('ProjectTools_UserProfile', 'projectProfileMain')),
		);
		
		// Let hooks add subActions
		ProjectTools_Extensions::runHooks('Profile_subActions', array(&$subActions));
	
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';
	
		$context[$context['profile_menu_name']]['tab_data']['title'] = $txt['project_tools_profile'];
		$context[$context['profile_menu_name']]['tab_data']['description'] = $txt['project_tools_profile_desc'];
	
		// Check permission if needed
		if (isset($subActions[$_REQUEST['sa']][1]))
			isAllowedTo($subActions[$_REQUEST['sa']][1]);
			
		call_user_func($subActions[$_REQUEST['sa']][0], $memID);
	}
	
	/**
	 * General stats
	 */
	public static function projectProfileMain($memID)
	{
		global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
		global $user_info, $smcFunc, $sourcedir;
	
		$context['pt_statistics'] = array();

		// Let hooks add statistics
		ProjectTools_Extensions::runHooks('Profile_addStatistics', array(&$context['pt_statistics']));
		
		//
		foreach ($context['pt_statistics'] as &$statistic)
			$statistic['number'] = comma_format($statistic['number']);
	
		// Template
		$context['sub_template'] = 'project_profile_main';
	}
}

?>