<?php
/**********************************************************************************
* IssueList.php                                                                   *
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
	Issue List / Search

*/

function IssueList()
{
	global $project, $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $board;

	projectIsAllowedTo('issue_view');

	// Sorting methods
	$sort_methods = array(
		'updated' => 'i.updated',
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
		$_REQUEST['sort'] = 'i.updated';

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

	$baseurl = array(
		'project' => $context['project']['id'],
		'sa' => 'issues'
	);

	// Build Search info
	$context['issue_search'] = array(
		'title' => '',
		'status' => 'open',
		'tag' => '',
		'tracker' => '',
		'version' => null,
		'version_fixed' => null,
		'category' => null,
		'reporter' => null,
		'assignee' => null,
	);

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $tracker)
		$context['possible_types'][$tracker['tracker']['short']] = &$context['issue_trackers'][$tracker['tracker']['short']];

	if (!empty($_REQUEST['title']))
	{
		$context['issue_search']['title'] = $smcFunc['htmlspecialchars']($_REQUEST['title']);
		$baseurl['tilte'] = $_REQUEST['title'];
	}

	if (!empty($_REQUEST['tracker']) && isset($context['possible_types'][$_REQUEST['tracker']]))
	{
		$context['issue_search']['tracker'] = $_REQUEST['tracker'];
		$baseurl['tracker'] = $_REQUEST['tracker'];
	}

	if (isset($_REQUEST['category']))
	{
		$context['issue_search']['category'] = $_REQUEST['category'];
		$baseurl['category'] = $_REQUEST['category'];
	}

	if (isset($_REQUEST['reporter']))
	{
		$context['issue_search']['reporter'] = $_REQUEST['reporter'];
		$baseurl['reporter'] = $_REQUEST['reporter'];
	}

	if (isset($_REQUEST['assignee']))
	{
		$context['issue_search']['assignee'] = $_REQUEST['assignee'];
		$baseurl['assignee'] = $_REQUEST['assignee'];
	}

	if (isset($_REQUEST['version']))
	{
		$_REQUEST['version'] = (int) trim($_REQUEST['version']);

		$context['issue_search']['version'] = $_REQUEST['version'];
		$baseurl['version'] = $_REQUEST['version'];
	}

	if (isset($_REQUEST['version_fixed']))
	{
		$_REQUEST['version_fixed'] = (int) trim($_REQUEST['version_fixed']);

		$context['issue_search']['version_fixed'] = $_REQUEST['version_fixed'];
		$baseurl['version_fixed'] = $_REQUEST['version_fixed'];

		$context['issue_search']['status'] = 'all';
	}

	if (!empty($_REQUEST['status']))
	{
		$context['issue_search']['status'] = $_REQUEST['status'];
		$baseurl['status'] = $_REQUEST['status'];
	}
	
	$tags_url = $baseurl;

	if (!empty($_REQUEST['tag']))
	{
		$context['issue_search']['tag'] = $_REQUEST['tag'];
		$baseurl['tag'] = $_REQUEST['tag'];
	}

	// Build where clause
	$where = array();

	if ($context['issue_search']['status'] == 'open')
		$where[] = 'NOT (i.status IN ({array_int:closed_status}))';
	elseif ($context['issue_search']['status'] == 'closed')
		$where[] = 'i.status IN ({array_int:closed_status})';
	elseif (is_numeric($context['issue_search']['status']))
		$where[] = 'i.status IN ({int:search_status})';

	if (!empty($context['issue_search']['title']))
		$where[] = 'i.subject LIKE {string:search_title}';

	if (!empty($context['issue_search']['tracker']))
		$where[] = 'i.id_tracker = {int:search_tracker}';

	if (isset($context['issue_search']['category']))
		$where[] = 'i.id_category = {int:search_category}';

	if (isset($context['issue_search']['reporter']))
		$where[] = 'i.id_reporter = {int:search_reporter}';

	if (isset($context['issue_search']['assignee']))
		$where[] = 'i.id_assigned = {int:search_assignee}';

	if (isset($context['issue_search']['version']))
		$where[] = '(i.id_version = {int:search_version})';

	if (isset($context['issue_search']['version_fixed']))
		$where[] = '(i.id_version_fixed = {int:search_version_f})';

	$context['show_checkboxes'] = projectAllowedTo('issue_moderate');
	$context['can_report_issues'] = projectAllowedTo('issue_report');

	// How many issues?
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($context['issue_search']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . '
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
		WHERE {query_see_issue_project}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND ' . implode('
			AND ', $where) : '') . '',
		array(
			'project' => $context['project']['id'],
			'closed_status' => $context['closed_status'],
			'search_status' => $context['issue_search']['status'],
			'search_title' => '%' . $context['issue_search']['title'] . '%',
			'search_version' => $context['issue_search']['version'],
			'search_version_f' => $context['issue_search']['version_fixed'],
			'search_category' => $context['issue_search']['category'],
			'search_assignee' => $context['issue_search']['assignee'],
			'search_reporter' => $context['issue_search']['reporter'],
			'search_tracker' => $context['issue_search']['tracker'],
			'search_tag' => $context['issue_search']['tag'],
		)
	);

	list ($issueCount) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	$context['page_index'] = constructPageIndex(project_get_url($baseurl), $_REQUEST['start'], $issueCount, $context['issues_per_page']);

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.id_tracker, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			rep.id_member AS id_reporter, IFNULL(rep.real_name, com.poster_name) AS reporter_name,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_version, IFNULL(ver.version_name, {string:empty}) AS version_name,
			i.id_version_fixed, IFNULL(ver2.version_name, {string:empty}) AS version_fixed_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
			GROUP_CONCAT(tags.tag SEPARATOR \', \') AS tags,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($context['issue_search']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)
			LEFT JOIN {db_prefix}members AS rep ON (rep.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}project_versions AS ver2 ON (ver2.id_version = i.id_version_fixed)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
			LEFT JOIN {db_prefix}issue_tags AS tags ON (tags.id_issue = i.id_issue)
		WHERE {query_see_issue_project}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND ' . implode('
			AND ', $where) : '') . '
		GROUP BY i.id_issue
		ORDER BY ' . $_REQUEST['sort']. (!$ascending ? ' DESC' : '') . '
		LIMIT {int:start},' . $context['issues_per_page'],
		array(
			'project' => $context['project']['id'],
			'empty' => '',
			'start' => $_REQUEST['start'],
			'current_member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
			'search_version' => $context['issue_search']['version'],
			'search_version_f' => $context['issue_search']['version_fixed'],
			'search_status' => $context['issue_search']['status'],
			'search_title' => '%' . $context['issue_search']['title'] . '%',
			'search_category' => $context['issue_search']['category'],
			'search_assignee' => $context['issue_search']['assignee'],
			'search_reporter' => $context['issue_search']['reporter'],
			'search_tracker' => $context['issue_search']['tracker'],
			'search_tag' => $context['issue_search']['tag'],
		)
	);

	$context['issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$row['tags'] = explode(', ', $row['tags']);
		array_walk($row['tags'], 'link_tags', $tags_url);

		$context['issues'][] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . project_get_url(array('issue' => $row['id_issue'] . '.0')) . '">' . $row['subject'] . '</a>',
			'href' => project_get_url(array('issue' => $row['id_issue'] . '.0')),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . project_get_url(array('project' => $project, 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'version' => array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => !empty($row['version_name']) ? '<a href="' . project_get_url(array('project' => $project, 'sa' => 'issues', 'version' => $row['id_version'])) . '">' . $row['version_name'] . '</a>' : ''
			),
			'version_fixed' => array(
				'id' => $row['id_version_fixed'],
				'name' => $row['version_fixed_name'],
				'link' => !empty($row['version_fixed_name']) ? '<a href="' . project_get_url(array('project' => $project, 'sa' => 'issues', 'version_fixed' => $row['id_version_fixed'])) . '">' . $row['version_fixed_name'] . '</a>' : ''
			),
			'tags' => $row['tags'],
			'tracker' => &$context['issue_trackers'][$row['id_tracker']],
			'updated' => timeformat($row['updated']),
			'created' => timeformat($row['created']),
			'status' => &$context['issue_status'][$row['status']],
			'reporter' => array(
				'id' => $row['id_reporter'],
				'name' => $row['reporter_name'],
				'link' => !empty($row['id_reporter']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter_name'] . '</a>' : $row['reporter_name'],
			),
			'is_assigned' => !empty($row['id_assigned']),
			'assigned' => array(
				'id' => $row['id_assigned'],
				'name' => $row['assigned_name'],
				'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_assigned'] . '">' . $row['assigned_name'] . '</a>',
			),
			'updater' => array(
				'id' => $row['id_updater'],
				'name' => empty($row['updater']) ? $txt['issue_guest'] : $row['updater'],
				'link' => empty($row['updater']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_updater'] . '">' . $row['updater'] . '</a>',
			),
			'replies' => comma_format($row['replies']),
			'priority' => $row['priority'],
			'new' => $row['new_from'] <= $row['id_event_mod'],
			'new_href' => project_get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from'])) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'issue_list';
	$context['page_title'] = sprintf($txt['project_title_issues'], $context['project']['name']);

	loadTemplate('IssueList');
}

?>