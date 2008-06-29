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

function IssueReply()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found');

	$issue = $context['current_issue']['id'];

	$context['destination'] = 'update;full';

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');

	if (isset($_POST['comment']))
		$context['comment'] = $_POST['comment'];
	else
		$context['comment'] = '';

	if (isset($_REQUEST['quote']) && is_numeric($_REQUEST['quote']))
	{
		checkSession('get');

		require_once($sourcedir . '/Subs-Post.php');

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE id_comment = {int:comment}
			ORDER BY id_comment',
			array(
				'issue' => $issue,
				'comment' => $_REQUEST['quote'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
		{
			fatal_lang_error('comment_not_found');
		}
		else
		{
			$context['comment'] .= '[quote author=' . $row['real_name'] . ' link=' . 'issue=' . $issue . '.com' . $_REQUEST['quote'] . '#com' . $_REQUEST['quote'] . ' date=' . $row['post_time'] . "]\n" . un_preparsecode($row['body']) . "\n[/quote]";
		}
	}

	$editorOptions = array(
		'id' => 'comment',
		'value' => $context['comment'],
		'labels' => array(
			'post_button' => $txt['issue_reply'],
		),
	);
	create_control_richedit($editorOptions);

	checkSubmitOnce('register');

	$context['post_box_name'] = 'comment';

	// Template
	$context['sub_template'] = 'issue_reply';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
}

function IssueUpload()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Post.php');

	projectIsAllowedTo('issue_attach');
	$total_size = 0;

	$attachIDs = array();

	foreach ($_FILES['attachment']['tmp_name'] as $n => $dummy)
	{
		if ($_FILES['attachment']['name'][$n] == '')
			continue;

		$total_size += $_FILES['attachment']['size'][$n];
		if (!empty($modSettings['attachmentPostLimit']) && $total_size > $modSettings['attachmentPostLimit'] * 1024)
			fatal_lang_error('file_too_big', false, array($modSettings['attachmentPostLimit']));

		$attachmentOptions = array(
			'poster' => $user_info['id'],
			'name' => $_FILES['attachment']['name'][$n],
			'tmp_name' => $_FILES['attachment']['tmp_name'][$n],
			'size' => $_FILES['attachment']['size'][$n],
			'approved' => !$modSettings['postmod_active'] || allowedTo('post_attachment'),
		);

		if (createAttachment($attachmentOptions))
		{
			$attachIDs[] = $attachmentOptions['id'];
			if (!empty($attachmentOptions['thumb']))
				$attachIDs[] = $attachmentOptions['thumb'];
		}
		else
		{
			if (in_array('could_not_upload', $attachmentOptions['errors']))
				fatal_lang_error('attach_timeout', 'critical');
			if (in_array('too_large', $attachmentOptions['errors']))
				fatal_lang_error('file_too_big', false, array($modSettings['attachmentSizeLimit']));
			if (in_array('bad_extension', $attachmentOptions['errors']))
				fatal_error($attachmentOptions['name'] . '.<br />' . $txt['cant_upload_type'] . ' ' . $modSettings['attachmentExtensions'] . '.', false);
			if (in_array('directory_full', $attachmentOptions['errors']))
				fatal_lang_error('ran_out_of_space', 'critical');
			if (in_array('bad_filename', $attachmentOptions['errors']))
				fatal_error(basename($attachmentOptions['name']) . '.<br />' . $txt['restricted_filename'] . '.', 'critical');
			if (in_array('taken_filename', $attachmentOptions['errors']))
				fatal_lang_error('filename_exisits');
		}
	}

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}attachments
		SET id_issue = {int:issue}
		WHERE id_attach IN({array_int:attach})',
		array(
			'issue' => $context['current_issue']['id'],
			'attach' => $attachIDs,
		)
	);

	$posterOptions = array(
		'id' => $user_info['id'],
		'name' => $user_info['name'],
		'ip' => $user_info['name'],
	);

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
			$context['project']['id'],
			$id_issue,
			$user_info['id'],
			$user_info['name'],
			$user_info['email'],
			$user_info['ip'],
			'upload_attachment',
			time(),
			serialize(array('attachments' => $attachIDs))
		),
		array()
	);

	$id_event = $smcFunc['db_insert_id']('{db_prefix}project_timeline', 'id_event');

	$rows = array();

	foreach ($attachIDs as $id)
		$rows[] = array($context['current_issue']['id'], $id, $user_info['id'], $id_event);

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_attachments',
		array(
			'id_issue' => 'int',
			'id_attach' => 'int',
			'id_member' => 'int',
			'id_event' => 'int',
		),
		$rows,
		array('id_issue', 'id_attach')
	);

	redirectexit('issue=' . $context['current_issue']['id']);
}

function IssueView()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');
	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = (projectAllowedTo('issue_update') && $context['current_issue']['is_mine']) || projectAllowedTo('issue_moderate');
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
		if (projectAllowedTo('issue_assign'))
		{
			$context['can_assign'] = true;
			$context['assign_members'] = &$context['project']['developers'];
		}
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

	/*// How many replies there are (first is "details")
	// Use
	$request = $smcFunc['db_query']('', '
		SELECT (COUNT(*) - 1)
		FROM {db_prefix}issue_comments
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $issue,
		)
	);
	list ($msg) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);*/

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
	else
	{
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
		fatal_lang_error('issue_not_found');

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

function IssueUpdate()
{
	global $context, $user_info, $smcFunc, $sourcedir;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	checkSession();

	if (!empty($_REQUEST['comment_mode']) && isset($_REQUEST['comment']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['comment'] = html_to_bbc($_REQUEST['comment']);
		$_REQUEST['comment'] = un_htmlspecialchars($_REQUEST['comment']);
		$_POST['comment'] = $_REQUEST['comment'];
	}

	if (isset($_REQUEST['full']))
		checkSubmitOnce('check');

	if (isset($_REQUEST['preview']))
		return IssueReply();

	$_POST['guestname'] = $user_info['username'];
	$_POST['email'] = $user_info['email'];

	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => $_POST['guestname'],
		'email' => $_POST['email'],
	);
	$issueOptions = array();

	if (empty($_POST['add_comment']) && (projectAllowedTo('issue_update') || projectAllowedTo('issue_moderate')))
	{
		// Assigning
		if (projectAllowedTo('issue_moderate') && isset($_POST['assign']))
		{
			if ((int) $_POST['assign'] != $context['current_issue']['assignee']['id'])
			{
				if (!isset($context['project']['developers'][(int) $_POST['assign']]))
					$_POST['assign'] = 0;

				$issueOptions['assignee'] = (int) $_POST['assign'];
			}
		}

		// Version
		if (isset($_POST['version']) && $context['current_issue']['version']['id'] != (int) $_POST['version'])
		{
			if (!isset($context['versions_id'][(int) $_POST['version']]))
				$_POST['version'] = 0;

			$issueOptions['version'] = (int) $_POST['version'];
		}

		// Version fixed
		if (projectAllowedTo('issue_moderate') && isset($_POST['version_fixed']) && $context['current_issue']['version_fixed']['id'] != (int) $_POST['version_fixed'])
		{
			if (!isset($context['versions_id'][(int) $_POST['version_fixed']]))
				$_POST['version_fixed'] = 0;

			$issueOptions['version_fixed'] = (int) $_POST['version_fixed'];
		}

		// Category
		if (isset($_POST['category']) && $context['current_issue']['category']['id'] != (int) $_POST['category'])
		{
			if (!isset($context['project']['category'][(int) $_POST['category']]))
				$_POST['category'] = 0;

			$issueOptions['category'] = (int) $_POST['category'];
		}

		// Status
		if (projectAllowedTo('issue_moderate') && isset($_POST['status']) && $context['current_issue']['status']['id'] != (int) $_POST['status'])
		{
			if (isset($context['issue']['status'][(int) $_POST['status']]))
				$issueOptions['status'] = (int) $_POST['status'];
		}

		$context['possible_types'] = array();

		foreach ($context['project']['trackers'] as $id => $type)
			$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];

		if (isset($context['possible_types'][$_POST['type']]))
			$issueOptions['type'] = $_POST['type'];
	}

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$id_event = 0;

	if ($id_event === true)
		$id_event = 0;

	$no_comment = false;

	if (empty($_POST['update_issue2']) && empty($_POST['add_comment']) && !isset($_REQUEST['full']))
		$no_comment = true;

	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['comment'])) == '')
		$no_comment = true;
	else
	{
		require_once($sourcedir . '/Subs-Post.php');

		$_POST['comment'] = $smcFunc['htmlspecialchars']($_POST['comment'], ENT_QUOTES);

		preparsecode($_POST['comment']);
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['comment'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['message'], '[html]') === false))
			$no_comment = true;
	}

	if ($no_comment)
		redirectexit('issue=' . $issue);
	else
	{
		$commentOptions = array(
			'event' => $id_event,
			'body' => $_POST['comment'],
		);
		$id_comment = createComment($context['project']['id'], $issue, $commentOptions, $posterOptions);

		redirectexit('issue=' . $issue . '.com' . $id_comment . '#com' . $id_comment);
	}

	redirectexit('issue=' . $issue);
}
?>