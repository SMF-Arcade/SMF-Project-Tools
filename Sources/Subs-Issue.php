<?php
/**********************************************************************************
* Subs-Issue.php                                                                  *
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

function loadIssue()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $issue, $project, $memberContext;

	if (empty($project) || empty($issue))
		return;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_project, i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.issue_type,
			i.id_comment_first, i.id_comment_last, i.id_event_mod, i.id_reporter, i.replies, i.private_issue,
			mem.id_member, mem.real_name,
			cat.id_category, cat.category_name,
			ver.id_version, ver.version_name, IFNULL(ver.member_groups, {string:any}) AS ver_member_groups,
			ver2.id_version AS vidfix, ver2.version_name AS vnamefix, ' . ($user_info['is_guest'] ? '0 AS new_from' : '(IFNULL(log.id_event, -1) + 1) AS new_from') . ',
			com.id_event_mod AS id_event_mod, com.post_time, com.edit_time, com.body, com.edit_name, com.edit_time,
			com.poster_ip
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:member} AND log.id_issue = i.id_issue)') . '
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}project_versions AS ver2 ON (ver2.id_version = i.id_version_fixed)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
		WHERE i.id_issue = {int:issue}
			AND i.id_project = {int:project}
		LIMIT 1',
		array(
			'member' => $user_info['id'],
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
			'id' => $row['id_member'],
			'name' => $row['real_name'],
			'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>',
		),
		'is_mine' => !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'],
		'type' => &$context['issue_types'][$row['issue_type']],
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
		'version_groups' => explode(',', $row['ver_member_groups']),
	);

	if ($row['ver_member_groups'] != '*' && count(array_intersect($user_info['groups'], $context['current_issue']['version_groups'])) == 0 && !$user_info['is_admin'])
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

	$id_event = createTimelineEvent($id_issue, $issueOptions['project'], 'new_issue', array('subject' => $issueOptions['subject']), $posterOptions, array('time' => $issueOptions['created']));

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
			id_project, subject, id_version, status, id_category,
			priority, issue_type, id_assigned, id_version_fixed, private_issue
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

	$issueUpdates = array();

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

	if (isset($issueOptions['version']) && $issueOptions['version'] != $row['id_version'])
	{
		$issueUpdates[] = 'id_version = {int:version}';

		$event_data['changes'][] = array(
			'version', $row['id_version'], $issueOptions['version'],
		);
	}

	if (isset($issueOptions['version_fixed']) && $issueOptions['version_fixed'] != $row['id_version_fixed'])
	{
		$issueUpdates[] = 'id_version_fixed = {int:version_fixed}';

		$event_data['changes'][] = array(
			'target_version', $row['id_version_fixed'], $issueOptions['version_fixed'],
		);
	}

	if (isset($issueOptions['comment_first']))
	{
		$issueUpdates[] = 'id_comment_first = {int:comment_first}';
	}

	if (isset($issueOptions['category']) && $issueOptions['category'] != $row['id_category'])
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
		$oldStatus = $context['issue_status'][$row['status']]['type'];
	else
		$oldStatus = '';

	if (!empty($issueOptions['status']))
		$newStatus = $context['issue_status'][$issueOptions['status']]['type'];
	else
		$newStatus = $oldStatus;

	if (!isset($issueOptions['type']))
		$issueOptions['type'] = $row['issue_type'];

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

	if (!empty($issueOptions['type']) && ($issueOptions['type'] != $row['issue_type'] || $oldStatus != $newStatus))
	{
		if (!empty($oldStatus))
			$projectUpdates[] = "{$oldStatus}_$row[issue_type] = {$oldStatus}_$row[issue_type] - 1";

		$projectUpdates[] = "{$newStatus}_$issueOptions[type] = {$newStatus}_$issueOptions[type] + 1";
	}

	if (!isset($issueOptions['project']))
		$issueOptions['project'] = $row['id_project'];

	if (!empty($projectUpdates))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}projects
			SET
				' . implode(',
				', $projectUpdates) . '
			WHERE id_project = {int:project}',
			array(
				'project' => $issueOptions['project'],
			)
		);

	if ($return_log)
		return $event_data;

	if (!isset($issueOptions['no_log']) && !empty($event_data))
		return createTimelineEvent($id_issue, $row['id_project'], 'update_issue', $event_data, $posterOptions, $issueOptions);

	return true;
}

function createTimelineEvent($id_issue, $id_project, $event_name, $event_data, $posterOptions, $issueOptions)
{
	global $smcFunc, $context;

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
			ORDER BY id_event DESC',
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

				foreach ($event_data['changes'] as $id => $data)
				{
					list ($field, $old_value, $new_value) = $data;

					$temp_changes[$field] = array($old_value, $new_value);
				}

				foreach ($event_data2['changes'] as $id => $data)
				{
					list ($field, $old_value, $new_value) = $data;

					if (!isset($temp_changes[$field]))
						$temp_changes[$field] = array($old_value, $new_value);
					else
					{
						$temp_changes[$field][1] = $new_value;

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

	$issue = array(
		'id' => $id_issue,
	);

	if ($event_name == 'update_issue')
		sendIssueNotification($issue, array(), $event_data, $event_name, $posterOptions['id']);

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

	$event_data = array(
		'subject' => $row['subject'],
		'changes' => array(),
	);

	if (!empty($row['status']))
		$status = $context['issue_status'][$row['status']]['type'];
	else
		$status = '';

	$projectUpdates = array(
		"{$status}_$row[issue_type] = {$status}_$row[issue_type] - 1"
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
		SET id_version = {int:version}
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $id_issue,
			'version' => $row['id_version'],
		)
	);

	$id_event = createTimelineEvent($id_issue, $row['id_project'], 'delete_issue', $event_data, $posterOptions, array('time' => time()));

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
		$id_event = createTimelineEvent($id_issue, $id_project, 'new_comment', $event_data, $posterOptions, array('time' => $time));
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

	// Mark read if user wants to
	if (!empty($commentOptions['mark_read']) && !$user_info['is_guest'])
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_issues',
			array(
				'id_issue' => 'int',
				'id_member' => 'int',
				'id_event' => 'int',
			),
			array(
				$id_issue,
				$user_info['id'],
				$id_event,
			),
			array('id_issue', 'id_member')
		);
	}

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

	if (isset($commentOptions['no_log']))
		return true;

	// Write to timeline unless it's not wanted (on new issue for example)
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
			$row['id_project'],
			$id_issue,
			$posterOptions['id'],
			$posterOptions['name'],
			$posterOptions['email'],
			$posterOptions['ip'],
			'edit_comment',
			time(),
			serialize(array('subject' => $row['subject'], 'comment' => $id_comment))
		),
		array()
	);

	return true;
}

function getIssueList($start = 0, $num_issues, $order = 'i.updated DESC', $where = '1 = 1', $queryArray = array())
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.issue_type, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			i.id_reporter, IFNULL(mr.real_name, {string:empty}) AS reporter,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_version, IFNULL(ver.version_name, {string:empty}) AS version_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
			' . ($user_info['is_guest'] ? '0 AS new_from' : '(IFNULL(log.id_comment, -1) + 1) AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:member} AND log.id_issue = i.id_issue)') . '
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = i.id_version)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
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
			'member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
		), $queryArray)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . project_get_url(array('issue' => $row['id_issue'] . '.0')) . '">' . $row['subject'] . '</a>',
			'href' => project_get_url(array('issue' => $row['id_issue'] . '.0')),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'version' => array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'link' => !empty($row['version_name']) ? '<a href="' . project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'version' => $row['id_version'])) . '">' . $row['version_name'] . '</a>' : ''
			),
			'type' => $row['issue_type'],
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
			'new_href' => project_get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from'])) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

?>