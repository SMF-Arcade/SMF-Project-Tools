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

	ProjectIsAllowedTo('issue_view');

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

	$baseurl = $scripturl . '?project=' . $context['project']['id'] . ';sa=issues';

	// Build Search info
	$context['issue_search'] = array(
		'title' => '',
		'status' => 'open',
		'version' => 0,
		'version_fix' => 0,
		'type' => '',
	);

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $id => $type)
		$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];

	if (!empty($_REQUEST['title']))
	{
		$context['issue_search']['title'] = $smcFunc['htmlspecialchars']($_REQUEST['title']);
		$baseurl .= ';title=' . $_REQUEST['title'];
	}

	if (!empty($_REQUEST['status']))
	{
		$context['issue_search']['status'] = $_REQUEST['status'];
		$baseurl .= ';status=' . $_REQUEST['status'];
	}

	if (!empty($_REQUEST['type']) && isset($context['possible_types'][$_REQUEST['type']]))
	{
		$context['issue_search']['type'] = $_REQUEST['type'];
		$baseurl .= ';type=' . $_REQUEST['type'];
	}

	// Build where clause
	$where = '';

	if ($context['issue_search']['status'] == 'open')
	{
		$where = '
			AND NOT i.status IN ({array_int:closed_status})';
	}
	elseif ($context['issue_search']['status'] == 'closed')
	{
		$where = '
			AND i.status IN ({array_int:closed_status})';
	}
	elseif (is_numeric($context['issue_search']['status']))
	{
		$where = '
			AND i.status IN ({int:search_status})';
	}

	if (!empty($context['issue_search']['title']))
	{
		$where = '
			AND i.subject LIKE {string:search_title}';
	}

	if (!empty($context['issue_search']['type']))
	{
		$where = '
			AND i.issue_type = {string:search_type}';
	}

	$issuesPerPage = 25;

	$context['show_checkboxes'] = projectAllowedTo('issue_update');

	// How many issues?
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
		WHERE {query_see_issue}
			AND i.id_project = {int:project}' . $where,
		array(
			'project' => $context['project']['id'],
			'closed_status' => $context['closed_status'],
			'search_status' => $context['issue_search']['status'],
			'search_title' => '%' . $context['issue_search']['title'] . '%',
			'search_type' => $context['issue_search']['type'],
		)
	);

	list ($issueCount) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex($baseurl, $_REQUEST['start'], $issueCount, $issuesPerPage);

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
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE {query_see_issue}
			AND i.id_project = {int:project}' . $where . '
		ORDER BY i.updated DESC
		LIMIT {int:start},' . $issuesPerPage,
		array(
			'project' => $context['project']['id'],
			'empty' => '',
			'start' => $_REQUEST['start'],
			'closed_status' => $context['closed_status'],
			'search_status' => $context['issue_search']['status'],
			'search_title' => '%' . $context['issue_search']['title'] . '%',
			'search_type' => $context['issue_search']['type'],
		)
	);

	$context['issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['issues'][] = array(
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

	// Template
	$context['sub_template'] = 'issue_list';
	$context['page_title'] = sprintf($txt['project_title_issues'], $context['project']['name']);

	loadTemplate('IssueList');
}

?>