<?php
/**********************************************************************************
* IssueComment.php                                                                *
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

function IssueReply()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $issue, $modSettings;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found', false);

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
			$context['possible_types'][$id] = &$context['issue_types'][$id];

		if (isset($context['possible_types'][$context['current_issue']['type']['id']]))
			$context['possible_types'][$context['current_issue']['type']['id']]['selected'] = true;

		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = &$context['project']['developers'];
	}

	$context['destination'] = 'reply2';

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');
	require_once($sourcedir . '/Subs-Post.php');

	$editing = false;

	$form_comment = '';

	// Editing
	if ($_REQUEST['sa'] == 'edit' || $_REQUEST['sa'] == 'edit2')
	{
		projectIsAllowedTo('edit_comment_own');
		require_once($sourcedir . '/Subs-Post.php');

		if (empty($_REQUEST['com']) || !is_numeric($_REQUEST['com']))
			fatal_lang_error('comment_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
				AND c.id_member = {int:current_user}' : '') . '
			ORDER BY id_comment',
			array(
				'current_user' => $user_info['id'],
				'issue' => $issue,
				'comment' => (int) $_REQUEST['com'],
			)
		);

		$context['destination'] = 'edit2;com=' . (int) $_REQUEST['com'];

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('comment_not_found', false);

		$form_comment = un_preparsecode($row['body']);

		$editing = true;
	}
	elseif (isset($_REQUEST['quote']) && is_numeric($_REQUEST['quote']))
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
			fatal_lang_error('comment_not_found', false);

		$form_comment = $row['body'];
		censorText($form_comment);

		// fix html tags
		if (strpos($form_comment, '[html]') !== false)
		{
			$parts = preg_split('~(\[/code\]|\[code(?:=[^\]]+)?\])~i', $form_comment, -1, PREG_SPLIT_DELIM_CAPTURE);
			for ($i = 0, $n = count($parts); $i < $n; $i++)
			{
				// It goes 0 = outside, 1 = begin tag, 2 = inside, 3 = close tag, repeat.
				if ($i % 4 == 0)
					$parts[$i] = preg_replace('~\[html\](.+?)\[/html\]~ise', '\'[html]\' . preg_replace(\'~<br\s?/?>~i\', \'&lt;br /&gt;<br />\', \'$1\') . \'[/html]\'', $parts[$i]);
			}
			$form_comment = implode('', $parts);
		}

		$form_comment = preg_replace('~<br(?: /)?' . '>~i', "\n", $form_comment);
		$form_comment = '[quote author=' . $row['real_name'] . ' link=' . 'issue=' . $issue . '.com' . $_REQUEST['quote'] . '#com' . $_REQUEST['quote'] . ' date=' . $row['post_time'] . "]\n" . rtrim($form_comment) . "\n[/quote]";
	}

	if (isset($_REQUEST['comment']) || !empty($context['post_error']))
	{
		if (!isset($_REQUEST['details']))
			$_REQUEST['details'] = '';

		if (empty($context['post_error']))
		{
			// TODO CHECKS

			$previewing = true;
		}
		else
			$previewing = !empty($_POST['preview']);

		$form_comment = $smcFunc['htmlspecialchars']($_REQUEST['comment'], ENT_QUOTES);

		if ($previewing)
		{
			$context['preview_comment'] = $form_comment;
			preparsecode($form_comment, true);
			preparsecode($context['preview_comment']);

			$context['preview_comment'] = parse_bbc($context['preview_comment']);
			censorText($context['preview_comment']);
		}

		$context['comment'] = $_REQUEST['comment'];
	}

	$context['comment'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $form_comment);

	$editorOptions = array(
		'form' => 'reportissue',
		'id' => 'comment',
		'value' => $context['comment'],
		'labels' => array(
			'post_button' => $editing ? $txt['issue_save'] : $txt['issue_reply'],
		),
	);
	create_control_richedit($editorOptions);

	checkSubmitOnce('register');

	$context['post_box_name'] = 'comment';

	// Template
	$context['sub_template'] = 'issue_reply';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueReport');
}

function IssueReply2()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $issue, $modSettings;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found', false);

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
			$context['possible_types'][$id] = &$context['issue_types'][$id];

		if (isset($context['possible_types'][$context['current_issue']['type']['id']]))
			$context['possible_types'][$context['current_issue']['type']['id']]['selected'] = true;

		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = &$context['project']['developers'];
	}

	if (!empty($_REQUEST['comment_mode']) && isset($_REQUEST['comment']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['comment'] = html_to_bbc($_REQUEST['comment']);
		$_REQUEST['comment'] = un_htmlspecialchars($_REQUEST['comment']);
		$_POST['comment'] = $_REQUEST['comment'];
	}

	if (isset($_REQUEST['preview']))
		return IssueReply();

	require_once($sourcedir . '/Subs-Post.php');
	require_once($sourcedir . '/IssueReport.php');

	checkSubmitOnce('check');

	$post_errors = array();

	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['comment'])) == '')
		$post_errors[] = 'no_message';
	else
	{
		$_POST['comment'] = $smcFunc['htmlspecialchars']($_POST['comment'], ENT_QUOTES);

		preparsecode($_POST['comment']);
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['comment'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['message'], '[html]') === false))
			$post_errors[] = 'no_message';
	}

	if (!empty($post_errors))
	{
		loadLanguage('Errors');
		$_REQUEST['preview'] = true;

		$context['post_error'] = array('messages' => array());
		foreach ($post_errors as $post_error)
		{
			$context['post_error'][$post_error] = true;
			$context['post_error']['messages'][] = $txt['error_' . $post_error];
		}

		return IssueReply();
	}

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

	if (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions);

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$id_event = 0;

	if ($id_event === true)
		$id_event = 0;

	if (empty($_REQUEST['com']))
	{
		$commentOptions = array(
			'event' => $id_event,
			'body' => $_POST['comment'],
		);
		$id_comment = createComment($context['project']['id'], $issue, $commentOptions, $posterOptions);

		$issueN = array(
			'id' => $issue,
		);

		sendIssueNotification($issueN, $commentOptions, 'new_comment', $user_info['id']);
	}
	else
	{
		projectIsAllowedTo('edit_comment_own');
		require_once($sourcedir . '/Subs-Post.php');

		if (empty($_REQUEST['com']) || !is_numeric($_REQUEST['com']))
			fatal_lang_error('comment_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
				AND c.id_member = {int:current_user}' : '') . '
			ORDER BY id_comment',
			array(
				'current_user' => $user_info['id'],
				'issue' => $issue,
				'comment' => (int) $_REQUEST['com'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('comment_not_found', false);

		$commentOptions = array(
			'body' => $_POST['comment'],
		);

		modifyComment($_REQUEST['com'], $issue, $commentOptions, $posterOptions);

		$id_comment = $_REQUEST['com'];
	}

	redirectexit(project_get_url(array('issue' => $issue . '.com' . $id_comment)) . '#com' . $id_comment);
}

function IssueDeleteComment()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']) || empty($_REQUEST['com']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('edit_comment_own');
	require_once($sourcedir . '/Subs-Post.php');

	$request = $smcFunc['db_query']('', '
		SELECT c.id_comment
		FROM {db_prefix}issue_comments AS c
		WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
			AND c.id_member = {int:current_user}' : '') . '
		ORDER BY id_comment',
		array(
			'current_user' => $user_info['id'],
			'comment' => (int) $_REQUEST['com'],
		)
	);

	$row = $smcFunc['db_fetch_assoc']($request);
	if (!$row)
		fatal_lang_error('comment_not_found', false);
	$smcFunc['db_free_result']($request);

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}issue_comments AS c
		WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
			AND c.id_member = {int:current_user}' : '') . '
		ORDER BY id_comment',
		array(
			'current_user' => $user_info['id'],
			'comment' => $row['id_comment'],
		)
	);

	redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));
}

?>
