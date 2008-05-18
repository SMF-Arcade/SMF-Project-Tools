<?php
/**********************************************************************************
* IssueList.php                                                                   *
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
	Issue List / Search

*/

function IssueList()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $board;

	if (empty($context['project']))
		fatal_lang_error('project_not_found');

	// Sorting methods
	$sort_methods = array(
		'updated' => 'MAX(i.updated, i.created)',
		'title' => 'i.subject',
		'id' => 'i.id_issue',
		'priority' => 'i.priority',
		'status' => 'i.status',
		'assigned' => 'i.id_assigned',
		'reporter' => 't.id_member_started'
	);

	// How user wants to sort issues?
	if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
	{
		$context['sort_by'] = 'updated';
		$_REQUEST['sort'] = 'MAX(i.updated, i.created)';

		$ascending = false;
		$context['sort_direction'] = 'down';
	}
	else
	{
		$context['sort_by'] = $_REQUEST['sort'];
		$_REQUEST['sort'] = $sort_methods[$_REQUEST['sort']];

		$ascending = !isset($_REQUEST['desc']);
		$context['sort_direction'] = $ascending ? 'up' : 'down';
	}

	$issuesPerPage = 25;

	// How many issues?
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
		WHERE {query_see_issue}
			AND i.id_project = {int:project}',
		array(
			'project' => $context['project']['id']
		)
	);

	list ($issueCount) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex('?project=' . $context['project']['id'] . ';sa=issues', $_REQUEST['start'], $issueCount, $issuesPerPage);

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.issue_type, i.subject, i.priority, i.status,
			i.id_category, i.id_reporter, i.id_version,
			IFNULL(mr.real_name, {string:empty}) AS reporter,
			IFNULL(cat.category_name, {string:empty}) AS category_name,
			IFNULL(ver.version_name, {string:empty}) AS version_name, i.created, i.updated
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE {query_see_issue}
			AND i.id_project = {int:project}
		ORDER BY i.updated DESC
		LIMIT {int:start},' . $issuesPerPage,
		array(
			'project' => $context['project']['id'],
			'empty' => '',
			'start' => $_REQUEST['start']
		)
	);

	$context['issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['issues'][] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'category' => !empty($row['category_name']) ? '<a href="' . $scripturl . '?project=' . $row['id_project'] . ';sa=issues;category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>' : '',
			'version' => !empty($row['version_name']) ? '<a href="' . $scripturl . '?project=' . $row['id_project'] . ';sa=issues;version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>' : '',
			'type' => $row['issue_type'],
			'link' => $scripturl . '?issue=' . $row['id_issue'],
			'updated' => $row['updated'] > 0 ? timeformat($row['updated']) : false,
			'created' => timeformat($row['created']),
			'status' => &$context['issue']['status'][$row['status']]['text'],
			'reporter' => empty($row['id_reporter']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter'] . '</a>',
			'priority' => $row['priority']
		);
	}

	// Template
	$context['sub_template'] = 'issue_list';
	//$context['page_title'] = sprintf($txt['project_title_issues'], $context['project']['name']);

	loadTemplate('IssueList');
}

?>