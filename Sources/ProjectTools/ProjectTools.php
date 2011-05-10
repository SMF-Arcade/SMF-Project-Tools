<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools
{
	/**
	 *
	 */
	static public $version = '0.6';
	
	/**
	 * Handles loading all required data of project tools
	 */
	static public function load()
	{
		global $context, $smcFunc, $modSettings, $user_info, $txt, $settings, $projects_show;
		global $sourcedir;
	
		//
		require_once($sourcedir . '/Subs-Project.php');
		require_once($sourcedir . '/Subs-Issue.php');
		
		// Issue Regex
		if (empty($modSettings['issueRegex']))
			$modSettings['issueRegex'] = array('[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+', '(\d+)');
		else
			$modSettings['issueRegex'] = explode("\n", $modSettings['issueRegex'], 2);
			
		$modSettings['projectExtensions'] = !empty($modSettings['projectExtensions']) ? explode(',', $modSettings['projectExtensions']) : array('Frontpage', 'IssueTracker', 'Roadmap');
	
		// Load extensions
		foreach ($modSettings['projectExtensions'] as $extension)
			ProjectTools_Extensions::loadExtension($extension);
			
		// Status, types, priorities
		$context['issue_status'] = array(
			1 => array(
				'id' => 1,
				'name' => 'new',
				'type' => 'open',
			),
			2 => array(
				'id' => 2,
				'name' => 'feedback',
				'type' => 'open',
			),
			3 => array(
				'id' => 3,
				'name' => 'confirmed',
				'type' => 'open',
			),
			4 => array(
				'id' => 4,
				'name' => 'assigned',
				'type' => 'open',
			),
			5 => array(
				'id' => 5,
				'name' => 'resolved',
				'type' => 'closed',
			),
			6 => array(
				'id' => 6,
				'name' => 'closed',
				'type' => 'closed',
			),
		);
	
		$context['closed_status'] = array(5, 6);
	
		// Priorities
		$context['issue']['priority'] = array(
			1 => 'issue_priority_low',
			'issue_priority_normal',
			'issue_priority_high'
		);
		
		self::setupQueries();
		self::loadTrackers();
		self::loadProject();
	}
	
	/**
	 *
	 */
	static protected function setupQueries()
	{
		global $user_info, $smcFunc;
		
		// Administrators can see all projects.
		if ($user_info['is_admin'] || allowedTo('project_admin'))
		{
			$see_project = '1 = 1';
			$see_version = '1 = 1';
			$see_version_timeline = '1 = 1';
			$see_issue = '1 = 1';
		}
		else
		{
			$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';
	
			// Version 0 can be always seen
			$user_info['project_allowed_versions'] = array(0);
			
			// Get versions that can be seen
			$request = $smcFunc['db_query']('', '
				SELECT id_version
				FROM {db_prefix}project_versions AS ver
				WHERE (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups))'
			);
			
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$user_info['project_allowed_versions'][] = $row['id_version'];
			$smcFunc['db_free_result']($request);
			
			// See version
			if (!empty($user_info['project_allowed_versions']))
				$see_version = '(id_version IN(' . implode(',', $user_info['project_allowed_versions']) . '))';
			else
				$see_version = '(0=1)';
				
			// See version in timeline query
			if (!empty($user_info['project_allowed_versions']))
				$see_version_timeline = '(FIND_IN_SET(' . implode(', IFNULL(i.versions, tl.versions)) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', IFNULL(i.versions, tl.versions)))';
			else
				$see_version_timeline = '(0=1)';
				
			// See private issues code
			$my_issue = $user_info['is_guest'] ? '(0=1)' : '(i.id_reporter = ' . $user_info['id'] . ')';
			
			// See version in issue query
			if (!empty($user_info['project_allowed_versions']))
				$see_version_issue = '(FIND_IN_SET(' . implode(', i.versions) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', i.versions))';
			else
				$see_version_issue = '(0=1)';
				
			// Private issues
			$see_private_profiles = getPrivateProfiles();
			if (!empty($see_private_profiles))
				$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR (' . $my_issue . ' OR p.id_profile IN(' . implode(', ', $see_private_profiles) . ')))';
			else
				$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR ' . $my_issue . ')';
				
			$see_issue = '((' . $see_version_issue . ') AND ' . $see_private . ')';
		}
		
		// See project
		$user_info['query_see_project'] = $see_project;
		// See version
		$user_info['query_see_version'] = $see_version;
		// See version timeline
		$user_info['query_see_version_timeline'] = $see_version_timeline;
		// Issue of any project
		$user_info['query_see_issue'] = $see_issue;
		
		if (isset($projects_show) && (empty($projects_show) || !is_array($projects_show)))
			$user_info['query_see_project'] = '0=1';
		elseif (isset($projects_show))
			$user_info['query_see_project'] = '(p.id_project IN(' . implode(',', $projects_show) . ') AND ' . $see_project . ')';
	}
	
	/**
	 *
	 */
	static protected function loadTrackers()
	{
		global $smcFunc, $context;
		
		// Trackers
		$context['issue_trackers'] = array();
		$context['tracker_columns'] = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT id_tracker, short_name, tracker_name, plural_name
			FROM {db_prefix}project_trackers',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['issue_trackers'][$row['id_tracker']] = array(
				'id' => $row['id_tracker'],
				'name' => $row['tracker_name'],
				'short' => $row['short_name'],
				'plural' => $row['plural_name'],
				'image' => $row['short_name'] . '.png',
				'column_open' => 'open_' . $row['short_name'],
				'column_closed' => 'closed_' . $row['short_name'],
			);
	
			$context['tracker_columns'][] = "open_$row[short_name]";
			$context['tracker_columns'][] = "closed_$row[short_name]";
		}
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	static protected function loadProject()
	{
		global $issue, $project, $context, $user_info, $smcFunc;
		
		// Issue with start
		if (isset($_REQUEST['issue']) && strpos($_REQUEST['issue'], '.') !== false)
		{
			list ($_REQUEST['issue'], $_REQUEST['start']) = explode('.', $_REQUEST['issue'], 2);
			$issue = (int) $_REQUEST['issue'];
			
			// This is for Who's online
			$_GET['issue'] = $issue;
		}
		elseif (isset($_REQUEST['issue']))
		{
			$issue = (int) $_REQUEST['issue'];
			
			// This is for Who's online
			$_GET['issue'] = $issue;
		}
		else
			$issue = 0;
			
		// 
		if (isset($force_project))
			$project = $force_project;
		// Project as parameter?
		elseif (!empty($_REQUEST['project']))
			$project = (int) $_REQUEST['project'];
		// Do we have issue?
		elseif (!empty($issue))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_project
				FROM {db_prefix}issues
				WHERE id_issue = {int:issue}',
				array(
					'issue' => (int) $issue
				)
			);
	
			list ($project) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
	
			if (empty($project))
			{
				$context['project_error'] = 'issue_not_found';
				return;
			}
		}
		
		if (!empty($project) && (empty($_REQUEST['action']) || $_REQUEST['action'] == 'projects'))
		{
			// For Who's online
			$_REQUEST['project'] = $_GET['project'] = $project;
			
			if (empty($_REQUEST['action']))
				$_REQUEST['action'] = $_GET['action'] = 'projects';
				
			if (!ProjectTools_Project::getCurrent())
			{
				$context['project_error'] = 'project_not_found';
				
				$project = null;
		
				return;
			}
		
			$context['possible_types'] = array();
		
			foreach (ProjectTools_Project::getCurrent()->trackers as $id => $tracker)
				$context['possible_types'][$tracker['tracker']['short']] = $id;
				
			// Developers can see all issues
			if (ProjectTools_Project::getCurrent()->isDeveloper())
				$user_info['query_see_issue_project'] = '1=1';
					
			if (count(array_intersect($user_info['groups'], ProjectTools_Project::getCurrent()->groups)) == 0 && !$user_info['is_admin'])
				$context['project_error'] = 'project_not_found';
				
			if (!empty($projects_show) && !in_array($cp->id, $projects_show))
				$context['project_error'] = 'project_not_found';
		}
	}
	
	/**
	* Checks whatever permission is allowed in current project
	*/
	public static function allowedTo($permission)
	{
		global $context, $user_info;
	   
		if (!ProjectTools_Project::getCurrent())
			trigger_error('projectAllowedTo(): Project not loaded', E_FATAL_ERROR);
		   
		return ProjectTools_Project::getCurrent()->allowedTo($permission);
	}
   
	/**
	 * Checks if permission is allowed in curernt project and shows error page if not
	 */
	public static function isAllowedTo($permission)
	{
		global $context, $txt, $user_info;
		
		if (!self::allowedTo($permission))
		{
			if ($user_info['is_guest'])
				is_not_guest($txt['cannot_project_' . $permission]);
		
			fatal_lang_error('cannot_project_' . $permission, false);
		
			// Getting this far is a really big problem, but let's try our best to prevent any cases...
			trigger_error('Hacking attempt...', E_USER_ERROR);
		}
	}

	/**
	* Generates url for project tools pages
	* @param array $params Array of GET parametrs
	* @param int $project 
	*/
	public static function get_url($params = array(), $project = null, $is_admin = false)
	{
		global $scripturl, $modSettings;
		
		$action = !$is_admin ? 'projects' : 'projectadmin';
		
		// Detect project
		if ($project === null && !empty($params))
		{
			if (isset($params['project']))
				$project = $params['project'];
			elseif (ProjectTools_Project::getCurrent())
				$project = ProjectTools_Project::getCurrent()->id;
			// Should never happen, log in case it happens
			else
			{
				log_error('Unable to detect project! Please include this in bug report: ' . print_r(debug_backtrace(), true));
				trigger_error('Unable to detect project! See error_log for details');
			}
		}
				
		// Running in "standalone" mode WITH rewrite
		if (!empty($modSettings['projectStandalone']) && $modSettings['projectStandalone'] == 2)
		{
			// Main Page? Too easy
			if (empty($params))
				return $modSettings['projectStandaloneUrl'] . '/';
				
			if (isset($params['project']))
				unset($params['project']);
			
			if (count($params) === 0)
				return $modSettings['projectStandaloneUrl'] . '/' . $project . '/';
		
			$query = '';
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($query))
					$query .= ';';
				else
					$query .= '?';
		
				if (is_int($p))
					$query .= $value;
				else
					$query .= $p . '=' . $value;
			}
		
			return $modSettings['projectStandaloneUrl'] . '/' . $project . '/' . $query;
		}
		// Running in "standalone" mode without rewrite
		elseif (!empty($modSettings['projectStandalone']))
		{
			$return = '';
			
			// Which url shall be base for this?
			$base = !empty($modSettings['projectStandaloneUrl_project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]) ? $modSettings['projectStandaloneUrl_project_' . $project] : (!empty($modSettings['projectStandaloneUrl']) ? $modSettings['projectStandaloneUrl'] : '{SCRIPTURL}');
			
			if (isset($params['project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]))
				unset($params['project']);
				
			if (count($params) === 0)
			{
				if ($base == '{SCRIPTURL}')
					return $scripturl . '?action=' . $action;
				
				return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl']));
			}
		
			if ($is_admin)
				$params['action'] = $action;
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($return))
					$return .= ';';
				else
					$return .= '?';
		
				if (is_int($p))
					$return .= $value;
				else
					$return .= $p . '=' . $value;
			}
		
			return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl'])) . $return;		
		}
		// Running in standard mode
		else
		{
			$return = '';
		
			if (empty($params) || $is_admin)
				$return .= '?action=' . $action;
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($return))
					$return .= ';';
				else
					$return .= '?';
		
				if (is_int($p))
					$return .= $value;
				else
					$return .= $p . '=' . $value;
			}
		
			return $scripturl . $return;
		}
	}
	
	/**
	*
	*/
	public static function get_admin_url($params = array(), $project = null)
	{
		return self::get_url($params, $project, true);
	}
	
	/**
	 *
	 */
	function projectTabSort($first, $second)
	{
		global $context;
		
		$orderFirst = isset($first['order']) ? $first['order'] : 1;
		$orderSecond = isset($second['order']) ? $second['order'] : 1;
		
		if ($orderFirst == $orderSecond)
			return 0;
		
		if ($orderFirst == 'first' || $orderSecond == 'last')
			return -1;
		elseif ($orderFirst == 'last' || $orderSecond == 'first')
			return 1;
		else
			return $orderFirst < $orderSecond ? -1 : 1;
	}
}

?>