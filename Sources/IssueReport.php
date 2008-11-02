<?php
/**********************************************************************************
* IssueReport.php                                                                 *
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
	require_once($sourcedir . '/Subs-Post.php');

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $id => $type)
	{
		$context['possible_types'][$id] = &$context['project_tools']['issue_types'][$id];
		$context['possible_types'][$id]['selected'] = isset($_REQUEST['type']) && $_REQUEST['type'] == $id;
	}

	$context['issue'] = array(
		'title' => '',
		'private' => !empty($_REQUEST['private']),
		'type' => isset($_REQUEST['type']) && isset($context['possible_types'][$_REQUEST['type']]) ? $_REQUEST['type'] : '',
		'version' => isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : 0,
		'category' => isset($_REQUEST['category']) ? (int) $_REQUEST['category'] : 0,
	);

	$form_title = '';
	$form_details = '';

	if (isset($_REQUEST['details']) || !empty($context['post_error']))
	{
		if (!isset($_REQUEST['title']))
			$_REQUEST['title'] = '';
		if (!isset($_REQUEST['details']))
			$_REQUEST['details'] = '';

		if (empty($context['post_error']))
		{
			// TODO CHECKS

			$previewing = true;
		}
		else
		{
			$previewing = !empty($_POST['preview']);
		}

		$form_title = strtr($smcFunc['htmlspecialchars']($_REQUEST['title']), array("\r" => '', "\n" => '', "\t" => ''));
		$form_details = $smcFunc['htmlspecialchars']($_REQUEST['details'], ENT_QUOTES);

		if ($previewing)
		{
			$context['preview_details'] = $form_details;
			preparsecode($form_details, true);
			preparsecode($context['preview_details']);

			$context['preview_details'] = parse_bbc($context['preview_details']);
			censorText($context['preview_details']);

			if ($form_title != '')
			{
				$context['preview_title'] = $form_title;
				censorText($context['preview_title']);
			}
			else
			{
				$context['preview_title'] = '<i>' . $txt['issue_no_title'] . '</i>';
			}
		}

		$context['issue']['title'] = $_REQUEST['title'];
		$context['details'] = $_REQUEST['details'];
	}

	$context['issue']['title'] = addcslashes($form_title, '"');
	$context['details'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $form_details);

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');

	$editorOptions = array(
		'form' => 'reportissue',
		'id' => 'details',
		'value' => $context['details'],
		'labels' => array(
			'post_button' => $txt['report_issue'],
		),
	);
	create_control_richedit($editorOptions);

	$context['post_box_name'] = $editorOptions['id'];
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
			$post_errors[] = 'no_details';
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
		'private' => !empty($_REQUEST['private']),
		'mark_read' => true,
	);

	createIssue($issueOptions, $posterOptions);

	redirectexit('project=' . $project . ';sa=issues');
}

function IssueUpdate()
{
	global $context, $user_info, $smcFunc, $sourcedir;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	is_not_guest();

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

	if (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions);

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$context['update_success'] = false;

	if (!isset($id_event) || $id_event === true)
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

	// Title
	if (!empty($_REQUEST['title']) && trim($_REQUEST['title']) != '')
	{
		$_REQUEST['title'] = strtr($smcFunc['htmlspecialchars']($_REQUEST['title']), array("\r" => '', "\n" => '', "\t" => ''));
		$issueOptions['title'] = $_REQUEST['title'];
	}

	// Private
	if (isset($_REQUEST['private']) && $context['current_issue']['private'] != !empty($_REQUEST['private']))
		$issueOptions['private'] = !empty($_REQUEST['private']);

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

	if (isset($_REQUEST['type']) && isset($context['possible_types'][$_REQUEST['type']]))
		$issueOptions['type'] = $_REQUEST['type'];
}

function IssueUpload()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Post.php');

	// Not possible
	if (empty($modSettings['projectAttachments']))
		redirectexit('issue=' . $context['current_issue']['id'] . '.0');

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

	if (empty($attachIDs))
		fatal_lang_error('no_files_selected');

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