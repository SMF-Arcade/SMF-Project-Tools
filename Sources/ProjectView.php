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
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $project;

	if (empty($context['project']))
		fatal_lang_error('project_not_found');

	$context['project']['long_description'] = parse_bbc($context['project']['long_description']);

	$issues_num = 5;

	$issue_list = array(
		'recent_issues' => array(
			'title' => 'recent_issues',
			'order' => 'i.updated DESC',
			'where' => '1 = 1',
			'show' => true,
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
			'where' => 'i.id_assigned = {int:member}',
			'show' => $context['project']['is_developer'],
		),
		'new_issues' => array(
			'title' => 'new_issues',
			'order' => 'i.created DESC',
			'where' => 'i.status = 1',
			'show' => $context['project']['is_developer'],
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

	// Load timeline
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.issue_type, i.subject, i.priority, i.status,
			tl.event, tl.event_data, tl.event_time, tl.id_version,
			mem.id_member, IFNULL(mem.real_name, {string:empty}) AS user,
			ver.member_groups
		FROM {db_prefix}project_timeline AS tl
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tl.id_member)
			LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = tl.id_version)
		WHERE tl.id_project = {int:project}
			AND {query_see_issue}
		ORDER BY tl.event_time DESC
		LIMIT 12',
		array(
			'project' => $project,
			'empty' => ''
		)
	);

	$context['events'] = array();

	$nowtime = forum_time();
	$now = @getdate($nowtime);
	$clockFromat = strpos($user_info['time_format'], '%I') === false ? '%H:%M' : '%I:%M %p';

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$data = unserialize($row['event_data']);

		$index = date('Ymd', forum_time(true, $row['event_time']));
		$date = @getdate(forum_time(true, $row['event_time']));

		if (!isset($context['events'][$index]))
		{
			$context['events'][$index] = array(
				'date' => '',
				'events' => array(),
			);

			if ($date['yday'] == $now['yday'] && $date['year'] == $now['year'])
				$context['events'][$index]['date'] = $txt['project_today'];
			elseif (($date['yday'] == $now['yday'] - 1 && $date['year'] == $now['year']) || ($now['yday'] == 0 && $date['year'] == $now['year'] - 1) && $date['mon'] == 12 && $date['mday'] == 31)
				$context['events'][$index]['date'] = $txt['project_yesterday'];
			else
				$context['events'][$index]['date'] = $date['mday'] . '. ' . $txt['months'][$date['mon']] . ' ' . $now['year'];
		}

		$context['events'][$index]['events'][] = array(
			'event' => $row['event'],
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
			'link' => !empty($row['subject']) ? '<a href="' . $scripturl . '?issue=' . $row['id_issue'] . '">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
			'time' => strftime($clockFromat, forum_time(true, $row['event_time'])),
			'data' => $data,
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'project_view';
	$context['page_title'] = sprintf($txt['project_title'], $context['project']['name']);
}

function getIssueList($num_issues, $order = 'i.updated DESC', $where = '1 = 1')
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.issue_type, i.subject, i.priority,
			i.status, i.created, i.updated,
			i.id_reporter, IFNULL(mr.real_name, {string:empty}) AS reporter,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_version, IFNULL(ver.version_name, {string:empty}) AS version_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS mu ON (mr.id_member = i.id_updater)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE {query_see_issue}
			AND ('. $where . ')
			AND i.id_project = {int:project}
		ORDER BY ' . $order . '
		LIMIT {int:start}, {int:num_issues}',
		array(
			'project' => $project,
			'empty' => '',
			'start' => 0,
			'num_issues' => $num_issues,
			'member' => $user_info['id']
		)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . $scripturl . '?issue=' . $row['id_issue'] . '">' . $row['subject'] . '</a>',
			'href' => $scripturl . '?issue=' . $row['id_issue'],
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
			'priority' => $row['priority']
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

?>