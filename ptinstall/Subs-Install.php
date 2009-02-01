<?php
/**********************************************************************************
* Subs-Install.php                                                                *
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

if (!defined('SMF'))
	die('Hacking attempt...');

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