<?php
/**
 * Admin pages for Projects 
 *
 * @package ProjectTools
 * @subpackage Frontpage
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * 
 */
class ProjectTools_Frontpage_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	public function Main()
	{
		$subActions = array(
			'main' => array($this, 'Frontpage'),
			'subscribe' => array($this, 'Subscribe'),
			'markasread' => array($this, 'MarkRead'),
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		call_user_func($subActions[$_REQUEST['sa']]);
	}
	
	/**
	 *
	 */
	public function RegisterArea()
	{
		global $txt;
		
		return array(
			'id' => 'main',
			'title' => $txt['project'],
			'callback' => 'Main',
			'hide_linktree' => true,
			'order' => 'first',
		);
	}
	
	/**
	 * Main project page
	 */
	public function Frontpage()
	{
		global $context, $modSettings, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		// Canonical url for search engines
		$context['canonical_url'] = ProjectTools::get_url(array('project' => $project));
		
		$context['can_subscribe'] = !$user_info['is_guest'];
		$context['can_report_issues'] = ProjectTools::allowedTo('issue_report');
	
		if (!$user_info['is_guest'] && !empty($modSettings['project_maxEventID']))
		{
			// We can't know they read it if we allow prefetches.
			if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
			{
				ob_end_clean();
				header('HTTP/1.1 403 Prefetch Forbidden');
				die;
			}
	
			$smcFunc['db_insert']('replace',
				'{db_prefix}log_projects',
				array('id_project' => 'int', 'id_member' => 'int', 'id_event' => 'int',),
				array($project, $user_info['id'], $modSettings['project_maxEventID'],),
				array('id_project', 'id_member')
			);
	
			$request = $smcFunc['db_query']('', '
				SELECT sent
				FROM {db_prefix}log_notify_projects
				WHERE id_project = {int:project}
					AND id_member = {int:current_member}
				LIMIT 1',
				array(
					'project' => $project,
					'current_member' => $user_info['id'],
				)
			);
			$context['is_subscribed'] = $smcFunc['db_num_rows']($request) != 0;
			
			// If user is subscribed reset sent status
			if ($context['is_subscribed'])
			{
				list ($sent) = $smcFunc['db_fetch_row']($request);
				if (!empty($sent))
				{
					$smcFunc['db_query']('', '
						UPDATE {db_prefix}log_notify_projects
						SET sent = {int:is_sent}
						WHERE id_project = {int:project}
							AND id_member = {int:current_member}',
						array(
							'project' => $project,
							'current_member' => $user_info['id'],
							'is_sent' => 0,
						)
					);
				}
			}
			$smcFunc['db_free_result']($request);
		}
		
		$frontpage_blocks = array();
		
		// Let Modules register Frontpage blocks
		if (!empty($context['active_project_modules']))
		{
			foreach ($context['active_project_modules'] as $module)
				if (method_exists($module, 'RegisterProjectFrontpageBlocks'))
					$module->RegisterProjectFrontpageBlocks($frontpage_blocks);
		}
		
		$context['project_blocks'] = array();
		
		// Load Frontpage Blocks
		if (!empty($frontpage_blocks))
		{
			foreach ($frontpage_blocks as $id => $block)
			{
				if (empty($block['show']))
					continue;
				
				$context['project_blocks'][$id] = array(
					'title' => is_array($block['title']) ? vsprintf($txt[$block['title'][0]], $block['title'][1]) : $txt[$block['title']],
					'href' => isset($block['href']) ? $block['href'] : '',
					'template' => isset($block['template']) ? $block['template'] : '',
					'data' => call_user_func_array($block['data_function'], $block['data_parameters']),
				);
			}
		}
	
		loadTimeline($project);
	
		// Template
		loadTemplate('ProjectView');
		$context['sub_template'] = 'project_view';
		$context['page_title'] = sprintf($txt['project_title'], ProjectTools_Project::getCurrent()->name);
	}
	
	/**
	 * Subscribe to project
	 */
	public function Subscribe()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;
	
		checkSession('get');
	
		if ($user_info['is_guest'])
			fatal_lang_error('cannot_project_subscribe');
	
		if (!empty($issue))
			return self::SubscribeIssue();
	
		$request = $smcFunc['db_query']('', '
			SELECT id_project
			FROM {db_prefix}log_notify_projects
			WHERE id_project = {int:project}
				AND id_member = {int:current_member}',
			array(
				'project' => $project,
				'current_member' => $user_info['id'],
			)
		);
	
		$row = $smcFunc['db_fetch_assoc']($request);
	
		if (!$row)
			$smcFunc['db_insert']('',
				'{db_prefix}log_notify_projects',
				array(
					'id_project' => 'int',
					'id_issue' => 'int',
					'id_member' => 'int',
					'sent' => 'int',
				),
				array(
					$project,
					0,
					$user_info['id'],
					0,
				),
				array('id_project', 'id_issue', 'id_member')
			);
		else
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}log_notify_projects
				WHERE id_project = {int:project}
					AND id_member = {int:current_member}',
				array(
					'project' => $project,
					'current_member' => $user_info['id'],
				)
			);
	
		$smcFunc['db_free_result']($request);
	
		redirectexit(ProjectTools::get_url(array('project' => $project)));
	}
	
	/**
	 * Subscribe to issue
	 *
	 * @todo Move to IssueTracker modules
	 */
	public function SubscribeIssue()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;
	
		$request = $smcFunc['db_query']('', '
			SELECT id_project
			FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
	
		$row = $smcFunc['db_fetch_assoc']($request);
	
		if (!$row)
			$smcFunc['db_insert']('',
				'{db_prefix}log_notify_projects',
				array(
					'id_project' => 'int',
					'id_issue' => 'int',
					'id_member' => 'int',
					'sent' => 'int',
				),
				array(
					0,
					$issue,
					$user_info['id'],
					0,
				),
				array('id_project', 'id_issue', 'id_member')
			);
		else
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}log_notify_projects
				WHERE id_issue = {int:issue}
					AND id_member = {int:current_member}',
				array(
					'issue' => $issue,
					'current_member' => $user_info['id'],
				)
			);
	
		$smcFunc['db_free_result']($request);
	
		redirectexit(ProjectTools::get_url(array('issue' => $issue . '.0')));
	}
	
	/**
	 *
	 */
	public function MarkRead()
	{
		global $project;
		
		markProjectsRead($project, isset($_REQUEST['unread']));
		
		redirectexit(ProjectTools::get_url(array('project' => $project)));
	}
}

?>