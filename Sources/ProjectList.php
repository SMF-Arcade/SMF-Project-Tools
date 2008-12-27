<?php
/**********************************************************************************
* ProjectList.php                                                                 *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.2                               *
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

function ProjectList()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.trackers, p.' . implode(', p.', $context['type_columns']) . ', p.id_event_mod,
			mem.id_member, mem.real_name,
			' . ($user_info['is_guest'] ? '0 AS new_from' : '(IFNULL(log.id_event, -1) + 1) AS new_from') . '
		FROM {db_prefix}projects AS p' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_projects AS log ON (log.id_member = {int:member}
				AND log.id_project = p.id_project)') . '
			LEFT JOIN {db_prefix}project_developer AS pdev ON (pdev.id_project = p.id_project)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = pdev.id_member)
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:member})
		WHERE {query_see_project}
		ORDER BY p.name',
		array(
			'member' => $user_info['id'],
		)
	);

	$context['projects'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (isset($context['projects'][$row['id_project']]))
		{
			if (empty($row['id_member']))
				continue;

			$context['projects'][$row['id_project']]['developers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';

			continue;
		}

		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
			'href' => project_get_url(array('project' => $row['id_project'])),
			'name' => $row['name'],
			'description' => $row['description'],
			'trackers' =>  explode(',', $row['trackers']),
			'new' => $row['new_from'] <= $row['id_event_mod'] && !$user_info['is_guest'],
			'issues' => array(),
			'developers' => array(
				'<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>'
			),
		);

		foreach ($context['projects'][$row['id_project']]['trackers'] as $key)
		{
			$context['projects'][$row['id_project']]['issues'][$key] = array(
				'open' => $row['open_' . $key],
				'closed' => $row['closed_' . $key],
				'total' => $row['open_' . $key] + $row['closed_' . $key],
				'link' => project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'type' => $key)),
			);
		}
	}
	$smcFunc['db_free_result']($request);

	loadTimeline();

	// Template
	$context['sub_template'] = 'project_list';
	$context['page_title'] = sprintf($txt['project_list_title'], $context['forum_name']);
}

?>