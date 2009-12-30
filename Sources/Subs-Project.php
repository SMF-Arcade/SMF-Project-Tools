<?php
/**********************************************************************************
* Subs-Project.php                                                                *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.4.2                             *
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

/*
	!!!
*/

function loadProjectTools()
{
	global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $project_version, $settings, $issue, $projects_show;

	if (!empty($project_version))
		return;

	// Which version this is?
	$project_version = '0.4.2';

	if (isset($_REQUEST['issue']) && strpos($_REQUEST['issue'], '.') !== false)
	{
		list ($_REQUEST['issue'], $_REQUEST['start']) = explode('.', $_REQUEST['issue'], 2);
		$issue = (int) $_REQUEST['issue'];
		
		// This is for Who's online
		$_GET['issue'] = $issue;
	}
	elseif (isset($_REQUEST['issue']))
	{
		$issue = (int) $_REQUEST['issue'];
		
		// This is for Who's online
		$_GET['issue'] = $issue;
	}
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
		$see_version_issue = '1 = 1';
		$see_version_timeline = '1 = 1';
		$see_issue = '1 = 1';
		$see_issue_p = '1 = 1';
	}
	else
	{
		$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';

		// Version 0 can be always seen
		$user_info['project_allowed_versions'] = array(0);
		
		// Get versions that can be seen
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions AS ver
			WHERE (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups))'
		);
		
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$user_info['project_allowed_versions'][] = $row['id_version'];
		$smcFunc['db_free_result']($request);
		
		// See version
		if (!empty($user_info['project_allowed_versions']))
			$see_version = '(id_version IN(' . implode(',', $user_info['project_allowed_versions']) . '))';
		else
			$see_version = '(0=1)';
			
		// See version in issue query
		if (!empty($user_info['project_allowed_versions']))
			$see_version_issue = '(FIND_IN_SET(' . implode(', i.versions) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', i.versions))';
		else
			$see_version_issue = '(0=1)';
			
		// See version in timeline query
		if (!empty($user_info['project_allowed_versions']))
			$see_version_timeline = '(FIND_IN_SET(' . implode(', IFNULL(i.versions, tl.versions)) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', IFNULL(i.versions, tl.versions)))';
		else
			$see_version_timeline = '(0=1)';
			
		// See private issues code
		$my_issue = $user_info['is_guest'] ? '(0=1)' : '(i.id_reporter = ' . $user_info['id'] . ')';
		
		// Private issues
		$see_private_profiles = getPrivateProfiles();
		if (!empty($see_private_profiles))
			$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR (' . $my_issue . ' OR p.id_profile IN(' . implode(', ', $see_private_profiles) . ')))';
		else
			$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR ' . $my_issue . ')';
			
		$see_issue = '((' . $see_version_issue . ') AND ' . $see_private . ')';
		$see_issue_p = '((' . $see_version_issue . ') AND (i.private_issue = 0 OR ' . $my_issue . '))';
		
		unset($allowed_versions);
	}
	
	// See project
	$user_info['query_see_project'] = $see_project;
	// See version
	$user_info['query_see_version'] = $see_version;
	// See version timeline
	$user_info['query_see_version_timeline'] = $see_version_timeline;
	
	// Issue of any project
	$user_info['query_see_issue'] = $see_issue;
	// See version of issue
	$user_info['query_see_version_issue'] = $see_version_issue;
	
	// See issue of current project
	$user_info['query_see_issue_project'] = $see_issue_p;
	
	if (isset($projects_show) && (empty($projects_show) || !is_array($projects_show)))
		$user_info['query_see_project'] = '0=1';
	elseif (isset($projects_show))
		$user_info['query_see_project'] = '(p.id_project IN(' . implode(',', $projects_show) . ') AND ' . $see_project . ')';
	
	// Trackers
	$context['issue_trackers'] = array();
	$context['tracker_columns'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_tracker, short_name, tracker_name, plural_name
		FROM {db_prefix}project_trackers',
		array(
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['issue_trackers'][$row['id_tracker']] = array(
			'id' => $row['id_tracker'],
			'name' => $row['tracker_name'],
			'short' => $row['short_name'],
			'plural' => $row['plural_name'],
			'image' => $row['short_name'] . '.png',
			'column_open' => 'open_' . $row['short_name'],
			'column_closed' => 'closed_' . $row['short_name'],
		);

		$context['tracker_columns'][] = "open_$row[short_name]";
		$context['tracker_columns'][] = "closed_$row[short_name]";
	}
	$smcFunc['db_free_result']($request);

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
	global $context, $smcFunc, $scripturl, $user_info, $user_info, $force_project, $project, $issue, $modSettings, $projects_show;

	if (isset($force_project))
		$project = $force_project;
	// Project as parameter?
	elseif (!empty($_REQUEST['project']))
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
		{
			$context['project_error'] = 'issue_not_found';
			return;
		}

		$_REQUEST['project'] = $project;
	}
	// Not needed
	else
		return;
	
	// For Who's online
	$_GET['project'] = $project;

	if (($context['project'] = cache_get_data('project-' . $project, 120)) === null)
	{
		$request = $smcFunc['db_query']('', '
			SELECT
				p.id_project, p.id_profile, p.name, p.description, p.long_description, p.trackers, p.member_groups,
				p.id_event_mod, p.' . implode(', p.', $context['tracker_columns']) . ', p.project_theme
			FROM {db_prefix}projects AS p
			WHERE p.id_project = {int:project}
			LIMIT 1',
			array(
				'project' => $project,
				'current_member' => $user_info['id'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
		{
			$context['project_error'] = 'project_not_found';
			
			$project = 0;

			return;
		}

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
			'is_developer' => false,
			'id_event_mod' => $row['id_event_mod'],
			'profile' => $row['id_profile'],
			'theme' => $row['project_theme'],
			'override_theme' => !empty($row['override_theme']),
		);

		$trackers = explode(',', $row['trackers']);

		foreach ($trackers as $id)
		{
			$tracker = &$context['issue_trackers'][$id];
			$context['project']['trackers'][$id] = array(
				'id' => $id,
				'tracker' => $tracker,
				'short' => $tracker['short'],
				'open' => $row['open_' . $tracker['short']],
				'closed' => $row['closed_' . $tracker['short']],
				'total' => $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']],
				'progress' => round(($row['closed_' . $tracker['short']] / max(1, $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']])) * 100, 2),
				'link' => project_get_url(array('project' => $row['id_project'], 'sa' => 'issues', 'tracker' => $tracker['short'])),
			);
			unset($tracker);
		}

		// Developers
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.real_name
			FROM {db_prefix}project_developer AS dev
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
			WHERE id_project = {int:project}',
			array(
				'project' => $project,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project']['developers'][$row['id_member']] = array(
				'id' => $row['id_member'],
				'name' => $row['real_name'],
			);
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

		cache_put_data('project-' . $project, $context['project'], 120);
	}

	if ((list ($context['versions'], $context['versions_id']) = cache_get_data('project-version-' . $project, 120)) === null)
	{
		// Load Versions
		$request = $smcFunc['db_query']('', '
			SELECT id_version, id_parent, version_name, release_date, status
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

		cache_put_data('project-version-' . $project, array($context['versions'], $context['versions_id']), 120);
	}

	$context['project']['is_developer'] = isset($context['project']['developers'][$user_info['id']]);

	// Developers can see all issues
	if ($context['project']['is_developer'])
		$user_info['query_see_issue_project'] = '1=1';
			
	if (count(array_intersect($user_info['groups'], $context['project']['groups'])) == 0 && !$user_info['is_admin'])
		$context['project_error'] = 'project_not_found';
		
	if (!empty($projects_show) && !in_array($context['project']['id'], $projects_show))
		$context['project_error'] = 'project_not_found';
}

function loadProjectToolsPage($mode = '')
{
	global $context, $smcFunc, $modSettings, $sourcedir, $user_info, $txt, $settings;

	if (loadLanguage('Project') == false)
		loadLanguage('Project', 'english');

	// Load status texts
	foreach ($context['issue_status'] as $id => $status)
	{
		if (isset($txt['issue_status_' . $status['name']]))
			$status['text'] = $txt['issue_status_' . $status['name']];

		$context['issue_status'][$id] = $status;
	}

	$context['issues_per_page'] = !empty($modSettings['issuesPerPage']) ? $modSettings['issuesPerPage'] : 25;
	$context['comments_per_page'] = !empty($modSettings['commentsPerPage']) ? $modSettings['commentsPerPage'] : 20;

	if ($mode == '')
	{
		loadTemplate('Project', array('forum', 'project'));

		$context['html_headers'] .= '
		<script language="JavaScript" type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/project.js"></script>';

		// If project is loaded parse BBC now for descriptions
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
		loadTemplate(false, array('project'));
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

// Load Timeline
function loadTimeline($project = 0)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	// Load timeline
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, i.id_tracker, i.subject, i.priority, i.status,
			tl.id_project, tl.event, tl.event_data, tl.event_time,
			mem.id_member, IFNULL(mem.real_name, tl.poster_name) AS user, p.id_project, p.name
		FROM {db_prefix}project_timeline AS tl
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = tl.id_project)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tl.id_member)
			LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})
		WHERE {query_see_project}' . (!empty($project) ? '
			AND {query_see_issue_project}
			AND tl.id_project = {int:project}' : '') . '
			AND {query_see_version_timeline}
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

	$members = array();

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
				$context['events'][$index]['date'] = $date['mday'] . '. ' . $txt['months'][$date['mon']] . ' ' . $date['year'];
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
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $old_value)
						{
							$old_value = $tracker['name'];
							break;
						}
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $new_value)
						{
							$new_value = $tracker['name'];
							break;
						}
				}
				elseif ($field == 'tracker')
				{
					$old_value = $context['issue_trackers'][$old_value]['name'];
					$new_value = $context['issue_trackers'][$new_value]['name'];
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

function markProjectsRead($projects, $unread = false)
{
	global $smcFunc, $modSettings, $user_info;

	if (!is_array($projects))
		$projects = array($projects);
	else
		$projects = array_unique($projects);

	if (empty($projects))
		return;

	// Mark unread
	if ($unread)
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_project_mark_read
			WHERE id_project IN ({array_int:projects})
				AND id_member = {int:current_member}',
			array(
				'current_member' => $user_info['id'],
				'projects' => $projects,
			)
		);
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_projects
			WHERE id_project IN ({array_int:projects})
				AND id_member = {int:current_member}',
			array(
				'current_member' => $user_info['id'],
				'projects' => $projects,
			)
		);
	}
	else
	{
		$markRead = array();
		foreach ($projects as $project)
			$markRead[] = array($project, $user_info['id'], $modSettings['project_maxEventID']);

		// Update log_project_mark_read and log_projects.
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_project_mark_read',
			array('id_project' => 'int', 'id_member' => 'int', 'id_event' => 'int'),
			$markRead,
			array('id_project', 'id_member')
		);
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_projects',
			array('id_project' => 'int', 'id_member' => 'int', 'id_event' => 'int'),
			$markRead,
			array('id_project', 'id_member')
		);
	}

	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_issues
		WHERE id_member = {int:current_member}
			AND id_project IN ({array_int:projects})',
		array(
			'current_member' => $user_info['id'],
			'projects' => $projects,
		)
	);
}

// Function to generate urls
function project_get_url($params = array(), $project = null)
{
	global $scripturl, $modSettings;

	// Detect project
	if ($project === null && !empty($params))
	{
		if (isset($params['project']))
			$project = $params['project'];
		elseif (!empty($GLOBALS['project']))
			$project = $GLOBALS['project'];
		// Should never happen
		else
			fatal_error(print_r(debug_backtrace(), true));
	}
			
	// Running in "standalone" mode WITH rewrite
	if (!empty($modSettings['projectStandalone']) && $modSettings['projectStandalone'] == 2)
	{
		// Main Page? Too easy
		if (empty($params))
			return $modSettings['projectStandaloneUrl'] . '/';
			
		if (isset($params['project']))
			unset($params['project']);
		
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
	// Running in "standalone" mode without rewrite
	elseif (!empty($modSettings['projectStandalone']))
	{
		$return = '';
		
		// Which url shall be base for this?
		$base = !empty($modSettings['projectStandaloneUrl_project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]) ?  $modSettings['projectStandaloneUrl_project_' . $project] : (!empty($modSettings['projectStandaloneUrl']) ? $modSettings['projectStandaloneUrl'] : '{SCRIPTURL}');
		
		if (isset($params['project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]))
			unset($params['project']);
			
		if (count($params) === 0)
		{
			if ($base == '{SCRIPTURL}')
				return $scripturl . '?action=projects';
			
			return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl']));
		}

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

		return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl'])) . $return;		
	}
	// Running in standard mode
	else
	{
		$return = '';

		if (empty($params))
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

		return $scripturl . $return;
	}
}

// Can I do that?
function projectAllowedTo($permission)
{
	global $context, $user_info, $project;

	if (empty($project))
		fatal_error('projectAllowedTo(): Project not loaded');

	// Admins and developers can do anything
	if (allowedTo('project_admin') || $context['project']['is_developer'])
		return true;

	if (isset($user_info['project_permissions'][$permission]) && $user_info['project_permissions'][$permission])
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

function project_link_issues($data)
{
	global $modSettings;
	
	// temp:
	return $data;
	
	return preg_replace_callback('/' . $modSettings['issueRegex'][0] . '/', !empty($modSettings['issueRegex'][1]) ? 'issue_link_callback' : 'issue_link_callback2', $data);
}

function issue_link_callback($data)
{
	global $modSettings;
	
	return preg_replace_callback('/' . $modSettings['issueRegex'][1] . '/', 'issue_link_callback_2', $data[0]);
}

function issue_link_callback_2($data)
{
	global $smcFunc, $modSettings;
	
	// Todo: Optimize this? And Include status etc?
	$data[1] = (int) $data[1];

	if (($project = cache_get_data('issue-project-' . $data[1], 120)) === null)
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_project
			FROM {db_prefix}issues
			WHERE id_issue = {int:issue}',
			array(
				'issue' => (int) $data[1],
			)
		);
		
		list ($project) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	
		cache_put_data('issue-project-' . $data[1], $project, 120);
	}
	
	if (!$project)
		return $data[0];
		
	return '<a href="' . project_get_url(array('issue' => $data[1] . '.0'), $project) . '">' . $data[1] . '</a>';
}

// Send Notification
function sendProjectNotification($issue, $type, $exclude = 0)
{
	global $smcFunc, $context, $sourcedir, $modSettings, $user_info, $language;

	if ($type == 'new_issue')
		$issue['body'] = trim(un_htmlspecialchars(strip_tags(strtr(parse_bbc($issue['body'], false), array('<br />' => "\n", '</div>' => "\n", '</li>' => "\n", '&#91;' => '[', '&#93;' => ']')))));

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT id_version, member_groups
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}',
		array(
			'project' => $issue['project'],
		)
	);
	
	$versions = array();
	
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$versions[$row['id_version']] = explode(',', $row['member_groups']);
	$smcFunc['db_free_result']($request);
	
	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.email_address, mem.notify_regularity, mem.notify_send_body, mem.lngfile,
			ln.sent, ln.id_project, mem.id_group, mem.additional_groups, mem.id_post_group, IFNULL(dev.id_member, 0) AS is_developer, p.member_groups
		FROM {db_prefix}log_notify_projects AS ln
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = ln.id_project)
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member = ln.id_member)
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = ln.id_project AND dev.id_member = mem.id_member)
		WHERE ln.id_project = {int:project}
			AND mem.is_activated = {int:is_activated}
			AND mem.id_member != {int:poster}
		ORDER BY mem.lngfile',
		array(
			'is_activated' => 1,
			'project' => $issue['project'],
			'poster' => $exclude,
		)
	);
	
	while ($rowmember = $smcFunc['db_fetch_assoc']($request))
	{
		if ($rowmember['id_group'] != 1 && empty($rowmember['is_developer']))
		{
			// Since this is posted by current user, private users shouldn't be sent to anyone expect admins/developers
			if (!empty($issue['private']))
				continue;

			$p_allowed = explode(',', $rowmember['member_groups']);

			// Groups this member is part of
			$rowmember['additional_groups'] = explode(',', $rowmember['additional_groups']);
			$rowmember['additional_groups'][] = $rowmember['id_group'];
			$rowmember['additional_groups'][] = $rowmember['id_post_group'];

			// can see project?
			if (count(array_intersect($p_allowed, $rowmember['additional_groups'])) == 0)
				continue;
			
			// Can see any of versions?
			if (!empty($issue['versions']) && $issue['versions'] !== array(0))
			{
				$can_see = false;
				
				foreach ($issue['versions'] as $ver)
				{
					if (isset($versions[$ver]) && count(array_intersect($versions[$ver], $rowmember['additional_groups'])) > 0)
						$can_see = true;
				}
				
			}
			else
				$can_see = true;

			if (!$can_see)
				continue;
		}

		loadLanguage('ProjectEmail', empty($rowmember['lngfile']) || empty($modSettings['userLanguage']) ? $language : $rowmember['lngfile'], false);

		$replacements = array(
			'ISSUENAME' => $issue['subject'],
			'ISSUELINK' => project_get_url(array('issue' => $issue['id'] . '.0'), $issue['project']),
			'DETAILS' => $issue['body'],
			'UNSUBSCRIBELINK' => project_get_url(array('project' => $issue['project'], 'sa' => 'subscribe'), $issue['project']),
		);

		if ($type == 'new_issue' && !empty($rowmember['notify_send_body']))
			$type .= '_body';

		$emailtype = 'notification_project_' . $type;

		$emaildata = loadEmailTemplate($emailtype, $replacements, '', false);
		sendmail($rowmember['email_address'], $emaildata['subject'], $emaildata['body'], null, null, false, 4);
	}
}

function sendIssueNotification($issue, $comment, $event_data, $type, $exclude = 0)
{
	global $smcFunc, $context, $sourcedir, $modSettings, $user_info, $language, $txt, $memberContext;

	require_once($sourcedir . '/Subs-Post.php');

	if ($type == 'new_comment')
		$comment['body'] = trim(un_htmlspecialchars(strip_tags(strtr(parse_bbc($comment['body'], false), array('<br />' => "\n", '</div>' => "\n", '</li>' => "\n", '&#91;' => '[', '&#93;' => ']')))));

	if (empty($comment['body']))
		$comment['body'] = '';
		
	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT id_version, member_groups
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}',
		array(
			'project' => $issue['project'],
		)
	);
	
	$versions = array();
	
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$versions[$row['id_version']] = explode(',', $row['member_groups']);
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.email_address, mem.notify_regularity, mem.notify_send_body, mem.lngfile,
			ln.sent, mem.id_group, mem.additional_groups, mem.id_post_group,
			p.id_project, p.member_groups, i.private_issue, IFNULL(dev.id_member, 0) AS is_developer,
			i.subject, i.id_reporter, i.versions
		FROM {db_prefix}log_notify_projects AS ln
			INNER JOIN {db_prefix}issues AS i ON (i.id_issue = ln.id_issue)
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
			INNER JOIN {db_prefix}members AS mem ON (mem.id_member = ln.id_member)
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project AND dev.id_member = mem.id_member)
		WHERE ln.id_issue = {int:issue}
			AND mem.is_activated = {int:is_activated}
			AND mem.id_member != {int:poster}
		ORDER BY mem.lngfile',
		array(
			'is_activated' => 1,
			'issue' => $issue['id'],
			'poster' => $exclude,
			'any' => '*',
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_group'] != 1 && empty($row['is_developer']))
		{
			if (!empty($row['private_issue']) && $row['id_reporter'] != $row['id_member'])
				continue;

			$p_allowed = explode(',', $row['member_groups']);

			$row['additional_groups'] = explode(',', $row['additional_groups']);
			$row['additional_groups'][] = $row['id_group'];
			$row['additional_groups'][] = $row['id_post_group'];

			// can see project?
			if (count(array_intersect($p_allowed, $row['additional_groups'])) == 0)
				continue;
			
			$row['versions'] = explode(',', $row['versions']);
			
			// Can see any of versions?
			if (!empty($row['versions']) && $row['versions'] !== array(0))
			{
				$can_see = false;
				
				foreach ($row['versions'] as $ver)
				{
					if (isset($versions[$ver]) && count(array_intersect($versions[$ver], $row['additional_groups'])) > 0)
						$can_see = true;
				}
				
			}
			else
				$can_see = true;

			if (!$can_see)
				continue;
		}

		$row['subject'] = un_htmlspecialchars($row['subject']);

		loadLanguage('Project', empty($row['lngfile']) || empty($modSettings['userLanguage']) ? $language : $row['lngfile'], false);
		loadLanguage('ProjectEmail', empty($row['lngfile']) || empty($modSettings['userLanguage']) ? $language : $row['lngfile'], false);

		$update_body = '';

		if (isset($event_data['changes']))
		{
			$changes = array();

			foreach ($event_data['changes'] as $key => $field)
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
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $old_value)
						{
							$old_value = $tracker['name'];
							break;
						}
					foreach ($context['issue_trackers'] as $tracker)
						if ($tracker['short'] == $new_value)
						{
							$new_value = $tracker['name'];
							break;
						}
				}
				elseif ($field == 'tracker')
				{
					$old_value = $context['issue_trackers'][$old_value]['name'];
					$new_value = $context['issue_trackers'][$new_value]['name'];
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
					if (empty($old_value))
						$old_value = $txt['issue_none'];
					else
						$old_value = getVersions(explode(',', $old_value), true);

					if (empty($new_value))
						$new_value = $txt['issue_none'];
					else
						$new_value = getVersions(explode(',', $new_value), true);
				}
				elseif ($field == 'assign')
				{
					loadMemberData(array($old_value, $new_value));

					if (empty($old_value))
						$old_value = $txt['issue_none'];
					elseif (loadMemberContext($old_value))
						$old_value = $memberContext[$old_value]['link'];

					if (empty($new_value))
						$new_value = $txt['issue_none'];
					elseif (loadMemberContext($new_value))
						$new_value = $memberContext[$new_value]['link'];
				}

				$changes[] = sprintf($txt['change_' . $field], $old_value, $new_value);
			}

			$update_body = strip_tags(implode("\n", $changes));
		}

		$replacements = array(
			'ISSUENAME' => $row['subject'],
			'ISSUELINK' => project_get_url(array('issue' => $issue['id'] . '.0'), $row['id_project']),
			'BODY' => $comment['body'],
			'UPDATES' => $update_body,
			'UNSUBSCRIBELINK' => project_get_url(array('issue' => $issue['id'] . '.0', 'sa' => 'subscribe'), $row['id_project']),
		);

		if (!empty($replacements['BODY']))
			$replacements['BODY'] .= "\n\n" . $update_body;
		else
			$replacements['BODY'] = $update_body;

		if (isset($comment['id']))
			$replacements['COMMENTLINK'] = project_get_url(array('issue' => $issue['id'] . '.com' . $comment['id']), $issue['project']);

		if ($type == 'new_comment' && empty($row['notify_send_body']) && !empty($update_body))
		{
			$replacements['BODY'] = $update_body;
			$type .= '_body';
		}

		$emailtype = 'notification_project_' . $type;

		$emaildata = loadEmailTemplate($emailtype, $replacements, '', false);
		sendmail($row['email_address'], $emaildata['subject'], $emaildata['body'], null, null, false, 4);
	}

	// Back to original language
	loadLanguage('Project');
}

if (!function_exists('JavaScriptEscape'))
{
	function JavaScriptEscape($string)
	{
		return '\'' . strtr($string, array(
			"\r" => '',
			"\n" => '\\n',
			'\\' => '\\\\',
			'\'' => '\\\'',
			'</' => '<\' + \'/',
			'script' => 'scri\' +\'pt',
		)) . '\'';
	}
}

?>
