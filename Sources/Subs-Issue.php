<?php
/**
 * Functions for issue tracker
 *
 * @package issuetracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

/**
 * Load issue based on global variable $issue
 */
function loadIssue()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $issue, $project, $memberContext;

	if (empty($project) || empty($issue))
		return;

	if (!ProjectTools_IssueTracker_Issue::getCurrent() || !ProjectTools_IssueTracker_Issue::getCurrent()->canSee())
	{
		$context['project_error'] = 'issue_not_found';
		return;
	}

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

/**
 * Inserts new issue to database
 * @param array $issueOptions
 * @param array &$posterOptions
 * @return int ID of issue created
 */
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

	list ($id_comment, $issueOptions['event_first']) = createComment(
		$issueOptions['project'],
		$id_issue,
		array(
			'id_event' => $id_event,
			'no_log' => true,
			'body' => $issueOptions['body'],
			'mark_read' => !empty($issueOptions['mark_read']),
		),
		$posterOptions,
		array()
	);

	unset($issueOptions['project'], $issueOptions['subject'], $issueOptions['body'], $issueOptions['created']);
	$issueOptions['no_log'] = true;

	if (!empty($issueOptions))
		updateIssue($id_issue, $issueOptions, $posterOptions);

	return $id_issue;
}

/**
 * Updates issue in database
 * @param int $id_issue ID of issue to update
 * @param array $issueOptions
 * @param array &$posterOptions
 * @param boolean $return_log Return event data instead of inserting into database
 */
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
	
	$oldVersions = array_merge($row['versions'], $row['versions_fixed']);
	$newVersions = array();

	if (isset($issueOptions['versions']) && $issueOptions['versions'] != $row['versions'])
	{
		$issueUpdates[] = 'versions = {string:versions}';
		
		if (empty($issueOptions['versions']))
			$issueOptions['versions'] = array(0);
	
		$newVersions = array_merge($newVersions, $issueOptions['versions']);
		$issueOptions['versions'] = implode(',', $issueOptions['versions']);

		$event_data['changes'][] = array(
			'version', implode(',', $row['versions']), $issueOptions['versions'],
		);
	}
	else
		$newVersions = array_merge($newVersions, $row['versions']);

	if (isset($issueOptions['versions_fixed']) && $issueOptions['versions_fixed'] != $row['versions_fixed'])
	{
		$issueUpdates[] = 'versions_fixed = {string:versions_fixed}';
		
		if (empty($issueOptions['versions_fixed']))
			$issueOptions['versions_fixed'] = array(0);
	
		$newVersions = array_merge($newVersions, $issueOptions['versions_fixed']);
		$issueOptions['versions_fixed'] = implode(',', $issueOptions['versions_fixed']);

		$event_data['changes'][] = array(
			'target_version', implode(',', $row['versions_fixed']), $issueOptions['versions_fixed'],
		);
	}
	else
		$newVersions = array_merge($newVersions, $row['versions_fixed']);

	if (isset($issueOptions['event_first']))
		$issueUpdates[] = 'id_issue_event_first = {int:event_first}';

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
		return !$return_log ? true : $event_data;

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

	// Update Issue Counts from project
	$projectUpdates = array();

	// Which tracker it belonged to and will belong in future?
	if (!empty($row['id_tracker']))
		$oldTracker = $context['issue_trackers'][$row['id_tracker']]['column_' . $oldStatus];
	$newTracker = $context['issue_trackers'][$issueOptions['tracker']]['column_' . $newStatus];
		
	if (!empty($issueOptions['tracker']) && ($issueOptions['tracker'] != $row['id_tracker'] || $oldStatus != $newStatus))
	{
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
			
	// If tracker hasn't changed remove values that doesn't need to be changed
	if (isset($oldTracker) && $oldTracker == $newTracker)
	{
		$oldVersions = array_diff($oldVersions, $newVersions);
		$newVersions = array_diff($newVersions, $oldVersions);
	}
			
	// Update issue counts in versions
	if (isset($oldTracker) && !empty($oldVersions))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET {raw:tracker} = {raw:tracker} - 1
			WHERE id_version IN({array_int:versions})',
			array(
				'tracker' => $oldTracker,
				'versions' => $oldVersions,
			)
		);
	if (!empty($newVersions))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET {raw:tracker} = {raw:tracker} + 1
			WHERE id_version IN({array_int:versions})',
			array(
				'tracker' => $newTracker,
				'versions' => $newVersions,
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

/**
 *
 *
 */
function createIssueEvent($id_issue, $id_comment, $posterOptions, $event_data)
{
	global $smcFunc;
	
	if ($posterOptions['id'] != 0)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_issue_event, changes
			FROM {db_prefix}issue_events
			WHERE id_issue = {int:issue}
				AND id_member = {int:member}' . (!empty($id_comment) ? '
				AND id_comment = 0' : '') . '
				AND event_time > {int:event_time}
			ORDER BY id_event DESC
			LIMIT 1',
			array(
				'issue' => $id_issue,
				'member' => $posterOptions['id'],
				// TODO: Make time configurable
				'event_time' => time() - 120,
			)
		);

		if ($smcFunc['db_num_rows']($request) > 0)
		{
			list ($id_issue_event, $event_data2) = $smcFunc['db_fetch_row']($request);

			$event_data2 = unserialize($event_data2);

			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}issue_events
				WHERE id_issue_event = {int:issue_event}',
				array(
					'issue_event' => $id_issue_event,
				)
			);

			if (isset($event_data2['changes']) && isset($event_data['changes']))
			{
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
					// Tags field has special format (array removed, array added)
					elseif ($field == 'tags')
					{
						$rem_prev = $temp_changes[$field][0];
						$rem_cur = $old_value;
						
						$add_prev = $temp_changes[$field][1];
						$add_cur = $new_value;
						
						// Added tags
						$temp_changes[$field][1] = array_merge(
							array_diff($add_cur, $rem_prev), // Addid in current - removed in prev (reverting)
							array_diff($add_prev, $rem_cur) // Added in prev - removed in current (reverting)
						);
						$temp_changes[$field][0] = array_merge(
							array_diff($rem_prev, $add_cur), // Removed in prev - added in current
							array_diff($rem_cur, $add_prev) // Removed in current - added in previous
						);
						
						// Change was reversed? Then remove it for good...
						if (empty($temp_changes[$field][0]) && empty($temp_changes[$field][1]))
							unset($temp_changes[$field]);
					}
					else
					{
						$temp_changes[$field][1] = $new_value;

						// Change was reversed? Then remove it for good...
						if ($temp_changes[$field][0] == $temp_changes[$field][1])
							unset($temp_changes[$field]);
					}
				}

				// Changed everything back to orignal?
				if (empty($temp_changes) && empty($id_comment))
					return;
				elseif (!empty($temp_changes))
					foreach ($temp_changes as $field => $data)
						$new_changes[] = array($field, $data[0], $data[1]);
			}
			// This is easier
			elseif (isset($event_data2['changes']))
				$new_changes = $event_data2['changes'];
			elseif (isset($event_data['changes']))
				$new_changes = $event_data['changes'];

			if (!empty($new_changes))
				$event_data['changes'] = $new_changes;
			else
				unset($event_data['changes']);
		}

		$smcFunc['db_free_result']($request);
	}
	
	// Create issue event
	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_events',
		array(
			'id_issue' => 'int',
			'id_member' => 'int',
			'id_comment' => 'int',
			'event_time' => 'int',
			'poster_name' => 'string-60',
			'poster_email' => 'string-256',
			'poster_ip' => 'string-60',
			'changes' => 'string',
		),
		array(
			$id_issue,
			$posterOptions['id'],
			$id_comment,
			time(),
			$posterOptions['username'],
			$posterOptions['email'],
			$posterOptions['ip'],
			serialize($event_data),
		),
		array()
	);

	return $smcFunc['db_insert_id']('{db_prefix}issue_events', 'id_comment');
}

/**
 * Creates event in timeline
 * @param int $id_issue ID of issue
 * @param int $id_project ID of project issue is in
 * @param string $event_name Name of event
 * @param array $event_data 
 * @param array $posterOptions
 * @param array $issueOptions
 */
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
			$posterOptions['username'],
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
		SET id_event_mod = {int:event}, updated = {int:time}
		WHERE id_issue = {int:issue}',
		array(
			'event' => $id_event_new,
			'issue' => $id_issue,
			'time' => $issueOptions['time'],
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
		
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues AS i
				LEFT JOIN {db_prefix}log_projects AS log ON (log.id_member = {int:current_member}
					AND log.id_project = {int:current_project})
			WHERE i.id_project = {int:current_project}
				AND i.id_event_mod > log.id_event',
			array(
				'current_project' => $id_project,
				'current_member' => $user_info['id'],
			)
		);
			
		list ($num_unread_issues) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		
		// Mark project read if there's only one unread issue
		if ($num_unread_issues <= 1)
			$smcFunc['db_insert']('replace',
				'{db_prefix}log_projects',
				array(
					'id_project' => 'int',
					'id_member' => 'int',
					'id_event' => 'int',
				),
				array(
					$id_project,
					$user_info['id'],
					$id_event_new,
				),
				array('id_project', 'id_member')
			);
	}

	if ($event_name == 'update_issue')
		sendIssueNotification(array('id' => $id_issue, 'project' => ProjectTools_Project::getCurrent()->id,), array(), $event_data, $event_name, $posterOptions['id']);

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

/**
 * Delete issue from database
 * @param int $id_issue ID of issue
 * @param array $posterOptions posterOptions for user deleting issue
 * @param boolean $log_delete Whatever to log delete
 * @return mixed ID of event or true if not logged in success. False on error
 */
function deleteIssue($id_issue, $posterOptions, $log_delete = true)
{
	global $smcFunc, $db_prefix, $context;

	if (!isset($context['issue_status']))
		trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);

	$request = $smcFunc['db_query']('', '
		SELECT
			id_project, id_tracker, subject, versions, versions_fixed, status, id_category,
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
		
	// Remove issue from versions too
	$versions = array_merge(explode(',', $row['versions']), explode(',', $row['versions_fixed']));
		
	if (!empty($versions))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET {raw:tracker} = {raw:tracker} - 1
			WHERE id_version IN({array_int:versions})',
			array(
				'tracker' => $curTracker,
				'versions' => $versions,
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

	if ($log_delete && $posterOptions !== false)
		$id_event = createTimelineEvent($id_issue, $row['id_project'], 'delete_issue', $event_data, $posterOptions, array('time' => time()));
	else
		return true;
	
	return $id_event;
}

/**
 * Insert comment into database
 * @param int $id_project ID of project
 * @param int $id_issue ID of issue
 * @param array $commentOptions
 * @param array $posterOptions
 * @param array $event_data
 * @return mixed ID of comment on success. false on error.
 */ 
function createComment($id_project, $id_issue, $commentOptions, $posterOptions, $event_data = array())
{
	global $smcFunc, $db_prefix, $context, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT subject, id_issue_event_first
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
			'body' => 'string',
		),
		array(
			$commentOptions['body'],
		),
		array()
	);

	$id_comment = $smcFunc['db_insert_id']('{db_prefix}issue_comments', 'id_comment');
	$time = time();
	
	// Create issue event
	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_events',
		array(
			'id_issue' => 'int',
			'id_member' => 'int',
			'id_comment' => 'int',
			'event_time' => 'int',
			'poster_name' => 'string-60',
			'poster_email' => 'string-256',
			'poster_ip' => 'string-60',
			'changes' => 'string',
		),
		array(
			$id_issue,
			$posterOptions['id'],
			$id_comment,
			$time,
			$posterOptions['username'],
			$posterOptions['email'],
			$posterOptions['ip'],
			serialize($event_data),
		),
		array()
	);

	$id_issue_event = $smcFunc['db_insert_id']('{db_prefix}issue_events', 'id_comment');
	
	// Make event
	$id_event = 0;

	if (!isset($commentOptions['no_log']))
		$id_event = createTimelineEvent($id_issue, $id_project, 'new_comment', array('subject' => $row['subject'], 'comment' => $id_comment), $posterOptions, array('time' => $time, 'mark_read' => !empty($commentOptions['mark_read'])));
	elseif (isset($commentOptions['id_event']))
		$id_event = $commentOptions['id_event'];
	// Temp
	else
		trigger_error('Missing id_event from createComment call', E_FATAL_ERROR);
		
	// Set id_event in issue_events
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_events
		SET id_event = {int:event}
		WHERE id_issue_event = {int:issue_event}',
		array(
			'issue_event' => $id_issue_event,
			'event' => $id_event
		)
	);

	// !!! Is updating id_event_mod needed?

	// Update Issues table too
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET
			replies = replies + {int:rpl}, updated = {int:time},
			id_event_mod = {int:event}, id_issue_event_last = {int:issue_event},
			id_updater = {int:current_user}
		WHERE id_issue = {int:issue}',
		array(
			'event' => $id_event,
			'current_user' => $posterOptions['id'],
			'issue' => $id_issue,
			'time' => $time,
			'issue_event' => $id_issue_event,
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

	return array($id_comment, $id_issue_event);
}

/**
 * Modifies comment in database
 * @param int $id_comment
 * @param int $id_issue
 * @param array $commentOptions
 * @param array $posterOptions
 * @return boolean Whatever operation was success or not
 * @todo Doesn't check if comment exists
 */
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

/**
 * Creates issue list for current project. Will be replaced in 0.6
 * @param int $start Offset to start from
 * @param int $num_issues Number ofissues per page
 * @param string $order SQL to order by
 * @param string $where SQL for WHERE condition
 * @param array $queryArray
 * @return array issue list
 * @deprecated since 0.5
 * @see createIssueList
 */
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
		WHERE ' . (!empty($project) ? '{query_project_see_issue}
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
			'link' => '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>',
			'href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'], 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions']), $row['id_project']),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed']), $row['id_project']),
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
			'new_href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from']), $row['id_project']) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

/**
 * Creates filter for createIssueList
 * @param string $mode Default to get default filter. Reguest to get filter based on reguest parametrs
 * @param array $options
 * @return array Filter array
 */
function getIssuesFilter($mode = 'default', $options = array())
{
	global $context, $smcFunc;
	
	$filter = array(
		'title' => '',
		'status' => 'all',
		'tag' => '',
		'tracker' => '',
		'version' => null,
		'version_fixed' => null,
		'category' => null,
		'reporter' => null,
		'assignee' => null,
	);
	
	if ($mode == 'request')
	{
		if (!empty($_REQUEST['title']))
			$filter['title'] = $smcFunc['htmlspecialchars']($_REQUEST['title']);
	
		if (!empty($_REQUEST['tracker']) && isset($context['possible_types'][$_REQUEST['tracker']]))
			$filter['tracker'] = $context['possible_types'][$_REQUEST['tracker']];
	
		if (isset($_REQUEST['category']))
			$filter['category'] = $_REQUEST['category'];
	
		if (isset($_REQUEST['reporter']))
			$filter['reporter'] = $_REQUEST['reporter'];
	
		if (isset($_REQUEST['assignee']))
			$filter['assignee'] = $_REQUEST['assignee'];
	
		if (isset($_REQUEST['version']))
		{
			$_REQUEST['version'] = (int) trim($_REQUEST['version']);
			$filter['version'] = $_REQUEST['version'];
		}
	
		if (isset($_REQUEST['version_fixed']))
		{
			$_REQUEST['version_fixed'] = (int) trim($_REQUEST['version_fixed']);
			$filter['version_fixed'] = $_REQUEST['version_fixed'];
		}
	
		if (!empty($_REQUEST['status']))
			$filter['status'] = $_REQUEST['status'];
			
		if (!empty($_REQUEST['tag']))
			$filter['tag'] = $_REQUEST['tag'];
	}
	
	return $filter;
}

/**
 * Creates issue list
 * @param array $issueListOptions
 * @retrun string Key Used for issue list in context array
 */
function createIssueList($issueListOptions)
{
	global $smcFunc, $context, $user_info, $scripturl, $txt;
	
	assert(isset($issueListOptions['id']));
	
	$key = 'issue_list_' . $issueListOptions['id'];
	
	$context[$key] = array();

	if (!isset($issueListOptions['filter']))
		$issueListOptions['filter'] = getIssuesFilter();
		
	if (!isset($issueListOptions['sort']))
		$issueListOptions['sort'] = 'i.updated DESC';
	if (!isset($issueListOptions['ascending']))
		$issueListOptions['ascending'] = true;
	
	// Build where clause
	$where = array();

	if ($issueListOptions['filter']['status'] == 'open')
		$where[] = 'NOT (i.status IN ({array_int:closed_status}))';
	elseif ($issueListOptions['filter']['status'] == 'closed')
		$where[] = 'i.status IN ({array_int:closed_status})';
	elseif (is_numeric($issueListOptions['filter']['status']))
		$where[] = 'i.status IN ({int:search_status})';

	if (!empty($issueListOptions['filter']['title']))
		$where[] = 'i.subject LIKE {string:search_title}';

	if (!empty($issueListOptions['filter']['tracker']))
		$where[] = 'i.id_tracker = {int:search_tracker}';

	if (isset($issueListOptions['filter']['category']))
		$where[] = 'i.id_category = {int:search_category}';

	if (isset($issueListOptions['filter']['reporter']))
		$where[] = 'i.id_reporter = {int:search_reporter}';

	if (isset($issueListOptions['filter']['assignee']))
		$where[] = 'i.id_assigned = {int:search_assignee}';

	if (isset($issueListOptions['filter']['version']))
		$where[] = '(FIND_IN_SET({int:search_version}, i.versions) OR FIND_IN_SET({int:search_version}, i.versions_fixed))';

	if (isset($issueListOptions['filter']['version_fixed']))
		$where[] = '(FIND_IN_SET({int:search_version_f}, i.versions_fixed))';

	// How many issues?
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($issueListOptions['filter']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . '
		WHERE {query_current_project_see_issue}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND (' . implode(')
			AND (', $where) . ')' : '') . '',
		array(
			'project' => ProjectTools_Project::getCurrent()->id,
			'closed_status' => $context['closed_status'],
			'search_status' => $issueListOptions['filter']['status'],
			'search_title' => '%' . $issueListOptions['filter']['title'] . '%',
			'search_version' => $issueListOptions['filter']['version'],
			'search_version_f' => $issueListOptions['filter']['version_fixed'],
			'search_category' => $issueListOptions['filter']['category'],
			'search_assignee' => $issueListOptions['filter']['assignee'],
			'search_reporter' => $issueListOptions['filter']['reporter'],
			'search_tracker' => $issueListOptions['filter']['tracker'],
			'search_tag' => $issueListOptions['filter']['tag'],
		)
	);

	list ($context[$key]['num_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);
	
	$context[$key]['start'] = isset($issueListOptions['start']) ? $issueListOptions['start'] : 0;
	
	if (isset($issueListOptions['page_index']))
		$context[$key]['page_index'] = constructPageIndex(ProjectTools::get_url($issueListOptions['base_url']), $context[$key]['start'], $context[$key]['num_issues'], !empty($issueListOptions['issues_per_page']) ? $issueListOptions['issues_per_page'] : $context['issues_per_page']);
	
	// Canonical url for search engines
	if (!empty($issueListOptions['page_index']) && !empty($return['start']))
		$context[$key]['canonical_url'] = ProjectTools::get_url(array_merge($issueListOptions['base_url'], array('start' => $context[$key]['start'])));
	elseif (!empty($issueListOptions['page_index']))
		$context[$key]['canonical_url'] = ProjectTools::get_url($issueListOptions['base_url']);
	
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.id_tracker, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			rep.id_member AS id_reporter, IFNULL(rep.real_name, iv.poster_name) AS reporter_name,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
			i.versions, i.versions_fixed,
			GROUP_CONCAT(tags.tag SEPARATOR \', \') AS tags,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($issueListOptions['filter']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}issue_events AS iv ON (iv.id_issue_event = i.id_issue_event_first)
			LEFT JOIN {db_prefix}members AS rep ON (rep.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
			LEFT JOIN {db_prefix}issue_tags AS tags ON (tags.id_issue = i.id_issue)
		WHERE {query_current_project_see_issue}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND ' . implode('
			AND ', $where) : '') . '
		GROUP BY i.id_issue
		ORDER BY ' . $issueListOptions['sort']. (!$issueListOptions['ascending'] ? ' DESC' : '') . '
		LIMIT {int:start},' . (!empty($issueListOptions['issues_per_page']) ? $issueListOptions['issues_per_page'] : $context['issues_per_page']),
		array(
			'project' => ProjectTools_Project::getCurrent()->id,
			'empty' => '',
			'start' => $context[$key]['start'],
			'current_member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
			'search_version' => $issueListOptions['filter']['version'],
			'search_version_f' => $issueListOptions['filter']['version_fixed'],
			'search_status' => $issueListOptions['filter']['status'],
			'search_title' => '%' . $issueListOptions['filter']['title'] . '%',
			'search_category' => $issueListOptions['filter']['category'],
			'search_assignee' => $issueListOptions['filter']['assignee'],
			'search_reporter' => $issueListOptions['filter']['reporter'],
			'search_tracker' => $issueListOptions['filter']['tracker'],
			'search_tag' => $issueListOptions['filter']['tag'],
		)
	);
	
	$context[$key]['filter'] = $issueListOptions['filter'];

	$context[$key]['issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$row['tags'] = explode(', ', $row['tags']);
		array_walk($row['tags'], 'link_tags', $issueListOptions['base_url']);

		$context[$key]['issues'][] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0')) . '">' . $row['subject'] . '</a>',
			'href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0')),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions']), $row['id_project']),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed']), $row['id_project']),
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
			'new_href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from'])) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);
	
	return $key;
}

/**
 * Links tag of arrays. Used in array walk
 */
function link_tags(&$tag, $key, $baseurl)
{
	if (is_array($baseurl))
		$tag = '<a href="' . ProjectTools::get_url(array_merge($baseurl, array('tag' => urlencode($tag)))). '">' . $tag . '</a>';
	else
		$tag = '<a href="' . $baseurl . (strpos($baseurl,'?') !== false ? ';' : '?') . 'tag=' . urlencode($tag) . '">' . $tag . '</a>';
	
}

/**
 * Creates list of versions from array of ids
 * @param array $versions array of version ids
 * @param boolean $as_string return as comma separated string instead of array
 */
function getVersions($versions, $project = null, $as_string = false)
{
	global $context;
	
	// Versions might be comma separated list from database
	if (!is_array($versions))
		$versions = explode(',', $versions);
		
	if ($project === null)
		$project = ProjectTools_Project::getCurrent()->id;
	
	$return = array();
	
	foreach ($versions as $ver)
	{
		if (!empty(ProjectTools_Project::getProject($project)->versions_id[$ver]))
			$return[$ver] = $as_string ?
				ProjectTools_Project::getProject($project)->versions['versions'][ ProjectTools_Project::getProject($project)->versions_id[$ver] ]['sub_versions'][$ver]['name'] :
				ProjectTools_Project::getProject($project)->versions['versions'][ ProjectTools_Project::getProject($project)->versions_id[$ver] ]['sub_versions'][$ver];
		elseif (!empty(ProjectTools_Project::getProject($project)->versions[$ver]))
			$return[$ver] = $as_string ?
				ProjectTools_Project::getProject($project)->versions[$ver]['name'] :
				ProjectTools_Project::getProject($project)->versions[$ver];
	}
	
	return $as_string ? implode(', ', $return) : $return;
}

?>