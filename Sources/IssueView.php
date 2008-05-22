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

	$issue = $context['current_issue']['id'];
	$type = $context['current_issue']['is_my'] ? 'own' : 'any';

	if (allowedTo('issue_update_' . $type))
	{
		require_once($sourcedir . '/Subs-Members.php');

		$context['can_update'] = true;

		/*if (allowedTo('issue_assign'))
		{
			$context['can_assign'] = true;
			$context['assign_members'] = array();

			$groups = groupsAllowedTo('issue_assign_to');

			$request = $smcFunc['db_query']('', "
				SELECT mem.id_member, mem.member_name, mem.real_name
				FROM {$db_prefix}members AS mem
				WHERE (id_group IN ('" . implode(', ', $groups['allowed']) . ") OR FIND_IN_SET(" . implode(', mem.additional_groups) OR FIND_IN_SET(', $groups['allowed']) . ")
					AND NOT (id_group IN ('" . implode(', ', $groups['denied']) . ") OR FIND_IN_SET(" . implode(', mem.additional_groups) OR FIND_IN_SET(', $groups['denied']) . ")", __FILE__, __LINE__);

			while ($row = $smcFunc['db_fetch_assoc']($request))
				$context['assign_members'][] = array($row['id_member'], $row['member_name'], $row['real_name']);
			$smcFunc['db_free_result']($request);
		}*/
	}

	// Template
	$context['sub_template'] = 'issue_view';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueView');
}

function IssueDelete()
{
	global $context;

	if (!isset($context['current_issue']))
		fatal_lang_error('issue_not_found');

	checkSession('get');

	isAllowedTo('issue_delete');

	deleteIssue($context['current_issue']['id']);

	redirectexit('project=' . $_REQUEST['project']. ';sa=issues');
}
?>