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