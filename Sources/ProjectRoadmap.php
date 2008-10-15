<?php
/**********************************************************************************
* ProjectRoadmap.php                                                              *
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
	loadTemplate('ProjectRoadmap');
}

?>