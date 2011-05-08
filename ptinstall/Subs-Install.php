<?php
/**
 * Helper functions for installer
 *
 * @package installer
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Creates tables based on array
 *
 * @param array $tables Array containing descriptions for tables
 * @param array $columnRename Array containing column renames
 * @return array Log of changes
 *
 * @since 0.1
 */
function doTables($tables, $columnRename = array())
{
	global $smcFunc, $db_prefix, $db_type, $db_show_debug;

	$log = array();
	$existingTables = $smcFunc['db_list_tables']();

	foreach ($tables as $table)
	{
		$table_name = $table['name'];

		$tableExists = in_array($db_prefix . $table_name, $existingTables);

		// Create table
		if (!$tableExists && empty($table['smf']))
			$smcFunc['db_create_table']('{db_prefix}' . $table_name, $table['columns'], $table['indexes']);
		// Update table
		else
		{
			$currentTable = $smcFunc['db_table_structure']('{db_prefix}' . $table_name);

			// Renames in this table?
			if (!empty($table['rename']))
			{
				foreach ($currentTable['columns'] as $column)
				{
					if (isset($table['rename'][$column['name']]))
					{
						$old_name = $column['name'];
						$column['name'] = $table['rename'][$column['name']];

						$smcFunc['db_change_column']('{db_prefix}' . $table_name, $old_name, $column);
					}
				}
			}

			// Global renames? (should be avoided)
			if (!empty($columnRename))
			{
				foreach ($currentTable['columns'] as $column)
				{
					if (isset($columnRename[$column['name']]))
					{
						$old_name = $column['name'];
						$column['name'] = $columnRename[$column['name']];
						$smcFunc['db_change_column']('{db_prefix}' . $table_name, $old_name, $column);
					}
				}
			}

			// Check that all columns are in
			foreach ($table['columns'] as $id => $col)
			{
				$exists = false;

				// TODO: Check that definition is correct
				foreach ($currentTable['columns'] as $col2)
				{
					if ($col['name'] === $col2['name'])
					{
						$exists = true;
						break;
					}
				}

				// Add missing columns
				if (!$exists)
					$smcFunc['db_add_column']('{db_prefix}' . $table_name, $col);
			}

			// Remove any unnecassary columns
			foreach ($currentTable['columns'] as $col)
			{
				$exists = false;

				foreach ($table['columns'] as $col2)
				{
					if ($col['name'] === $col2['name'])
					{
						$exists = true;
						break;
					}
				}

				if (!$exists && isset($table['upgrade']['columns'][$col['name']]))
				{
					if ($table['upgrade']['columns'][$col['name']] == 'drop')
						$smcFunc['db_remove_column']('{db_prefix}' . $table_name, $col['name']);
				}
				elseif (!$exists && !empty($db_show_debug) && empty($table['smf']))
					$log[] = sprintf('Table %s has non-required column %s', $table_name, $col['name']);
			}

			// Check that all indexes are in and correct
			foreach ($table['indexes'] as $id => $index)
			{
				$exists = false;

				foreach ($currentTable['indexes'] as $index2)
				{
					// Primary is special case
					if ($index['type'] == 'primary' && $index2['type'] == 'primary')
					{
						$exists = true;

						if ($index['columns'] !== $index2['columns'])
						{
							$smcFunc['db_remove_index']('{db_prefix}' . $table_name, 'primary');
							$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
						}

						break;
					}
					// Make sure index is correct
					elseif (isset($index['name']) && isset($index2['name']) && $index['name'] == $index2['name'])
					{
						$exists = true;

						// Need to be changed?
						if ($index['type'] != $index2['type'] || $index['columns'] !== $index2['columns'])
						{
							$smcFunc['db_remove_index']('{db_prefix}' . $table_name, $index['name']);
							$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
						}

						break;
					}
				}

				if (!$exists)
					$smcFunc['db_add_index']('{db_prefix}' . $table_name, $index);
			}

			// Remove unnecassary indexes
			foreach ($currentTable['indexes'] as $index)
			{
				$exists = false;

				foreach ($table['indexes'] as $index2)
				{
					// Primary is special case
					if ($index['type'] == 'primary' && $index2['type'] == 'primary')
						$exists = true;
					// Make sure index is correct
					elseif (isset($index['name']) && isset($index2['name']) && $index['name'] == $index2['name'])
						$exists = true;
				}

				if (!$exists)
				{
					if (isset($table['upgrade']['indexes']))
					{
						foreach ($table['upgrade']['indexes'] as $index2)
						{
							if ($index['type'] == 'primary' && $index2['type'] == 'primary' && $index['columns'] === $index2['columns'])
								$smcFunc['db_remove_index']('{db_prefix}' . $table_name, 'primary');
							elseif (isset($index['name']) && isset($index2['name']) && $index['name'] == $index2['name'] && $index['type'] == $index2['type'] && $index['columns'] === $index2['columns'])
								$smcFunc['db_remove_index']('{db_prefix}' . $table_name, $index['name']);
							elseif (!empty($db_show_debug))
								$log[] = $table_name . ' has Unneeded index ' . var_dump($index);
						}
					}
					elseif (!empty($db_show_debug))
						$log[] = $table_name . ' has Unneeded index ' . var_dump($index);
				}
			}
		}
	}

	if (!empty($log))
		log_error(implode('<br />', $log));

	return $log;
}


?>