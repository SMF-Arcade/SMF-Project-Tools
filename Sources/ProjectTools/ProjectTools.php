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
		global $sourcedir, $project;
	
		//
		require_once($sourcedir . '/Subs-Project.php');
		require_once($sourcedir . '/Subs-Issue.php');
		
		// Issue Regex
		if (empty($modSettings['issueRegex']))
			$modSettings['issueRegex'] = array('[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+', '(\d+)');
		else
			$modSettings['issueRegex'] = explode("\n", $modSettings['issueRegex'], 2);
			
		$modSettings['projectExtensions'] = !empty($modSettings['projectExtensions']) ? explode(',', $modSettings['projectExtensions']) : array('Frontpage', 'IssueTracker', 'Roadmap');
		
		self::setupQueries();
		
		// 
		if (isset($force_project))
			$project = $force_project;
		// Project as parameter?
		elseif (!empty($_REQUEST['project']))
			$project = (int) $_REQUEST['project'];
		
		// Load extensions
		foreach ($modSettings['projectExtensions'] as $extension)
			ProjectTools_Extensions::loadExtension($extension);
		
		ProjectTools_Extensions::runHooks('load', array());

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
			$see_project = '1 = 1';
		else
			$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';
		
		// See project
		$user_info['query_see_project'] = $see_project;
		
		if (isset($projects_show) && (empty($projects_show) || !is_array($projects_show)))
			$user_info['query_see_project'] = '0=1';
		elseif (isset($projects_show))
			$user_info['query_see_project'] = '(p.id_project IN(' . implode(',', $projects_show) . ') AND ' . $see_project . ')';
	}
	
	/**
	 *
	 */
	static protected function loadProject()
	{
		global $project, $context, $user_info, $smcFunc;
		
		if (!empty($project) && (empty($_REQUEST['action']) || $_REQUEST['action'] == 'projects' || $_REQUEST['action'] == 'projectadmin'))
		{
			if (empty($_REQUEST['action']))
				$_GET['action'] = $_REQUEST['action'] = 'projects';
			
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
					
			if (ProjectTools_Project::getCurrent()->canAccess())
				$context['project_error'] = 'project_not_found';
				
			if (!empty($projects_show) && !in_array($cp->id, $projects_show))
				$context['project_error'] = 'project_not_found';
		}
		
		//
		define('IN_PRJOECT_TOOLS', isset($_REQUEST['action']) && ($_REQUEST['action'] == 'projects' || $_REQUEST['action'] == 'projectadmin'));
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