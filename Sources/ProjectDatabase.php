<?php
/**********************************************************************************
* IssueDatabase.php                                                               *
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

$project_version = '0.2';

$addSettings = array(
	'issuesPerPage' => array(25, false),
	'commentsPerPage' => array(20, false),
	'projectEnabled' => array(true, false),
	'projectAttachments' => array(true, false),
	'linkIssues' => array(true, false),
);

$permissions = array(
	'project_access' => array(-1, 0, 2),
	'project_admin' => array(),
);

$tables = array(
	// Projects
	'projects' => array(
		'name' => 'projects',
		// Rename old columns
		'rename' => array(
			'id_comment_mod' => 'id_event_mod',
		),
		// Columns
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'description',
				'type' => 'text',
			),
			array(
				'name' => 'long_description',
				'type' => 'text',
			),
			array(
				'name' => 'trackers',
				'type' => 'varchar',
				'size' => 255,
				'default' => 'bug,feature',
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'size' => 255,
				'default' => '-1,0,2',
			),
			array(
				'name' => 'open_bug',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'closed_bug',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'open_feature',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'closed_feature',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'id_event_mod',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'id_profile',
				'type' => 'int',
				'unsigned' => true,
				'default' => 1,
			),
			// Project Theme
			array(
				'name' => 'project_theme',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'override_theme',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			// Show in board index
			array(
				'name' => 'id_category',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			// But where is cat?
			array(
				'name' => 'cat_position',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_project')
			),
		)
	),
	// Developers
	'project_developer' => array(
		'name' => 'project_developer',
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_member', 'id_project')
			),
			array(
				'name' => 'id_project',
				'type' => 'index',
				'columns' => array('id_project')
			),
		)
	),
	// Project Permission Profiles
	'project_profiles' => array(
		'name' => 'project_profiles',
		'columns' => array(
			array(
				'name' => 'id_profile',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'profile_name',
				'type' => 'varchar',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_profile')
			),
		),
	),
	// Permissions
	'project_permissions' => array(
		'name' => 'project_permissions',
		'columns' => array(
			array(
				'name' => 'id_profile',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_group',
				'type' => 'int',
			),
			array(
				'name' => 'permission',
				'type' => 'varchar',
				'size' => 30,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_profile', 'id_group', 'permission')
			),
			array(
				'name' => 'id_group',
				'type' => 'index',
				'columns' => array('id_profile', 'id_group')
			),
		),
	),
	// Versions
	'project_versions' => array(
		'name' => 'project_versions',
		'columns' => array(
			array(
				'name' => 'id_version',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_parent',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'version_name',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'description',
				'type' => 'text',
			),
			array(
				'name' => 'release_date',
				'type' => 'varchar',
				'default' => '',
				'size' => 255,
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'default' => '-1,0,2',
				'size' => 255,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_version')
			),
			array(
				'name' => 'id_project',
				'type' => 'index',
				'columns' => array('id_project')
			),
		)
	),
	// Categories/modules table
	'issue_category' => array(
		'name' => 'issue_category',
		'columns' => array(
			array(
				'name' => 'id_category',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'category_name',
				'type' => 'varchar',
				'size' => 30,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_category')
			),
			array(
				'name' => 'id_project',
				'type' => 'index',
				'columns' => array('id_project')
			),
		)
	),
	// Issues table
	'issues' => array(
		'name' => 'issues',
		// Rename old columns
		'rename' => array(
			'id_comment_mod' => 'id_event_mod',
		),
		// Columns
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'auto' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
			),
			array(
				'name' => 'subject',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'issue_type',
				'type' => 'varchar',
				'size' => 255,
				'default' => 'bug',
			),
			array(
				'name' => 'id_category',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_assigned',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_reporter',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_updater',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_version',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_version_fixed',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_comment_first',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_comment_last',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_event_mod',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'created',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'updated',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'priority',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'replies',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'private_issue',
				'type' => 'int',
				'default' => 0,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_issue')
			),
			array(
				'type' => 'index',
				'columns' => array('id_project', 'id_issue')
			)
		)
	),
	// Comments
	'issue_comments' => array(
		'name' => 'issue_comments',
		// Rename old columns
		'rename' => array(
			'id_comment_mod' => 'id_event_mod',
		),
		// Columns
		'columns' => array(
			array(
				'name' => 'id_comment',
				'type' => 'int',
				'auto' => true,
			),
			array(
				'name' => 'id_issue',
				'type' => 'int',
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'id_event_mod',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'post_time',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'poster_name',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'poster_email',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'poster_ip',
				'type' => 'varchar',
				'size' => 60,
				'default' => '',
			),
			array(
				'name' => 'edit_time',
				'type' => 'int',
				'default' => 0,
			),
			array(
				'name' => 'edit_name',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'body',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_comment')
			),
			array(
				'type' => 'index',
				'columns' => array('id_issue')
			),
			array(
				'type' => 'index',
				'columns' => array('id_event')
			)
		)
	),
	// Project Timeline
	'project_timeline' => array(
		'name' => 'project_timeline',
		'columns' => array(
			array(
				'name' => 'id_event',
				'type' => 'int',
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_version',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'poster_name',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'poster_email',
				'type' => 'varchar',
				'size' => 255,
				'default' => '',
			),
			array(
				'name' => 'poster_ip',
				'type' => 'varchar',
				'size' => 60,
				'default' => '',
			),
			array(
				'name' => 'event',
				'type' => 'varchar',
				'size' => 15,
				'default' => '',
			),
			array(
				'name' => 'event_time',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'event_data',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_event')
			),
			array(
				'name' => 'id_project',
				'type' => 'index',
				'columns' => array('id_project')
			),
		),
	),
	// Tags
	'issue_tags' => array(
		'name' => 'issue_tags',
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'tag',
				'type' => 'varchar',
				'size' => 35,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_issue', 'tag')
			),
		)
	),
	// Log table for notify requests
	'log_notify_projects' => array(
		'name' => 'log_notify_projects',
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
			array(
				'name' => 'sent',
				'type' => 'int',
				'unsigned' => true,
				'default' => 0,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_project', 'id_issue', 'id_member')
			),
			array(
				'type' => 'index',
				'columns' => array('id_project')
			),
		)
	),
	// Log tables for read marks
	'log_projects' => array(
		'name' => 'log_projects',
		// Rename old columns
		'rename' => array(
			'id_comment' => 'id_event',
		),
		// Columns
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_project', 'id_member')
			),
			array(
				'type' => 'index',
				'columns' => array('id_project')
			),
		)
	),
	'log_issues' => array(
		'name' => 'log_issues',
		// Rename old columns
		'rename' => array(
			'id_comment' => 'id_event',
		),
		// Columns
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_issue', 'id_member')
			),
			array(
				'type' => 'index',
				'columns' => array('id_issue')
			),
		)
	),
	// Settings
	'projects_settings' => array(
		'name' => 'projects_settings',
		'columns' => array(
			array(
				'name' => 'id_member',
				'type' => 'int',
				'unsigned' => true,
			),
			array(
				'name' => 'variable',
				'type' => 'varchar',
				'size' => 30,
			),
			array(
				'name' => 'value',
				'type' => 'text',
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_member', 'variable')
			),
			array(
				'type' => 'index',
				'columns' => array('id_member')
			),
		)
	),
	// SMF Attachements table
	'attachments' => array(
		'name' => 'attachments',
		'smf' => true,
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'index',
				'columns' => array('id_issue')
			),
		)
	),
	// Issue Attachements
	'issue_attachments' => array(
		'name' => 'issue_attachments',
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_attach',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
				'default' => 0,
				'unsigned' => true,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_issue', 'id_attach')
			),
			array(
				'type' => 'index',
				'columns' => array('id_issue')
			),
		)
	),
);

// Functions
function doTables($tbl, $tables, $columnRename = array(), $smf2 = true)
{
	global $smcFunc, $db_prefix, $db_type;

	foreach ($tables as $table)
	{
		$table_name = $db_prefix . $table['name'];

		if (!empty($table['rename']))
		{
			$oldTable = $smcFunc['db_table_structure']($table_name, array('no_prefix' => true));

			foreach ($oldTable['columns'] as $column)
			{
				if (isset($table['rename'][$column['name']]))
				{
					$old_name = $column['name'];
					$column['name'] = $table['rename'][$column['name']];

					$smcFunc['db_change_column']($table_name, $old_name, $column, array('no_prefix' => true));
				}
			}
		}

		if (empty($table['smf']))
			$smcFunc['db_create_table']($table_name, $table['columns'], $table['indexes'], array('no_prefix' => true));

		if (in_array($table_name, $tbl))
		{
			foreach ($table['columns'] as $column)
			{
				$smcFunc['db_add_column']($table_name, $column, array('no_prefix' => true));

				// TEMPORARY until SMF package functions works with this
				if (isset($column['unsigned']) && $db_type == 'mysql')
				{
					$column['size'] = isset($column['size']) ? $column['size'] : null;

					list ($type, $size) = $smcFunc['db_calculate_type']($column['type'], $column['size']);
					if ($size !== null)
						$type = $type . '(' . $size . ')';

					$smcFunc['db_query']('', "
						ALTER TABLE $table_name
						CHANGE COLUMN $column[name] $column[name] $type UNSIGNED " . (empty($column['null']) ? 'NOT NULL' : '') . ' ' .
							(empty($column['default']) ? '' : "default '$column[default]'") . ' ' .
							(empty($column['auto']) ? '' : 'auto_increment') . ' ',
						'security_override'
					);
				}
			}

			// Update table
			foreach ($table['indexes'] as $index)
			{
				if ($index['type'] != 'primary')
					$smcFunc['db_add_index']($table_name, $index, array('no_prefix' => true));
			}
		}
	}
}

function doSettings($addSettings, $smf2 = true)
{
	global $smcFunc;

	$update = array();

	foreach ($addSettings as $variable => $s)
	{
		list ($value, $overwrite) = $s;

		$result = $smcFunc['db_query']('', '
			SELECT value
			FROM {db_prefix}settings
			WHERE variable = {string:variable}',
			array(
				'variable' => $variable,
			)
		);

		if ($smcFunc['db_num_rows']($result) == 0 || $overwrite == true)
			$update[$variable] = $value;
	}

	if (!empty($update))
		updateSettings($update);
}

function doPermission($permissions, $smf2 = true)
{
	global $smcFunc;

	$perm = array();

	foreach ($permissions as $permission => $default)
	{
		$result = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}permissions
			WHERE permission = {string:permission}',
			array(
				'permission' => $permission
			)
		);

		list ($num) = $smcFunc['db_fetch_row']($result);

		if ($num == 0)
		{
			foreach ($default as $grp)
				$perm[] = array($grp, $permission);
		}
	}

	$group = $smf2 ? 'id_group': 'ID_GROUP';

	if (empty($perm))
		return;

	$smcFunc['db_insert'](
		'insert',
		'{db_prefix}permissions',
		array(
			$group => 'int',
			'permission' => 'string'
		),
		$perm,
		array()
	);
}

?>
