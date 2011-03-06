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
	static function fix_url()
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
	static function ProjectMain()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;
		
		$subActions = array(
			'list' => array('ProjectList.php', 'ProjectList'),
		);
		
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
		
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
		call_user_func($subActions[$_REQUEST['sa']][1]);
		
		return;		
	}
	
	/**
	 * Main Project Tools functions, handles calling correct module and action
	 */
	static function Main($standalone = false)
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;
	
		loadProjectToolsPage();
	
		// Check that user can access Project Tools
		isAllowedTo('project_access');
	
		if (isset($context['project_error']))
			fatal_lang_error($context['project_error'], false);
	
		// Admin made mistake on manual edits? (for safety reasons!!)
		if (isset($context['project_error']))
			fatal_lang_error($context['project_error'], false);
	
		// Add "Projects" to Linktree
		$context['linktree'][] = array(
			'name' => $txt['linktree_projects'],
			'url' => project_get_url(),
		);
		
		// Project was not selected
		if (empty($project))
			return self::ProjectMain();
		
		self::fix_url();
		
		ProjectTools_ProjectPage();
	}
}

?>