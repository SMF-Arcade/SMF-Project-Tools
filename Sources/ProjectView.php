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

	// Load Recently updated issues
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.issue_type, i.subject, i.priority, i.status,
			i.id_category, i.id_reporter, i.id_version,
			IFNULL(mr.real_name, {string:empty}) AS reporter,
			IFNULL(cat.category_name, {string:empty}) AS category_name,
			IFNULL(ver.version_name, {string:empty}) AS version_name, i.created, i.updated
		FROM {db_prefix}issues AS i
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE {query_see_issue}
			AND i.id_project = {int:project}
		ORDER BY i.updated DESC
		LIMIT {int:start}, {int:number_recent}',
		array(
			'project' => $project,
			'empty' => '',
			'start' => 0,
			'number_recent' => 5,
		)
	);

	$context['recent_issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['recent_issues'][] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'category' => !empty($row['category_name']) ? '<a href="' . $scripturl . '?project=' . $project . ';sa=issues;category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>' : '',
			'version' => !empty($row['version_name']) ? '<a href="' . $scripturl . '?project=' . $project . ';sa=issues;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>' : '',
			'type' => $row['issue_type'],
			'link' => $scripturl . '?issue=' . $row['id_issue'],
			'updated' => timeformat($row['updated']),
			'status' => &$context['issue']['status'][$row['status']]['text'],
			'reporter_link' => empty($row['id_reporter']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter'] . '</a>',
			'priority' => $row['priority']
		);
	}
	$smcFunc['db_free_result']($request);

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
		LIMIT 25',
		array(
			'project' => $project,
			'empty' => ''
		)
	);

	$context['events'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$data = unserialize($row['event_data']);

		$context['events'][] = array(
			'event' => $row['event'],
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
			'link' => !empty($row['subject']) ? '<a href="' . $scripturl . '?issue=' . $row['id_issue'] . '">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
			'time' => timeformat($row['event_time']),
			'data' => $data,
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'project';
	$context['page_title'] = sprintf($txt['project_title'], $context['project']['name']);
}

?>