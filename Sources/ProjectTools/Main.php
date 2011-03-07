<?php
/**
 * Main handler for Project Tools
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_Main
{
	/**
	 *
	 */
	static public $version = '0.6';
	
	/**
	 *
	 */
	static protected $areas;
	
	/**
	 *
	 */
	static protected $current_area;
	
	/**
	 *
	 */
	static protected function fix_url()
	{
		global $issue;
		
		// Array for fixing old < 0.5 urls
		$saToArea = array(
			'main' => 'main',
			'subscribe' => array('main', 'subscribe'),
			// issues
			'issues' => 'issues',
			'viewIssue' => array('issues', 'view'),
			'tags' => array('issues', 'tags'),
			'update' => array('issues', 'update'),
			'upload' => array('issues', 'upload'),
			'move' => array('issues', 'move'),
			'reply' => array('issues', 'reply'),
			'reply2' => array('issues', 'reply2'),
			'edit' => array('issues', 'edit'),
			'edit2' => array('issues', 'edit2'),
			'removeComment' => array('issues', 'removeComment'),
			'reportIssue' => array('issues', 'report'),
			'reportIssue2' => array('issues', 'report2'),
		);
		
		if (empty($_REQUEST['area']) && !empty($_REQUEST['sa']) && isset($saToArea[$_REQUEST['sa']]))
		{
			if (is_array($saToArea[$_REQUEST['sa']]))
				list ($_REQUEST['area'], $_REQUEST['sa']) = $saToArea[$_REQUEST['sa']];
			else
			{
				$_REQUEST['area'] = $saToArea[$_REQUEST['sa']];
				unset($_REQUEST['sa']);
			}
		}
		
		if ((!isset($_REQUEST['area']) || !isset($_REQUEST['sa'])) && !empty($issue))
		{
			$_REQUEST['area'] = 'issues';
			
			if (!isset($_REQUEST['sa']))
				$_REQUEST['sa'] = 'view';
		}
	}
	
	/**
	 *
	 */
	static private function CreateAreas($areas)
	{
		foreach ($areas as $id => $area)
		{
			if (!empty($area['project_permission']) && !ProjectTools_Project::getCurrent()->allowedTo($area['project_permission']))
				continue;
			elseif (!empty($area['permission']) && !allowedTo($area['permission']))
				continue;
		
			self::$areas[$id] = $area;
		}
	}
	
	/**
	 * Handles loading all required data of project tools
	 */
	static public function load()
	{
		global $context, $smcFunc, $modSettings, $user_info, $txt, $settings, $projects_show;
		global $project, $issue, $sourcedir;
	
		//
		require_once($sourcedir . '/Subs-Project.php');
		require_once($sourcedir . '/Subs-Issue.php');
		
		// Issue Regex
		if (empty($modSettings['issueRegex']))
			$modSettings['issueRegex'] = array('[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+', '(\d+)');
		else
			$modSettings['issueRegex'] = explode("\n", $modSettings['issueRegex'], 2);
			
		$modSettings['projectExtensions'] = !empty($modSettings['projectExtensions']) ? explode(',', $modSettings['projectExtensions']) : array('admin', 'issues', 'roadmap');
	
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
		global $user_info;
		
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
	 * Loads data for spefific page
	 */
	static public function loadPage($mode = '')
	{
		global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $settings;
	
		// In SMF (SSI, etc)
		if ($mode == 'smf')
		{
			loadTemplate(false, array('project'));
		}
		// Profile
		elseif ($mode == 'profile')
		{
			loadTemplate('ProjectProfile', array('project'));
	
			$context['html_headers'] .= '
			<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
		}
		elseif ($mode == 'admin')
		{
			require_once($sourcedir . '/Subs-ProjectAdmin.php');
	
			$user_info['query_see_project'] = '1 = 1';
			$user_info['query_see_version'] = '1 = 1';
	
			loadLanguage('ProjectAdmin');
			loadTemplate('ProjectAdmin',  array('project'));
	
			if (!isset($_REQUEST['xml']))
				$context['template_layers'][] = 'project_admin';
		}
	}
	
	/**
	 * Main Project Tools functions, handles calling correct module and action
	 */
	static public function Main($standalone = false)
	{
		global $context, $smcFunc, $user_info, $txt;
	
		// Check that user can access Project Tools
		isAllowedTo('project_access');

		// Admin made mistake on manual edits? (for safety reasons!!)
		if (isset($context['project_error']))
			fatal_lang_error($context['project_error'], false);
	
		// Add "Projects" to Linktree
		$context['linktree'][] = array(
			'name' => $txt['linktree_projects'],
			'url' => project_get_url(),
		);
		
		// Project was not selected
		if (ProjectTools_Project::getCurrent())
		{		
			self::fix_url();
			self::ProjectMain();
			return;
		}
		
		$subActions = array(
			'list' => array('ProjectList'),
		);
		
		ProjectTools_Extensions::runHooks('add_subActions', array(&$subActions));
		
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
		
		call_user_func(array(get_class(), $subActions[$_REQUEST['sa']][0]));
		
		return;	
	}
	
	/**
	 *
	 */
	static public function ProjectMain()
	{
		global $context, $txt, $settings;
			
		$context['active_project_modules'] = array();
			
		// Load Modules
		foreach (ProjectTools_Project::getCurrent()->modules as $id)
		{
			$module = ProjectTools_Extensions::getModule($id);
			
			if ($module)
				$context['active_project_modules'][$id] = new $module['class_name'](ProjectTools_Project::getCurrent());
		}
		
		//
		$project_areas = array();
		
		//
		foreach ($context['active_project_modules'] as $id => $module)
		{
			$area = $module->RegisterArea();
			$area['module'] = $module;
			$project_areas[$area['id']] = $area;
		}
		
		self::CreateAreas($project_areas);
		unset($project_areas);
		
		// Tabs
		$context['project_tabs'] = array(
			'title' => ProjectTools_Project::getCurrent()->name,
			'description' => ProjectTools_Project::getCurrent()->description,
			'tabs' => array(),
		);
		
		//
		if (empty($_REQUEST['area']) || !isset(self::$areas[$_REQUEST['area']]))
			$_REQUEST['area'] = 'main';
			
		if (empty($_REQUEST['sa']))
			$_REQUEST['sa'] = 'main';
			
		self::$current_area = &self::$areas[$_REQUEST['area']];
		
		// Create Tabs
		foreach (self::$areas as $id => &$area)
		{
			$area['href'] = $area['id'] !== 'main' ? project_get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => $id))
				: project_get_url(array('project' => ProjectTools_Project::getCurrent()->id));
			
			$context['project_tabs']['tabs'][$id] = array(
				'title' => $area['title'],
				'href' => $area['href'],
				'is_selected' => $area === self::$current_area,
				'hide_linktree' => !empty($area['hide_linktree']),
				'order' => $area['order'],
			);
		}
	
		// Sort tabs to correct order
		uksort($context['project_tabs']['tabs'], 'projectTabSort');
			
		// Linktree
		$context['linktree'][] = array(
			'name' => strip_tags(ProjectTools_Project::getCurrent()->name),
			'url' => project_get_url(array('project' => ProjectTools_Project::getCurrent()->id)),
		);
		
		// Add area to linktree
		if (empty(self::$current_area['hide_linktree']))
			$context['linktree'][] = array(
				'name' => self::$current_area['title'],
				'url' => self::$current_area['href'],
			);
		
		//
		if (isset(self::$current_area['module']->subTabs[$_REQUEST['sa']]))
		{
			self::$current_area['module']->subTabs[$_REQUEST['sa']]['is_selected'] = true;
			
			if (empty(self::$current_area['module']->subTabs[$_REQUEST['sa']]['hide_linktree']))
				$context['linktree'][] = array(
					'name' => self::$current_area['module']->subTabs[$_REQUEST['sa']]['title'],
					'url' => self::$current_area['module']->subTabs[$_REQUEST['sa']]['href'],
				);
			
			$context['project_sub_tabs'] = self::$current_area['module']->subTabs;
		}
		
		// Template
		loadTemplate('Project', array('project'));
		
		if (!isset($_REQUEST['xml']))
		{
			$context['template_layers'][] = 'project';
			
			$context['html_headers'] .= '
			<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
		}
		
		call_user_func(array(self::$current_area['module'], self::$current_area['callback']), array($_REQUEST['sa']));
	}
	
	/**
	 * Projects list
	 */
	static public function ProjectList()
	{
		global $context, $smcFunc, $scripturl, $user_info, $txt;
		
		// Canonical url for search engines
		$context['canonical_url'] = project_get_url();
	
		$request = $smcFunc['db_query']('', '
			SELECT
				p.id_project, p.name, p.description, p.trackers, p.' . implode(', p.', $context['tracker_columns']) . ', p.id_event_mod,
				mem.id_member, mem.real_name,
				' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
			FROM {db_prefix}projects AS p' . ($user_info['is_guest'] ? '' : '
				LEFT JOIN {db_prefix}log_projects AS log ON (log.id_member = {int:current_member}
					AND log.id_project = p.id_project)
				LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
				LEFT JOIN {db_prefix}project_developer AS pdev ON (pdev.id_project = p.id_project)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = pdev.id_member)
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_project}
			ORDER BY p.name',
			array(
				'current_member' => $user_info['id'],
			)
		);
	
		$context['projects'] = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (isset($context['projects'][$row['id_project']]))
			{
				if (empty($row['id_member']))
					continue;
	
				$context['projects'][$row['id_project']]['developers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';
	
				continue;
			}
	
			$context['projects'][$row['id_project']] = array(
				'id' => $row['id_project'],
				'link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
				'href' => project_get_url(array('project' => $row['id_project'])),
				'name' => $row['name'],
				'description' => $row['description'],
				'new' => $row['new_from'] <= $row['id_event_mod'] && !$user_info['is_guest'],
				'trackers' => array(),
				'developers' => array(),
			);
	
			if (!empty($row['id_member']))
				$context['projects'][$row['id_project']]['developers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';
	
			$trackers = explode(',', $row['trackers']);
	
			foreach ($trackers as $id)
			{
				$tracker = &$context['issue_trackers'][$id];
				$context['projects'][$row['id_project']]['trackers'][$id] = array(
					'tracker' => &$context['issue_trackers'][$id],
					'open' => $row['open_' . $tracker['short']],
					'closed' => $row['closed_' . $tracker['short']],
					'total' => $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']],
					'progress' => round(($row['closed_' . $tracker['short']] / max(1, $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']])) * 100, 2),
					'link' => project_get_url(array('project' => $row['id_project'], 'area' => 'issues', 'tracker' => $tracker['short'])),
				);
				unset($tracker);
			}
		}
		$smcFunc['db_free_result']($request);
	
		loadTimeline();
	
		// Template
		loadTemplate('ProjectList');
		$context['sub_template'] = 'project_list';
		$context['page_title'] = sprintf($txt['project_list_title'], $context['forum_name']);
	}
}

?>