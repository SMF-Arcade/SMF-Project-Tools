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
			define('PT_IN_ADMIN', true);
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
			'url' => ProjectTools::get_url(),
		);
		
		// Project was not selected
		if (ProjectTools_Project::getCurrent())
		{
			self::fix_url();
			ProjectTools_ProjectView::Main();
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
	 * Projects list
	 */
	static public function ProjectList()
	{
		global $context, $smcFunc, $scripturl, $user_info, $txt;
		
		// Canonical url for search engines
		$context['canonical_url'] = ProjectTools::get_url();
	
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
				'link' => '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
				'href' => ProjectTools::get_url(array('project' => $row['id_project'])),
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
					'link' => ProjectTools::get_url(array('project' => $row['id_project'], 'area' => 'issues', 'tracker' => $tracker['short'])),
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