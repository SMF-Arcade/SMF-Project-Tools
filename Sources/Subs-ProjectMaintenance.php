<?php
/**
 * Generic functions for Project Tools maintenance
 *
 * @package admin
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Maintenance function for upgrading project modules
 */
function ptUpgrade_database06($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	db_extend('packages');

	// Moving issue event to new table '{db_prefix}issue_events'
	if (in_array('id_event_mod', $smcFunc['db_list_columns']('{db_prefix}issue_comments')))
	{
		$smcFunc['db_query']('', 'TRUNCATE TABLE {db_prefix}issue_events');
		
		$request = $smcFunc['db_query']('', '
			SELECT
				tl.*,
				c.id_comment, IFNULL(c.id_event_mod, tl.id_event) AS id_event_mod
			FROM {db_prefix}project_timeline AS tl
				LEFT JOIN {db_prefix}issue_comments AS c ON (c.id_event = tl.id_event)
			WHERE tl.id_issue > 0',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$changes = unserialize($row['event_data']);
			
			if (isset($changes['changes']) && is_array($changes['changes']))
				$newchanges = array('changes' => $changes['changes']);
			else
				$newchanges = array();
				
			if (isset($changes['attachments']))
				$newchanges['attachments'] = $changes['attachments'];
				
			if ($row['id_comment'] == NULL)
				continue;
			

			$smcFunc['db_insert']('',
				'{db_prefix}issue_events', 
				array(
					'id_issue' => 'int',
					'id_member' => 'int',
					'id_comment' => 'int',
					'id_event' => 'int',
					'id_event_mod' => 'int',
					'event_time' => 'int',
					'poster_name' => 'string-255',
					'poster_email' => 'string-255',
					'poster_ip' => 'string-60',
					'changes' => 'string',
				),
				array(
					$row['id_issue'],
					$row['id_member'],
					$row['id_comment'],
					$row['id_event'],
					$row['id_event_mod'],
					$row['event_time'],
					$row['poster_name'],
					$row['poster_email'],
					$row['poster_ip'],
					serialize($newchanges),
				),
				array('id_issue_event')
			);
			
			$id_issue_event = $smcFunc['db_insert_id']('{db_prefix}issue_events', 'id_issue_event');
			
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET id_issue_event_first = {int:event}
				WHERE id_comment_first = {int:comment}',
				array(
					'event' => $id_issue_event,
					'comment' => $row['id_comment'],
				)
			);
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET id_issue_event_last = {int:event}
				WHERE id_comment_last = {int:comment}',
				array(
					'event' => $id_issue_event,
					'comment' => $row['id_comment'],
				)
			);
		}
		$smcFunc['db_free_result']($request);
		
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_issue');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_member');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_event');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_event_mod');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'post_time');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_name');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_email');
		//$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_ip');
	//	$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_ip');
		//$smcFunc['db_remove_column']('{db_prefix}issues', 'id_comment_first');
		//$smcFunc['db_remove_column']('{db_prefix}issues', 'id_comment_last');
			
		//die('abcd');

		//$smcFunc['db_remove_column']('issues', 'issue_type');
	}	
}

/**
 * Maintenance function for adding id_project to log_issues
 */
function ptUpgrade_log_issues($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	$request = $smcFunc['db_query']('', '
		SELECT log.id_issue, i.id_project
		FROM {db_prefix}log_issues AS log
			INNER JOIN {db_prefix}issues AS i ON (i.id_issue = log.id_issue)
		WHERE log.id_project = {int:no_project}
		GROUP BY log.id_issue',
		array(
			'no_project' => 0,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}log_issues
			SET id_project = {int:project}
			WHERE id_issue = {int:issue}',
			array(
				'project' => $row['id_project'],
				'issue' => $row['id_issue'],
			)
		);
	}
	$smcFunc['db_free_result']($request);
}

/**
 * Maintenance function for uprading tracker columns
 */
function ptUpgrade_trackers($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	db_extend('packages');

	$trackers = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_tracker, short_name
		FROM {db_prefix}project_trackers',
		array(
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$trackers[$row['short_name']] = $row['id_tracker'];
	$smcFunc['db_free_result']($request);

	$request = $smcFunc['db_query']('', '
		SELECT id_project, trackers
		FROM {db_prefix}projects',
		array(
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$update = false;
		$currentTrackers = explode(',', $row['trackers']);
		foreach ($currentTrackers as $key => $shortName)
		{
			if (!is_numeric($shortName))
			{
				$update = true;
				if (!isset($trackers[$shortName]))
					fatal_lang_error('upgrade_no_tracker', null, $shortName);

				$currentTrackers[$key] = $trackers[$shortName];
			}
		}

		if ($update)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}projects
				SET trackers = {string:trackers}
				WHERE id_project = {int:project}',
				array(
					'project' => $row['id_project'],
					'trackers' => implode(',', $currentTrackers),
				)
			);
	}
	$smcFunc['db_free_result']($request);

	if (in_array('issue_type', $smcFunc['db_list_columns']('{db_prefix}issues')))
	{
		$request = $smcFunc['db_query']('', '
			SELECT DISTINCT issue_type
			FROM {db_prefix}issues',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!isset($trackers[$row['issue_type']]))
				fatal_lang_error('upgrade_no_tracker', null, $row['issue_type']);

			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET id_tracker = {int:tracker}
				WHERE issue_type = {string:shortname}',
				array(
					'shortname' => $row['issue_type'],
					'tracker' => $trackers[$row['issue_type']],
				)
			);
		}
		$smcFunc['db_free_result']($request);

		$smcFunc['db_remove_column']('issues', 'issue_type');
	}
}

/**
 * Maintenance function for upgrading version fields
 */
function ptUpgrade_versionFields($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	db_extend('packages');

	if (in_array('id_version', $smcFunc['db_list_columns']('{db_prefix}issues')))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}issues
			SET versions = id_version'
		);

		$smcFunc['db_remove_column']('issues', 'id_version');
	}
	
	if (in_array('id_version_fixed', $smcFunc['db_list_columns']('{db_prefix}issues')))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}issues
			SET versions_fixed = id_version_fixed'
		);

		$smcFunc['db_remove_column']('issues', 'id_version_fixed');
	}
	
	if (in_array('id_version', $smcFunc['db_list_columns']('{db_prefix}project_timeline')))
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_timeline
			SET versions = id_version'
		);

		$smcFunc['db_remove_column']('project_timeline', 'id_version');
	}
}

/**
 * Maintenance function for upgrading project modules
 */
function ptUpgrade_projectModules($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	db_extend('packages');

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}projects
		SET modules = {string:modules}
		WHERE modules = {string:empty}',
		array(
			'modules' => 'general,admin',
			'empty' => '',
		)
	);
}

/**
 * Maintenance function for generic maintenance
 */
function ptMaintenanceGeneral($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
		return true;

	// Set maxEventID
	$request = $smcFunc['db_query']('', '
		SELECT MAX(id_event)
		FROM {db_prefix}project_timeline');

	list ($maxEventID) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	updateSettings(array('project_maxEventID' => $maxEventID));

	return true;
}

/**
 * Maintenance function for comments not linked to events
 */
function ptMaintenanceEvents1($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issue_comments
			WHERE id_event = 0');

		list ($numErrors) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $numErrors > 0;
	}

	$request = $smcFunc['db_query']('', '
		SELECT id_comment
		FROM {db_prefix}issue_comments
		WHERE id_event = 0');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$event_req = $smcFunc['db_query']('', '
			SELECT id_event
			FROM {db_prefix}project_timeline AS tl
			WHERE tl.event = {string:new_comment}
				AND INSTR(tl.event_data , {string:comment})',
			array(
				'new_comment' => 'new_comment',
				'comment' => 's:7:"comment";i:' . $row['id_comment'] . ''
			)
		);

		list ($id_event) = $smcFunc['db_fetch_row']($event_req);
		$smcFunc['db_free_result']($event_req);

		if (!$id_event)
		{
			$event_req = $smcFunc['db_query']('', '
				SELECT id_event
				FROM {db_prefix}issues AS i
					LEFT JOIN {db_prefix}project_timeline AS tl ON (tl.id_issue = i.id_issue)
				WHERE i.id_comment_first = {int:comment}
					AND tl.event = {string:new_issue}',
				array(
					'new_issue' => 'new_issue',
					'comment' => $row['id_comment'],
				)
			);
			list ($id_event) = $smcFunc['db_fetch_row']($event_req);
			$smcFunc['db_free_result']($event_req);
		}

		if ($id_event)
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issue_comments
				SET id_event = {int:event}
				WHERE id_comment = {int:comment}',
				array(
					'event' => $id_event,
					'comment' => $row['id_comment'],
				)
			);
	}
	$smcFunc['db_free_result']($request);

	return true;
}

/**
 * Maintenance function for events without poster info
 */
function ptMaintenanceEvents2($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}project_timeline
			WHERE poster_name = {string:empty} OR poster_email = {string:empty} OR poster_ip = {string:empty}',
			array(
				'empty' => '',
			)
		);

		list ($numErrors) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return $numErrors > 0;
	}

	$request = $smcFunc['db_query']('', '
		SELECT tl.id_event, com.poster_name, com.poster_email, com.poster_ip
		FROM {db_prefix}project_timeline AS tl
			INNER JOIN {db_prefix}issue_comments AS com ON (com.id_event = tl.id_event)
		WHERE tl.poster_name = {string:empty} OR tl.poster_email = {string:empty} OR tl.poster_ip = {string:empty}',
		array(
			'empty' => '',
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_timeline
			SET poster_name = {string:poster_name}, poster_email = {string:poster_email}, poster_ip = {string:poster_ip}
			WHERE id_event = {int:event}', array(
				'event' => $row['id_event'],
				'poster_name' => $row['poster_name'],
				'poster_email' => $row['poster_email'],
				'poster_ip' => $row['poster_ip'],
			)
		);

	return true;
}

/**
 * Maintenance function for unnecassary events
 */
function ptMaintenanceEvents3($check = false)
{
	global $smcFunc, $txt;

	// Is this step required to run?
	if ($check)
	{
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}project_timeline
			WHERE event = {string:edit_comment} OR event = {string:delete_comment}',
			array(
				'edit_comment' => 'edit_comment',
				'delete_comment' => 'delete_comment',
			)
		);

		list ($numErrors) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}project_timeline AS tl
				LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
			WHERE ISNULL(i.id_issue)');

		list ($numErrors2) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		return (int) $numErrors + (int) $numErrors2;
	}

	$request = $smcFunc['db_query']('', '
		DELETE FROM {db_prefix}project_timeline
		WHERE event = {string:edit_comment} OR event = {string:delete_comment}',
		array(
			'edit_comment' => 'edit_comment',
			'delete_comment' => 'delete_comment',
		)
	);

	return true;
}

/**
 * Maintenance function for updating issue counts
 *
 * @todo Write code for this function
 */
function ptMaintenanceIssueCounts($check = false)
{
	global $smcFunc, $txt;
	
	if ($check)
	{
		// TODO: Write actual code
		return true;
	}
	
	// TODO: Write code to recount issues
	
	return true;
}

/**
 * Maintenance function for deleting invalid issues
 *
 * @todo Check function needs to be written
 */
function ptMaintenanceIssues1($check = false)
{
	global $smcFunc, $txt;

	if ($check)
	{
		// TODO: Write actual code
		return true;
	}

	$request = $smcFunc['db_query']('', '
		SELECT i.id_issue
		FROM {db_prefix}issues AS i
			LEFT JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)
		WHERE ISNULL(com.id_comment)');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		deleteIssue($row['id_issue'], false);

	$smcFunc['db_free_result']($request);
	
	return true;
}

/**
 * Maintenance function for setting deleted posters as guests
 *
 * @todo Write check code
 */
function ptMaintenanceIssues2($check = false)
{
	global $smcFunc, $txt;

	if ($check)
	{
		// TODO: Write actual code
		return true;
	}
	
	$deletedMembers = array();

	// Reporters
	$request = $smcFunc['db_query']('', '
		SELECT DISTINCT i.id_reporter
		FROM {db_prefix}issues AS i
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = i.id_reporter)
		WHERE ISNULL(mem.id_member)');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$deletedMembers[$row['id_reporter']] = $row['id_reporter'];
	$smcFunc['db_free_result']($request);
	
	// Updaters
	$request = $smcFunc['db_query']('', '
		SELECT DISTINCT i.id_updater
		FROM {db_prefix}issues AS i
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = i.id_updater)
		WHERE ISNULL(mem.id_member)');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$deletedMembers[$row['id_updater']] = $row['id_updater'];
	$smcFunc['db_free_result']($request);
	
	// Commenters
	$request = $smcFunc['db_query']('', '
		SELECT DISTINCT com.id_member
		FROM {db_prefix}issue_comments AS com
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = com.id_member)
		WHERE ISNULL(mem.id_member)');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$deletedMembers[$row['id_member']] = $row['id_member'];
	$smcFunc['db_free_result']($request);
	
	// Timeline
	$request = $smcFunc['db_query']('', '
		SELECT DISTINCT tl.id_member
		FROM {db_prefix}project_timeline AS tl
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tl.id_member)
		WHERE ISNULL(mem.id_member)');
	
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$deletedMembers[$row['id_member']] = $row['id_member'];
	$smcFunc['db_free_result']($request);
	
	if (empty($deletedMembers))
		return true;
	
	// Make Project Tools comments guest posts
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_comments
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'blank_email' => '',
			'users' => $deletedMembers,
		)
	);
	// Make Project Tools issues guest
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET id_reporter = {int:guest_id}
		WHERE id_reporter IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'blank_email' => '',
			'users' => $deletedMembers,
		)
	);	
	// Make Project Tools issues updated by guest
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET id_updater = {int:guest_id}
		WHERE id_updater IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'blank_email' => '',
			'users' => $deletedMembers,
		)
	);
	// Make Project Tools events guests
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}project_timeline
		SET id_member = {int:guest_id}
		WHERE id_member IN ({array_int:users})',
		array(
			'guest_id' => 0,
			'blank_email' => '',
			'users' => $deletedMembers,
		)
	);
	// Delete the members notifications and read logs
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_notify_projects
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_projects
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);	
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_project_mark_read
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}log_issues
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);
	// Delete developer status
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}project_developer
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);
	// Delete possible settings
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}project_settings
		WHERE id_member IN ({array_int:users})',
		array(
			'users' => $deletedMembers,
		)
	);
	
	return true;
}

?>