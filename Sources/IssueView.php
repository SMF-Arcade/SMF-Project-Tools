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
	View and Edit issue

*/

function IssueView()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('issue_view');

	$issue = $context['current_issue']['id'];
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
	{
		$context['current_tags'][] = array(
			'id' => urlencode($row['tag']),
			'tag' => $row['tag'],
			'link' => '<a href="' . $scripturl .'?project=' . $context['project']['id'] . ';sa=issues;tag=' . urlencode($row['tag']) . '">' . $row['tag'] . '</a>',
		);
	}

	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate');
	$context['can_issue_attach'] = projectAllowedTo('issue_attach') && !empty($modSettings['projectAttachments']);

	// Tags
	$context['can_add_tags'] = projectAllowedTo('issue_moderate');
	$context['can_remove_tags'] = projectAllowedTo('issue_moderate');

	$context['allowed_extensions'] = strtr($modSettings['attachmentExtensions'], array(',' => ', '));

	if ($context['can_issue_update'])
	{
		$context['possible_types'] = array();

		foreach ($context['project']['trackers'] as $id => $type)
			$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];
		$context['possible_types'][$context['current_issue']['type']['id']]['selected'] = true;

		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = $context['project']['developers'];
	}

	// Fix start to be a number
	if (!is_numeric($_REQUEST['start']))
	{
		// To first new
		if ($_REQUEST['start'] == 'new')
		{
			if ($user_info['is_guest'])
				$_REQUEST['start'] = $context['current_issue']['replies'];
			else
			{
				$request = $smcFunc['db_query']('', '
					SELECT (IFNULL(log.id_comment, -1) + 1) AS new_from
					FROM {db_prefix}issues AS i
						LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:member} AND log.id_issue = {int:current_issue})
					WHERE i.id_issue = {int:current_issue}
					LIMIT 1',
					array(
						'member' => $user_info['id'],
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

			if ($virtual_msg >= $context['current_issue']['comment_last'])
				$context['start_from'] = $context['current_issue']['replies'] - 1;
			elseif ($virtual_msg <= $context['current_issue']['comment_first'])
				$context['start_from'] = 0;
			else
			{
				// How many comments before this
				$request = $smcFunc['db_query']('', '
					SELECT (COUNT(*) - 1)
					FROM {db_prefix}issue_comments
					WHERE id_comment < {int:virtual_msg}
						AND id_issue = {int:current_issue}',
					array(
						'current_issue' => $issue,
						'virtual_msg' => $virtual_msg,
					)
				);
				list ($context['start_from']) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);
			}

			$_REQUEST['start'] = $context['start_from'];
			$context['robot_no_index'] = true;
		}
		elseif ($_REQUEST['start'] == 'log' || $_REQUEST['start'] == 'attachments')
		{
			$_REQUEST['view'] = $_REQUEST['start'];
			$_REQUEST['start'] = 0;
		}
		else
		{
			$context['robot_no_index'] = true;
		}
	}

	// Template
	loadTemplate('IssueView');
	$context['template_layers'][] = 'issue_view';

	$context['current_view'] = 'comments';

	if (isset($_REQUEST['view']) && $_REQUEST['view'] == 'log')
		$context['current_view'] = 'log';

	prepareComments($context['current_view'] == 'comments');

	if ($context['current_view'] == 'comments')
		IssueViewComments();
	elseif ($context['current_view'] == 'log')
		IssueViewLog();
	elseif ($context['current_view'] == 'attachments')
		IssueViewAttachments();
}

function IssueViewComments()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;

	// Mark this issue as read
	if (!$user_info['is_guest'])
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_issues',
			array(
				'id_issue' => 'int',
				'id_member' => 'int',
				'id_comment' => 'int',
			),
			array(
				$issue,
				$user_info['id'],
				$context['current_issue']['comment_mod']
			),
			array('id_issue', 'id_member')
		);
	}

	$context['page_index'] = constructPageIndex($scripturl . '?issue=' . $issue . '.%d', $_REQUEST['start'], $context['current_issue']['replies'], $context['comments_per_page'], true);

	loadAttachmentData();

	// Template
	$context['sub_template'] = 'issue_comments';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);
}

function IssueViewLog()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;

	$request = $smcFunc['db_query']('', '
		SELECT
			ev.id_event, ev.id_member, ev.poster_name, ev.poster_email,
			ev.poster_ip, ev.event, ev.event_time, ev.event_data,
			mem.id_member, IFNULL(mem.real_name, ev.poster_name) AS poster_name
		FROM {db_prefix}project_timeline AS ev
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = ev.id_member)
		WHERE id_issue = {int:issue}
		ORDER BY id_event DESC',
		array(
			'issue' => $issue,
		)
	);

	$context['issue_log'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['issue_log'][] = array(
			'event' => $row['event'],
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['poster_name'] . '</a>' : $txt['issue_guest'],
			'time' => timeformat($row['event_time']),
			'data' => $data,
		);
	}
	$smcFunc['db_free_result']($request);

		print_r($context['issue_log']);


	// Template
	$context['sub_template'] = 'issue_log';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);
}

function prepareComments($all = true)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;

	$posters = array($context['current_issue']['id_reporter']);
	$comments = array($context['current_issue']['comment_first']);

	if ($all)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_comment, id_member
			FROM {db_prefix}issue_comments
			WHERE id_issue = {int:issue}
				AND NOT (id_comment = {int:comment_first})
			LIMIT {int:start}, {int:limit}',
			array(
				'issue' => $issue,
				'comment_first' => $context['current_issue']['comment_first'],
				'start' => $_REQUEST['start'],
				'limit' => $context['comments_per_page'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!empty($row['id_member']))
				$posters[] = $row['id_member'];
			$comments[] = $row['id_comment'];
		}
		$smcFunc['db_free_result']($request);
		$posters = array_unique($posters);
	}

	loadMemberData($posters);
	$context['num_comments'] = count($comments) - 1;

	// Load Comments
	$context['comment_request'] = $smcFunc['db_query']('', '
		SELECT c.id_comment, c.post_time, c.edit_time, c.body,
			c.poster_name, c.poster_email, c.poster_ip, c.id_member,
			c.edit_name, c.edit_time,
			id_comment_mod < {int:new_from} AS is_read
		FROM {db_prefix}issue_comments AS c
		WHERE id_comment IN({array_int:comments})
		ORDER BY id_comment',
		array(
			'issue' => $issue,
			'comments' => $comments,
			'new_from' => $context['current_issue']['new_from']
		)
	);

	$context['counter_start'] = $_REQUEST['start'];
}

function loadAttachmentData()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $issue;

	$attachmentData = array();

	$request = $smcFunc['db_query']('', '
		SELECT
			a.id_attach, a.id_folder, a.id_msg, a.filename, IFNULL(a.size, 0) AS filesize, a.downloads, a.approved,
			a.width, a.height, IFNULL(mem.real_name, t.poster_name) AS real_name, t.poster_ip' . (empty($modSettings['attachmentShowImages']) || empty($modSettings['attachmentThumbnails']) ? '' : ',
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

function getComment()
{
	global $context, $smcFunc, $scripturl, $user_info, $txt, $modSettings, $memberContext;
	static $counter = 0;
	static $first_new = true;
	static $first = true;

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

	censorText($row['body']);

	$type = $row['id_member'] == $user_info['id'] && $row['id_member'] != 0 ? 'own' : 'any';

	$comment = array(
		'id' => $row['id_comment'],
		'first' => $row['id_comment'] == $context['current_issue']['comment_first'],
		'counter' => $counter,
		'member' => &$memberContext[$row['id_member']],
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
		'new' => empty($row['is_read']),
		'first_new' => $first_new && empty($row['is_read']),
	);

	if ($first)
	{
		$first = false;
		$counter = $context['counter_start'];
	}

	if ($first_new && empty($row['is_read']))
		$first_new = false;

	$counter++;

	return $comment;
}

function IssueTag()
{
	global $context, $user_info, $smcFunc;

	checkSession('request');

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

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
			redirectexit('issue=' . $context['current_issue']['id']);

		$smcFunc['db_insert']('replace',
			'{db_prefix}issue_tags',
			array(
				'id_issue' => 'int',
				'tag' => 'string-30',
			),
			$tags,
			array('id_issue', 'tag')
		);
	}
	elseif (isset($_REQUEST['tag']))
	{
		projectIsAllowedTo('issue_moderate');
		$_REQUEST['tag'] = urldecode($_REQUEST['tag']);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_tags
			WHERE id_issue = {int:issue}
				AND tag = {string:tag}',
			array(
				'issue' => $context['current_issue']['id'],
				'tag' => $_REQUEST['tag'],
			)
		);
	}

	redirectexit('issue=' . $context['current_issue']['id']);
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
		'email' => htmlspecialchars($user_info['email']),
	);
	deleteIssue($context['current_issue']['id'], $posterOptions);

	redirectexit('project=' . $context['project']['id'] . ';sa=issues');
}

?>