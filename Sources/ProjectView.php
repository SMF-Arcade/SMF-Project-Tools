<?php
/**********************************************************************************
* ProjectView.php                                                                 *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.3                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2009 by:     Niko Pahajoki (http://www.madjoki.com)              *
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
	global $context, $modSettings, $smcFunc, $sourcedir, $user_info, $txt, $project;

	$context['can_subscribe'] = !$user_info['is_guest'];
	$context['can_report_issues'] = projectAllowedTo('issue_report');

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

	$issues_num = 5;

	$issue_list = array(
		'recent_issues' => array(
			'title' => 'recent_issues',
			'href' => project_get_url(array('project' => $project, 'sa' => 'issues')),
			'order' => 'i.updated DESC',
			'where' => '1 = 1',
			'show' => projectAllowedTo('issue_view'),
		),
		'my_reports' => array(
			'title' => 'reported_by_me',
			'href' => project_get_url(array('project' => $project, 'sa' => 'issues', 'reporter' => $user_info['id'])),
			'order' => 'i.updated DESC',
			'where' => 'i.id_reporter = {int:current_member}',
			'show' => projectAllowedTo('issue_report'),
		),
		'assigned' => array(
			'title' => 'assigned_to_me',
			'href' => project_get_url(array('project' => $project, 'sa' => 'issues', 'assignee' => $user_info['id'])),
			'order' => 'i.updated DESC',
			'where' => 'i.id_assigned = {int:current_member} AND NOT (i.status IN ({array_int:closed_status}))',
			'show' => projectAllowedTo('issue_resolve'),
		),
		'new_issues' => array(
			'title' => 'new_issues',
			'href' => project_get_url(array('project' => $project, 'sa' => 'issues')),
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
				'issues' => getIssueList(0, $issues_num, $issuel['order'], $issuel['where']),
			);
	}

	loadTimeline($project);

	// Template
	$context['sub_template'] = 'project_view';
	$context['page_title'] = sprintf($txt['project_title'], $context['project']['name']);
}

function ProjectSubscribe()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $project, $issue;

	checkSession('get');

	if ($user_info['is_guest'])
		fatal_lang_error('cannot_project_subscribe');

	if (!empty($issue))
		return ProjectSubscribeIssue();

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

	redirectexit(project_get_url(array('project' => $project)));
}

function ProjectSubscribeIssue()
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

	redirectexit(project_get_url(array('issue' => $issue . '.0')));
}

?>