<?php
/**********************************************************************************
* Subs-Issue.php                                                                  *
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

function loadIssueTracker()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	// Status, types, priorities
	$context['issue']['status'] = array(
		1 => array(
			'text' => $txt['issue_new'],
			'type' => 'open',
		),
		array(
			'text' => $txt['issue_feedback'],
			'type' => 'open',
		),
		array(
			'text' => $txt['issue_confirmed'],
			'type' => 'open',
		),
		array(
			'text' => $txt['issue_assigned'],
			'type' => 'open',
		),
		array(
			'text' => $txt['issue_resolved'],
			'type' => 'closed',
		),
		array(
			'text' => $txt['issue_closed'],
			'type' => 'closed',
		),
	);

	$context['issue']['priority'] = array(
		1 => 'issue_priority_low',
		'issue_priority_normal',
		'issue_priority_high'
	);

	// Load issue if needed
	if (!empty($_REQUEST['issue']))
	{
		if (!loadIssue((int) $_REQUEST['issue']))
			fatal_lang_error('issue_not_found');

		$_REQUEST['project'] = $context['current_issue']['project']['id'];
	}

	// Load Project if needed
	if (!empty($_REQUEST['project']) && !loadProject((int) $_REQUEST['project'], true, 'issues'))
		fatal_lang_error('project_not_found');

	$context['linktree'][] = array(
		'name' => $txt['linktree_issues'],
		'url' => $scripturl . '?action=issues;project=' . $context['project']['id']
	);
}

function loadIssue($id_issue)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $memberContext;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.issue_type,
			i.id_reporter, i.id_assigned, i.body,
			p.id_project, p.name AS project_name,
			cat.id_category, cat.category_name,
			ver.id_version, ver.version_name,
			ver2.id_version AS vidfix, ver2.version_name AS vnamefix
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			LEFT JOIN {db_prefix}members AS ma ON (ma.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}project_versions AS ver2 ON (ver.id_version = i.id_version_fixed)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE id_issue = {int:issue}
			AND {query_see_issue}
		LIMIT 1',
		array(
			'issue' => $id_issue,
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	loadMemberData(array($row['id_reporter']));

	if (!loadMemberContext($row['id_reporter']))
	{
		$memberContext[$row['id_reporter']]['name'] = $txt['issue_guest'];
		$memberContext[$row['id_reporter']]['id'] = 0;
		$memberContext[$row['id_reporter']]['group'] = $txt['guest_title'];
		$memberContext[$row['id_reporter']]['link'] = $txt['issue_guest'];
		$memberContext[$row['id_reporter']]['email'] = '';
		$memberContext[$row['id_reporter']]['hide_email'] = true;
		$memberContext[$row['id_reporter']]['is_guest'] = true;
	}
	else
	{
		$memberContext[$row['id_reporter']]['can_view_profile'] = allowedTo('profile_view_any') || ($row['id_reporter'] == $user_info['id'] && allowedTo('profile_view_own'));
		//$memberContext[$row['id_reporter']]['is_reporter'] = true;
	}

	censorText($row['body']);

	$context['current_issue'] = array(
		'id' => $row['id_issue'],
		'name' => $row['subject'],
		'link' => $scripturl . '?action=issue;issue=' . $row['id_issue'],
		'category' => $row['category_name'],
		'id_category' => $row['id_category'],
		'version' => array(
			'id' => $row['id_version'],
			'name' => $row['version_name'],
		),
		'version_fixed' => array(
			'id' => $row['vidfix'],
			'name' => $row['vnamefix'],
		),
		'reporter' => &$memberContext[$row['id_reporter']],
		'is_my_issue' => !$user_info['guest'] && $row['id_reporter'] == $user_info['id'],
		'type' => $context['project_tools']['issue_types'][$row['issue_type']],
		'status' => $context['issue']['status'][$row['status']],
		'priority' => $context['issue']['priority'][$row['priority']],
		'created' => timeformat($row['created']),
		'updated' => $row['updated'] > 0 ? timeformat($row['updated']) : false,
		'project' => array(
			'id' => $row['id_project'],
			'link' => $scripturl . '?action=issues;sa=project;p=' . $row['id_project'],
			'name' => $row['project_name']
		),
		'body' => parse_bbc($row['body']),
	);

	return true;
}

function createIssue($issueOptions, &$posterOptions)
{
	global $smcFunc, $db_prefix;

	if (empty($issueOptions['created']))
		$issueOptions['created'] = time();

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}issues',
		array(
			'id_project' => 'int',
			'subject' => 'string-100',
			'body' => 'string',
			'created' => 'int',
			'id_reporter' => 'int',
		),
		array(
			$issueOptions['project'],
			$issueOptions['subject'],
			$issueOptions['body'],
			$issueOptions['created'],
			$posterOptions['id']
		),
		array()
	);

	$id_issue = $smcFunc['db_insert_id']('{db_prefix}issues', 'id_issue');

	$event_data = array(
		'subject' => $issueOptions['subject']
	);

	$event_data = serialize($event_data);

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_timeline',
		array(
			'id_project' => 'int',
			'id_issue' => 'int',
			'id_member' => 'int',
			'event' => 'string',
			'event_time' => 'int',
			'event_data' => 'string',
		),
		array(
			$issueOptions['project'],
			$id_issue,
			$posterOptions['id'],
			'new_issue',
			$issueOptions['created'],
			$event_data
		),
		array()
	);

	unset($issueOptions['project'], $issueOptions['subject'], $issueOptions['body'], $issueOptions['created']);
	$issueOptions['no_log'] = true;

	if (!empty($issueOptions))
		updateIssue($id_issue, $issueOptions);

	return $id_issue;
}

function updateIssue($id_issue, $issueOptions)
{
	global $smcFunc, $db_prefix, $context;

	if (!isset($context['issue']['status']))
		trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);

	$request = $smcFunc['db_query']('', '
		SELECT i.id_project, i.id_issue, i.id_project, i.id_version, i.status, i.id_category, i.issue_type, i.id_assigned
		FROM {db_prefix}issues AS i
		WHERE i.id_issue = {int:issue}',
		array(
			'issue' => $id_issue
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;
	$row = $smcFunc['db_fetch_assoc']($request);

	$issueUpdates = array();

	if (!empty($issueOptions['subject']))
		$issueUpdates[] = 'subject = {string:subject}';

	if (!empty($issueOptions['body']))
		$issueUpdates[] = 'body = {string:body}';

	if (!empty($issueOptions['status']))
		$issueUpdates[] = 'status = {int:status}';

	if (!empty($issueOptions['assignee']))
		$issueUpdates[] = 'id_assignee = {int:assignee}';

	if (!empty($issueOptions['priority']))
		$issueUpdates[] = 'priority = {int:priority}';

	if (!empty($issueOptions['version']))
		$issueUpdates[] = 'id_version = {int:version}';

	if (!empty($issueOptions['category']))
		$issueUpdates[] = 'id_category = {int:category}';

	if (!empty($issueOptions['project']))
		$issueUpdates[] = 'id_project = {int:project}';

	if (!empty($issueOptions['type']))
		$issueUpdates[] = 'issue_type = {string:type}';

	if (!empty($row['status']))
		$oldStatus = $context['issue']['status'][$row['status']]['type'];
	else
		$oldStatus = '';

	if (!empty($issueOptions['status']))
		$newStatus = $context['issue']['status'][$issueOptions['status']]['type'];
	else
		$newStatus = $oldStatus;

	if (empty($newStatus))
		fatal_error('status must exists');

	if (!isset($issueOptions['type']))
		$issueOptions['type'] = $row['issue_type'];

	// Update database
	if (!empty($issueUpdates))
	{
		$issueUpdates[] = 'updated = {int:time}';
		$issueOptions['time'] = time();

		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}issues
			SET
				' . implode(',
				', $issueUpdates) . '
			WHERE id_issue = {int:issue}',
			array_merge($issueOptions ,array(
				'issue' => $id_issue,
			))
		);
	}

	// Update issue count
	if (isset($issueOptions['version']) || $newStatus != $oldStatus)
	{
		if (!isset($issueOptions['version']))
			$issueOptions['version'] =  $row['id_version'];

		if (!empty($oldStatus) && (($newStatus != $oldStatus) || (!empty($row['id_version']) && $issueOptions['version'] != $row['id_version'])))
		{
			$colname = $oldStatus . '_' . $row['issue_type'];

			$smfFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET ' . $colname . ' = ' . $colname . '- 1
				WHERE id_version = {int:version}',
				array(
					'version' => $row['id_version']
				)
			);
		}

		if (!empty($issueOptions['version']))
		{
			$colname = $newStatus . '_' . $issueOptions['type'];

			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET ' . $colname . ' = ' . $colname . ' + 1
				WHERE id_version = {int:version}',
				array(
					'version' => $row['id_version']
				)
			);
		}
	}

	if (isset($issueOptions['category']) || $newStatus != $oldStatus)
	{
		if (!isset($issueOptions['category']))
			$issueOptions['category'] = $row['id_category'];

		if (!empty($oldStatus) && ($newStatus != $oldStatus || (!empty($row['id_category']) && $issueOptions['category'] != $row['id_category'])))
		{
			$colname = $oldStatus . '_' . $row['issue_type'];

			$smcFunc['db_query']('', '
				UPDATE {$db_prefix}issue_category
				SET ' . $colname . ' = ' . $colname . ' - 1
				WHERE id_category = {int:category}',
				array(
					'category' => $row['id_category']
				)
			);
		}

		if (!empty($issueOptions['category']))
		{
			$colname = $newStatus . '_' . $issueOptions['type'];

			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issue_category
				SET ' . $colname . ' = ' . $colname . ' + 1
				WHERE id_category = {int:category}',
				array(
					'category' => $row['id_category']
				)
			);
		}
	}

	$projectUpdates = array();

	if (!empty($issueOptions['type']) && $issueOptions['type'] != $row['issue_type'])
	{
		if (!empty($oldStatus))
			$projectUpdates[] = "{$oldStatus}_$row[issue_type] = {$oldStatus}_$row[issue_type]";

		$projectUpdates[] = "{$newStatus}_$issueOptions[type] = {$newStatus}_$issueOptions[type] + 1";
	}

	if (!isset($issueOptions['project']))
		$issueOptions['project'] = $row['id_project'];

	if (!empty($projectUpdates))
		$smcFunc['db_query']('', "
			UPDATE {$db_prefix}projects
			SET
				" . implode(',
				', $projectUpdates) . "
			WHERE id_project = {int:project}",
			array(
				'project' => $issueOptions['project'],
			)
		);

	return true;
}

?>