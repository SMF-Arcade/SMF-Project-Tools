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
class ProjectTools_IssueTracker_View
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
			'assign' => array(
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
				currentIssue = new PTIssue(' . ProjectTools_IssueTracker_Issue::getCurrent()->id . ', "' . $issue_xml_url . '", ' . ProjectTools_IssueTracker_Issue::getCurrent()->id_event_mod . ', "loaded_events");';


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
				var dd' . $id . ' = currentIssue.addMultiDropdown("issue_' . $id . '", "' . $id . '", ' . $value . ');';
				
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
	 * Displays Issue View page
	 */
	public static function Main(ProjectTools_IssueTracker_Module $module)
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings, $project, $issue;
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent())
			fatal_lang_error('issue_not_found', false);
	
		ProjectTools::isAllowedTo('issue_view');
	
		$type = ProjectTools_IssueTracker_Issue::getCurrent()->is_mine ? 'own' : 'any';
	
		$context['current_tags'] = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT tag
			FROM {db_prefix}issue_tags
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $issue,
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['current_tags'][] = array(
				'id' => urlencode($row['tag']),
				'tag' => $row['tag'],
				'link' => '<a href="' . ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'issues', 'tag' => urlencode($row['tag']))) . '">' . $row['tag'] . '</a>',
			);
	
		self::loadIssueView();
	
		// How many event there are?
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}project_timeline
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $issue,
			)
		);
		list ($num_events) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	
		// Fix start to be a number
		if (!is_numeric($_REQUEST['start']))
		{
			// To first new
			if ($_REQUEST['start'] == 'new')
			{
				if ($user_info['is_guest'])
					$_REQUEST['start'] = $num_events;
				else
				{
					$request = $smcFunc['db_query']('', '
						SELECT IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from
						FROM {db_prefix}issues AS i
							LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = {int:current_issue})
							LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = i.id_project AND lmr.id_member = {int:current_member})
						WHERE i.id_issue = {int:current_issue}
						LIMIT 1',
						array(
							'current_member' => $user_info['id'],
							'current_issue' => $issue,
						)
					);
					list ($new_from) = $smcFunc['db_fetch_row']($request);
					$smcFunc['db_free_result']($request);
	
					$_REQUEST['start'] = 'com' . $new_from;
				}
	
				$context['robot_no_index'] = true;
			}
	
			if (substr($_REQUEST['start'], 0, 3) == 'com')
			{
				$virtual_msg = (int) substr($_REQUEST['start'], 3);
	
				// How many events before this
				$request = $smcFunc['db_query']('', '
					SELECT (COUNT(*) - 1)
					FROM {db_prefix}project_timeline
					WHERE id_event < {int:virtual_msg}
						AND id_issue = {int:current_issue}',
					array(
						'current_issue' => $issue,
						'virtual_msg' => $virtual_msg,
					)
				);
				list ($context['start_from']) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);
	
				$_REQUEST['start'] = $context['start_from'];
				$context['robot_no_index'] = true;
			}
		}
	
		// Page Index
		$context['page_index'] = constructPageIndex(ProjectTools::get_url(array('issue' => $issue . '.%d')), $_REQUEST['start'], $num_events - 1, $context['comments_per_page'], true);
	
		// Canonical url for search engines
		$context['canonical_url'] = ProjectTools::get_url(array('issue' => $issue . '.' . $_REQUEST['start']));
		
		$context['start'] = $_REQUEST['start'];
	
		$posters = array();
		$events = array(ProjectTools_IssueTracker_Issue::getCurrent()->event_first);
	
		$request = $smcFunc['db_query']('', '
			SELECT id_issue_event, id_member
			FROM {db_prefix}issue_events
			WHERE id_issue = {int:issue}
			LIMIT {int:start}, {int:limit}',
			array(
				'issue' => $issue,
				'start' => $_REQUEST['start'] + 1,
				'limit' => $context['comments_per_page'],
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!empty($row['id_member']))
				$posters[$row['id_member']] = $row['id_member'];
			$events[] = $row['id_issue_event'];
		}
		$smcFunc['db_free_result']($request);
	
		if (!empty($posters))
			loadMemberData($posters);
	
		$context['num_events'] = count($events);
	
		// Load events
		if (!empty($events))
		{
			$context['comment_request'] = $smcFunc['db_query']('', '
				SELECT
					iv.id_issue_event, iv.id_member, iv.event_time, iv.changes,
					iv.poster_name, iv.poster_email, iv.poster_ip, 
					IFNULL(c.id_comment, 0) AS is_comment, c.id_comment, c.body, c.edit_name, c.edit_time,
					IFNULL(iv.id_event_mod, {int:new_from}) < {int:new_from} AS is_read
				FROM {db_prefix}issue_events AS iv
					LEFT JOIN {db_prefix}issue_comments AS c ON (c.id_comment = iv.id_comment)
				WHERE iv.id_issue_event IN ({array_int:events})
				ORDER BY id_issue_event',
				array(
					'events' => $events,
					'new_from' => ProjectTools_IssueTracker_Issue::getCurrent()->new_from,
				)
			);
		}
		else
			$context['comment_request'] = false;
	
		$context['counter_start'] = $_REQUEST['start'];
	
		// Mark this issue as read
		if (!$user_info['is_guest'])
		{
			if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
			{
				ob_end_clean();
				header('HTTP/1.1 403 Prefetch Forbidden');
				die;
			}
	
			$smcFunc['db_insert']('replace',
				'{db_prefix}log_issues',
				array(
					'id_project' => 'int',
					'id_issue' => 'int',
					'id_member' => 'int',
					'id_event' => 'int',
				),
				array(
					$project,
					$issue,
					$user_info['id'],
					$modSettings['project_maxEventID'],
				),
				array('id_issue', 'id_member')
			);
		}
	
		// Load attachments
		self::loadAttachmentData();
	
		// Template
		loadTemplate('IssueView');
		$context['template_layers'][] = 'issue_view';
		$context['sub_template'] = 'issue_view_main';
		$context['page_title'] = sprintf($txt['project_view_issue'], ProjectTools_Project::getCurrent()->name, ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_IssueTracker_Issue::getCurrent()->name);
	}
	
	/**
	 * Callback for getting next event from template. Done this way to save memory.
	 */
	public static function getEvent()
	{
		global $context, $smcFunc, $user_info, $txt, $modSettings, $memberContext;
		static $counter = 0;
		static $first_new = true;
		static $first = true;
	
		if (!$context['comment_request'])
			return false;
	
		if ($first_new)
			$first_new = !ProjectTools_IssueTracker_Issue::getCurrent()->details['first_new'];
	
		$row = $smcFunc['db_fetch_assoc']($context['comment_request']);
	
		if (!$row)
		{
			$smcFunc['db_free_result']($context['comment_request']);
			return false;
		}
	
		if (!loadMemberContext($row['id_member']))
		{
			$memberContext[$row['id_member']]['name'] = $row['poster_name'];
			$memberContext[$row['id_member']]['id'] = 0;
			$memberContext[$row['id_member']]['group'] = $txt['guest_title'];
			$memberContext[$row['id_member']]['link'] = $row['poster_name'];
			$memberContext[$row['id_member']]['email'] = $row['poster_email'];
			$memberContext[$row['id_member']]['show_email'] = showEmailAddress(true, 0);
			$memberContext[$row['id_member']]['is_guest'] = true;
		}
		else
		{
			$memberContext[$row['id_member']]['can_view_profile'] = allowedTo('profile_view_any') || ($row['id_member'] == $user_info['id'] && allowedTo('profile_view_own'));
		}
	
		$changes = array();
	
		// Parse event data
		if (!empty($row['changes']))
		{
			$data = unserialize($row['changes']);
	
			if (isset($data['changes']))
				$changes = ProjectTools_ChangesParser::Parse(ProjectTools_Project::getCurrent(), $data['changes']);
		}
	
		$type = $row['id_member'] == $user_info['id'] && $row['id_member'] != 0 ? 'own' : 'any';
	
		$event = array(
			'id' => $row['id_issue_event'],
			'counter' => $context['counter_start'] + $counter,
			//'title' => sprintf($txt['evt_' . $row['event']], $memberContext[$row['id_member']]['link']),
			'type' => $row['is_comment'] ? 'comment' : 'event',
			'is_comment' => $row['is_comment'],
			'member' => &$memberContext[$row['id_member']],
			'time' => timeformat($row['event_time']),
			'ip' => $row['poster_ip'],
			'can_see_ip' => allowedTo('moderate_forum') || ($row['id_member'] == $user_info['id'] && !empty($user_info['id'])),
			'changes' => $changes,
		);
	
		if ($row['is_comment'])
		{
			$event['type'] = 'comment';
			$event['title'] = '';
	
			censorText($row['body']);
	
			$event['comment'] = array(
				'id' => $row['id_comment'],
				'body' => parse_bbc($row['body']),
				'modified' => array(
					'time' => timeformat($row['edit_time']),
					'timestamp' => forum_time(true, $row['edit_time']),
					'name' => $row['edit_name'],
				),
				'can_remove' => ProjectTools::allowedTo('delete_comment_' . $type),
				'can_edit' => ProjectTools::allowedTo('edit_comment_' . $type),
				'new' => empty($row['is_read']),
				'first_new' => $first_new && empty($row['is_read']),
			);
	
			if ($first_new && empty($row['is_read']))
				$first_new = false;
		}
		else
		{
			
		}
	
		$counter++;
	
		return $event;
	}
	
	/**
	 * Loads data for attachments
	 *
	 * @todo Move Issue Attachments to module
	 */
	public static function loadAttachmentData()
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;
	
		$attachmentData = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT
				a.id_attach, a.id_folder, a.id_msg, a.filename, IFNULL(a.size, 0) AS filesize, a.downloads, a.approved,
				a.fileext, a.width, a.height, IFNULL(mem.real_name, t.poster_name) AS real_name, t.poster_ip' . (empty($modSettings['attachmentShowImages']) || empty($modSettings['attachmentThumbnails']) ? '' : ',
				IFNULL(thumb.id_attach, 0) AS id_thumb, thumb.width AS thumb_width, thumb.height AS thumb_height') . '
			FROM {db_prefix}issue_attachments AS ia
				INNER JOIN {db_prefix}attachments AS a ON (a.id_attach = ia.id_attach)' . (empty($modSettings['attachmentShowImages']) || empty($modSettings['attachmentThumbnails']) ? '' : '
				LEFT JOIN {db_prefix}attachments AS thumb ON (thumb.id_attach = a.id_thumb)') . '
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = ia.id_member)
				LEFT JOIN {db_prefix}project_timeline AS t ON (t.id_event = ia.id_event)
			WHERE ia.id_issue = {int:issue}
				AND a.attachment_type = {int:attachment_type}' . (!$modSettings['postmod_active'] || allowedTo('approve_posts') ? '' : '
				AND a.approved = {int:is_approved}'),
			array(
				'issue' => $issue,
				'attachment_type' => 0,
				'is_approved' => 1,
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$i = $row['id_attach'];
	
			$attachmentData[$i] = array(
				'id' => $i,
				'name' => preg_replace('~&amp;#(\\d{1,7}|x[0-9a-fA-F]{1,6});~', '&#\\1;', htmlspecialchars($row['filename'])),
				'href' => $scripturl . '?action=dlattach;issue=' . $issue . '.0;attach=' . $i,
				'link' => '<a href="' . $scripturl . '?action=dlattach;issue=' . $issue . '.0;attach=' . $i . '">' . htmlspecialchars($row['filename']) . '</a>',
				'extension' => $row['fileext'],
				'downloads' => comma_format($row['downloads']),
				'poster' => $row['real_name'],
				'ip' => $row['poster_ip'],
				'size' => round($row['filesize'] / 1024, 2) . ' ' . $txt['kilobyte'],
				'byte_size' => $row['filesize'],
				'is_image' => !empty($row['width']) && !empty($row['height']) && !empty($modSettings['attachmentShowImages']),
				'is_approved' => $row['approved'],
			);
	
			if (!$attachmentData[$i]['is_image'])
				continue;
	
			$attachmentData[$i] += array(
				'real_width' => $row['width'],
				'width' => $row['width'],
				'real_height' => $row['height'],
				'height' => $row['height'],
			);
	
			if (!empty($modSettings['attachmentThumbnails']) && !empty($modSettings['attachmentThumbWidth']) && !empty($modSettings['attachmentThumbHeight']) && ($row['width'] > $modSettings['attachmentThumbWidth'] || $row['height'] > $modSettings['attachmentThumbHeight']) && strlen($row['filename']) < 249)
			{
				// ...
	
				$attachmentData[$i]['width'] = $row['thumb_width'];
				$attachmentData[$i]['height'] = $row['thumb_height'];
			}
	
			if (!empty($row['id_thumb']))
				$attachmentData[$i]['thumbnail'] = array(
					'id' => $row['id_thumb'],
					'href' => $scripturl . '?action=dlattach;issue=' . $issue . '.0;attach=' . $row['id_thumb'] . ';image',
				);
			$attachmentData[$i]['thumbnail']['has_thumb'] = !empty($row['id_thumb']);
	
			// If thumbnails are disabled, check the maximum size of the image.
			if (!$attachmentData[$i]['thumbnail']['has_thumb'] && ((!empty($modSettings['max_image_width']) && $row['width'] > $modSettings['max_image_width']) || (!empty($modSettings['max_image_height']) && $row['height'] > $modSettings['max_image_height'])))
			{
				if (!empty($modSettings['max_image_width']) && (empty($modSettings['max_image_height']) || $row['height'] * $modSettings['max_image_width'] / $row['width'] <= $modSettings['max_image_height']))
				{
					$attachmentData[$i]['width'] = $modSettings['max_image_width'];
					$attachmentData[$i]['height'] = floor($row['height'] * $modSettings['max_image_width'] / $row['width']);
				}
				elseif (!empty($modSettings['max_image_width']))
				{
					$attachmentData[$i]['width'] = floor($row['width'] * $modSettings['max_image_height'] / $row['height']);
					$attachmentData[$i]['height'] = $modSettings['max_image_height'];
				}
			}
			elseif ($attachmentData[$i]['thumbnail']['has_thumb'])
			{
				// If the image is too large to show inline, make it a popup.
				if (((!empty($modSettings['max_image_width']) && $attachmentData[$i]['real_width'] > $modSettings['max_image_width']) || (!empty($modSettings['max_image_height']) && $attachmentData[$i]['real_height'] > $modSettings['max_image_height'])))
					$attachmentData[$i]['thumbnail']['javascript'] = 'return reqWin(\'' . $attachmentData[$i]['href'] . ';image\', ' . ($row['width'] + 20) . ', ' . ($row['height'] + 20) . ', true);';
				else
					$attachmentData[$i]['thumbnail']['javascript'] = 'return expandThumb(' . $i . ');';
			}
	
			if (!$attachmentData[$i]['thumbnail']['has_thumb'])
				$attachmentData[$i]['downloads']++;
		}
		$smcFunc['db_free_result']($request);
	
		$context['attachments'] = &$attachmentData;
	}
	
	/**
	 * Adds or removes tags from Issue
	 *
	 * @todo Move tagging to own module? If not move to IssueReport.php
	 */
	public static function IssueTag()
	{
		global $context, $user_info, $smcFunc;
	
		checkSession('request');
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent())
			fatal_lang_error('issue_not_found', false);
	
		$posterOptions = array(
			'id' => $user_info['id'],
			'name' => $user_info['name'],
			'username' => $user_info['username'],
			'email' => $user_info['email'],
			'ip' => $user_info['ip'],
		);
		$eventOptions = array(
			'time' => time(),
		);
		
		if (isset($_REQUEST['tag']) && !isset($_REQUEST['remove']))
		{
			ProjectTools::isAllowedTo('issue_moderate');
	
			$rows = array();
			$tags = array();
	
			foreach (explode(',', $_REQUEST['tag']) as $tag)
			{
				$tag = trim($tag);
	
				if (!empty($tag))
				{
					$rows[] = array(ProjectTools_IssueTracker_Issue::getCurrent()->id, $smcFunc['htmlspecialchars']($tag, ENT_QUOTES));
					$tags[] = $tag;
				}
			}
	
			if (empty($rows))
				redirectexit(ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
	
			$smcFunc['db_insert']('replace',
				'{db_prefix}issue_tags',
				array('id_issue' => 'int', 'tag' => 'string-30',),
				$rows,
				array('id_issue', 'tag')
			);
			
			$event_data = array(
				'changes' => array(
					// Format: array(tags, array removed, array added)
					array('tags', array(), $tags),
				),
			);
			
			$id_event = createTimelineEvent(ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_Project::getCurrent()->id, 'update_issue', $event_data, $posterOptions, $eventOptions);
		}
		elseif (isset($_REQUEST['tag']))
		{
			ProjectTools::isAllowedTo('issue_moderate');
	
			$rows = array();
			$tags = array();
	
			foreach (explode(',', $_REQUEST['tag']) as $tag)
			{
				$tag = trim($tag);
	
				if (!empty($tag))
				{
					$rows[] = $smcFunc['htmlspecialchars']($tag, ENT_QUOTES);
					$tags[] = $tag;
				}
			}
	
			if (empty($rows))
				redirectexit(ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
	
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}issue_tags
				WHERE id_issue = {int:issue}
					AND tag IN({array_string:tag})',
				array(
					'issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id,
					'tag' => $rows,
				)
			);
			
			$event_data = array(
				'changes' => array(
					// Format: array(tags, array removed, array added)
					array('tags', $tags, array()),
				),
			);
			
			$id_event = createTimelineEvent(ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_Project::getCurrent()->id, 'update_issue', $event_data, $posterOptions, $eventOptions);
		}
	
		redirectexit(ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
	}
	
	/**
	 * Display page to move issue to another project
	 *
	 * @todo Merge to Updating Issue?
	 */
	public static function IssueMove()
	{
		global $context, $project, $user_info, $smcFunc;
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent())
			fatal_lang_error('issue_not_found', false);
	
		ProjectTools::isAllowedTo('issue_move');
	
		// Get list of projects
		$request = $smcFunc['db_query']('', '
			SELECT p.id_project, p.name
			FROM {db_prefix}projects AS p
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_project}
				AND NOT p.id_project = {int:current_project}
			ORDER BY p.name',
			array(
				'current_member' => $user_info['id'],
				'current_project' => $project,
			)
		);
		
		$context['projects'] = array();
		
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['projects'][$row['id_project']] = array(
				'id' => $row['id_project'],
				'link' => '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
				'href' => ProjectTools::get_url(array('project' => $row['id_project'])),
				'name' => $row['name'],
			);
		$smcFunc['db_free_result']($request);
		
		if (!empty($_POST['move_issue']) && isset($context['projects'][$_POST['project_to']]))
		{
			checkSession('post');
	
			$posterOptions = array(
				'id' => $user_info['id'],
				'ip' => $user_info['ip'],
				'username' => htmlspecialchars($user_info['username']),
				'name' => htmlspecialchars($user_info['name']),
				'email' => htmlspecialchars($user_info['email']),
			);
			
			ProjectTools_IssueTracker_Issue::getCurrent()->update(array('project' => $_POST['project_to']), $posterOptions);
			
			redirectexit(ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
		}
		
		// Template
		loadTemplate('IssueView');
		$context['sub_template'] = 'issue_move';
	}
}

?>