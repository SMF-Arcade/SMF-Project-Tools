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
	
		$context['statistics'] = array();
	
		// Reported Issues
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues
			WHERE id_reporter = {int:member}',
			array(
				'member' => $memID,
			)
		);
	
		list ($context['statistics']['reported_issues']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	
		// Assigned Issues
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues
			WHERE id_assigned = {int:member}',
			array(
				'member' => $memID,
			)
		);
	
		list ($context['statistics']['assigned_issues']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	
		// Format
		$context['statistics']['reported_issues'] = comma_format($context['statistics']['reported_issues']);
		$context['statistics']['assigned_issues'] = comma_format($context['statistics']['assigned_issues']);
	
		$context['reported_issues'] = getIssueList(0, 10, 'i.updated DESC', 'i.id_reporter = {int:profile_member}', array('profile_member' => $memID));
	
		// Template
		$context['sub_template'] = 'project_profile_main';
	}
}

?>