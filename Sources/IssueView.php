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

function IssueView()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');
	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	// Don't index in this case
	if (isset($_REQUEST['comment']))
		$context['robot_no_index'] = true;

	// Mark this issue as read
	if (!$user_info['is_guest'])
	{
		$smcFunc['db_insert']('replace',
			array(
				'id_issue' => 'int',
				'id_member' => 'int',
				'id_comment' => 'int',
			),
			array(
				$context['current_issue']['id'],
				$user_info['id'],
				$context['current_issue']['id']
			),
			array('id_issue', 'id_member')
		);
	}

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type);

	$context['page_index'] = '';

	if ((projectAllowedTo('issue_update') && $context['current_issue']['is_mine']) || projectAllowedTo('issue_moderate'))
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

	$request = $smcFunc['db_query']('', '
		SELECT id_comment, id_member
		FROM {db_prefix}issue_comments
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $issue,
		)
	);
	$posters = array();
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
		'is_new' => empty($row['is_read']),
		'first_new' => $first_new && empty($row['is_read']),
	);

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

	if (empty($_POST['add_comment']) && (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate')))
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
		createComment($context['project']['id'], $issue, $commentOptions, $posterOptions);
	}

	redirectexit('issue=' . $issue);
}
?>