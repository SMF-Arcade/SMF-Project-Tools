<?php
/**********************************************************************************
* ProjectView.php                                                                 *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.1                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2008 by:     Niko Pahajoki (http://www.madjoki.com)              *
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

function ProjectView()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $project;

	if (!$user_info['is_guest'])
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
			array(
				'id_project' => 'int',
				'id_member' => 'int',
				'id_comment' => 'int',
			),
			array(
				$project,
				$user_info['id'],
				$context['project']['comment_mod']
			),
			array('id_project', 'id_member')
		);
	}

	$issues_num = 5;

	$issue_list = array(
		'recent_issues' => array(
			'title' => 'recent_issues',
			'href' => $scripturl . '?project=' . $project . ';sa=issues',
			'order' => 'i.updated DESC',
			'where' => '1 = 1',
			'show' => projectAllowedTo('issue_view'),
		),
		'my_reports' => array(
			'title' => 'reported_by_me',
			'href' => $scripturl . '?project=' . $project . ';sa=issues;reporter=' . $user_info['id'],
			'order' => 'i.updated DESC',
			'where' => 'i.id_reporter = {int:member}',
			'show' => projectAllowedTo('issue_report'),
		),
		'assigned' => array(
			'title' => 'assigned_to_me',
			'href' => $scripturl . '?project=' . $project . ';sa=issues;assignee=' . $user_info['id'],
			'order' => 'i.updated DESC',
			'where' => 'i.id_assigned = {int:member} AND NOT (i.status IN ({array_int:closed_status}))',
			'show' => projectAllowedTo('issue_resolve'),
		),
		'new_issues' => array(
			'title' => 'new_issues',
			'href' => $scripturl . '?project=' . $project . ';sa=issues',
			'order' => 'i.created DESC',
			'where' => 'i.status = 1',
			'show' => projectAllowedTo('issue_view'),
		),
	);

	$context['issue_list'] = array();

	foreach ($issue_list as $issuel)
	{
		if ($issuel['show'])
			$context['issue_list'][] = array(
				'title' => $txt[$issuel['title']],
				'href' => $issuel['href'],
				'issues' => getIssueList($issues_num, $issuel['order'], $issuel['where']),
			);
	}

	loadTimeline($project);

	// Template
	$context['sub_template'] = 'project_view';
	$context['page_title'] = sprintf($txt['project_title'], $context['project']['name']);
}

?>