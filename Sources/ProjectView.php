<?php
/**********************************************************************************
* ProjectView.php                                                                 *
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
				$context['project']['id'],
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
			'order' => 'i.updated DESC',
			'where' => '1 = 1',
			'show' => projectAllowedTo('issue_view'),
		),
		'my_reports' => array(
			'title' => 'reported_by_me',
			'order' => 'i.updated DESC',
			'where' => 'i.id_reporter = {int:member}',
			'show' => projectAllowedTo('issue_report'),
		),
		'assigned' => array(
			'title' => 'assigned_to_me',
			'order' => 'i.updated DESC',
			'where' => 'i.id_assigned = {int:member} AND NOT (i.status IN ({array_int:closed_status}))',
			'show' => projectAllowedTo('issue_resolve'),
		),
		'new_issues' => array(
			'title' => 'new_issues',
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
				'issues' => getIssueList($issues_num, $issuel['order'], $issuel['where']),
			);
	}

	loadTimeline($project);

	// Template
	$context['sub_template'] = 'project_view';
	$context['page_title'] = sprintf($txt['project_title'], $context['project']['name']);
}

function ProjectRoadmap()
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$parents = array();
	$ids = array();
	$context['roadmap'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT
			ver.id_version, ver.id_parent, ver.version_name, ver.status,
			ver.description, ver.release_date
		FROM {db_prefix}project_versions AS ver
		WHERE {query_see_version}
			AND id_project = {int:project}
		ORDER BY id_parent',
		array(
			'project' => $project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$ids[] = $row['id_version'];

		if (!empty($row['id_parent']))
		{
			$parents[$row['id_version']] = $row['id_parent'];

			$context['roadmap'][$row['id_parent']]['versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'description' => parse_bbc($row['description']),
				'issues' => array(
					'open' => 0,
					'closed' => 0,
				),
			);
		}
		else
		{
			$context['roadmap'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'description' => parse_bbc($row['description']),
				'versions' => array(),
				'issues' => array(
					'open' => 0,
					'closed' => 0,
				),
			);
		}
	}
	$smcFunc['db_free_result']($request);

	if (!empty($ids))
	{
		// Load issue counts
		$request = $smcFunc['db_query']('', '
			SELECT id_version, id_version_fixed, status, COUNT(*) AS num
			FROM {db_prefix}issues AS ver
			WHERE (id_version IN({array_int:versions}) OR id_version_fixed IN({array_int:versions}))
			GROUP BY id_version, id_version_fixed, status',
			array(
				'project' => $project,
				'versions' => $ids,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$row['id_version_real'] = $row['id_version'];

			if (!empty($row['id_version_fixed']))
				$row['id_version'] = $row['id_version_fixed'];

			$open = !in_array($row['status'], $context['closed_status']);

			if (!isset($parents[$row['id_version']]))
			{
				if ($open)
					$context['roadmap'][$row['id_version']]['issues']['open'] += $row['num'];
				else
					$context['roadmap'][$row['id_version']]['issues']['closed'] += $row['num'];
			}
			else
			{
				if ($open)
					$context['roadmap'][$parents[$row['id_version']]]['versions'][$row['id_version']]['issues']['open'] += $row['num'];
				else
					$context['roadmap'][$parents[$row['id_version']]]['versions'][$row['id_version']]['issues']['closed'] += $row['num'];
			}
		}
		$smcFunc['db_free_result']($request);

		foreach ($context['roadmap'] as $id => $d)
		{
			$d['issues']['total'] = $d['issues']['open'] + $d['issues']['closed'];

			if ($d['issues']['total'] > 0)
				$d['progress'] = round($d['issues']['closed'] / $d['issues']['total'] * 100, 2);
			else
				$d['progress'] = 0;

			foreach ($d['versions'] as $idx => $dx)
			{
				$dx['issues']['total'] = $dx['issues']['open'] + $dx['issues']['closed'];

				if ($dx['issues']['total'] > 0)
					$dx['progress'] = round($dx['issues']['closed'] / $dx['issues']['total'] * 100, 2);
				else
					$dx['progress'] = 0;

				// Back to array
				$d['versions'][$idx] = $dx;
			}

			// Back to array
			$context['roadmap'][$id] = $d;
		}
	}

	// Template
	$context['sub_template'] = 'project_roadmap';
}

function getIssueList($num_issues, $order = 'i.updated DESC', $where = '1 = 1')
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.issue_type, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_comment_mod, i.replies,
			i.id_reporter, IFNULL(mr.real_name, {string:empty}) AS reporter,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_version, IFNULL(ver.version_name, {string:empty}) AS version_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
			' . ($user_info['is_guest'] ? '0 AS new_from' : '(IFNULL(log.id_comment, -1) + 1) AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:member} AND log.id_issue = i.id_issue)') . '
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE {query_see_issue_project}
			AND ('. $where . ')
			AND i.id_project = {int:project}
		ORDER BY ' . $order . '
		LIMIT {int:start}, {int:num_issues}',
		array(
			'project' => $project,
			'empty' => '',
			'start' => 0,
			'num_issues' => $num_issues,
			'member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
		)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . $scripturl . '?issue=' . $row['id_issue'] . '.0">' . $row['subject'] . '</a>',
			'href' => $scripturl . '?issue=' . $row['id_issue'] . '.0',
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . $scripturl . '?project=' . $row['id_project'] . ';sa=issues;category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>' : '',
			),
			'version' => array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => !empty($row['version_name']) ? '<a href="' . $scripturl . '?project=' . $row['id_project'] . ';sa=issues;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>' : ''
			),
			'type' => $row['issue_type'],
			'updated' => timeformat($row['updated']),
			'created' => timeformat($row['created']),
			'status' => &$context['issue']['status'][$row['status']],
			'reporter' => array(
				'id' => $row['id_reporter'],
				'name' => empty($row['reporter']) ? $txt['issue_guest'] : $row['reporter'],
				'link' => empty($row['reporter']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter'] . '</a>',
			),
			'updater' => array(
				'id' => $row['id_updater'],
				'name' => empty($row['updater']) ? $txt['issue_guest'] : $row['updater'],
				'link' => empty($row['updater']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_updater'] . '">' . $row['updater'] . '</a>',
			),
			'replies' => comma_format($row['replies']),
			'priority' => $row['priority'],
			'new' => $row['new_from'] <= $row['id_comment_mod'],
			'new_href' => $scripturl . '?issue=' . $row['id_issue'] . '.com' . $row['new_from'] . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

?>