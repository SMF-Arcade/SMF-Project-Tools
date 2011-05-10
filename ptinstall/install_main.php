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
global $addSettings, $permissions, $tables, $sourcedir;

if (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please run ptinstall/index.php instead');

require_once($sourcedir . '/Madjoki/Install/Helper.php');
require_once($sourcedir . '/Subs-ProjectMaintenance.php');

// Step 1: Do tables
doTables($tables);

ProjectTools_Install::install();
	
// Step 7: Run general maintenance
ptMaintenanceGeneral();


?>