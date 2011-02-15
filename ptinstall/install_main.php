<?php
/**
 * Main installer. Used in package-info.xml and install.php standalone installer
 *
 * @package installer
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

global $txt, $smcFunc, $db_prefix, $modSettings;
global $project_version, $addSettings, $permissions, $tables, $sourcedir;

if (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please run ptinstall/index.php instead');

require_once($sourcedir . '/Subs-ProjectMaintenance.php');

// Step 1: Do tables
doTables($tables);

// Step 2: Do Settings
doSettings($addSettings);

// Step 3: Update admin features
updateAdminFeatures('pj', !empty($modSettings['projectEnabled']));

// Step 3: Do Permissions
doPermission($permissions);

// Step 4: Install default groups if needed
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}project_profiles
	WHERE id_profile = 1');

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);
if ($count == 0)
{
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_profiles',
		array('id_profile' => 'int', 'profile_name' => 'string',),
		array(1, 'Default',),
		array('id_profile')
	);
	$smcFunc['db_insert']('insert',
		'{db_prefix}project_permissions',
		array('id_profile' => 'int', 'id_group' => 'int', 'permission' => 'string'),
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
		array('id_profile', 'id_group')
	);
}

// Step 5: Install Default trackers
$smcFunc['db_insert']('ignore',
	'{db_prefix}project_trackers',
	array('id_tracker' => 'int', 'short_name' => 'string', 'tracker_name' => 'string',  'plural_name' => 'string'),
	array(
		array(1, 'bug', 'Bug', 'Bugs'),
		array(2, 'feature', 'Feature', 'Features'),
	),
	array('id_tracker')
);

// Step 6: Install SMF Project Package server
$request = $smcFunc['db_query']('', '
	SELECT COUNT(*)
	FROM {db_prefix}package_servers
	WHERE name = {string:name}',
	array(
		'name' => 'SMF Project Tools Package Server',
	)
);

list ($count) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

if ($count == 0)
	$smcFunc['db_insert']('insert',
		'{db_prefix}package_servers',
		array(
			'name' => 'string',
			'url' => 'string',
		),
		array(
			'SMF Project Tools Package Server',
			'http://download.smfproject.net',
		),
		array()
	);
	
// Step 7: Run general maintenance
ptMaintenanceGeneral();

// Step 8: Hooks
add_integration_function('integrate_pre_include', '$sourcedir/PTHooks.php');
//add_integration_function('integrate_actions', 'Arcade_actions');
//add_integration_function('integrate_core_features', 'Arcade_core_features');
//add_integration_function('integrate_load_permissions', 'Arcade_load_permissions');
//add_integration_function('integrate_profile_areas', 'Arcade_profile_areas');
//add_integration_function('integrate_menu_buttons', 'Arcade_menu_buttons');
//add_integration_function('integrate_admin_areas', 'Arcade_admin_areas');

?>