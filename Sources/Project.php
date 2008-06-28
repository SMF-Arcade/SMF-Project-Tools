<?php
/**********************************************************************************
* Project.php                                                                       *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.1 Alpha                         *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007 by:          Niko Pahajoki (http://www.madjoki.com)              *
* Support, News, Updates at:  http://www.madjoki.com                              *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	!!!
*/

function Projects()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $project;

	isAllowedTo('project_access');

	$project = 0;

	loadProjectTools();

	$subActions = array(
		// Project
		'list' => array('ProjectList.php', 'ProjectList'),
		'viewProject' => array('ProjectView.php', 'ProjectView', true),
		'roadmap' => array('ProjectView.php', 'ProjectRoadmap', true),
		// Issues
		'issues' => array('IssueList.php', 'IssueList', true),
		'viewIssue' => array('IssueView.php', 'IssueView', true),
		'replyIssue' => array('IssueView.php', 'IssueReply', true),
		'updateIssue' => array('IssueView.php', 'IssueUpdate', true),
		'deleteIssue' => array('IssueView.php', 'IssueDelete', true),
		// Report Issue
		'reportIssue' => array('IssueReport.php', 'ReportIssue', true),
		'reportIssue2' => array('IssueReport.php', 'ReportIssue2', true),
	);

	// Linktree
	$context['linktree'][] = array(
		'name' => $txt['linktree_projects'],
		'url' => $scripturl . '?action=projects'
	);

	// Load Issue if needed
	if (!empty($_REQUEST['issue']) && !isset($_REQUEST['project']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_project
			FROM {db_prefix}issues
			WHERE id_issue = {int:issue}',
			array(
				'issue' => (int) $_REQUEST['issue']
			)
		);

		list ($_REQUEST['project']) = $smcFunc['db_fetch_row']($request);
	}

	// Load Project if needed
	if (!empty($_REQUEST['project']))
	{
		if (!($context['project'] = loadProject((int) $_REQUEST['project'])))
			fatal_lang_error('project_not_found');
		$project = $context['project']['id'];
		projectIsAllowedTo('view');

		$context['project']['long_description'] = parse_bbc($context['project']['long_description']);

		// Show everything?
		if (!projectAllowedTo('issue_view'))
			$user_info['query_see_issue'] = '((' . $user_info['query_see_version'] . ") AND i.reporter = $user_info[id])";
		else
			$user_info['query_see_issue'] = '(' . $user_info['query_see_version'] . ')';

		if (isset($_REQUEST['issue']))
		{
			if (!loadIssue((int) $_REQUEST['issue']))
				fatal_lang_error('issue_not_found');

			if (!isset($_REQUEST['sa']))
				$_REQUEST['sa'] = 'viewIssue';
		}
		else
		{
			if (!isset($_REQUEST['sa']))
				$_REQUEST['sa'] = 'viewProject';
		}
	}

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (!empty($project) && empty($subActions[$_REQUEST['sa']][2]))
		$_REQUEST['sa'] = 'viewProject';
	elseif (empty($project) && !empty($subActions[$_REQUEST['sa']][2]))
		fatal_lang_error('project_not_found');

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][3]))
		isAllowedTo($subActions[$_REQUEST['sa']][3]);

	// Template if Project selected
	if (!empty($project))
	{
		$context['project_tabs'] = array(
			'title' => $context['project']['name'],
			'text' => parse_bbc($context['project']['description']),
			'tabs' => array(
				array(
					'href' => $scripturl . '?project=' . $project,
					'title' => $txt['project'],
					'is_selected' => in_array($_REQUEST['sa'], array('viewProject')),
				),
				'issues' => array(
					'href' => $scripturl . '?project=' . $project . ';sa=issues',
					'title' => $txt['issues'],
					'is_selected' => in_array($_REQUEST['sa'], array('issues', 'viewIssue', 'reportIssue', 'reportIssue2', 'replyIssue')),
				)
			),
		);

		// Linktree
		$context['linktree'][] = array(
			'name' => $context['project']['name'],
			'url' => $scripturl . '?project=' . $project
		);

		if ($context['project_tabs']['tabs']['issues']['is_selected'])
			$context['linktree'][] = array(
				'name' => $txt['linktree_issues'],
				'url' => $scripturl . '?project=' . $project . ';sa=issues',
			);

		$context['template_layers'][] = 'project_view';
		loadTemplate('ProjectView');
	}

	require_once($sourcedir . '/' . $subActions[$_REQUEST['sa']][0]);
	$subActions[$_REQUEST['sa']][1]();
}

function loadProjectTools($mode = '')
{
	global $context, $smcFunc, $modSettings, $sourcedir, $scripturl, $user_info, $txt, $project_version, $settings;

	if (!empty($project_version))
		return;

	require_once($sourcedir . '/Subs-Project.php');
	require_once($sourcedir . '/Subs-Issue.php');

	// Which version this is?
	$project_version = '0.1 Alpha';

	$context['project_tools'] = array();
	$context['issue_tracker'] = array();

	$context['issues_per_page'] = !empty($modSettings['issuesPerPage']) ? $modSettings['issuesPerPage'] : 25;

	if (empty($mode))
	{
		// Administrators can see all projects.
		if ($user_info['is_admin'])
		{
			$see_project = '1 = 1';
			$see_version = '1 = 1';
		}
		// Registered user.... just the groups in $user_info['groups'].
		else
		{
			// Load my groups
			$request = $smcFunc['db_query']('', '
				SELECT id_group, id_project, access_level
				FROM {db_prefix}project_groups
				WHERE
					(FIND_IN_SET(' . implode(', member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', member_groups))',
				array(
				)
			);

			$projectGroups = array();
			$context['project_levels'] = array();

			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if (empty($context['project_levels'][$row['id_project']]))
					$context['project_levels'][$row['id_project']] = $row['access_level'];
				else
					$context['project_levels'][$row['id_project']] = max($row['access_level'], $context['project_levels'][$row['id_project']]);

				$projectGroups[] = $row['id_group'];
			}
			$smcFunc['db_free_result']($request);

			$see_project = '(FIND_IN_SET(' . implode(', p.project_groups) OR FIND_IN_SET(', $projectGroups) . ', p.project_groups))';
			$see_version = '(ISNULL(ver.project_groups) OR (FIND_IN_SET(' . implode(', ver.project_groups) OR FIND_IN_SET(', $projectGroups) . ', ver.project_groups)))';
		}

		$user_info['query_see_project'] = $see_project;
		$user_info['query_see_version'] = $see_version;

		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/jquery.js"></script>
		<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
			var $j = jQuery.noConflict();
		// ]]></script>';
	}
	elseif ($mode == 'admin')
	{
		$user_info['query_see_project'] = '1 = 1';
		$user_info['query_see_version'] = '1 = 1';
	}

	loadLanguage($mode != 'admin' ? 'Project' : 'Project+ProjectAdmin');
	loadIssueTypes();
	loadTemplate('Project', array('forum', 'project'));

	$context['template_layers'][] = 'project';
}

?>