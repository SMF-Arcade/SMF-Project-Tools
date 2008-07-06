<?php
/**********************************************************************************
* IssueReport.php                                                                 *
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

/*	Report Issue / Edit issue

	void ReportIssue()
		- Shows report form

	void ReportIssue2()
		- Validates
		- Shows form again if theres errors
		- Calls createIssue which handles rest

*/

function ReportIssue()
{
	global $smcFunc, $context, $user_info, $txt, $scripturl, $modSettings, $sourcedir, $project;

	projectIsAllowedTo('issue_report');

	if (!isset($context['versions']))
		list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $id => $type)
	{
		$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];
		$context['possible_types'][$id]['selected'] = isset($_REQUEST['type']) && $_REQUEST['type'] == $id;
	}

	$context['issue'] = array(
		'title' => '',
		'type' => isset($_REQUEST['type']) && isset($context['possible_types'][$_REQUEST['type']]) ? $_REQUEST['type'] : '',
		'version' => isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : 0,
		'category' => isset($_REQUEST['category']) ? (int) $_REQUEST['category'] : 0,
	);
	$context['details'] = '';

	if (isset($_POST['title']))
		$context['issue']['title'] = $_POST['title'];

	if (isset($_POST['details']))
		$context['details'] = $_POST['details'];

	$context['issue']['title'] = addcslashes($context['issue']['title'], '"');
	$context['details'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $context['details']);

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');

	$editorOptions = array(
		'id' => 'details',
		'value' => $context['details'],
		'labels' => array(
			'post_button' => $txt['report_issue'],
		),
	);
	create_control_richedit($editorOptions);

	$context['post_box_name'] = 'details';
	$context['destination'] = 'reportIssue2';

	$context['show_version'] = !empty($context['versions']);
	$context['show_category'] = !empty($context['project']['category']);

	checkSubmitOnce('register');

	$context['linktree'][] = array(
		'name' => $txt['linktree_report_issue'],
		'url' => $scripturl . '?project=' . $project . ';sa=reportIssue'
	);

	// Template
	loadTemplate('IssueReport');

	$context['sub_template'] = 'report_issue';
	$context['page_title'] = sprintf($txt['project_report_issue'], $context['project']['name']);
}

function ReportIssue2()
{
	global $smcFunc, $context, $user_info, $txt, $scripturl, $modSettings, $sourcedir, $project;

	projectIsAllowedTo('issue_report');

	if (!isset($context['versions']))
		list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	if (!empty($_REQUEST['details_mode']) && isset($_REQUEST['details']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['details'] = html_to_bbc($_REQUEST['details']);
		$_REQUEST['details'] = un_htmlspecialchars($_REQUEST['details']);
		$_POST['details'] = $_REQUEST['details'];
	}

	if (isset($_REQUEST['preview']))
		return ReportIssue();

	require_once($sourcedir . '/Subs-Post.php');

	checkSubmitOnce('check');

	$post_errors = array();

	if (checkSession('post', '', false) != '')
		$post_errors[] = 'session_timeout';
	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['title'])) == '')
		$post_errors[] = 'no_tile';
	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['details'])) == '')
		$post_errors[] = 'no_message';
	else
	{
		$_POST['details'] = $smcFunc['htmlspecialchars']($_POST['details'], ENT_QUOTES);

		preparsecode($_POST['details']);
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['details'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['details'], '[html]') === false))
			$post_errors[] = 'no_message';
	}

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $id => $type)
		$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];

	if (empty($_POST['type']) || !isset($context['possible_types'][$_POST['type']]))
		$post_errors[] = 'no_issue_type';
	if (!empty($_POST['version']) && !isset($context['versions_id'][$_POST['version']]))
		$_POST['version'] = 0;

	$_POST['guestname'] = $user_info['username'];
	$_POST['email'] = $user_info['email'];

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

		return ReportIssue();
	}

	$_POST['title'] = strtr($smcFunc['htmlspecialchars']($_POST['title']), array("\r" => '', "\n" => '', "\t" => ''));
	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	if ($smcFunc['strlen']($_POST['title']) > 100)
		$_POST['title'] = $smcFunc['substr']($_POST['title'], 0, 100);

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => $_POST['guestname'],
		'email' => $_POST['email'],
	);
	$issueOptions = array(
		'project' => $project,
		'subject' => $_POST['title'],
		'type' => $_POST['type'],
		'status' => 1,
		'priority' => 2,
		'category' => isset($_POST['category']) ? (int) $_POST['category'] : 0,
		'version' => !empty($_POST['version']) ? (int) $_POST['version'] : 0,
		'assignee' => 0,
		'body' => $_POST['details'],
		'created' => time(),
		'updated' => time(),
	);

	createIssue($issueOptions, $posterOptions);

	redirectexit('project=' . $project . ';sa=issues');
}

function IssueReply()
{
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found');

	if (!isset($context['versions']))
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

	$context['destination'] = 'reply2';

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

function IssueReply2()
{
	global $context, $user_info, $smcFunc, $sourcedir;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found');

	if (!isset($context['versions']))
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

	if (projectAllowedTo('issue_update') || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions);

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$id_event = 0;

	if ($id_event === true)
		$id_event = 0;

	$commentOptions = array(
		'event' => $id_event,
		'body' => $_POST['comment'],
	);
	$id_comment = createComment($context['project']['id'], $issue, $commentOptions, $posterOptions);

	redirectexit('issue=' . $issue . '.com' . $id_comment . '#com' . $id_comment);
}

function IssueUpdate()
{
	global $context, $user_info, $smcFunc, $sourcedir;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	checkSession('get');

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

	if (projectAllowedTo('issue_update') || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions);

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$context['update_success'] = false;

	if ($id_event === true)
		$id_event = 0;

	$context['update_success'] = true;

	// Template
	loadTemplate('Xml');
	$context['sub_template'] = 'issue_update';
}

function template_issue_update()
{
	global $context, $settings, $options, $txt;

	echo '<', '?xml version="1.0" encoding="', $context['character_set'], '"?', '>
<smf>
	<update success="', $context['update_success'] ? 1 : 0, '"></update>
</smf>';
}

function handleUpdate(&$posterOptions, &$issueOptions)
{
	global $context, $user_info, $smcFunc, $sourcedir;

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	// Assigning
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['assign']))
	{
		if ((int) $_REQUEST['assign'] != $context['current_issue']['assignee']['id'])
		{
			if (!isset($context['project']['developers'][(int) $_REQUEST['assign']]))
				$_REQUEST['assign'] = 0;

			$issueOptions['assignee'] = (int) $_REQUEST['assign'];
		}
	}

	// Priority
	if (isset($_REQUEST['priority']) && $context['current_issue']['priority_num'] != (int) $_REQUEST['priority'])
	{
		if (isset($context['issue']['priority'][(int) $_REQUEST['priority']]))
			$issueOptions['priority'] = (int) $_REQUEST['priority'];
	}

	// Version
	if (isset($_REQUEST['version']) && $context['current_issue']['version']['id'] != (int) $_REQUEST['version'])
	{
		if (!isset($context['versions_id'][(int) $_REQUEST['version']]))
			$_REQUEST['version'] = 0;

		$issueOptions['version'] = (int) $_REQUEST['version'];
	}

	// Version fixed
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['version_fixed']) && $context['current_issue']['version_fixed']['id'] != (int) $_REQUEST['version_fixed'])
	{
		if (!isset($context['versions_id'][(int) $_REQUEST['version_fixed']]))
			$_REQUEST['version_fixed'] = 0;

		$issueOptions['version_fixed'] = (int) $_REQUEST['version_fixed'];
	}

	// Category
	if (isset($_REQUEST['category']) && $context['current_issue']['category']['id'] != (int) $_REQUEST['category'])
	{
		if (!isset($context['project']['category'][(int) $_REQUEST['category']]))
			$_REQUEST['category'] = 0;

		$issueOptions['category'] = (int) $_REQUEST['category'];
	}

	// Status
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['status']) && $context['current_issue']['status']['id'] != (int) $_REQUEST['status'])
	{
		if (isset($context['issue']['status'][(int) $_REQUEST['status']]))
			$issueOptions['status'] = (int) $_REQUEST['status'];
	}

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $id => $type)
		$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];

	if (isset($context['possible_types'][$_REQUEST['type']]))
		$issueOptions['type'] = $_REQUEST['type'];
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
			$context['current_issue']['id'],
			$user_info['id'],
			$user_info['name'],
			$user_info['email'],
			$user_info['ip'],
			'new_attachment',
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

	redirectexit('issue=' . $context['current_issue']['id'] . '.0');
}

?>