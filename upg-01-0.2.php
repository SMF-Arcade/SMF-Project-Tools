<?php
/**********************************************************************************
* installDatabase.php                                                             *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.5                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2010 by:     Niko Pahajoki (http://www.madjoki.com)              *
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

global $txt, $boarddir, $sourcedir, $modSettings, $context, $settings, $db_prefix, $forum_version, $smcFunc;
global $db_package_log;
global $db_connection, $db_name;

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
// Make sure we have access to install packages
if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

require_once($sourcedir . '/ProjectDatabase.php');

// Fix comments without id_event
$request = $smcFunc['db_query']('', '
	SELECT id_comment
	FROM {db_prefix}issue_comments
	WHERE id_event = 0');

$s = 0;
$f = 0;

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
				AND tl.event = {string:new_comment}',
			array(
				'new_comment' => 'new_issue',
				'comment' => $row['id_comment'],
			)
		);
		list ($id_event) = $smcFunc['db_fetch_row']($event_req);
		$smcFunc['db_free_result']($event_req);

		if (!$id_event)
			$f++;
	}
	else
		$s++;
}
$smcFunc['db_free_result']($request);

echo round(100*($s/($s+$f))), '%';

if (SMF == 'SSI')
	echo 'Database upgrade complete!';

?>