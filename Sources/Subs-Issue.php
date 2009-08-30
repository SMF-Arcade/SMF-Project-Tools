<?php
/**********************************************************************************
* Subs-Issue.php                                                                  *
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

function loadIssue()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $issue, $project, $memberContext;

	if (empty($project) || empty($issue))
		return;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_project, i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.id_tracker,
			i.id_comment_first, i.id_comment_last, i.id_event_mod, i.id_reporter, i.replies, i.private_issue,
			i.versions, i.versions_fixed,
			mem.id_member, mem.real_name, cat.id_category, cat.category_name,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . ',
			com.id_event_mod AS id_event_mod_com, com.post_time, com.edit_time, com.body, com.edit_name, com.edit_time,
			com.poster_ip
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = i.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE i.id_issue = {int:issue}
			AND i.id_project = {int:project}
		LIMIT 1',
		array(
			'current_member' => $user_info['id'],
			'issue' => $issue,
			'project' => $project,
			'any' => '*',
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
	{
		$context['project_error'] = 'issue_not_found';

		return;
	}

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	// Load reporter
	loadMemberData(array($row['id_reporter']));
	loadMemberContext($row['id_reporter']);

	$memberContext[$row['id_reporter']]['can_view_profile'] = allowedTo('profile_view_any') || ($row['id_member'] == $user_info['id'] && allowedTo('profile_view_own'));

	$type = !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'] ? 'own' : 'any';

	// Prepare issue array
	$context['current_issue'] = array(
		'id' => $row['id_issue'],
		'name' => $row['subject'],
		'href' => project_get_url(array('issue' => $row['id_issue'] . '.0')),
		'details' => array(
			'id' => $row['id_comment_first'],
			'time' => timeformat($row['post_time']),
			'body' => parse_bbc($row['body']),
			'ip' => $row['poster_ip'],
			'modified' => array(
				'time' => timeformat($row['edit_time']),
				'timestamp' => forum_time(true, $row['edit_time']),
				'name' => $row['edit_name'],
			),
			'can_see_ip' => allowedTo('moderate_forum') || ($row['id_member'] == $user_info['id'] && !empty($user_info['id'])),
			'can_remove' => projectAllowedTo('delete_comment_' . $type),
			'can_edit' => projectAllowedTo('edit_comment_' . $type),
			'first_new' => $row['id_event_mod'] > $row['new_from'],
		),
		'category' => array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'link' => '<a href="' . project_get_url(array('project' => $project, 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>',
		),
		'versions' => getVersions(explode(',', $row['versions'])),
		'versions_fixed' => getVersions(explode(',', $row['versions_fixed'])),
		'reporter' => &$memberContext[$row['id_reporter']],
		'assignee' => array(
			'id' => $row['id_member'],
			'name' => $row['real_name'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
		),
		'is_mine' => !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'],
		'tracker' => &$context['issue_trackers'][$row['id_tracker']],
		'status' => &$context['issue_status'][$row['status']],
		'priority_num' => $row['priority'],
		'priority' => $context['issue']['priority'][$row['priority']],
		'created' => timeformat($row['created']),
		'updated' => timeformat($row['updated']),
		'new_from' => $row['new_from'],
		'comment_first' => $row['id_comment_first'],
		'comment_last' => $row['id_comment_last'],
		'id_event_mod' => $row['id_event_mod'],
		'replies' => $row['replies'],
		'private' => !empty($row['private_issue']),
	);

	if (!$user_info['is_admin'] && count(array_intersect(array_keys($context['current_issue']['versions']), $user_info['project_allowed_versions'])) == 0)
		$context['project_error'] = 'issue_not_found';
	// If this is private issue are you allowed to see it?
	elseif ($context['current_issue']['private'] && !$user_info['is_admin'] && !$context['project']['is_developer'] && $user_info['id'] != $row['id_reporter'] && !projectAllowedTo('issue_view_private'))
		$context['project_error'] = 'issue_not_found';

	if (!$user_info['is_guest'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT sent
			FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}
			LIMIT 1',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
		$context['is_subscribed'] = $smcFunc['db_num_rows']($request) != 0;
		if ($context['is_subscribed'])
		{
			list ($sent) = $smcFunc['db_fetch_row']($request);
			if (!empty($sent))
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}log_notify_projects
					SET sent = {int:is_sent}
					WHERE id_issue = {int:issue}
						AND id_member = {int:current_member}',
					array(
						'issue' => $issue,
						'current_member' => $user_info['id'],
						'is_sent' => 0,
					)
				);
			}
		}
		$smcFunc['db_free_result']($request);
	}
}

function createIssue($issueOptions, &$posterOptions)
{
	global $smcFunc;

	if (empty($issueOptions['created']))
		$issueOptions['created'] = time();

	$smcFunc['db_insert']('insert',
		'{db_prefix}issues',
		array(
			'id_project' => 'int',
			'subject' => 'string-100',
			'created' => 'int',
			'id_reporter' => 'int',
		),
		array(
			$issueOptions['project'],
			$issueOptions['subject'],
			$issueOptions['created'],
			$posterOptions['id'],
		),
		array()
	);

	$id_issue = $smcFunc['db_insert_id']('{db_prefix}issues', 'id_issue');

	$id_event = createTimelineEvent($id_issue, $issueOptions['project'], 'new_issue', array('subject' => $issueOptions['subject']), $posterOptions,
		array(
			'time' => $issueOptions['created'],
			'mark_read' => !empty($issueOptions['mark_read']),
		)
	);

	$id_comment = createComment(
		$issueOptions['project'],
		$id_issue,
		array(
			'no_log' => true,
			'body' => $issueOptions['body'],
			'mark_read' => !empty($issueOptions['mark_read']),
		),
		$posterOptions,
		array('id_event' => $id_event)
	);

	$issueOptions['comment_first'] = $id_comment;

	unset($issueOptions['project'], $issueOptions['subject'], $issueOptions['body'], $issueOptions['created']);
	$issueOptions['no_log'] = true;

	if (!empty($issueOptions))
		updateIssue($id_issue, $issueOptions, $posterOptions);

	return $id_issue;
}

function updateIssue($id_issue, $issueOptions, $posterOptions, $return_log = false)
{
	global $smcFunc, $context;

	if (!isset($context['issue_status']))
		trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);

	$request = $smcFunc['db_query']('', '
		SELECT
			id_project, subject, status, id_category,
			priority, id_tracker, id_assigned, private_issue, versions, versions_fixed
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

	$event_data = array(
		'subject' => isset($issueOptions['subject']) ? $issueOptions['subject'] : $row['subject'],
		'changes' => array(),
	);
	
	// Make versions and fixed versions array
	$row['versions'] = explode(',', $row['versions']);
	$row['versions_fixed'] = explode(',', $row['versions_fixed']);

	$issueUpdates = array();

	// Make sure project exists always
	if (!isset($issueOptions['project']))
		$issueOptions['project'] = $row['id_project'];

	if (isset($issueOptions['project']) && $issueOptions['project'] != $row['id_project'])
	{
		$issueUpdates[] = 'id_project = {int:project}';
		$issueOptions['project'] = $issueOptions['project'];

		$event_data['changes'][] = array(
			'project', $row['id_project'], $issueOptions['project']
		);
	}

	if (isset($issueOptions['private']) && $issueOptions['private'] != $row['private_issue'])
	{
		$issueUpdates[] = 'private_issue = {int:private}';
		$issueOptions['private'] = !empty($issueOptions['private']) ? 1 : 0;

		$event_data['changes'][] = array(
			'view_status', $row['private_issue'], $issueOptions['private']
		);
	}

	if (!empty($issueOptions['subject']) && $issueOptions['subject'] != $row['subject'])
	{
		$issueUpdates[] = 'subject = {string:subject}';

		$event_data['changes'][] = array(
			'rename', $row['subject'], $issueOptions['subject']
		);
	}

	if (!empty($issueOptions['status']) && $issueOptions['status'] != $row['status'])
	{
		$issueUpdates[] = 'status = {int:status}';

		$event_data['changes'][] = array(
			'status', $row['status'], $issueOptions['status'],
		);
	}

	if (isset($issueOptions['assignee']) && $issueOptions['assignee'] != $row['id_assigned'])
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

	if (isset($issueOptions['versions']) && $issueOptions['versions'] != $row['versions'])
	{
		$issueUpdates[] = 'versions = {string:versions}';
		
		if (empty($issueOptions['versions']))
			$issueOptions['versions'] = array(0);
		
		$issueOptions['versions'] = implode(',', $issueOptions['versions']);

		$event_data['changes'][] = array(
			'version', implode(',', $row['versions']), $issueOptions['versions'],
		);
	}

	if (isset($issueOptions['versions_fixed']) && $issueOptions['versions_fixed'] != $row['versions_fixed'])
	{
		$issueUpdates[] = 'versions_fixed = {string:versions_fixed}';
		
		if (empty($issueOptions['versions_fixed']))
			$issueOptions['versions_fixed'] = array(0);
	
		$issueOptions['versions_fixed'] = implode(',', $issueOptions['versions_fixed']);

		$event_data['changes'][] = array(
			'target_version', implode(',', $row['versions_fixed']), $issueOptions['versions_fixed'],
		);
	}

	if (isset($issueOptions['comment_first']))
		$issueUpdates[] = 'id_comment_first = {int:comment_first}';

	if (isset($issueOptions['category']) && $issueOptions['category'] != $row['id_category'])
	{
		$issueUpdates[] = 'id_category = {int:category}';

		$event_data['changes'][] = array(
			'category', $row['id_category'], $issueOptions['category'],
		);
	}

	if (!empty($issueOptions['tracker']) && $issueOptions['tracker'] != $row['id_tracker'])
	{
		$issueUpdates[] = 'id_tracker = {int:tracker}';

		$event_data['changes'][] = array(
			'tracker', $row['id_tracker'], $issueOptions['tracker'],
		);
	}

	if (!empty($row['status']))
		$oldStatus = $context['issue_status'][$row['status']]['type'];
	else
		$oldStatus = '';

	if (!empty($issueOptions['status']))
		$newStatus = $context['issue_status'][$issueOptions['status']]['type'];
	else
		$newStatus = $oldStatus;

	if (!isset($issueOptions['tracker']))
		$issueOptions['tracker'] = $row['id_tracker'];

	// Updates needed?
	if (empty($issueUpdates))
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

	$projectUpdates = array();

	if (!empty($issueOptions['tracker']) && ($issueOptions['tracker'] != $row['id_tracker'] || $oldStatus != $newStatus))
	{
		if (!empty($row['id_tracker']))
			$oldTracker = $context['issue_trackers'][$row['id_tracker']]['column_' . $oldStatus];
		
		$newTracker = $context['issue_trackers'][$issueOptions['tracker']]['column_' . $newStatus];

		if (!empty($oldStatus))
			$projectUpdates[$row['id_project']][] = "$oldTracker = $oldTracker - 1";

		$projectUpdates[$issueOptions['project']][] = "$newTracker = $newTracker + 1";
	}

	if (!empty($projectUpdates))
		foreach ($projectUpdates as $id => $updates)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}projects
				SET
					' . implode(',
					', $updates) . '
				WHERE id_project = {int:project}',
				array(
					'project' => $id,
				)
			);
			
	// Update id_project in timeline if needed
	if ($row['id_project'] != $issueOptions['project'])
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_timeline
			SET id_project = {int:project}
			WHERE id_issue = {int:issue}',
			array(
				'project' => $issueOptions['project'],
				'issue' => $id_issue,
			)
		);

	if ($return_log)
		return $event_data;

	if (!isset($issueOptions['no_log']) && !empty($event_data))
		return createTimelineEvent($id_issue, $issueOptions['project'], 'update_issue', $event_data, $posterOptions, $issueOptions);

	return true;
}

function createTimelineEvent($id_issue, $id_project, $event_name, $event_data, $posterOptions, $issueOptions)
{
	global $smcFunc, $context, $user_info;

	$id_event = 0;

	if ($posterOptions['id'] != 0 && ($event_name == 'update_issue' || $event_name == 'new_comment'))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_event, event, event_data
			FROM {db_prefix}project_timeline
			WHERE id_project = {int:project}
				AND id_issue = {int:issue}
				AND event IN({array_string:event})
				AND id_member = {int:member}
				AND event_time > {int:event_time}
			ORDER BY id_event DESC
			LIMIT 1',
			array(
				'issue' => $id_issue,
				'project' => $id_project,
				'member' => $posterOptions['id'],
				// Update issue can be merged with new_comment and update_issue
				// new comment can be merged with update_issue
				'event' => $event_name == 'update_issue' ? array('new_comment', 'update_issue') : array('update_issue'),
				'event_time' => time() - 120,
			)
		);

		if ($smcFunc['db_num_rows']($request) > 0)
		{
			list ($id_event, $event_name2, $event_data2) = $smcFunc['db_fetch_row']($request);

			$event_data2 = unserialize($event_data2);

			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}project_timeline
				WHERE id_event = {int:event}',
				array(
					'event' => $id_event,
				)
			);

			// 'new_comment' > 'update_issue'
			if ($event_name2 == 'new_comment' || $event_name == 'new_comment')
				$event_name = 'new_comment';

			if (isset($event_data2['changes']) && isset($event_data['changes']))
			{
				//$new_changes = array_merge($event_data['changes'], $event_data2['changes']);

				$temp_changes = array();

				// Add old changes to array first
				foreach ($event_data2['changes'] as $id => $data)
				{
					list ($field, $old_value, $new_value) = $data;

					$temp_changes[$field] = array($old_value, $new_value);
				}

				// Then new changes
				foreach ($event_data['changes'] as $id => $data)
				{
					list ($field, $old_value, $new_value) = $data;

					if (!isset($temp_changes[$field]))
						$temp_changes[$field] = array($old_value, $new_value);
					else
					{
						$temp_changes[$field][1] = $new_value;

						// Change was reversed? Then remove it for good...
						if ($temp_changes[$field][0] == $temp_changes[$field][1])
							unset($temp_changes[$field]);
					}
				}

				// Changed everything back to orignal?
				if (empty($temp_changes))
					return;

				foreach ($temp_changes as $field => $data)
					$new_changes[] = array($field, $data[0], $data[1]);
			}
			// This is easier :P
			elseif (isset($event_data2['changes']))
				$new_changes = $event_data2['changes'];
			elseif (isset($event_data['changes']))
				$new_changes = $event_data['changes'];

			$event_data['changes'] = $new_changes;
		}

		$smcFunc['db_free_result']($request);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_timeline',
		array(
			'id_project' => 'int',
			'id_issue' => 'int',
			'id_member' => 'int',
			'poster_name' => 'string',
			'poster_email' => 'string',
			'poster_ip' => 'string-60',
			'event' => 'string',
			'event_time' => 'int',
			'event_data' => 'string',
		),
		array(
			$id_project,
			$id_issue,
			$posterOptions['id'],
			$posterOptions['name'],
			$posterOptions['email'],
			$posterOptions['ip'],
			$event_name,
			$issueOptions['time'],
			serialize($event_data)
		),
		array()
	);

	$id_event_new = $smcFunc['db_insert_id']('{db_prefix}project_timeline', 'id_event');

	// Update latest event id
	updateSettings(array('project_maxEventID' => $id_event_new));

	// Update Issues table too
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET id_event_mod = {int:event}
		WHERE id_issue = {int:issue}',
		array(
			'event' => $id_event_new,
			'issue' => $id_issue,
		)
	);

	// And projects
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}projects
		SET id_event_mod = {int:event}
		WHERE id_project = {int:project}',
		array(
			'event' => $id_event_new,
			'project' => $id_project,
		)
	);

	// Mark read if asked to
	if (!empty($issueOptions['mark_read']) && !$user_info['is_guest'])
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_issues',
			array(
				'id_project' => 'int',
				'id_issue' => 'int',
				'id_member' => 'int',
				'id_event' => 'int',
			),
			array(
				$id_project,
				$id_issue,
				$user_info['id'],
				$id_event_new,
			),
			array('id_issue', 'id_member')
		);
	}

	if ($event_name == 'update_issue')
		sendIssueNotification(array('id' => $id_issue, 'project' => $context['project']['id'],), array(), $event_data, $event_name, $posterOptions['id']);

	if (empty($id_event))
		return $id_event_new;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_comments
		SET id_event = {int:new_event}
		WHERE id_event = {int:event}',
		array(
			'new_event' => $id_event_new,
			'event' => $id_event,
		)
	);

	return $id_event_new;
}

function deleteIssue($id_issue, $posterOptions)
{
	global $smcFunc, $db_prefix, $context;

	if (!isset($context['issue_status']))
		trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);

	$request = $smcFunc['db_query']('', '
		SELECT
			id_project, id_tracker, subject, versions, status, id_category,
			priority, id_assigned
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

	$event_data = array(
		'subject' => $row['subject'],
		'changes' => array(),
	);

	if (!empty($row['status']))
		$status = $context['issue_status'][$row['status']]['type'];
	else
		$status = '';

	$curTracker = $context['issue_trackers'][$row['id_tracker']]['column_' . $status];

	$projectUpdates = array(
		"$curTracker = $curTracker - 1"
	);

	if (!empty($projectUpdates) && !empty($status))
		$smcFunc['db_query']('', "
			UPDATE {$db_prefix}projects
			SET
				" . implode(',
				', $projectUpdates) . "
			WHERE id_project = {int:project}",
			array(
				'project' => $row['id_project'],
			)
		);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}issues
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}issue_comments
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue,
		)
	);

	// Update Timeline entries to make sure uses who have no permission won't see it
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}project_timeline
		SET versions = {string:versions}
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue,
			'versions' => $row['versions'],
		)
	);

	// Remove notifications of this Issue
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_notify_projects
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue,
		)
	);

	if ($posterOptions !== false)
		$id_event = createTimelineEvent($id_issue, $row['id_project'], 'delete_issue', $event_data, $posterOptions, array('time' => time()));
	else
		return true;
	
	return $id_event;
}

function createComment($id_project, $id_issue, $commentOptions, $posterOptions, $event_data = array())
{
	global $smcFunc, $db_prefix, $context, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT subject, id_comment_first
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

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_comments',
		array(
			'id_issue' => 'int',
			'body' => 'string',
			'post_time' => 'int',
			'id_member' => 'int',
			'poster_name' => 'string-60',
			'poster_email' => 'string-256',
			'poster_ip' => 'string-60',
		),
		array(
			$id_issue,
			$commentOptions['body'],
			time(),
			$posterOptions['id'],
			$posterOptions['name'],
			$posterOptions['email'],
			$posterOptions['ip']
		),
		array()
	);

	$id_comment = $smcFunc['db_insert_id']('{db_prefix}issue_comments', 'id_comment');
	$time = time();

	// Make event
	$id_event = 0;

	if (!isset($commentOptions['no_log']))
	{
		$event_data['subject'] = $row['subject'];
		$event_data['comment'] = $id_comment;
		$id_event = createTimelineEvent($id_issue, $id_project, 'new_comment', $event_data, $posterOptions, array('time' => $time, 'mark_read' => !empty($commentOptions['mark_read'])));
	}
	elseif (isset($event_data['id_event']))
		$id_event = $event_data['id_event'];
	// Temp
	else
		fatal_error('Missing id_event from createComment call');

	// !!! Is updating id_event_mod needed?

	// Update Issues table too
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET
			replies = replies + {int:rpl}, updated = {int:time},
			id_event_mod = {int:event}, id_comment_last = {int:comment},
			id_updater = {int:current_user}
		WHERE id_issue = {int:issue}',
		array(
			'event' => $id_event,
			'current_user' => $user_info['id'],
			'issue' => $id_issue,
			'time' => $time,
			'comment' => $id_comment,
			'rpl' => empty($row['id_comment_first']) ? 0 : 1,
		)
	);

	// And projects
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}projects
		SET id_event_mod = {int:event}
		WHERE id_project = {int:project}',
		array(
			'event' => $id_event,
			'project' => $id_project,
		)
	);

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}projects
		SET id_event_mod = {int:event}
		WHERE id_project = {int:project}',
		array(
			'event' => $id_event,
			'project' => $id_project,
		)
	);

	// Set this for read marks
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_comments
		SET id_event_mod = {int:event},
			id_event = {int:event}
		WHERE id_comment = {int:comment}',
		array(
			'event' => $id_event,
			'comment' => $id_comment,
		)
	);

	return $id_comment;
}

function modifyComment($id_comment, $id_issue, $commentOptions, $posterOptions)
{
	global $smcFunc, $db_prefix, $context;

	$request = $smcFunc['db_query']('', '
		SELECT subject, id_project, id_comment_first
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

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_comments
		SET
			edit_time = {int:edit_time},
			edit_name = {string:edit_name},
			body = {string:body}
		WHERE id_comment = {int:comment}',
		array(
			'comment' => $id_comment,
			'edit_time' => time(),
			'edit_name' => $posterOptions['name'],
			'body' => $commentOptions['body'],
		)
	);

	if (!isset($commentOptions['no_log']))
		logAction('project_modify_comment', array('comment' => $id_comment));

	return true;
}

function getIssueList($start = 0, $num_issues, $order = 'i.updated DESC', $where = '1 = 1', $queryArray = array())
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.id_tracker, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			i.id_reporter, IFNULL(mr.real_name, {string:empty}) AS reporter,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater, i.versions, i.versions_fixed,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)' . (empty($project) ? '
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})' : '') . '
		WHERE ' . (!empty($project) ? '{query_see_issue_project}
			AND i.id_project = {int:project}' : '{query_see_project}
			AND {query_see_issue}') . '
			AND ('. $where . ')
		ORDER BY ' . $order . '
		LIMIT {int:start}, {int:num_issues}',
		array_merge(array(
			'project' => $project,
			'empty' => '',
			'start' => 0,
			'num_issues' => $num_issues,
			'current_member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
		), $queryArray)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . project_get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>',
			'href' => project_get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions'])),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed'])),
			'tracker' => &$context['issue_trackers'][$row['id_tracker']],
			'updated' => timeformat($row['updated']),
			'created' => timeformat($row['created']),
			'status' => &$context['issue_status'][$row['status']],
			'reporter' => array(
				'id' => $row['id_reporter'],
				'name' => empty($row['reporter']) ? $txt['issue_guest'] : $row['reporter'],
				'link' => empty($row['reporter']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter'] . '</a>',
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
			'new_href' => project_get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from']), $row['id_project']) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

function link_tags(&$tag, $key, $baseurl)
{
	if (is_array($baseurl))
		$tag = '<a href="' . project_get_url(array_merge($baseurl, array('tag' => urlencode($tag)))). '">' . $tag . '</a>';
	else
		$tag = '<a href="' . $baseurl . ';tag=' . urlencode($tag) . '">' . $tag . '</a>';
}

function getVersions($versions)
{
	global $context;
	
	$return = array();
	
	foreach ($versions as $ver)
	{
		if (!empty($context['versions_id'][$ver]))
			$return[$ver] = $context['versions'][$context['versions_id'][$ver]]['sub_versions'][$ver];
		elseif (!empty($context['versions'][$ver]))
			$return[$ver] = $context['versions'][$ver];
	}
	
	return $return;
}

?>