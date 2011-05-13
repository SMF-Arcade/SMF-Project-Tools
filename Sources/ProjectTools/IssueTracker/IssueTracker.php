<?php
/**
 * 
 *
 * @package IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_IssueTracker
{
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
	function createIssueEvent($id_issue, $id_comment = 0, $posterOptions, $event_data)
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
					'event_time' => time() - 30,
				)
			);
	
			if ($smcFunc['db_num_rows']($request) > 0)
			{
				list ($id_issue_event, $event_data2) = $smcFunc['db_fetch_row']($request);
	
				$event_data2 = unserialize($event_data2);
	
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
					
				//
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}issue_events
					SET ' . (!empty($id_comment) ? '
						id_comment = {int:comment},' : '') . '
						changes = {string:changes}
					WHERE id_issue_event = {int:issue_event}',
					array(
						'issue_event' => $id_issue_event,
						'changes' => serialize($event_data),
					)
				);
				
				return;
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
			SET
				id_event_mod = {int:event},
				id_event = {int:event}
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
}

?>