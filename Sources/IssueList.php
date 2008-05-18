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
	View and List issues

*/

function IssueList()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $board;

	require_once($sourcedir . '/Subs-Project.php');

	loadProjectTools('issue');

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

	$context['page_index'] = constructPageIndex('?action=issues;project=' . $context['project']['id'], $_REQUEST['start'], $issueCount, $issuesPerPage);

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
			'category' => !empty($row['category_name']) ? '<a href="' . $scripturl . '?action=issues;project=' . $row['id_project'] . ';category=' . $row['id_category'] . '">' . $row['category_name'] . '</a>' : '',
			'version' => !empty($row['version_name']) ? '<a href="' . $scripturl . '?action=issues;project=' . $row['id_project'] . ';version=' . $row['id_version'] . '">' . $row['version_name'] . '</a>' : '',
			'type' => $row['issue_type'],
			'link' => $scripturl . '?action=issue;issue=' . $row['id_issue'],
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

function IssueViewIssue()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;
	global $comment_req;

	require_once($sourcedir . '/Subs-Project.php');
	loadProjectTools('issue');

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	$issue = $context['current_issue']['id'];

	// Permission stolen from display.php ;)
	$common_permissions = array(
		'can_approve' => 'approve_posts',
		'can_ban' => 'manage_bans',
		'can_sticky' => 'make_sticky',
		'can_merge' => 'merge_any',
		'can_split' => 'split_any',
		'can_mark_notify' => 'mark_any_notify',
		'can_send_pm' => 'pm_send',
		'can_report_moderator' => 'report_any',
		'can_moderate_forum' => 'moderate_forum',
		'can_issue_warning' => 'issue_warning',
	);
	foreach ($common_permissions as $contextual => $perm)
		$context[$contextual] = allowedTo($perm);

	$start = $_REQUEST['start'];
	$limit = $modSettings['defaultMaxMessages'];

	$type = $context['current_issue']['is_my'] ? 'own' : 'any';

	if (allowedTo('issue_update_' . $type))
	{
		require_once($sourcedir . '/Subs-Members.php');

		$context['can_update'] = true;

		/*if (allowedTo('issue_assign'))
		{
			$context['can_assign'] = true;
			$context['assign_members'] = array();

			$groups = groupsAllowedTo('issue_assign_to');

			$request = $smcFunc['db_query']('', "
				SELECT mem.id_member, mem.member_name, mem.real_name
				FROM {$db_prefix}members AS mem
				WHERE (id_group IN ('" . implode(', ', $groups['allowed']) . ") OR FIND_IN_SET(" . implode(', mem.additional_groups) OR FIND_IN_SET(', $groups['allowed']) . ")
					AND NOT (id_group IN ('" . implode(', ', $groups['denied']) . ") OR FIND_IN_SET(" . implode(', mem.additional_groups) OR FIND_IN_SET(', $groups['denied']) . ")", __FILE__, __LINE__);

			while ($row = $smcFunc['db_fetch_assoc']($request))
				$context['assign_members'][] = array($row['id_member'], $row['member_name'], $row['real_name']);
			$smcFunc['db_free_result']($request);
		}*/
	}

	// Template
	$context['sub_template'] = 'issue_view';
	//$context['page_title'] = sprintf($txt['issue_tracker_view_issue'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueList');
}


function IssueDelete()
{
	global $context;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	checkSession('get');

	isAllowedTo('issue_delete');

	deleteIssue($context['current_issue']['id']);

	redirectexit('action=issues;project=' . $_REQUEST['project']);

}

?>