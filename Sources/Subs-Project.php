<?php
/**********************************************************************************
* Subs-Project.php                                                                *
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
	!!!
*/

function loadProjectToolsPermissions()
{
	global $context, $sourcedir, $modSettings, $user_info;

	require_once($sourcedir . '/Subs-Issue.php');

	if (empty($modSettings['issueRegex']))
		$modSettings['issueRegex'] = array('[Ii]ssues?:?(\s*(,|and)?\s*#\d+)+', '(\d+)');
	else
		$modSettings['issueRegex'] = explode("\n", $modSettings['issueRegex'], 2);

	$context['project_tools'] = array();
	$context['issue_tracker'] = array();

	// Administrators can see all projects.
	if ($user_info['is_admin'])
	{
		$see_project = '1 = 1';
		$see_issue = '1 = 1';
		$see_version = '1 = 1';
	}
	else
	{
		$see_project = '(FIND_IN_SET(' . implode(', p.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', p.member_groups))';
		$see_version = '(ISNULL(ver.member_groups) OR (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups)))';
		$see_issue = $see_version;
	}

	$user_info['query_see_project'] = $see_project;
	$user_info['query_see_version'] = $see_version;
	$user_info['query_see_issue'] = $see_issue;

	if (loadLanguage('Project') == false)
		loadLanguage('Project', 'english');

	loadIssueTypes();
}

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
		WHERE {query_see_project}
			AND {query_see_issue}' . (!empty($project) ? '
			AND tl.id_project = {int:project}' : '') . '
		ORDER BY tl.event_time DESC
		LIMIT 12',
		array(
			'project' => $project,
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

		$context['events'][$index]['events'][] = array(
			'event' => $row['event'],
			'project_link' => '<a href="' . $scripturl . '?project=' . $row['id_project'] . '">' . $row['name'] . '</a>',
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
			'link' => !empty($row['subject']) ? '<a href="' . $scripturl . '?issue=' . $row['id_issue'] . '.0">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
			'time' => strftime($clockFromat, forum_time(true, $row['event_time'])),
			'data' => $data,
		);
	}
	$smcFunc['db_free_result']($request);
}

function loadProject()
{
	global $context, $smcFunc, $scripturl, $user_info, $txt, $user_info, $project;

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.long_description, p.trackers, p.member_groups,
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
		'link' => '<a href="' . $scripturl . '?project=' . $row['id_project'] . '">' . $row['name'] . '</a>',
		'href' => $scripturl . '?project=' . $row['id_project'],
		'name' => $row['name'],
		'description' => $row['description'],
		'long_description' => $row['long_description'],
		'category' => array(),
		'groups' => explode(',', $row['member_groups']),
		'trackers' => array(),
		'developers' => array(),
		'is_developer' => !empty($row['is_dev']),
		'comment_mod' => $row['id_comment_mod'],
	);

	$trackers = explode(',', $row['trackers']);

	foreach ($trackers as $key)
	{
		$context['project']['trackers'][$key] = array(
			'info' => &$context['project_tools']['issue_types'][$key],
			'open' => $row['open_' . $key],
			'closed' => $row['closed_' . $key],
			'total' => $row['open_' . $key] + $row['closed_' . $key],
			'link' => $scripturl . '?project='. $project['id'] . ';sa=issues;type=' . $key,
		);
	}

	// Developers
	$request = $smcFunc['db_query']('', '
		SELECT mem.id_member, mem.real_name
		FROM {db_prefix}project_developer AS dev
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
		WHERE id_project = {int:project}',
		array(
			'project' => $id_project,
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
			'project' => $id_project,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['project']['category'][$row['id_category']] = array(
			'id' => $row['id_category'],
			'name' => $row['category_name']
		);
	$smcFunc['db_free_result']($request);

	if ($permissions && !$user_info['is_admin'] && !$context['project']['is_developer'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_profile
			FROM {db_prefix}projects
			WHERE id_project = {int:project}',
			array(
				'project' => $project,
			)
		);

		list ($profile) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		if (!$profile)
			return;

		$request = $smcFunc['db_query']('', '
			SELECT permission
			FROM {db_prefix}project_permissions
			WHERE id_group IN({array_int:groups})
				AND id_profile = {int:profile}',
			array(
				'profile' => $profile,
				'groups' => $user_info['groups'],
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['project_permissions'][$row['permission']] = true;

		if (empty($context['project_permissions']['view_issue_private']))

		$smcFunc['db_free_result']($request);
	}
}

function loadVersions($project)
{
	global $context, $smcFunc, $scripturl, $user_info, $txt;

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT
			id_version, id_parent, version_name, release_date, status
		FROM {db_prefix}project_versions AS ver
		WHERE id_project = {int:project}
			AND {query_see_version}
		ORDER BY id_parent',
		array(
			'project' => $project['id'],
		)
	);

	$versions = array();
	$version_ids = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_parent'] == 0)
		{
			$versions[$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'sub_versions' => array(),
			);
		}
		else
		{
			if (!isset($versions[$row['id_parent']]))
				continue;

			$versions[$row['id_parent']]['sub_versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
				'released' => $row['status'] >= 4,
			);
		}

		$version_ids[$row['id_version']] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);

	return array($versions, $version_ids);
}

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

function createProject($projectOptions)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (empty($projectOptions['name']) || !isset($projectOptions['description']) || !isset($projectOptions['member_groups']))
		trigger_error('createProject(): required parameters missing or invalid', E_USER_ERROR);

	$smcFunc['db_insert']('insert',
		'{db_prefix}projects',
		array(
			'name' => 'string',
			'description' => 'string',
			'member_groups' => 'string',
			'id_profile' => 'int',
		),
		array(
			$projectOptions['name'],
			$projectOptions['description'],
			implode(',', $projectOptions['member_groups']),
			1,
		),
		array()
	);

	$id_project = $smcFunc['db_insert_id']('{db_prefix}projects', 'id_project');

	unset($projectOptions['name'], $projectOptions['description'], $projectOptions['member_groups']);

	// Anything left?
	if (!empty($projectOptions))
		updateProject($id_project, $projectOptions);

	return $id_project;
}

function updateProject($id_project, $projectOptions)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

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

function createVersion($id_project, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	if (empty($versionOptions['name']))
		trigger_error('createVersion(): required parameters missing or invalid');

	if (empty($versionOptions['release_date']) || empty($versionOptions['parent']))
		$versionOptions['release_date'] = serialize(array('day' => 0, 'month' => 0, 'year' => 0));

	if (empty($versionOptions['description']))
		$versionOptions['description'] = '';

	if (empty($versionOptions['parent']))
	{
		$versionOptions['parent'] = 0;
		$versionOptions['status'] = 0;
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND id_version = {int:version}',
			array(
				'project' => $id_project,
				'version' => $versionOptions['parent'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			trigger_error('createVersion(): invalid parent');
		$smcFunc['db_free_result']($request);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_versions',
		array(
			'id_project' => 'int',
			'id_parent' => 'int',
			'version_name' => 'string',
			'description' => 'string',
		),
		array(
			$id_project,
			$versionOptions['parent'],
			$versionOptions['name'],
			$versionOptions['description']
		),
		array('id_version')
	);

	$id_version = $smcFunc['db_insert_id']('{db_prefix}project_versions', 'id_version');

	unset($versionOptions['parent'], $versionOptions['name'], $versionOptions['description']);

	updateVersion($id_version, $versionOptions);

	return $id_version;
}

function updateVersion($id_version, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

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

function createPTCategory($id_project, $categoryOptions)
{
	global $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_category',
		array(
			'id_project' => 'int',
			'category_name' => 'string'
		),
		array(
			$id_project,
			$categoryOptions['name']
		),
		array('id_category')
	);

	return true;
}

function updatePTCategory($id_category, $categoryOptions)
{
	global $smcFunc, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

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
	global $smcFunc, $scripturl, $modSettings;

	$data[0] = preg_replace('/' . $modSettings['issueRegex'][1] . '/', '<a href="' . $scripturl . '?issue=\1.0">\1</a>', $data[0]);

	return $data[0];
}

?>