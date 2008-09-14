<?php
/**********************************************************************************
* IssueDatabase.php                                                               *
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

$project_version = '0.1 Alpha';

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
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'description',
				'type' => 'text',
				'null' => false,
			),
			array(
				'name' => 'long_description',
				'type' => 'text',
				'null' => false,
			),
			array(
				'name' => 'trackers',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'open_bug',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'closed_bug',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'open_feature',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'closed_feature',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_comment_mod',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_profile',
				'type' => 'int',
				'null' => false,
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
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'null' => false,
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
				'null' => false,
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'profile_name',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
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
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_group',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'permission',
				'type' => 'varchar',
				'size' => 30,
				'null' => false,
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
				'null' => false,
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_parent',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'version_name',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'description',
				'type' => 'text',
				'null' => false
			),
			array(
				'name' => 'release_date',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'member_groups',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
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
				'null' => false,
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'category_name',
				'type' => 'varchar',
				'size' => 30,
				'null' => false,
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
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'null' => false,
				'auto' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'subject',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'issue_type',
				'type' => 'varchar',
				'size' => 255,
				'null' => false,
			),
			array(
				'name' => 'id_category',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_assigned',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_reporter',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_updater',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_version',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_comment_first',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_comment_last',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_comment_mod',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'id_version_fixed',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'status',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'created',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'updated',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'priority',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'replies',
				'type' => 'int',
				'null' => false,
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
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
			),
			array(
				'name' => 'id_comment_mod',
				'type' => 'int',
				'null' => false,
			),
			array(
				'name' => 'post_time',
				'type' => 'int',
			),
			array(
				'name' => 'poster_name',
				'type' => 'varchar',
				'size' => 256,
			),
			array(
				'name' => 'poster_email',
				'type' => 'varchar',
				'size' => 256,
			),
			array(
				'name' => 'poster_ip',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'edit_time',
				'type' => 'int',
			),
			array(
				'name' => 'edit_name',
				'type' => 'varchar',
				'size' => 256,
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
				'null' => false,
				'auto' => true,
				'unsigned' => true,
			),
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_version',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'poster_name',
				'type' => 'varchar',
				'size' => 256,
			),
			array(
				'name' => 'poster_email',
				'type' => 'varchar',
				'size' => 256,
			),
			array(
				'name' => 'poster_ip',
				'type' => 'varchar',
				'size' => 60,
			),
			array(
				'name' => 'event',
				'type' => 'varchar',
				'size' => 15,
				'null' => false,
			),
			array(
				'name' => 'event_time',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'event_data',
				'type' => 'text',
				'null' => false
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
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'tag',
				'type' => 'varchar',
				'size' => 35,
				'null' => false,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_issue', 'tag')
			),
		)
	),
	// Log tables for read marks
	'log_projects' => array(
		'name' => 'log_projects',
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_comment',
				'type' => 'int',
				'null' => false,
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
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_comment',
				'type' => 'int',
				'null' => false,
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
	// SMF Boards
	'boards' => array(
		'name' => 'boards',
		'smf' => true,
		'columns' => array(
			array(
				'name' => 'id_project',
				'type' => 'int',
				'default' => 0,
				'null' => false,
				'unsigned' => true,
			),
		),
		'indexes' => array(
		),
	),
	// SMF Attachements table
	'attachments' => array(
		'name' => 'attachments',
		'smf' => true,
		'columns' => array(
			array(
				'name' => 'id_issue',
				'type' => 'int',
				'null' => false,
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
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_attach',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_member',
				'type' => 'int',
				'null' => false,
				'unsigned' => true,
			),
			array(
				'name' => 'id_event',
				'type' => 'int',
				'null' => false,
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

		if (!empty($columnRename))
		{
			$table = $smcFunc['db_table_structure']($table_name, array('no_prefix' => true));

			foreach ($table['columns'] as $column)
			{
				if (isset($columnRename[$column['name']]))
				{
					$old_name = $column['name'];
					$column['name'] = $columnRename[$column['name']];
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