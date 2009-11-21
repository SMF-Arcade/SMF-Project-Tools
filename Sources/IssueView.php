<?php
/**********************************************************************************
* IssueList.php                                                                   *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.5                               *
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
	View and Edit issue

*/

function IssueView()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings, $project, $issue;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('issue_view');

	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

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
			'link' => '<a href="' . project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'tag' => urlencode($row['tag']))) . '">' . $row['tag'] . '</a>',
		);

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_move'] = projectAllowedTo('issue_move');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate');
	$context['can_issue_attach'] = projectAllowedTo('issue_attach') && !empty($modSettings['projectAttachments']);
	$context['can_issue_warning'] = allowedTo('issue_warning');
	$context['can_moderate_forum'] = allowedTo('moderate_forum');
	
	// Show signatures
	$context['signature_enabled'] = substr($modSettings['signature_settings'], 0, 1) == 1;
	
	// URL for posting updates from ajax
	$context['issue_xml_url'] = project_get_url(array('issue' => $context['current_issue']['id'], 'sa' => 'update', 'xml', $context['session_var'] => $context['session_id']));
	
	// Tags
	$context['can_add_tags'] = projectAllowedTo('issue_moderate');
	$context['can_remove_tags'] = projectAllowedTo('issue_moderate');

	$context['allowed_extensions'] = strtr($modSettings['attachmentExtensions'], array(',' => ', '));

	// Disabled Fields
	$context['disabled_fields'] = isset($modSettings['disabled_profile_fields']) ? array_flip(explode(',', $modSettings['disabled_profile_fields'])) : array();

	if ($context['can_issue_update'])
	{
		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = $context['project']['developers'];
	}

	$context['can_subscribe'] = !$user_info['is_guest'];
	$context['can_send_pm'] = allowedTo('pm_send');

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
	$context['page_index'] = constructPageIndex(project_get_url(array('issue' => $issue . '.%d')), $_REQUEST['start'], $num_events, $context['comments_per_page'], true);

	// Canonical url for search engines
	$context['canonical_url'] = project_get_url(array('issue' => $issue . '.' . $_REQUEST['start']));
	
	$context['start'] = $_REQUEST['start'];

	$posters = array();
	$events = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_event, id_member
		FROM {db_prefix}project_timeline
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
		$events[] = $row['id_event'];
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
				tl.id_event, tl.id_member, tl.event, tl.event_time , tl.event_data, tl.poster_name, tl.poster_email, tl.poster_ip,
				IFNULL(c.id_comment, 0) AS is_comment, c.id_comment, c.post_time, c.edit_time, c.body, c.edit_name, c.edit_time, tl.event_data,
				IFNULL(c.id_event_mod, {int:new_from}) < {int:new_from} AS is_read
			FROM {db_prefix}project_timeline AS tl
				LEFT JOIN {db_prefix}issue_comments AS c ON (c.id_event = tl.id_event)
			WHERE tl.id_event IN ({array_int:events})',
			array(
				'events' => $events,
				'new_from' => $context['current_issue']['new_from'],
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
	loadAttachmentData();

	// Template
	loadTemplate('IssueView');
	$context['template_layers'][] = 'issue_view';
	$context['sub_template'] = 'issue_view_main';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);
}

function getEvent()
{
	global $context, $smcFunc, $user_info, $txt, $modSettings, $memberContext;
	static $counter = 0;
	static $first_new = true;
	static $first = true;

	if (!$context['comment_request'])
		return false;

	if ($first_new)
		$first_new = !$context['current_issue']['details']['first_new'];

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
	if (!empty($row['event_data']))
	{
		$data = unserialize($row['event_data']);

		if (isset($data['changes']) && is_array($data['changes']))
		{
			foreach ($data['changes'] as $key => $field)
			{
				list ($field, $old_value, $new_value) = $field;

				// Change values to something meaningful
				if ($field == 'status')
				{
					$old_value = $context['issue_status'][$old_value]['text'];
					$new_value = $context['issue_status'][$new_value]['text'];
				}
				elseif ($field == 'type')
				{
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $old_value)
						{
							$old_value = $tracker['name'];
							break;
						}
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $new_value)
						{
							$new_value = $tracker['name'];
							break;
						}
				}
				elseif ($field == 'tracker')
				{
					$old_value = $context['issue_trackers'][$old_value]['name'];
					$new_value = $context['issue_trackers'][$new_value]['name'];
				}
				elseif ($field == 'view_status')
				{
					if (empty($old_value))
						$old_value = $txt['issue_view_status_public'];
					else
						$old_value = $txt['issue_view_status_private'];

					if (empty($new_value))
						$new_value = $txt['issue_view_status_public'];
					else
						$new_value = $txt['issue_view_status_private'];
				}
				elseif ($field == 'version' || $field == 'target_version')
				{
					if (empty($old_value))
						$old_value = $txt['issue_none'];
					elseif (!empty($context['versions_id'][$old_value]))
						$old_value = $context['versions'][$context['versions_id'][$old_value]]['sub_versions'][$old_value]['name'];
					elseif (!empty($context['versions'][$old_value]))
						$old_value = $context['versions'][$old_value]['name'];

					if (empty($new_value))
						$new_value = $txt['issue_none'];
					elseif (!empty($context['versions_id'][$new_value]))
						$new_value = $context['versions'][$context['versions_id'][$new_value]]['sub_versions'][$new_value]['name'];
					elseif (!empty($context['versions'][$new_value]))
						$new_value = $context['versions'][$new_value]['name'];
				}
				elseif ($field == 'assign')
				{
					loadMemberData(array($old_value, $new_value));

					if (empty($old_value))
						$old_value = $txt['issue_none'];
					elseif (loadMemberContext($old_value))
						$old_value = $memberContext[$old_value]['link'];

					if (empty($new_value))
						$new_value = $txt['issue_none'];
					elseif (loadMemberContext($new_value))
						$new_value = $memberContext[$new_value]['link'];
				}

				$changes[] = sprintf($txt['change_' . $field], $old_value, $new_value);
			}
		}
	}

	$type = $row['id_member'] == $user_info['id'] && $row['id_member'] != 0 ? 'own' : 'any';

	$event = array(
		'id' => $row['id_event'],
		'counter' => $context['counter_start'] + $counter,
		'title' => sprintf($txt['evt_' . $row['event']], $memberContext[$row['id_member']]['link']),
		'type' => $row['is_comment'] ? 'comment' : $row['event'],
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
			'can_remove' => projectAllowedTo('delete_comment_' . $type),
			'can_edit' => projectAllowedTo('edit_comment_' . $type),
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

function loadAttachmentData()
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

function IssueTag()
{
	global $context, $user_info, $smcFunc;

	checkSession('request');

	if (!isset($context['current_issue']))
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
		projectIsAllowedTo('issue_moderate');

		$tags = array();

		foreach (explode(',', $_REQUEST['tag']) as $tag)
		{
			$tag = trim($tag);

			if (!empty($tag))
				$tags[] = array($context['current_issue']['id'], $smcFunc['htmlspecialchars']($tag, ENT_QUOTES));
		}

		if (empty($tags))
			redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));

		$smcFunc['db_insert']('replace',
			'{db_prefix}issue_tags',
			array('id_issue' => 'int', 'tag' => 'string-30',),
			$tags,
			array('id_issue', 'tag')
		);
		
		$id_event = createTimelineEvent($context['current_issue']['id'], $context['project']['id'], 'new_tag', serialize(array('tags' => $tags)), $posterOptions, $eventOptions);
	}
	elseif (isset($_REQUEST['tag']))
	{
		projectIsAllowedTo('issue_moderate');
		$_REQUEST['tag'] = urldecode($_REQUEST['tag']);
		
		$tags = array($_REQUEST['tag']);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_tags
			WHERE id_issue = {int:issue}
				AND tag IN({array_string:tag})',
			array(
				'issue' => $context['current_issue']['id'],
				'tag' => $tags,
			)
		);
		
		$id_event = createTimelineEvent($context['current_issue']['id'], $context['project']['id'], 'remove_tag', serialize(array('tags' => $tags)), $posterOptions, $eventOptions);
	}

	redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));
}

function IssueMove()
{
	global $context, $project, $user_info, $smcFunc;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('issue_move');

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
			'link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
			'href' => project_get_url(array('project' => $row['id_project'])),
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
		
		updateIssue($context['current_issue']['id'], array('project' => $_POST['project_to']), $posterOptions);
		
		redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));
	}
	
	// Template
	loadTemplate('IssueView');
	$context['sub_template'] = 'issue_move';
}

function IssueDelete()
{
	global $context, $user_info, $smcFunc;

	checkSession('get');

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('issue_moderate');

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => htmlspecialchars($user_info['name']),
		'username' => htmlspecialchars($user_info['username']),
		'email' => htmlspecialchars($user_info['email']),
	);

	// Send Notifications
	sendIssueNotification(array('id' => $context['current_issue']['id'], 'project' => $context['project']['id']), array(), array(), 'issue_delete', $user_info['id']);

	deleteIssue($context['current_issue']['id'], $posterOptions);

	redirectexit(project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues')));
}

?>