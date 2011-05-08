<?php
/**
 * Generic functions for Project Tools admin
 *
 * @package admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Inserts new version to project
 */
function createVersion($id_project, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	if (empty($versionOptions['name']))
		trigger_error('createVersion(): required parameters missing or invalid');

	if (empty($versionOptions['release_date']))
		$versionOptions['release_date'] = serialize(array('day' => 0, 'month' => 0, 'year' => 0));

	if (empty($versionOptions['description']))
		$versionOptions['description'] = '';

	if (empty($versionOptions['parent']))
	{
		$versionOptions['parent'] = 0;
		$versionOptions['status'] = 0;
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND id_version = {int:version}',
			array(
				'project' => $id_project,
				'version' => $versionOptions['parent'],
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			trigger_error('createVersion(): invalid parent');
		$smcFunc['db_free_result']($request);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_versions',
		array(
			'id_project' => 'int',
			'id_parent' => 'int',
			'version_name' => 'string',
			'description' => 'string',
			'member_groups' => 'string',
		),
		array(
			$id_project,
			$versionOptions['parent'],
			$versionOptions['name'],
			$versionOptions['description'],
			implode(',', $versionOptions['member_groups']),
		),
		array('id_version')
	);

	$id_version = $smcFunc['db_insert_id']('{db_prefix}project_versions', 'id_version');

	unset($versionOptions['parent'], $versionOptions['name'], $versionOptions['description'], $versionOptions['member_groups']);

	updateVersion($id_project, $id_version, $versionOptions);

	return $id_version;
}

/**
 * Updates vesion
 */
function updateVersion($id_project, $id_version, $versionOptions)
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;
	
	$request = $smcFunc['db_query']('', '
		SELECT id_parent, permission_inherit
		FROM {db_prefix}project_versions
		WHERE id_project = {int:project}
			AND id_version = {int:version}',
		array(
			'project' => $id_project,
			'version' => $id_version,
		)
	);
	
	$versionRow = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	
	if (!$versionRow)
		return false;
	
	$inherited = !empty($versionRow['permission_inherit']);
	
	// Will it change?
	if (isset($versionOptions['permission_inherit']))
		$inherited = !empty($versionOptions['permission_inherit']);
	
	// Don't allow changing member_groups when inherited
	if (isset($versionOptions['member_groups']) && !$inherited)
		unset($versionOptions['member_groups']);
			
	$versionUpdates = array();

	if (isset($versionOptions['name']))
		$versionUpdates[] = 'version_name = {string:name}';

	if (isset($versionOptions['description']))
		$versionUpdates[] = 'description = {string:description}';

	if (isset($versionOptions['release_date']))
		$versionUpdates[] = 'release_date = {string:release_date}';
		
	if (isset($versionOptions['permission_inherit']))
	{
		// Make sure it's not overwritten
		if (isset($versionOptions['member_groups']) && !empty($versionOptions['permission_inherit']))
			unset($versionOptions['member_groups']);
			
		$versionUpdates[] = 'permission_inherit = {int:permission_inherit}';
		$versionOptions['permission_inherit'] = !empty($versionOptions['permission_inherit']) ? 1 : 0;
		$versionRow = $versionOptions['permission_inherit'];
		
		// Inherit from parent version
		if (!empty($versionRow['id_parent']))
			$request = $smcFunc['db_query']('', '
				SELECT member_groups
				FROM {db_prefix}project_versions
				WHERE id_project = {int:project}
					AND id_version = {int:version}',
				array(
					'project' => $id_project,
					'version' => $versionRow['id_parent'],
				)
			);
		// or from project
		else
			$request = $smcFunc['db_query']('', '
				SELECT member_groups
				FROM {db_prefix}projects
				WHERE id_project = {int:project}',
				array(
					'project' => $id_project,
				)
			);
			
		$versionUpdates[] = 'member_groups = {string:member_groups}';
		list ($versionOptions['member_groups']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	}

	if (isset($versionOptions['member_groups']))
	{
		// Update versions with permission inherited
		$request = $smcFunc['db_query']('', '
			SELECT id_version
			FROM {db_prefix}project_versions
			WHERE id_project = {int:project}
				AND permission_inherit = {int:inherit}
				AND id_parent = {int:parent}',
			array(
				'project' => $id_project,
				'inherit' => 1,
				'parent' => $id_version,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			updateVersion($id_project, $row['id_version'], array('member_groups' => $versionOptions['member_groups']));
		$smcFunc['db_free_result']($request);
		
		$versionUpdates[] = 'member_groups = {string:member_groups}';
		$versionOptions['member_groups'] = is_array($versionOptions['member_groups']) ? implode(',', $versionOptions['member_groups']) : $versionOptions['member_groups'];
	}

	if (isset($versionOptions['status']))
		$versionUpdates[] = 'status = {int:status}';

	if (!empty($versionUpdates))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET
				' . implode(',
				', $versionUpdates) . '
			WHERE id_version = {int:version}',
			array_merge($versionOptions, array(
				'version' => $id_version,
			))
		);

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

/**
 * Creates new category for project
 */
function createPTCategory($id_project, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_category',
		array('id_project' => 'int', 'category_name' => 'string'),
		array($id_project, $categoryOptions['name']),
		array('id_category')
	);

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}

/**
 * Updates category
 */
function updatePTCategory($id_project, $id_category, $categoryOptions)
{
	global $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	$categoryUpdates = array();

	if (isset($categoryOptions['name']))
		$categoryUpdates[] = 'category_name = {string:name}';

	if (isset($categoryOptions['project']))
		$categoryUpdates[] = 'id_project = {int:project}';

	if (!empty($categoryOptions))
		$request = $smcFunc['db_query']('', '
			UPDATE {db_prefix}issue_category
			SET
				' . implode(',
				', $categoryUpdates) . '
			WHERE id_category = {int:category}',
			array_merge($categoryOptions, array(
				'category' => $id_category,
			))
		);

	cache_put_data('project-' . $id_project, null, 120);
	cache_put_data('project-version-' . $id_project, null, 120);

	return true;
}





?>