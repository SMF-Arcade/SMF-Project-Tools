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
	global $context, $smcFunc, $db_prefix, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');
	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type);

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

	// Load Comments
	$context['comments'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT c.id_commment, c.post_time, c.edit_time, c.body, c.poster_ip,
			mem.id_member, IFNULL(mem.real_name, c.poster_name) AS real_name
		FROM {db_prefix}issue_comments AS c
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $issue,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['comments'][] = array(
			'id' => $row['id_comment'],
			'post_time' => timeformat($row['post_time']),
			'edit_time' => timeformat($row['edit_time']),
			'body' => parse_bbc($row['body']),
			''
		);
	}
	$smcFunc['db_free_result']($request);

	// Template
	$context['sub_template'] = 'issue_view';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
}

function IssueDelete()
{
	global $context, $user_info;

	checkSession('get');

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	projectIsAllowedTo('issue_moderate');

	$posterOptions = array(
		'id' => $user_info['id']
	);

	deleteIssue($context['current_issue']['id'], $posterOptions);

	redirectexit('project=' . $context['project']['id'] . ';sa=issues');
}

function IssueUpdate()
{
	global $context, $user_info;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');
	list ($context['versions'], $context['versions_id']) = loadVersions($context['project']);

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	checkSession();

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

	// DEBUG
	//print_r(array($_POST, $issueOptions));
	//die();

	if (!empty($issueOptions))
	{
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	}
	else
	{
		$id_event = 0;
	}

	$no_comment = false;

	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['comment'])) == '')
	{
		$no_comment = true;
	}
	else
	{
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
		createComment($issue, $commentOptions, $posterOptions);
	}

	redirectexit('issue=' . $issue);
}
?>