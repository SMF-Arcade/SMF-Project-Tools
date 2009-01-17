<?php
/**********************************************************************************
* installDatabase.php                                                             *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.2                               *
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

// Temporary
if (SMF == 'SSI') die('Installing database via SSI isn\'t supported due to bugs!!');

require_once($sourcedir . '/ProjectDatabase.php');

$tbl = array_keys($tables);

// Add prefixes to array
foreach ($tbl as $id => $table)
	$tbl[$id] = $db_prefix . $table;

db_extend('packages');
$tbl = array_intersect($tbl, $smcFunc['db_list_tables']());

doTables($tbl, $tables);
doSettings($addSettings);
doPermission($permissions);

// Step: Install default groups
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}project_profiles');

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);
if ($count == 0)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_profiles',
		array(
			'id_profile' => 'int',
			'profile_name' => 'string',
		),
		array(
			array(
				1,
				'Default',
			),
		),
		array()
	);
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_permissions',
		array(
			'id_profile' => 'int',
			'id_group' => 'int',
			'permission' => 'string',
		),
		array(
			// Guest
			array(1, -1, 'issue_view'),
			// Regular members
			array(1, 0, 'issue_view'),
			array(1, 0, 'issue_report'),
			array(1, 0, 'issue_comment'),
			array(1, 0, 'issue_update_own'),
			array(1, 0, 'issue_attach'),
			array(1, 0, 'edit_comment_own'),
			// Global Moderators
			array(1, 2, 'issue_view'),
			array(1, 2, 'issue_report'),
			array(1, 2, 'issue_comment'),
			array(1, 2, 'issue_update_own'),
			array(1, 2, 'issue_update_any'),
			array(1, 2, 'issue_attach'),
			array(1, 2, 'issue_moderate'),
			array(1, 2, 'edit_comment_own'),
			array(1, 2, 'edit_comment_any'),
			array(1, 2, 'delete_comment_own'),
			array(1, 2, 'delete_comment_any'),
		),
		array()
	);
}

if (SMF == 'SSI')
	echo 'Database changes are complete!';

?>