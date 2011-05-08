<?php
/**
 * Generic functions for Project Tools
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');



/**
 * Returns list of profiles where viewing private issues is allowed
 */
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

/**
 * Loads timeline
 */
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
			AND {query_project_see_issue}
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
		
		// Fix: Some events in past have had double serialised array
		if (is_string($data))
			$data = unserialize($data);
			
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
			$changes = ProjectTools_ChangesParser::Parse($row['id_project'], $data['changes'], true);

			if (!empty($changes))
				$extra = implode(', ', $changes);
		}

		$context['events'][$index]['events'][] = array(
			'event' => $row['event'],
			'project_link' => '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
			'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
			'link' => !empty($row['subject']) ? '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
			'time' => strftime($clockFromat, forum_time(true, $row['event_time'])),
			'extra' => $extra,
		);
	}
	$smcFunc['db_free_result']($request);
}

/**
 * Marks spefific projects read
 */
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

/**
 * Parses Diff text
 */
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

/**
 * broken function related to issue linking
 *
 * @todo Fix ME
 */
function project_link_issues($data)
{
	global $modSettings;
	
	// temp:
	return $data;
	
	return preg_replace_callback('/' . $modSettings['issueRegex'][0] . '/', !empty($modSettings['issueRegex'][1]) ? 'issue_link_callback' : 'issue_link_callback2', $data);
}

/**
 * broken function related to issue linking
 *
 * @todo Fix ME
 */
function issue_link_callback($data)
{
	global $modSettings;
	
	return preg_replace_callback('/' . $modSettings['issueRegex'][1] . '/', 'issue_link_callback_2', $data[0]);
}

/**
 * broken function related to issue linking
 *
 * @todo Fix ME
 */
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
		
	return '<a href="' . ProjectTools::get_url(array('issue' => $data[1] . '.0'), $project) . '">' . $data[1] . '</a>';
}

/**
 * Sends notification for new issues
 */
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
			'ISSUELINK' => ProjectTools::get_url(array('issue' => $issue['id'] . '.0'), $issue['project']),
			'DETAILS' => $issue['body'],
			'UNSUBSCRIBELINK' => ProjectTools::get_url(array('project' => $issue['project'], 'sa' => 'subscribe'), $issue['project']),
		);

		if ($type == 'new_issue' && !empty($rowmember['notify_send_body']))
			$type .= '_body';

		$emailtype = 'notification_project_' . $type;

		$emaildata = loadEmailTemplate($emailtype, $replacements, '', false);
		sendmail($rowmember['email_address'], $emaildata['subject'], $emaildata['body'], null, null, false, 4);
	}
}

/**
 * Sends notification for updated issues
 */
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
			$changes = ProjectTools_ChangesParser::Parse($event_data['changes']);
			$update_body = strip_tags(implode("\n", $changes));
		}

		$replacements = array(
			'ISSUENAME' => $row['subject'],
			'ISSUELINK' => ProjectTools::get_url(array('issue' => $issue['id'] . '.0'), $row['id_project']),
			'BODY' => $comment['body'],
			'UPDATES' => $update_body,
			'UNSUBSCRIBELINK' => ProjectTools::get_url(array('issue' => $issue['id'] . '.0', 'sa' => 'subscribe'), $row['id_project']),
		);

		if (!empty($replacements['BODY']))
			$replacements['BODY'] .= "\n\n" . $update_body;
		else
			$replacements['BODY'] = $update_body;

		if (isset($comment['id']))
			$replacements['COMMENTLINK'] = ProjectTools::get_url(array('issue' => $issue['id'] . '.com' . $comment['id']), $issue['project']);

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

/**
 * Handles modules registering new features 
 */
function register_project_feature($module, $class_name)
{
	global $projectModules, $extensionInformation;
	
	$projectModules[$module] = array(
		'class_name' => $class_name,
	);
}

function projectTabSort($first, $second)
{
	global $context;
	
	$orderFirst = isset($context['project_tabs']['tabs'][$first]['order']) ? $context['project_tabs']['tabs'][$first]['order'] : 1;
	$orderSecond = isset($context['project_tabs']['tabs'][$second]['order']) ? $context['project_tabs']['tabs'][$second]['order'] : 1;
	
	if ($orderFirst == $orderSecond)
		return 0;
	
	if ($orderFirst == 'first' || $orderSecond == 'last')
		return -1;
	elseif ($orderFirst == 'last' || $orderSecond == 'first')
		return 1;
	else
		return $orderFirst < $orderSecond ? -1 : 1;
}

?>