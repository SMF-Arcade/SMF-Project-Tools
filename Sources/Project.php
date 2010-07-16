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
 * Main Project Tools functions, handles calling correct module and action
 */
function Projects($standalone = false)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;

	// Check that user can access Project Tools
	isAllowedTo('project_access');
	
	loadProjectToolsPage();

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
	{
		$subActions = array(
			'list' => array('ProjectList.php', 'ProjectList'),
		);
		
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';
		
		require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
		call_user_func($subActions[$_REQUEST['sa']][1]);
		
		return;
	}
	
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
	
	// Areas are sets of subactions (registered by modules)
	$subAreas = array();
	
	// Tabs
	$context['project_tabs'] = array(
		'title' => $context['project']['name'],
		'description' => $context['project']['description'],
		'tabs' => array(
			'main' => array(
				'href' => project_get_url(array('project' => $project)),
				'title' => $txt['project'],
				'is_selected' => false,
				'hide_linktree' => true,
				'order' => 'first',
			),
		),
	);

	// Let Modules register subAreas
	if (!empty($context['active_project_modules']))
	{
		foreach ($context['active_project_modules'] as $id => $module)
		{
			if (method_exists($module, 'RegisterProjectArea'))
			{
				$area = $module->RegisterProjectArea();
				
				$subAreas[$area['area']] = array(
					'area' => $area['area'],
					'module' => $id,
					'tab' => !empty($area['tab']) ? $area['tab'] : $area['area'],
				);
			}
			if (method_exists($module, 'RegisterProjectTabs'))
				$module->RegisterProjectTabs($context['project_tabs']['tabs']);
		}
	}
	
	// Remove tabs which user has no permission to see 
	foreach ($context['project_tabs']['tabs'] as $id => $tab)
	{
		if (!empty($tab['permission']) && !allowedTo($tab['permission']))
			unset($context['project_tabs']['tabs'][$id]);
		elseif (!empty($tab['project_permission']) && !projectAllowedTo($tab['project_permission']))
			unset($context['project_tabs']['tabs'][$id]);
	}

	// Sort tabs to correct order
	uksort($context['project_tabs']['tabs'], 'projectTabSort');

	if (empty($_REQUEST['area']) || !isset($subAreas[$_REQUEST['area']]))
		$_REQUEST['area'] = 'main';
		
	if (empty($_REQUEST['sa']))
		$_REQUEST['sa'] = 'main';
		
	$current_area = &$subAreas[$_REQUEST['area']];
	$context['current_project_module'] = &$context['active_project_modules'][$current_area['module']];
	
	if (isset($context['project_tabs']['tabs'][$current_area['tab']]))
		$context['project_tabs']['tabs'][$current_area['tab']]['is_selected'] = true;
	else
		$context['project_tabs']['tabs']['main']['is_selected'] = true;
		
	// Can access this area?
	if (isset($current_area['permission']))
		isAllowedTo($current_area['permission']);
	if (isset($current_area['project_permission']))
		projectIsAllowedTo($current_area['project_permission']);
		
	// Call Initialize View function
	if (isset($context['current_project_module']) && method_exists($context['current_project_module'], 'beforeSubaction'))
		$context['current_project_module']->beforeSubaction($_REQUEST['sa']);
		
	// Linktree
	$context['linktree'][] = array(
		'name' => strip_tags($context['project']['name']),
		'url' => project_get_url(array('project' => $project)),
	);
	
	if (empty($context['project_tabs']['tabs'][$current_area['tab']]['hide_linktree']))
		$context['linktree'][] = array(
			'name' => $context['project_tabs']['tabs'][$current_area['tab']]['title'],
			'url' => $context['project_tabs']['tabs'][$current_area['tab']]['href'],
		);
		
	if (isset($context['current_project_module']->subTabs[$_REQUEST['sa']]))
	{
		$context['current_project_module']->subTabs[$_REQUEST['sa']]['is_selected'] = true;
		
		if (empty($context['current_project_module']->subTabs[$_REQUEST['sa']]['hide_linktree']))
			$context['linktree'][] = array(
				'name' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['title'],
				'url' => $context['current_project_module']->subTabs[$_REQUEST['sa']]['href'],
			);
		
		$context['project_sub_tabs'] = $context['current_project_module']->subTabs;
	}
	
	$context['current_project_module']->main($_REQUEST['sa']);
}

?>