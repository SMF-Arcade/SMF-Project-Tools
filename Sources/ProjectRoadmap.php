<?php
/**********************************************************************************
* ProjectRoadmap.php                                                              *
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

function ProjectRoadmap()
{
	global $context, $project, $user_info, $smcFunc, $txt;

	if (!isset($_REQUEST['version']))
		ProjectRoadmapMain();
	else
		ProjectRoadmapVersion();
}

function ProjectRoadmapMain()
{
	global $context, $project, $user_info, $smcFunc, $txt;

	$ids = array();
	$context['roadmap'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT
			ver.id_version, ver.id_parent, ver.version_name, ver.status, ver.description, ver.release_date
		FROM {db_prefix}project_versions AS ver
		WHERE {query_see_version}
			AND ver.id_project = {int:project}' . (!isset($_REQUEST['all']) ? '
			AND ver.status IN ({array_int:status})' : '' ) . '
		ORDER BY ver.id_version DESC',
		array(
			'project' => $project,
			'status' => array(0, 1),
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$ids[] = $row['id_version'];

		$row['release_date'] = unserialize($row['release_date']);

		$time = array();

		if (empty($row['release_date']['day']) && empty($row['release_date']['month']) && empty($row['release_date']['year']))
			$time = array('roadmap_no_release_date', array());
		elseif (empty($row['release_date']['day']) && empty($row['release_date']['month']))
			$time = array('roadmap_release_date_year', array($row['release_date']['year']));
		elseif (empty($row['release_date']['day']))
			$time = array('roadmap_release_date_year_month', array($txt['months'][$row['release_date']['month']], $row['release_date']['year']));
		else
			$time = array('roadmap_release_date_year_month_day', array($row['release_date']['day'], $txt['months'][$row['release_date']['month']], $row['release_date']['year']));

		$context['roadmap'][$row['id_version']] = array(
			'id' => $row['id_version'],
			'name' => $row['version_name'],
			'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => $row['id_version'])),
			'description' => parse_bbc($row['description']),
			'release_date' => vsprintf($txt[$time[0]], $time[1]),
			'versions' => array(),
			'issues' => array(
				'open' => 0,
				'closed' => 0,
			),
		);
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

			if (!in_array($row['status'], $context['closed_status']))
				$context['roadmap'][$row['id_version']]['issues']['open'] += $row['num'];
			else
				$context['roadmap'][$row['id_version']]['issues']['closed'] += $row['num'];
		}
		$smcFunc['db_free_result']($request);

		foreach ($context['roadmap'] as $id => $d)
		{
			$d['issues']['total'] = $d['issues']['open'] + $d['issues']['closed'];

			if ($d['issues']['total'] > 0)
				$d['progress'] = round($d['issues']['closed'] / $d['issues']['total'] * 100, 2);
			else
				$d['progress'] = 0;

			// Back to array
			$context['roadmap'][$id] = $d;
		}
	}

	// Template
	$context['page_title'] = sprintf($context['project']['name'], $txt['project_roadmap_title']);
	$context['sub_template'] = 'project_roadmap';
	loadTemplate('ProjectRoadmap');
}

function ProjectRoadmapVersion()
{
	global $context, $project, $user_info, $smcFunc, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			ver.id_version, ver.id_parent, ver.version_name, ver.status,
			ver.description, ver.release_date
		FROM {db_prefix}project_versions AS ver
		WHERE ({query_see_version})
			AND ver.id_project = {int:project}
			AND ver.id_version = {int:version}',
		array(
			'project' => $project,
			'version' => $_REQUEST['version'],
		)
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	if (!$row)
		fatal_lang_error('version_not_found', false);

		$row['release_date'] = unserialize($row['release_date']);

	// Make release date string
	$time = array();

	if (empty($row['release_date']['day']) && empty($row['release_date']['month']) && empty($row['release_date']['year']))
		$time = array('roadmap_no_release_date', array());
	elseif (empty($row['release_date']['day']) && empty($row['release_date']['month']))
		$time = array('roadmap_release_date_year', array($row['release_date']['year']));
	elseif (empty($row['release_date']['day']))
		$time = array('roadmap_release_date_year_month', array($txt['months'][$row['release_date']['month']], $row['release_date']['year']));
	else
		$time = array('roadmap_release_date_year_month_day', array($row['release_date']['day'], $txt['months'][$row['release_date']['month']], $row['release_date']['year']));

	$context['version'] = array(
		'id' => $row['id_version'],
		'name' => $row['version_name'],
		'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => $row['id_version'])),
		'description' => parse_bbc($row['description']),
		'release_date' => vsprintf($txt[$time[0]], $time[1]),
		'versions' => array(),
		'issues' => array(
			'open' => 0,
			'closed' => 0,
		),
	);

	// Load Issues
	$context['issues'] = getIssueList(10, 'i.updated DESC');
	$context['issues_href'] = project_get_url(array('project' => $project, 'sa' => 'issues', 'version' => $context['version']['id']));

	// Template
	$context['sub_template'] = 'project_roadmap_version';
	loadTemplate('ProjectRoadmap');
}

?>