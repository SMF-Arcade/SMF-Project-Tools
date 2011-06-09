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
	 * Loads variables for issue view page
	 */
	public static function loadIssueView()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings, $project, $issue;
		
		$type = ProjectTools_IssueTracker_Issue::getCurrent()->is_mine ? 'own' : 'any';
		
		$context['show_update'] = false;
		$context['can_comment'] = ProjectTools::allowedTo('issue_comment');
		$context['can_issue_moderate'] = ProjectTools::allowedTo('issue_moderate');
		$context['can_issue_move'] = ProjectTools::allowedTo('issue_move');
		$context['can_issue_update'] = ProjectTools::allowedTo('issue_update_' . $type) || ProjectTools::allowedTo('issue_moderate');
		$context['can_issue_attach'] = ProjectTools::allowedTo('issue_attach') && !empty($modSettings['projectAttachments']);
		$context['can_issue_warning'] = allowedTo('issue_warning');
		$context['can_moderate_forum'] = allowedTo('moderate_forum');
		$context['can_subscribe'] = !$user_info['is_guest'];
		$context['can_send_pm'] = allowedTo('pm_send');
		
		// Show signatures
		$context['signature_enabled'] = substr($modSettings['signature_settings'], 0, 1) == 1;
		
		// Tags
		$context['can_add_tags'] = ProjectTools::allowedTo('issue_moderate');
		$context['can_remove_tags'] = ProjectTools::allowedTo('issue_moderate');
	
		$context['allowed_extensions'] = strtr($modSettings['attachmentExtensions'], array(',' => ', '));
	
		// Disabled Fields
		$context['disabled_fields'] = isset($modSettings['disabled_profile_fields']) ? array_flip(explode(',', $modSettings['disabled_profile_fields'])) : array();
	
		if ($context['can_issue_update'])
		{
			$context['can_edit'] = true;
			$context['show_update'] = true;
		}
	
		if (ProjectTools::allowedTo('issue_moderate'))
		{
			$context['can_assign'] = true;
			$context['assign_members'] = ProjectTools_Project::getCurrent()->developers;
		}
		
		//
		$context['issue_details'] = array(
			'reported' => array(
				'text' => $txt['issue_reported'],
			),
			'updated' => array(
				'text' => $txt['issue_updated'],
				'edit' => 'label',
			),
			'private' => array(
				'text' => $txt['issue_view_status'],
				'can_edit' => $context['can_issue_moderate'],
				'edit' => 'dropdown',
				'items' => array($txt['issue_view_status_public'], $txt['issue_view_status_private']),
			),
			'tracker' => array(
				'text' => $txt['issue_type'],
				'can_edit' => $context['can_issue_update'],
				'edit' => 'tracker',
			),
			'status' => array(
				'text' => $txt['issue_status'],
				'can_edit' => $context['can_issue_moderate'],
				'edit' => 'status',
			),
			'priority' => array(
				'text' => $txt['issue_priority'],
				'can_edit' => $context['can_issue_update'],
				'edit' => 'priority',
			),
			'versions' => array(
				'text' => $txt['issue_version'],
				'can_edit' => $context['can_issue_update'],
				'edit' => 'versions',
			),
			'versions_fixed' => array(
				'text' => $txt['issue_version_fixed'],
				'can_edit' => $context['can_issue_moderate'],
				'edit' => 'versions',
			),
			'assignee' => array(
				'text' => $txt['issue_assigned_to'],
				'can_edit' => $context['can_issue_moderate'],
				'edit' => 'members',
			),
			'category' => array(
				'text' => $txt['issue_category'],
				'can_edit' => $context['can_issue_update'],
				'edit' => 'category',
			),
		);

		// URL for posting updates from ajax
		$issue_xml_url = ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id, 'area' => 'issues', 'sa' => 'update', 'xml', $context['session_var'] => $context['session_id']));
		
		//
		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript">
			function ProjectTools_load()
			{
				currentIssue = new PTIssue(' . ProjectTools_IssueTracker_Issue::getCurrent()->id . ', "' . $issue_xml_url . '", ' . ProjectTools_IssueTracker_Issue::getCurrent()->id_event_mod . ', "loaded_events", "issue_save", "issue_save_button");';


		// Load Values
		foreach ($context['issue_details'] as $id => &$field)
		{
			$field['value'] = ProjectTools_IssueTracker_Issue::getCurrent()->getFieldValue($id);
			
			if (!empty($field['can_edit']))
				$field['raw_value'] = ProjectTools_IssueTracker_Issue::getCurrent()->getFieldValue($id, true);
			
			if (isset($field['edit']) && ($field['edit'] == 'label' || empty($field['can_edit'])))
			{
				// Add label so value gets updated with ajax
				$context['html_headers'] .= '
				currentIssue.addLabel("issue_' . $id . '", "' . $id . '");';
			}
			elseif (
				isset($field['edit']) && !empty($field['can_edit'])
				&& in_array($field['edit'], array('dropdown', 'tracker', 'status', 'priority', 'category', 'members'))
			)
			{
				if (!is_numeric($field['raw_value']))
					$value = JavaScriptEscape($field['raw_value']);
				else
					$value = $field['raw_value'];
					
				$context['html_headers'] .= '
				var dd' . $id . ' = currentIssue.addDropdown("issue_' . $id . '", "' . $id . '", ' . $value . ');';
				
				if (isset($field['items']))
				{
					foreach ($field['items'] as $val => $text)
					{
						if (!is_numeric($val))
							$val = JavaScriptEscape($val);
						$text = JavaScriptEscape($text);
							
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $val. ', ' . $text . ');';
					}
				}
				elseif ($field['edit'] == 'tracker')
				{
					foreach (ProjectTools_Project::getCurrent()->trackers as $tid => $tracker)				
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $tid. ', ' . JavaScriptEscape($tracker['tracker']['name']) . ');';				
				}
				elseif ($field['edit'] == 'status')
				{
					foreach ($context['issue_status'] as $status)		
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $status['id']. ', ' . JavaScriptEscape($status['text']) . ');';				
				}
				elseif ($field['edit'] == 'priority')
				{
					foreach ($context['issue']['priority'] as $priority => $text)
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $priority . ', ' . JavaScriptEscape($txt[$text]) . ');';		
				}
				elseif ($field['edit'] == 'category')
				{
					$context['html_headers'] .= '
						dd' . $id . '.addOption(0, ' . JavaScriptEscape($txt['issue_none']) . ');';
					foreach (ProjectTools_Project::getCurrent()->categories as $c)
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $c['id'] . ', ' . JavaScriptEscape($c['name']) . ');';				
				}
				elseif ($field['edit'] == 'members')
				{
					$context['html_headers'] .= '
						dd' . $id . '.addOption(0, ' . JavaScriptEscape($txt['issue_none']) . ');';
					foreach ($context['assign_members'] as $mem)
						$context['html_headers'] .= '
						dd' . $id . '.addOption(' . $mem['id'] . ', ' . JavaScriptEscape($mem['name']) . ');';				
				}
			}
			elseif (isset($field['edit']) && !empty($field['can_edit']) && $field['edit'] == 'versions')
			{
				$context['html_headers'] .= '
				var dd' . $id . ' = currentIssue.addMultiDropdown("issue_' . $id . '", "' . $id . '");';
				
				foreach (ProjectTools_Project::getCurrent()->versions as $vid => $v)
				{
					$context['html_headers'] .= '
					dd' . $id . '.addOption(' . $vid . ', ' . JavaScriptEscape($v['name']) . ', ' . (in_array($vid, $field['raw_value']) ? 1 : 0) . ', "group");';			
				
				foreach ($v['sub_versions'] as $sid => $subv)
					$context['html_headers'] .= '
					dd' . $id . '.addOption(' . $sid . ', ' . JavaScriptEscape($subv['name']) . ', ' . (in_array($sid, $field['raw_value']) ? 1 : 0) . ');';			
				}
			}
		}
		
		$context['html_headers'] .= '
			}
			addLoadEvent(ProjectTools_load);
		</script>';
	}
	
	/**
	 *
	 *
	 */
	function createIssueEvent($id_issue, $id_comment = 0, $posterOptions, $event_data)
	{
		global $smcFunc;
		
		/*if ($posterOptions['id'] != 0)
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
		}*/
		
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
				$commentOptions['comment'],
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
				'rpl' => empty($row['id_issue_event_first']) ? 0 : 1,
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
	 * 
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
			SELECT subject, id_project
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
				'body' => $commentOptions['comment'],
			)
		);
	
		if (!isset($commentOptions['no_log']))
			logAction('project_modify_comment', array('comment' => $id_comment));
	
		return true;
	}
}

?>