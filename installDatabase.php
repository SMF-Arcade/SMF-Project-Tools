<?php
/**********************************************************************************
* installDatabase.php                                                             *
***********************************************************************************
* SMF Issue: Issue tracker for SMF                                                *
* =============================================================================== *
* Software Version:           SMF Issue 0.1 Alpha                                 *
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

global $txt, $boarddir, $sourcedir, $modSettings, $context, $settings, $db_prefix, $forum_version, $smcFunc;
global $db_package_log;
global $db_connection, $db_name;

// Ugh...
if (!isset($forum_version))
{
	require_once(dirname(__FILE__) . '/index.php');
}

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

$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}project_groups');

// Install default groups
list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);
if ($count == 0)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_groups',
		array(
			'id_group' => 'int',
			'id_project' => 'int',
			'group_name' => 'string',
			'member_groups' => 'string',
		),
		array(
			array(
				1,
				0,
				'Viewer',
				'-1'
			),
			array(
				2,
				0,
				'Reporter',
				'0'
			),
			array(
				3,
				0,
				'Beta Tester',
				'3',
			),
			array(
				4,
				0,
				'Team Member',
				'1',
			),
		),
		array()
	);
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_permissions',
		array(
			'id_group' => 'int',
			'permission' => 'string',
		),
		array(
			// Viewer
			array(
				1,
				'issue_view',
			),

			// Reporter
			array(
				2,
				'issue_view',
			),
			array(
				2,
				'issue_report',
			),
			array(
				2,
				'issue_comment',
			),
			array(
				2,
				'issue_update_own',
			),
			array(
				2,
				'issue_attach',
			),

			// Beta Tester
			array(
				3,
				'issue_view',
			),
			array(
				3,
				'issue_report',
			),
			array(
				3,
				'issue_comment',
			),
			array(
				3,
				'issue_update_own',
			),
			array(
				3,
				'issue_attach',
			),

			// Team Member
			array(
				4,
				'issue_view',
			),
			array(
				4,
				'issue_report',
			),
			array(
				4,
				'issue_comment',
			),
			array(
				4,
				'issue_update_own',
			),
			array(
				4,
				'issue_update_any',
			),
			array(
				4,
				'delete_comment_own',
			),
			array(
				4,
				'delete_comment_any',
			),
			array(
				4,
				'issue_moderate',
			),
			array(
				4,
				'issue_attach',
			),
		),
		array()
	);
}

?>