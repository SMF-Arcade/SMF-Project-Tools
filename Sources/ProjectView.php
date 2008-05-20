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