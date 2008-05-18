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
	global $smcFunc, $context, $user_info, $txt, $scripturl, $modSettings, $sourcedir;

	if (empty($context['project']))
		fatal_lang_error('project_not_found');

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $type)
	{
		$context['possible_types'][$type] = &$context['project_tools']['issue_types'][$type];
		$context['possible_types'][$type]['selected'] = isset($_REQUEST['type']) && $_REQUEST['type'] == $type;
	}

	$context['issue'] = array(
		'title' => '',
		'type' => isset($_REQUEST['type']) && isset($context['possible_types'][$_REQUEST['type']]) ? $_REQUEST['type'] : '',
		'version' => 0,
		'category' => 0,
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
	$context['destination'] = 'issueReport2';

	$context['show_version'] = !empty($context['project']['versions']);
	$context['show_category'] = !empty($context['project']['category']);

	checkSubmitOnce('register');

	// Template
	$context['linktree'][] = array(
		'name' => $txt['linktree_report_issue'],
		'url' => $scripturl . '?project=' . $context['project']['id'] . ';sa=reportIssue'
	);

	loadTemplate('IssueReport');
	$context['sub_template'] = 'report_issue';
}

function ReportIssue2()
{
	global $smcFunc, $context, $user_info, $txt, $scripturl, $modSettings, $sourcedir;

	if (empty($context['project']))
		fatal_lang_error('project_not_found');

	// If we came from WYSIWYG then turn it back into BBC regardless.
	if (!empty($_REQUEST['details_mode']) && isset($_REQUEST['details']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['details'] = html_to_bbc($_REQUEST['details']);

		// We need to unhtml it now as it gets done shortly.
		$_REQUEST['details'] = un_htmlspecialchars($_REQUEST['details']);

		// We need this for everything else.
		$_POST['details'] = $_REQUEST['details'];
	}

	if (isset($_REQUEST['preview']))
		return IssueReport();

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
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['details'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['message'], '[html]') === false))
			$post_errors[] = 'no_message';
	}

	$context['possible_types'] = array();

	foreach ($context['project']['trackers'] as $type)
		$context['possible_types'][$type] = &$context['project_tools']['issue_types'][$type];

	if (empty($_POST['type']) || !isset($context['possible_types'][$_POST['type']]))
		$post_errors[] = 'no_issue_type';

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

		return IssueReport();
	}

	$_POST['title'] = strtr($smcFunc['htmlspecialchars']($_POST['title']), array("\r" => '', "\n" => '', "\t" => ''));
	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	if ($smcFunc['strlen']($_POST['title']) > 100)
		$_POST['title'] = $smcFunc['substr']($_POST['title'], 0, 100);

	$posterOptions = array(
		'id' => $user_info['id'],
		'name' => $_POST['guestname'],
		'email' => $_POST['email'],
	);
	$issueOptions = array(
		'project' => $context['project']['id'],
		'subject' => $_POST['title'],
		'type' => $_POST['type'],
		'status' => 1,
		'priority' => 2,
		'category' => 0,
		'version' => 0,
		'assignee' => 0,
		'body' => $_POST['details'],
		'created' => time(),
		'updated' => time(),
	);

	createIssue($issueOptions, $posterOptions);

	redirectexit('project=' . $context['project']['id']);
}

?>