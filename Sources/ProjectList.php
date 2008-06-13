<?php
/**********************************************************************************
* ProjectList.php                                                                 *
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

function ProjectList()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT p.id_project, p.name, p.description, p.trackers, ' . implode(', p.', $context['type_columns']) . '
		FROM {db_prefix}projects AS p
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:member})
			LEFT JOIN {db_prefix}project_developer AS devg ON (devg.id_project = p.id_project
				AND ({query_devg_group}))
		WHERE {query_see_project}
		GROUP BY devg.id_group',
		array(
			'member' => $user_info['id'],
		)
	);

	$context['projects'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'link' => $scripturl . '?project=' . $row['id_project'],
			'name' => $row['name'],
			'description' => $row['description'],
			'trackers' =>  explode(',', $row['trackers']),
			'issues' => array()
		);

		foreach ($context['projects'][$row['id_project']]['trackers'] as $key)
		{
			$context['projects'][$row['id_project']]['issues'][$key] = array(
				'info' => &$context['project_tools']['issue_types'][$key],
				'open' => $row['open_' . $key],
				'closed' => $row['closed_' . $key],
				'total' => $row['open_' . $key] + $row['closed_' . $key],
				'link' => $scripturl . '?project='. $row['id_project'] . ';sa=issues;type=' . $key
			);
		}
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['linktrgee'][] = array(
		'name' => $txt['projects'],
		'url' => $scripturl . '?action=projects'
	);

	$context['sub_template'] = 'project_list';
	$context['page_title'] = $txt['project_list_title'];
}

?>