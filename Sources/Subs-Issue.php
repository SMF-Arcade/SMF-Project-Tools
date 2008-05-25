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

function loadIssueTypes()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt;

	$context['project_tools']['issue_types'] = array(
		'bug' => array(
			'id' => 'bug',
			'name' => $txt['project_bug'],
			'plural' => $txt['project_bugs'],
			'help' => $txt['project_bug_help'],
			'image' => 'bug.png',
		),
		'feature' => array(
			'id' => 'feature',
			'name' => $txt['project_feature'],
			'plural' => $txt['project_features'],
			'help' => $txt['project_feature_help'],
			'image' => 'feature.png',
		),
	);

	// Make list of columns that need to be selected
	$context['type_columns'] = array();
	foreach ($context['project_tools']['issue_types'] as $id => $info)
	{
		$context['type_columns'][] = "open_$id";
		$context['type_columns'][] = "closed_$id";
	}

	// Status, types, priorities
	$context['issue']['status'] = array(
		1 => array(
			'text' => $txt['issue_new'],
			'type' => 'open',
		),
		2 => array(
			'text' => $txt['issue_feedback'],
			'type' => 'open',
		),
		3 => array(
			'text' => $txt['issue_confirmed'],
			'type' => 'open',
		),
		4 => array(
			'text' => $txt['issue_assigned'],
			'type' => 'open',
		),
		5 => array(
			'text' => $txt['issue_resolved'],
			'type' => 'closed',
		),
		6 => array(
			'text' => $txt['issue_closed'],
			'type' => 'closed',
		),
	);

	$context['closed_status'] = array(5, 6);

	$context['issue']['priority'] = array(
		1 => 'issue_priority_low',
		'issue_priority_normal',
		'issue_priority_high'
	);
}

function loadIssue($id_issue)
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $memberContext;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.issue_type,
			i.id_reporter, i.body,
			i.id_assigned, ma.real_name AS a_real_name,
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
		'link' => $scripturl . '?issue=' . $row['id_issue'],
		'project' => array(
			'id' => $row['id_project'],
			'link' => '<a href="' . $scripturl . '?project=' . $row['id_project'] . '">' . $row['project_name'] . '</a>',
			'href' => $scripturl . '?project=' . $row['id_project'],
			'name' => $row['project_name'],
		),
		'category' => array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
		),
		'version' => array(
			'id' => $row['id_version'],
			'name' => $row['version_name'],
		),
		'version_fixed' => array(
			'id' => $row['vidfix'],
			'name' => $row['vnamefix'],
		),
		'reporter' => &$memberContext[$row['id_reporter']],
		'assignee' => array(
			'id' => $row['id_assigned'],
			'name' => '',
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_assigned'] . '">' . $row['a_real_name'] . '</a>',
		),
		'is_mine' => !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'],
		'type' => $context['project_tools']['issue_types'][$row['issue_type']],
		'status' => $context['issue']['status'][$row['status']],
		'priority' => $context['issue']['priority'][$row['priority']],
		'created' => timeformat($row['created']),
		'updated' => $row['updated'] > 0 ? timeformat($row['updated']) : false,
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
			serialize(array(
				'subject' => $issueOptions['subject']
			)),
		),
		array()
	);

	unset($issueOptions['project'], $issueOptions['subject'], $issueOptions['body'], $issueOptions['created']);
	$issueOptions['no_log'] = true;

	if (!empty($issueOptions))
		updateIssue($id_issue, $issueOptions, $posterOptions);

	return $id_issue;
}

function updateIssue($id_issue, $issueOptions, $posterOptions)
{
	global $smcFunc, $db_prefix, $context;

	if (!isset($context['issue']['status']))
		trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);

	$request = $smcFunc['db_query']('', '
		SELECT
			id_project, subject, id_version, status, id_category,
			priority, issue_type, id_assigned, id_version_fixed
		FROM {db_prefix}issues
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		return false;
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$issueUpdates = array(
		'subject' => isset($issueOptions['subject']) ? $issueOptions['subject'] : $row['subject'],
		'changes' => array(),
	);

	if (!empty($issueOptions['subject']) && $issueOptions['subject'] != $row['subject'])
	{
		$issueUpdates[] = 'subject = {string:subject}';

		$event_data['changes'][] = array(
			'rename', $row['subject'], $issueOptions['subject']
		);
	}

	if (!empty($issueOptions['body']))
	{
		$issueUpdates[] = 'body = {string:body}';

		$event_data['changes'][] = array(
			'details_edit'
		);
	}

	if (!empty($issueOptions['status']) && $issueOptions['status'] != $row['status'])
	{
		$issueUpdates[] = 'status = {int:status}';

		$event_data['changes'][] = array(
			'status', $row['status'], $issueOptions['status'],
		);
	}

	if (!empty($issueOptions['assignee']) && $issueOptions['assignee'] != $row['id_assigned'])
	{
		$issueUpdates[] = 'id_assigned = {int:assignee}';

		$event_data['changes'][] = array(
			'assign', $row['id_assigned'], $issueOptions['assignee'],
		);
	}

	if (!empty($issueOptions['priority']) && $issueOptions['priority'] != $row['priority'])
	{
		$issueUpdates[] = 'priority = {int:priority}';

		$event_data['changes'][] = array(
			'priority', $row['priority'], $issueOptions['priority'],
		);
	}

	if (!empty($issueOptions['version']) && $issueOptions['version'] != $row['id_version'])
	{
		$issueUpdates[] = 'id_version = {int:version}';

		$event_data['changes'][] = array(
			'version', $row['id_version'], $issueOptions['version'],
		);
	}

	if (!empty($issueOptions['version_fixed']) && $issueOptions['version_fixed'] != $row['id_version_fixed'])
	{
		$issueUpdates[] = 'id_version = {int:version}';

		$event_data['changes'][] = array(
			'target_version', $row['id_version_fixed'], $issueOptions['version_fixed'],
		);
	}

	if (!empty($issueOptions['category']) && $issueOptions['category'] != $row['id_category'])
	{
		$issueUpdates[] = 'id_category = {int:category}';

		$event_data['changes'][] = array(
			'category', $row['id_category'], $issueOptions['category'],
		);
	}

	if (!empty($issueOptions['type']) && $issueOptions['type'] != $row['issue_type'])
	{
		$issueUpdates[] = 'issue_type = {string:type}';

		$event_data['changes'][] = array(
			'type', $row['issue_type'], $issueOptions['type'],
		);
	}

	if (!empty($row['status']))
		$oldStatus = $context['issue']['status'][$row['status']]['type'];
	else
		$oldStatus = '';

	if (!empty($issueOptions['status']))
		$newStatus = $context['issue']['status'][$issueOptions['status']]['type'];
	else
		$newStatus = $oldStatus;

	if (!isset($issueOptions['type']))
		$issueOptions['type'] = $row['issue_type'];

	// Update database
	if (!empty($issueUpdates))
		return true;

	$issueUpdates[] = 'updated = {int:time}';
	$issueOptions['time'] = time();
	$issueUpdates[] = 'id_updater = {int:updater}';
	$issueOptions['updater'] = $posterOptions['id'];

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET
			' . implode(',
			', $issueUpdates) . '
		WHERE id_issue = {int:issue}',
		array_merge($issueOptions ,array(
			'issue' => $id_issue,
		))
	);

	// Update issue count
	if (isset($issueOptions['version']) || $newStatus != $oldStatus)
	{
		if (!isset($issueOptions['version']))
			$issueOptions['version'] =  $row['id_version'];

		$colname = $oldStatus . '_' . $row['issue_type'];

		if (!empty($row['id_version']))
			$smfFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET ' . $colname . ' = ' . $colname . '- 1
				WHERE id_version = {int:version}',
				array(
					'version' => $row['id_version']
				)
			);

		$colname = $newStatus . '_' . $issueOptions['type'];

		if (!empty($issueOptions['version']))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET ' . $colname . ' = ' . $colname . ' + 1
				WHERE id_version = {int:version}',
				array(
					'version' => $issueOptions['version']
				)
			);
	}

	if (isset($issueOptions['category']) || $newStatus != $oldStatus)
	{
		if (!isset($issueOptions['category']))
			$issueOptions['category'] = $row['id_category'];

		$colname = $oldStatus . '_' . $row['issue_type'];

		if (!empty($row['id_category']))
			$smcFunc['db_query']('', '
				UPDATE {$db_prefix}issue_category
				SET ' . $colname . ' = ' . $colname . ' - 1
				WHERE id_category = {int:category}',
				array(
					'category' => $row['id_category']
				)
			);

		$colname = $newStatus . '_' . $issueOptions['type'];

		if (!empty($issueOptions['category']))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issue_category
				SET ' . $colname . ' = ' . $colname . ' + 1
				WHERE id_category = {int:category}',
				array(
					'category' => $issueOptions['category']
				)
			);
	}

	$projectUpdates = array();

	if (!empty($issueOptions['type']) && $issueOptions['type'] != $row['issue_type'])
	{
		if (!empty($oldStatus))
			$projectUpdates[] = "{$oldStatus}_$row[issue_type] = {$oldStatus}_$row[issue_type] - 1";

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

	if (!isset($issueOptions['no_log']) && !empty($event_data))
	{
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
				$row['id_project'],
				$id_issue,
				$posterOptions['id'],
				'update_issue',
				$issueOptions['time'],
				serialize($event_data)
			),
			array()
		);

		return $smcFunc['db_insert_id']('{db_prefix}project_timeline', 'id_event');
	}

	return true;
}

?>