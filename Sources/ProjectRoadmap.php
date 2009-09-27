<?php
/**********************************************************************************
* ProjectRoadmap.php                                                              *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.4                               *
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

	// Canonical url for search engines
	$context['canonical_url'] = project_get_url(array('project' => $project, 'sa' => 'roadmap'));
	
	$ids = array(0);
	$context['roadmap'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT ver.id_version, ver.id_parent, ver.version_name, ver.status, ver.description, ver.release_date
		FROM {db_prefix}project_versions AS ver
		WHERE {query_see_version}
			AND ver.id_project = {int:project}' . (!isset($_REQUEST['all']) ? '
			AND ver.status IN ({array_int:status})' : '' ) . '
		ORDER BY ver.version_name DESC',
		array(
			'project' => $project,
			'status' => array(0, 1),
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$ids[] = $row['id_version'];

		if (!empty($row['release_date']))
			$row['release_date'] = @unserialize($row['release_date']);
		else
			$row['release_date'] = array();

		$time = array();

		if (empty($row['release_date']['day']) && empty($row['release_date']['month']) && empty($row['release_date']['year']))
			$time = array('roadmap_no_release_date', array());
		elseif (empty($row['release_date']['day']) && empty($row['release_date']['month']))
			$time = array('roadmap_release_date_year', array($row['release_date']['year']));
		elseif (empty($row['release_date']['day']))
			$time = array('roadmap_release_date_year_month', array($txt['months'][(int) $row['release_date']['month']], $row['release_date']['year']));
		else
			$time = array('roadmap_release_date_year_month_day', array($row['release_date']['day'], $txt['months'][(int) $row['release_date']['month']], $row['release_date']['year']));

		$context['roadmap'][$row['id_version']] = array(
			'id' => $row['id_version'],
			'name' => $row['version_name'],
			'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => $row['id_version'])),
			'description' => parse_bbc($row['description']),
			'release_date' => vsprintf($txt[$time[0]], $time[1]),
			'issues' => array(
				'open' => 0,
				'closed' => 0,
			),
			'progress' => 0,
		);
		
		foreach (array_keys($context['project']['trackers']) as $tracker)
		{
			$context['roadmap'][$row['id_version']]['open'] += $row['open_' . $tracker];
			$context['roadmap'][$row['id_version']]['closed'] += $row['closed_' . $tracker];		
			$context['roadmap'][$row['id_version']]['total'] += $row['open_' . $tracker] + $row['closed_' . $tracker];
		}
		
		if ($context['roadmap'][$row['id_version']]['total'] > 0)
			$context['roadmap'][$row['id_version']]['progress'] = round($context['roadmap'][$row['id_version']]['closed'] / $context['roadmap'][$row['id_version']]['total'] * 100, 2);	
	}
	$smcFunc['db_free_result']($request);

	// N/A version
	$context['roadmap'][0] = array(
		'id' => 0,
		'name' => $txt['version_na'],
		'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => 0)),
		'description' => $txt['version_na_desc'],
		'release_date' => $txt['roadmap_no_release_date'],
		'issues' => array(
			'open' => 0,
			'closed' => 0,
		),
		'progress' => 0,
	);

	// Hide "not set" version if it has no issues
	/*if ($context['roadmap'][0]['issues']['total'] == 0)
		unset($context['roadmap'][0]);*/

	// Template
	$context['page_title'] = sprintf($txt['project_roadmap_title'], $context['project']['name']);
	$context['sub_template'] = 'project_roadmap';
	loadTemplate('ProjectRoadmap');
}

function ProjectRoadmapVersion()
{
	global $context, $project, $user_info, $smcFunc, $txt;

	if ($_REQUEST['version'] != '0')
	{
		$request = $smcFunc['db_query']('', '
			SELECT ver.id_version, ver.id_parent, ver.version_name, ver.status, ver.description, ver.release_date, ver.' . implode(', ver.', $context['tracker_columns']) . '
			FROM {db_prefix}project_versions AS ver
			WHERE ({query_see_version})
				AND ver.id_project = {int:project}
				AND ver.id_version = {int:version}',
			array(
				'project' => $project,
				'version' => (int) $_REQUEST['version'],
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
	}
	else
	{
		$row = array(
			'id_version' => 0,
			'id_parent' => 0,
			'version_name' => $txt['version_na'],
			'status' => 0,
			'description' => $txt['version_na_desc'],
			'release_date' => '',
		);
	}

	if (!$row)
		fatal_lang_error('version_not_found', false);

	// Canonical url for search engines
	$context['canonical_url'] = project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => $row['id_version']));
	
	// Make release date string
	if (!empty($row['release_date']))
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

	$context['version'] = array(
		'id' => $row['id_version'],
		'name' => $row['version_name'],
		'href' => project_get_url(array('project' => $project, 'sa' => 'roadmap', 'version' => $row['id_version'])),
		'description' => parse_bbc($row['description']),
		'release_date' => vsprintf($txt[$time[0]], $time[1]),
		'versions' => array(),
		'progress' => 0,
		'issues' => array(
			'open' => 0,
			'closed' => 0,
			'total' => 0,
		),
	);
	
	foreach (array_keys($context['project']['trackers']) as $tracker)
	{
		$context['version']['issues']['open'] += $row['open_' . $tracker];
		$context['version']['issues']['closed'] += $row['closed_' . $tracker];		
		$context['version']['issues']['total'] += $row['open_' . $tracker] + $row['closed_' . $tracker];
	}

	if (!empty($context['version']['issues']['total']))
		$context['version']['progress'] = round($context['version']['issues']['closed'] / $context['version']['issues']['total'] * 100, 2);

	// Load Issues
	$context['issues'] = getIssueList(0, 10, 'i.updated DESC', 'i.id_version IN({array_int:versions})', array('versions' => array((int) $context['version']['id'])));
	$context['issues_href'] = project_get_url(array('project' => $project, 'sa' => 'issues', 'version_fixed' => $context['version']['id']));

	// Template
	$context['sub_template'] = 'project_roadmap_version';
	loadTemplate('ProjectRoadmap');
}

?>