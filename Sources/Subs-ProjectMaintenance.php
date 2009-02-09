<?php
/**********************************************************************************
* Subs-ProjectMaintenance.php                                                     *
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

function ptUpgrade_log_issues($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
	{
		return true;
	}

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

function ptMaintenanceGeneral($check = false)
{
	global $smcFunc;

	// Is this step required to run?
	if ($check)
	{
		return true;
	}

	// Set maxEventID
	$request = $smcFunc['db_query']('', '
		SELECT MAX(id_event)
		FROM {db_prefix}project_timeline');

	list ($maxEventID) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	updateSettings(array('project_maxEventID' => $maxEventID));

	return true;
}

// Comments not linked to events
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

// Events without poster info
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

// Unnecessary events
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

	/*$request = $smcFunc['db_query']('', '
		SELECT id_event
		FROM {db_prefix}project_timeline AS tl
			LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
		WHERE ISNULL(i.id_issue)');

	$temp = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$temp[] = sprintf($txt['error_issue_info_event'], $row['id_event']);
	$smcFunc['db_free_result']($request);

	// TEMP
	var_dump($temp);die();*/

	return true;
}

?>