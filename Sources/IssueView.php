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
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('issue_view');

	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate');
	$context['can_issue_attach'] = projectAllowedTo('issue_attach');

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

	// Temp
	$commentsPerPage = 20;

	// Fix start to be a number
	if (!is_numeric($_REQUEST['start']))
	{
		$context['robot_no_index'] = true;

		// To first new
		if ($_REQUEST['start'] == 'new')
		{
			if ($user_info['is_guest'])
			{
				$_REQUEST['start'] = $context['current_issue']['replies'];
			}
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
		}
	}

	$msg = $context['current_issue']['replies'];

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
				$context['current_issue']['id'],
				$user_info['id'],
				$context['current_issue']['comment_mod']
			),
			array('id_issue', 'id_member')
		);
	}

	$context['page_index'] = constructPageIndex($scripturl . '?issue=' . $issue . '.%d', $_REQUEST['start'], $msg, $commentsPerPage, true);

	$request = $smcFunc['db_query']('', '
		SELECT id_comment, id_member
		FROM {db_prefix}issue_comments
		WHERE id_issue = {int:issue}
			AND NOT (id_comment = {int:comment_first})
		LIMIT {int:start}, {int:perpage}',
		array(
			'issue' => $issue,
			'comment_first' => $context['current_issue']['comment_first'],
			'start' => $_REQUEST['start'],
			'perpage' => $commentsPerPage,
		)
	);
	$posters = array($context['current_issue']['id_reporter']);
	$comments = array($context['current_issue']['comment_first']);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!empty($row['id_member']))
			$posters[] = $row['id_member'];
		$comments[] = $row['id_comment'];
	}
	$smcFunc['db_free_result']($request);
	$posters = array_unique($posters);

	$context['num_comments'] = count($comments) - 1;

	loadMemberData($posters);

	// Load Comments
	$context['comment_request'] = $smcFunc['db_query']('', '
		SELECT c.id_comment, c.post_time, c.edit_time, c.body,
			c.poster_name, c.poster_email, c.poster_ip, c.id_member,
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

	$context['attachments'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT
			at.id_attach, at.filename, at.fileext, at.downloads,
			at.attachment_type, at.size, at.width, at.height, at.mime_type,
			IFNULL(mem.real_name, t.poster_name) AS real_name, t.poster_ip
		FROM {db_prefix}issue_attachments AS ia
			INNER JOIN {db_prefix}attachments AS at ON (at.id_attach = ia.id_attach)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = ia.id_member)
			LEFT JOIN {db_prefix}project_timeline AS t ON (t.id_event = ia.id_event)
		WHERE ia.id_issue = {int:issue}',
		array(
			'issue' => $issue,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['attachments'][$row['id_attach']] = array(
			'id' => $row['id_attach'],
			'name' => preg_replace('~&amp;#(\\d{1,7}|x[0-9a-fA-F]{1,6});~', '&#\\1;', htmlspecialchars($row['filename'])),
			'href' => $scripturl . '?action=dlattach;issue=' . $issue . '.0;attach=' . $row['id_attach'],
			'link' => '<a href="' . $scripturl . '?action=dlattach;issue=' . $issue . '.0;attach=' . $row['id_attach'] . '">' . htmlspecialchars($row['filename']) . '</a>',
			'extension' => $row['fileext'],
			'downloads' => comma_format($row['downloads']),
			'poster' => $row['real_name'],
			'ip' => $row['poster_ip'],
			'size' => round($row['size'] / 1024, 2) . ' ' . $txt['kilobyte'],
			'byte_size' => $row['size'],
			'is_image' => !empty($row['width']) && !empty($row['height']) && !empty($modSettings['attachmentShowImages']),
		);

		/*if (!$context['attachments'][$row['id_attach']]['is_image'])
			continue;

		$context['attachments'][$row['id_attach']] += array(
			'real_width' => $row['width'],
			'width' => $row['width'],
			'real_height' => $row['height'],
			'height' => $row['height'],
		);*/

	}
	$smcFunc['db_fetch_assoc']($request);

	$context['counter_start'] = $_REQUEST['start'];

	// Template
	$context['sub_template'] = 'issue_view';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
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

function IssueDelete()
{
	global $context, $user_info;

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