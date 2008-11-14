<?php
/**********************************************************************************
* Subs-Project.php                                                                *
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

/*
	!!!
*/

function loadProjectTools()
{
	global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $project_version, $settings, $issue;

	if (!empty($project_version))
		return;

	// Which version this is?
	$project_version = '0.2';

	if (isset($_REQUEST['issue']) && strpos($_REQUEST['issue'], '.') !== false)
	{
		list ($_REQUEST['issue'], $_REQUEST['start']) = explode('.', $_REQUEST['issue'], 2);
		$issue = (int) $_REQUEST['issue'];
	}
	elseif (isset($_REQUEST['issue']))
		$issue = (int) $_REQUEST['issue'];
	else
		$issue = 0;

	// Issue Regex
	if (empty($modSettings['issueRegex']))
		$modSettings['issueRegex'] = array('[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+', '(\d+)');
	else
		$modSettings['issueRegex'] = explode("\n", $modSettings['issueRegex'], 2);

	// Administrators can see all projects.
	if ($user_info['is_admin'] || allowedTo('project_admin'))
	{
		$see_project = '1 = 1';
		$see_version = '1 = 1';
		$see_issue = '1 = 1';
		$see_issue_p = '1 = 1';
	}
	else
	{
		// This is for private issues

		$my_issue = $user_info['is_guest'] ? '(0 = 1)' : '(i.id_reporter = ' . $user_info['id'] . ')';
		$see_private_profiles = getPrivateProfiles();
		if (!empty($see_private_profiles))
			$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR (' . $my_issue . ' OR p.id_profile IN(' . implode(', ', $see_private_profiles) . ')))';
		else
			$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR ' . $my_issue . ')';

		$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';
		$see_version = '(ISNULL(ver.member_groups) OR (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups)))';
		$see_issue = '(' . $see_version . ' AND ' . $see_private . ')';
		$see_issue_p = '(' . $see_version . ' AND (i.private_issue = 0 OR ' . $my_issue . '))';
	}

	$user_info['query_see_project'] = $see_project;
	$user_info['query_see_version'] = $see_version;
	$user_info['query_see_issue'] = $see_issue;
	$user_info['query_see_issue_project'] = $see_issue_p;

	// Issue Types
	$context['issue_types'] = array(
		'bug' => array(
			'id' => 'bug',
			'image' => 'bug.png',
		),
		'feature' => array(
			'id' => 'feature',
			'image' => 'feature.png',
		),
	);

	// Make list of columns that need to be selected
	$context['type_columns'] = array();
	foreach ($context['issue_types'] as $id => $info)
	{
		$context['type_columns'][] = "open_$id";
		$context['type_columns'][] = "closed_$id";
	}

	// Status, types, priorities
	$context['issue_status'] = array(
		1 => array(
			'id' => 1,
			'name' => 'new',
			'type' => 'open',
		),
		2 => array(
			'id' => 2,
			'name' => 'feedback',
			'type' => 'open',
		),
		3 => array(
			'id' => 3,
			'name' => 'confirmed',
			'type' => 'open',
		),
		4 => array(
			'id' => 4,
			'name' => 'assigned',
			'type' => 'open',
		),
		5 => array(
			'id' => 5,
			'name' => 'resolved',
			'type' => 'closed',
		),
		6 => array(
			'id' => 6,
			'name' => 'closed',
			'type' => 'closed',
		),
	);

	$context['closed_status'] = array(5, 6);

	// Priorities
	$context['issue']['priority'] = array(
		1 => 'issue_priority_low',
		'issue_priority_normal',
		'issue_priority_high'
	);
}

// Loads current project
function loadProject()
{
	global $context, $smcFunc, $scripturl, $user_info, $user_info, $project, $issue;

	// Project as parameter?
	if (!empty($_REQUEST['project']))
		$project = (int) $_REQUEST['project'];
	// Do we have issue?
	elseif (!empty($issue))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_project
			FROM {db_prefix}issues
			WHERE id_issue = {int:issue}',
			array(
				'issue' => (int) $issue
			)
		);

		list ($project) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if (empty($project))
			fatal_lang_error('issue_not_found', false);

		$_REQUEST['project'] = $project;
	}
	// Not needed
	else
		return;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.id_profile, p.name, p.description, p.long_description, p.trackers, p.member_groups,
			p.id_comment_mod, p.' . implode(', p.', $context['type_columns']) . ',
			dev.id_member AS is_dev
		FROM {db_prefix}projects AS p
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})
		WHERE {query_see_project}
			AND p.id_project = {int:project}
		LIMIT 1',
		array(
			'project' => $project,
			'current_member' => $user_info['id'],
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('project_not_found', false);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['project'] = array(
		'id' => $row['id_project'],
		'link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
		'href' => project_get_url(array('project' => $row['id_project'])),
		'name' => $row['name'],
		'description' => $row['description'],
		'long_description' => $row['long_description'],
		'category' => array(),
		'groups' => explode(',', $row['member_groups']),
		'trackers' => array(),
		'developers' => array(),
		'is_developer' => !empty($row['is_dev']),
		'comment_mod' => $row['id_comment_mod'],
		'profile' => $row['id_profile'],
	);

	$trackers = explode(',', $row['trackers']);

	foreach ($trackers as $key)
	{
		$context['project']['trackers'][$key] = array(
			'info' => &$context['issue_types'][$key],
			'open' => $row['open_' . $key],
			'closed' => $row['closed_' . $key],
			'total' => $row['open_' . $key] + $row['closed_' . $key],
			'link' => project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'type' => $key)),
		);
	}

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT
			id_version, id_parent, version_name, release_date, status
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}
			AND {query_see_version}
		ORDER BY id_parent, version_name',
		array(
			'project' => $context['project']['id'],
		)
	);

	$context['versions'] = array();
	$context['versions_id'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_parent'] == 0)
		{
			$context['versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'sub_versions' => array(),
			);
		}
		else
		{
			if (!isset($context['versions'][$row['id_parent']]))
				continue;

			$context['versions'][$row['id_parent']]['sub_versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
				'released' => $row['status'] >= 4,
			);
		}

		$context['versions_id'][$row['id_version']] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);

	// Developers
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name
		FROM {db_prefix}project_developer AS dev
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
		WHERE id_project = {int:project}',
		array(
			'project' => $project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['project']['developers'][$row['id_member']] = array(
			'id' => $row['id_member'],
			'name' => $row['real_name'],
		);
	}
	$smcFunc['db_free_result']($request);

	// Category
	$request = $smcFunc['db_query']('', '
		SELECT id_category, category_name
		FROM {db_prefix}issue_category AS cat
		WHERE id_project = {int:project}',
		array(
			'project' => $project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['project']['category'][$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name']
		);
	$smcFunc['db_free_result']($request);

	if ($context['project']['is_developer'])
	{
		$user_info['query_see_issue_project'] = $user_info['query_see_version'];
	}
	elseif (!$user_info['is_admin'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT permission
			FROM {db_prefix}project_permissions
			WHERE id_group IN({array_int:groups})
				AND id_profile = {int:profile}',
			array(
				'profile' => $context['project']['profile'],
				'groups' => $user_info['groups'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_permissions'][$row['permission']] = true;

		if (!empty($context['project_permissions']['issue_view_private']))
			$user_info['query_see_issue_project'] = $user_info['query_see_version'];

		$smcFunc['db_free_result']($request);
	}
}

function loadProjectToolsPage($mode = '')
{
	global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $settings;

	if (loadLanguage('Project') == false)
		loadLanguage('Project', 'english');

	// Load Issue Type texts
	foreach ($context['issue_types'] as $id => $type)
	{
		if (isset($txt['issue_type_' . $type['id']]))
			$type['name'] = $txt['issue_type_' . $type['id']];

		if (isset($txt['issue_type_plural_' . $type['id']]))
			$type['plural'] = $txt['issue_type_plural_' . $type['id']];

		$context['issue_types'][$id] = $type;
	}

	// Load status texts
	foreach ($context['issue_status'] as $id => $status)
	{
		if (isset($txt['issue_status_' . $status['name']]))
			$status['text'] = $txt['issue_status_' . $status['name']];

		$context['issue_status'][$id] = $status;
	}

	if ($mode == '')
	{
		$context['issues_per_page'] = !empty($modSettings['issuesPerPage']) ? $modSettings['issuesPerPage'] : 25;
		$context['comments_per_page'] = !empty($modSettings['commentsPerPage']) ? $modSettings['commentsPerPage'] : 20;

		loadTemplate('Project', array('forum', 'project'));

		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';

		// Make Procject Descriptions BBC if needed to
		if (isset($context['project']))
		{
			$context['project']['description'] = parse_bbc($context['project']['description']);
			$context['project']['long_description'] = parse_bbc($context['project']['long_description']);
		}

		if (!isset($_REQUEST['xml']))
			$context['template_layers'][] = 'project';
	}
	// In SMF (SSI, etc)
	elseif ($mode == 'smf')
	{

	}
	// Profile
	elseif ($mode == 'profile')
	{
		loadTemplate('ProjectProfile', array('project'));

		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';
	}
	elseif ($mode == 'admin')
	{
		require_once($sourcedir . '/Subs-ProjectAdmin.php');

		$user_info['query_see_project'] = '1 = 1';
		$user_info['query_see_version'] = '1 = 1';

		if (loadLanguage('ProjectAdmin') == false)
			loadLanguage('ProjectAdmin', 'english');

		loadTemplate('ProjectAdmin',  array('project'));

		if (!isset($_REQUEST['xml']))
			$context['template_layers'][] = 'project_admin';
	}
}

// TODO: Cache this
function getPrivateProfiles()
{
	global $smcFunc, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT id_profile
		FROM {db_prefix}project_permissions
		WHERE id_group IN({array_int:groups})
			AND permission = {string:permission}',
		array(
			'permission' => 'view_issue_private',
			'groups' => $user_info['groups'],
		)
	);

	$profiles = array();

	while ($profile = $smcFunc['db_fetch_assoc']($request))
		$profiles[] = $profile['id_profile'];

	return $profiles;
}

// Send Notification
function sendProjectNotification($issue, $type, $exclude = 0)
{
	global $smcFunc, $context, $sourcedir, $modSettings, $user_info;

	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.email_address, mem.notify_regularity, mem.notify_send_body, mem.lngfile,
			ln.sent, ln.id_project, mem.id_group, mem.additional_groups, mem.id_post_group,
			p.member_groups' . (!empty($issue['version']) ? ', ver.member_groups AS member_groups_version' : '') . '
		FROM {db_prefix}log_notify_projects AS ln
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = ln.id_project)
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member = ln.id_member)' . (!empty($issue['version']) ? '
			INNER JOIN {db_prefix}project_versions AS ver ON (ver.id_project = p.id_project)' : '') . '
		WHERE ln.id_project = {int:project}' . (!empty($issue['version']) ? '
			AND ver.id_version = {int:version}' : '') . '
			AND mem.is_activated = {int:is_activated}
			AND mem.id_member != {int:poster}
		ORDER BY mem.lngfile',
		array(
			'is_activated' => 1,
			'project' => $issue['project'],
			'version' => $issue['version'],
			'poster' => $exclude,
		)
	);

	while ($rowmember = $smcFunc['db_fetch_assoc']($request))
	{
		if ($rowmember['id_group'] != 1)
		{
			$p_allowed = explode(',', $rowmember['member_groups']);

			if (!empty($rowmember['member_groups']))
				$v_allowed = explode(',', $rowmember['member_groups_version']);

			$rowmember['additional_groups'] = explode(',', $rowmember['additional_groups']);
			$rowmember['additional_groups'][] = $rowmember['id_group'];
			$rowmember['additional_groups'][] = $rowmember['id_post_group'];

			// can see project?
			if (count(array_intersect($p_allowed, $rowmember['additional_groups'])) == 0)
				continue;
			// what about version?
			if (isset($v_allowed) && count(array_intersect($v_allowed, $rowmember['additional_groups'])) == 0)
				continue;
		}

		loadLanguage('ProjectEmail', empty($rowmember['lngfile']) || empty($modSettings['userLanguage']) ? $language : $rowmember['lngfile'], false);

		$replacements = array(
			'ISSUENAME' => $issue['subject'],
			'ISSUELINK' => project_get_url(array('issue' => $issue['id'] . '.0')),
			'DETAILS' => $issue['body'],
			'UNSUBSCRIBELINK' => project_get_url(array('project' => $issue['project'], 'sa' => 'subscribe')),
		);

		$emailtype = 'notification_project_' . $type;

		$emaildata = loadEmailTemplate($emailtype, $replacements, '', false);
		sendmail($rowmember['email_address'], $emaildata['subject'], $emaildata['body'], null, null, false, 4);
	}
}

// Function to generate urls
function project_get_url($params = array(), $project = null)
{
	global $scripturl, $modSettings;

	// Running in "standalone" mode WITH rewrite
	if (!empty($modSettings['projectStandalone']) && $modSettings['projectStandalone'] == 2)
	{
		// Main Page? Too easy
		if (empty($params))
			return $modSettings['projectStandaloneUrl'] . '/';

		if (isset($params['project']))
		{
			$project = $params['project'];
			unset($params['project']);
		}
		elseif (!empty($GLOBALS['project']))
			$project = $GLOBALS['project'];
		elseif ($project == null)
			die(print_r(debug_backtrace(), true));


		if (count($params) === 0)
			return $modSettings['projectStandaloneUrl'] . '/' . $project . '/';

		$query = '';

		foreach ($params as $p => $value)
		{
			if ($value === null)
				continue;

			if (!empty($query))
				$query .= ';';
			else
				$query .= '?';

			if (is_int($p))
				$query .= $value;
			else
				$query .= $p . '=' . $value;
		}

		return $modSettings['projectStandaloneUrl'] . '/' . $project . '/' . $query;
	}
	//Running in "standalone" mode without rewrite or standard mode
	else
	{
		$return = '';

		if (empty($params) && empty($modSettings['projectStandaloneUrl']))
			$params['action'] = 'projects';

		foreach ($params as $p => $value)
		{
			if ($value === null)
				continue;

			if (!empty($return))
				$return .= ';';
			else
				$return .= '?';

			if (is_int($p))
				$return .= $value;
			else
				$return .= $p . '=' . $value;
		}

		if (!empty($modSettings['projectStandalone']))
			return $modSettings['projectStandaloneUrl'] . $return;
		else
			return $scripturl . $return;
	}
}

// Load Timeline
function loadTimeline($project = 0)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	// Load timeline
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.issue_type, i.subject, i.priority, i.status,
			tl.id_project, tl.event, tl.event_data, tl.event_time, tl.id_version,
			mem.id_member, IFNULL(mem.real_name, tl.poster_name) AS user,
			p.id_project, p.name
		FROM {db_prefix}project_timeline AS tl
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = tl.id_project)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tl.id_member)
			LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
			LEFT JOIN {db_prefix}project_versions AS ver ON (ver.id_version = IFNULL(i.id_version, tl.id_version))
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})
		WHERE {query_see_project}' . (!empty($project) ? '
			AND {query_see_issue_project}
			AND tl.id_project = {int:project}' : '
			AND {query_see_issue}') . '
		ORDER BY tl.event_time DESC
		LIMIT 12',
		array(
			'project' => $project,
			'current_member' => $user_info['id'],
			'empty' => ''
		)
	);

	$context['events'] = array();

	$nowtime = forum_time();
	$now = @getdate($nowtime);
	$clockFromat = strpos($user_info['time_format'], '%I') === false ? '%H:%M' : '%I:%M %p';

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$data = unserialize($row['event_data']);

		$index = date('Ymd', forum_time(true, $row['event_time']));
		$date = @getdate(forum_time(true, $row['event_time']));

		if (!isset($context['events'][$index]))
		{
			$context['events'][$index] = array(
				'date' => '',
				'events' => array(),
			);

			if ($date['yday'] == $now['yday'] && $date['year'] == $now['year'])
				$context['events'][$index]['date'] = $txt['project_today'];
			elseif (($date['yday'] == $now['yday'] - 1 && $date['year'] == $now['year']) || ($now['yday'] == 0 && $date['year'] == $now['year'] - 1) && $date['mon'] == 12 && $date['mday'] == 31)
				$context['events'][$index]['date'] = $txt['project_yesterday'];
			else
				$context['events'][$index]['date'] = $date['mday'] . '. ' . $txt['months'][$date['mon']] . ' ' . $now['year'];
		}

		$extra = '';

		if (isset($data['changes']))
		{
			$changes = array();

			foreach ($data['changes'] as $key => $field)
			{
				list ($field, $old_value, $new_value) = $field;

				// Change values to something meaningful
				if ($field == 'status')
				{
					$old_value = $context['issue_status'][$old_value]['text'];
					$new_value = $context['issue_status'][$new_value]['text'];
				}
				elseif ($field == 'type')
				{
					$old_value = $context['issue_types'][$old_value]['name'];
					$new_value = $context['issue_types'][$new_value]['name'];
				}
				elseif ($field == 'view_status')
				{
					if (empty($old_value))
						$old_value = $txt['issue_view_status_public'];
					else
						$old_value = $txt['issue_view_status_private'];

					if (empty($new_value))
						$new_value = $txt['issue_view_status_public'];
					else
						$new_value = $txt['issue_view_status_private'];
				}
				elseif ($field == 'version' || $field == 'target_version')
				{
					// TODO: Make this work?
					// Check if version is subversion
					/*if (empty($old_value))
						$old_value = $txt['issue_none'];
					elseif (!empty($context['versions_id'][$old_value]))
						$old_value = $context['versions'][$context['versions_id'][$old_value]]['sub_versions'][$old_value]['name'];
					else
						$old_value = $context['versions'][$old_value]['name'];

					if (empty($new_value))
						$new_value = $txt['issue_none'];
					elseif (!empty($context['versions_id'][$new_value]))
						$new_value = $context['versions'][$context['versions_id'][$new_value]]['sub_versions'][$new_value]['name'];
					else
						$new_value = $context['versions'][$new_value]['name'];*/
				}

				$changes[] = sprintf($txt['change_timeline_' . $field], $old_value, $new_value);
			}

			if (!empty($changes))
				$extra = implode(', ', $changes);
		}

		$context['events'][$index]['events'][] = array(
			'event' => $row['event'],
			'project_link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
			'link' => !empty($row['subject']) ? '<a href="' . project_get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
			'time' => strftime($clockFromat, forum_time(true, $row['event_time'])),
			'extra' => $extra,
		);
	}
	$smcFunc['db_free_result']($request);
}

// Can I do that?
function projectAllowedTo($permission)
{
	global $context, $project;

	if (empty($project))
		fatal_error('projectAllowedTo(): Project not loaded');

	// Admins can do anything
	if (allowedTo('project_admin'))
		return true;

	// Project Developers can do anything too
	if ($context['project']['is_developer'])
		return true;

	if (isset($context['project_permissions'][$permission]) && $context['project_permissions'][$permission])
		return true;

	return false;
}

function projectIsAllowedTo($permission)
{
	global $context, $project, $txt, $user_info;

	if ($project === null)
		fatal_error('projectAllowed(): Project not loaded');

	if (!projectAllowedTo($permission))
	{
		if ($user_info['is_guest'])
			is_not_guest($txt['cannot_project_' . $permission]);

		fatal_lang_error('cannot_project_' . $permission, false);

		// Getting this far is a really big problem, but let's try our best to prevent any cases...
		trigger_error('Hacking attempt...', E_USER_ERROR);
	}
}

function updateProject($id_project, $projectOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Boards.php');

	$projectUpdates = array();

	if (isset($projectOptions['name']))
		$projectUpdates[] = 'name = {string:name}';
	if (isset($projectOptions['description']))
		$projectUpdates[] = 'description = {string:description}';

	if (isset($projectOptions['long_description']))
		$projectUpdates[] = 'long_description = {string:long_description}';

	if (isset($projectOptions['trackers']))
	{
		$projectUpdates[] = 'trackers = {string:trackers}';
		$projectOptions['trackers'] = implode(',', $projectOptions['trackers']);
	}

	if (isset($projectOptions['member_groups']))
	{
		$projectUpdates[] = 'member_groups = {string:member_groups}';
		$projectOptions['member_groups'] = implode(',', $projectOptions['member_groups']);
	}

	if (isset($projectOptions['category']))
	{
		$projectUpdates[] = 'id_category = {int:category}';
		$projectOptions['category'] = $projectOptions['category'];
	}
	if (isset($projectOptions['category_position']))
	{
		$projectUpdates[] = 'cat_position = {string:category_position}';
		$projectOptions['category_position'] = $projectOptions['category_position'];
	}

	if (!empty($projectUpdates))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}projects
			SET
				' . implode(',
				', $projectUpdates) . '
			WHERE id_project = {int:project}',
			array_merge($projectOptions, array(
				'project' => $id_project,
			))
		);

	return true;
}

function updateVersion($id_version, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	$versionUpdates = array();

	if (isset($versionOptions['name']))
		$versionUpdates[] = 'version_name = {string:name}';

	if (isset($versionOptions['description']))
		$versionUpdates[] = 'description = {string:description}';

	if (isset($versionOptions['release_date']))
		$versionUpdates[] = 'release_date = {string:release_date}';

	if (isset($versionOptions['member_groups']))
	{
		$versionUpdates[] = 'member_groups = {string:member_groups}';
		$versionOptions['member_groups'] = implode(',', $versionOptions['member_groups']);
	}

	if (isset($versionOptions['status']))
		$versionUpdates[] = 'status = {int:status}';

	if (!empty($versionUpdates))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET
				' . implode(',
				', $versionUpdates) . '
			WHERE id_version = {int:version}',
			array_merge($versionOptions, array(
				'version' => $id_version,
			))
		);

	return true;
}

function updatePTCategory($id_category, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	$categoryUpdates = array();

	if (isset($categoryOptions['name']))
		$categoryUpdates[] = 'category_name = {string:name}';

	if (isset($categoryOptions['project']))
		$categoryUpdates[] = 'id_project = {int:project}';

	if (!empty($categoryOptions))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}issue_category
			SET
				' . implode(',
				', $categoryUpdates) . '
			WHERE id_category = {int:category}',
			array_merge($categoryOptions, array(
				'category' => $id_category,
			))
		);

	return true;
}

function DiffParser($text)
{
	$text = explode("\n", str_replace(array("\r\n", "\r"), "\n", $text));

	$data = array();
	$file = array();

	$inFile = false;

	$lineNum = 0;
	$lineNumNew = 0;

	foreach ($text as $line)
	{
		$trim = trim($line);
		if (substr($trim, 0, 6) == 'Index:')
			continue;
		if (!empty($file) && !empty($file['actions']) && str_repeat('=', strlen($line)) == $line)
		{
			$data[] = $file;
			$file = array();
			$inFile = false;
		}

		if (!$inFile)
		{
			if (substr($line, 0, 3) == '---')
			{
				$info = explode("\t", substr($line, 4), 2);
				$file['name_before'] = $info[0];
			}
			elseif (substr($line, 0, 3) == '+++')
			{
				$info = explode("\t", substr($line, 4), 2);
				$file['name_after'] = $info[0];

				$inFile = true;
			}
		}
		else
		{
			$act = substr($line, 0, 1);
			$line = substr($line, 1);

			if ($act == '@')
			{
				$lines = substr($line, 3, -3);

				$file['actions'][] = array(
					'@',
					$lines,
				);

				list ($old, $new) = explode(' +', $lines, 2);

				list ($lineNum, )  = explode(',', $lines, 2);
				list ($lineNumNew, )  = explode(',', $lines, 2);
				$lineNum--;
				$lineNumNew--;

				continue;
			}
			elseif ($act == '-')
			{
				$act = 'd';
				$lineNum++;
			}
			elseif ($act == '+')
			{
				$act = 'a';
				$lineNumNew++;
			}
			else
			{
				$act = '';
				$lineNum++;
				$lineNumNew++;
			}

			$file['actions'][] = array(
				$act,
				$line,
				$act != 'a' ? $lineNum : '',
				$act != 'd' ? $lineNumNew : '',
			);
		}
	}

	if (!empty($file) && !empty($file['actions']))
		$data[] = $file;

	if (empty($data))
		return false;

	return $data;
}

function issue_link_callback($data)
{
	global $smcFunc, $modSettings;

	$data[0] = preg_replace('/' . $modSettings['issueRegex'][1] . '/', '<a href="' . project_get_url(array('issue' => '\1.0')) . '">\1</a>', $data[0]);

	return $data[0];
}

?>
