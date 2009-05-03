<?php
/**********************************************************************************
* IssueReport.php                                                                 *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.3                               *
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
	global $smcFunc, $context, $user_info, $txt, $modSettings, $sourcedir, $project, $options;

	projectIsAllowedTo('issue_report');
	require_once($sourcedir . '/Subs-Post.php');

	$context['can_subscribe'] = !$user_info['is_guest'];

	$context['issue'] = array(
		'title' => '',
		'private' => !empty($_REQUEST['private']),
		'tracker' => isset($_REQUEST['tracker']) && isset($context['project']['trackers'][$_REQUEST['tracker']]) ? $_REQUEST['tracker'] : '',
		'version' => isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : 0,
		'category' => isset($_REQUEST['category']) ? (int) $_REQUEST['category'] : 0,
	);

	$context['notify'] = isset($_POST['issue_subscribe']) ? !empty($_POST['issue_subscribe']) : !empty($options['auto_notify']);

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
			$previewing = !empty($_POST['preview']);

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
		'url' => project_get_url(array('project' => $project, 'sa' => 'reportIssue')),
	);

	// Template
	loadTemplate('IssueReport');

	$context['sub_template'] = 'report_issue';
	$context['page_title'] = sprintf($txt['project_report_issue'], $context['project']['name']);
}

function ReportIssue2()
{
	global $smcFunc, $context, $user_info, $txt, $modSettings, $sourcedir, $project;

	projectIsAllowedTo('issue_report');

	$context['can_subscribe'] = !$user_info['is_guest'];

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

	if (count($context['project']['trackers']) == 1)
		list ($_POST['tracker']) = array_keys($context['project']['trackers']);

	if (empty($_POST['tracker']) || !isset($context['project']['trackers'][$_POST['tracker']]))
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
		'tracker' => $_POST['tracker'],
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

	$issueOptions['id'] = createIssue($issueOptions, $posterOptions);

	// Send notifications
	sendProjectNotification($issueOptions, 'new_issue', $user_info['id']);

	if (!empty($_POST['issue_subscribe']) && $context['can_subscribe'])
	{
		$smcFunc['db_insert']('',
			'{db_prefix}log_notify_projects',
			array('id_project' => 'int', 'id_issue' => 'int', 'id_member' => 'int', 'sent' => 'int'),
			array(0, $issueOptions['id'], $user_info['id'], 0),
			array('id_project', 'id_issue', 'id_member')
		);
	}

	cache_put_data('project-' . $project, null, 120);

	redirectexit(project_get_url(array('project' => $project, 'sa' => 'issues')));
}

function IssueUpdate()
{
	global $context, $user_info, $smcFunc, $issue, $sourcedir;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found', false);

	is_not_guest();

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
	$issueOptions = array(
		'mark_read' => true,
	);

	$context['xml_data'] = array(
		'updates' => array(
			'identifier' => 'update',
			'children' => array(
			),
		),
	);


	if (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions, true);

	if (!empty($issueOptions))
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	else
		$id_event = false;

	$context['xml_data']['success'] = array(
		'identifier' => 'success',
		'children' => array(
			array('value' => $id_event !== false ? 1 : 0)
		),		
	);

	// Template
	loadTemplate('Xml');
	$context['sub_template'] = 'generic_xml';
}

function handleUpdate(&$posterOptions, &$issueOptions, $xml_data = false)
{
	global $context, $user_info, $smcFunc, $sourcedir;

	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	// Assigning
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['assign']))
	{
		if ((int) $_REQUEST['assign'] != $context['current_issue']['assignee']['id'])
		{
			if (!isset($context['project']['developers'][(int) $_REQUEST['assign']]))
				$_REQUEST['assign'] = 0;

			$issueOptions['assignee'] = (int) $_REQUEST['assign'];
			
			if ($xml_data)
				$context['xml_data']['updates']['children'][] = array('field' => 'assign', 'value' => $issueOptions['assignee']);
		}
	}

	// Title
	if (!empty($_REQUEST['title']) && trim($_REQUEST['title']) != '')
	{
		$_REQUEST['title'] = strtr($smcFunc['htmlspecialchars']($_REQUEST['title']), array("\r" => '', "\n" => '', "\t" => ''));
		$issueOptions['subject'] = $_REQUEST['title'];
		
		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'subject', 'value' => $issueOptions['subject']);
	}

	// Private
	if (isset($_REQUEST['private']))
		$issueOptions['private'] = !empty($_REQUEST['private']);

	// Priority
	if (isset($_REQUEST['priority']) && isset($context['issue']['priority'][(int) $_REQUEST['priority']]))
	{
		$issueOptions['priority'] = (int) $_REQUEST['priority'];
		
		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'priority', 'value' => $issueOptions['priority']);
	}

	// Version
	if (isset($_REQUEST['version']))
	{
		if (!isset($context['versions_id'][(int) $_REQUEST['version']]))
			$_REQUEST['version'] = 0;

		$issueOptions['version'] = (int) $_REQUEST['version'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'version', 'value' => $issueOptions['version']);
	}

	// Version fixed
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['version_fixed']))
	{
		if (!isset($context['versions_id'][(int) $_REQUEST['version_fixed']]))
			$_REQUEST['version_fixed'] = 0;

		$issueOptions['version_fixed'] = (int) $_REQUEST['version_fixed'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'version_fixed', 'value' => $issueOptions['version_fixed']);
	}

	// Category
	if (isset($_REQUEST['category']))
	{
		if (!isset($context['project']['category'][(int) $_REQUEST['category']]))
			$_REQUEST['category'] = 0;

		$issueOptions['category'] = (int) $_REQUEST['category'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'category', 'value' => $issueOptions['category']);
	}

	// Status
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['status']))
	{
		if (isset($context['issue_status'][(int) $_REQUEST['status']]))
			$issueOptions['status'] = (int) $_REQUEST['status'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'status', 'value' => $issueOptions['status']);
	}

	if (isset($_REQUEST['tracker']) && isset($context['project']['trackers'][$_REQUEST['tracker']]))
	{
		$issueOptions['tracker'] = $_REQUEST['tracker'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array('field' => 'tracker', 'value' => $issueOptions['tracker']);
	}
}

function IssueUpload()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Post.php');

	// Not possible
	if (empty($modSettings['projectAttachments']))
		redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));

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
		'email' => $user_info['email'],
		'ip' => $user_info['ip'],
	);
	$eventOptions = array(
		'time' => time(),
	);

	$id_event = createTimelineEvent($context['current_issue']['id'], $context['project']['id'], 'new_attachment', serialize(array('attachments' => $attachIDs)), $posterOptions, $eventOptions);

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

	redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));
}

?>