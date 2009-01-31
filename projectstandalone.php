<?php

/*
	This file is for running Project Tools in standalone mode, where Project Tools is not located inside forum.
	Meant for having Project Tools inside site rather than inside forum

	Eexample settings

		INSERT INTO `smf_settings` (`variable` ,`value`)
		VALUES
			('projectStandalone', '2'),
			('projectStandaloneUrl', 'http://www.smfarcade.info/projects')

	projectStandalone:
		1 - No "SEO" urls
		2 - "SEO" urls (not supported yet)

	Example .htaccess rules for "SEO" urls
		(not supported yet)

	projectStandaloneUrl:

		Base url for SEO urls.

			eg. http://www.smfarcade.info/wiki

		url to projectstandalone.php for non SEO urls

			eg. http://www.smfarcade.info/projects/index.php

*/

// Here you can add SSI settings or any settings for your site
$section = 'products';

// Path to Settings file
$smf_settings_file = dirname(__FILE__);

// Get everything started up...
// Section based on SMF index.php & SSI.php
define('SMF', 'SSI');
if (function_exists('set_magic_quotes_runtime'))
	@set_magic_quotes_runtime(0);
error_reporting(defined('E_STRICT') ? E_ALL | E_STRICT : E_ALL);
$time_start = microtime();

// Do some cleaning, just in case.
foreach (array('db_character_set', 'cachedir') as $variable)
	if (isset($GLOBALS[$variable]))
		unset($GLOBALS[$variable]);

// Load the settings...
require_once($smf_settings_file . '/Settings.php');

// Make absolutely sure the cache directory is defined.
if ((empty($cachedir) || !file_exists($cachedir)) && file_exists($boarddir . '/cache'))
	$cachedir = $boarddir . '/cache';

// And important includes.
require_once($sourcedir . '/QueryString.php');
require_once($sourcedir . '/Subs.php');
require_once($sourcedir . '/Errors.php');
require_once($sourcedir . '/Load.php');
require_once($sourcedir . '/Security.php');

// Using an pre-PHP5 version?
if (@version_compare(PHP_VERSION, '5') == -1)
	require_once($sourcedir . '/Subs-Compat.php');

// If $maintenance is set specifically to 2, then we're upgrading or something.
if (!empty($maintenance) && $maintenance == 2)
	db_fatal_error();

// Create a variable to store some SMF specific functions in.
$smcFunc = array();

// Database & settings & clean request
loadDatabase();
reloadSettings();
cleanRequest();
$context = array();

if (empty($modSettings['rand_seed']) || mt_rand(1, 250) == 69)
	smf_seed_generator();

// Check if compressed output is enabled, supported, and not already being done.
if (!empty($modSettings['enableCompressedOutput']) && !headers_sent() && ob_get_length() == 0)
{
	if (@ini_get('zlib.output_compression') == '1' || @ini_get('output_handler') == 'ob_gzhandler' || @version_compare(PHP_VERSION, '4.2.0') == -1)
		$modSettings['enableCompressedOutput'] = '0';
	else
		ob_start('ob_gzhandler');
}
if (empty($modSettings['enableCompressedOutput']))
	ob_start();

// Error handler and section
set_error_handler('error_handler');
loadSession();

// No wireless support
define('WIRELESS', false);

// Call load Functions
loadUserSettings();
// This is required for linktree
loadBoard();
loadPermissions();

// Project Tools begin
require_once($sourcedir . '/Subs-Project.php');
require_once($sourcedir . '/Subs-Issue.php');
require_once($sourcedir . '/Project.php');

// TEMP
if (empty($modSettings['projectStandalone']))
{
	$modSettings['projectStandalone'] = 2;
	$modSettings['projectStandaloneUrl'] = '/projects';
}

loadProjectTools();
loadProject();

// Load theme and check bans
loadTheme();
is_not_banned();

loadIssue();

if (empty($_REQUEST['action']) && (!empty($project) || !empty($issue)))
{
	$_REQUEST['action'] = 'projects';
	$_GET['action'] = 'projects';
}

if (isset($context['project_error']))
	fatal_lang_error($context['project_error'], false);

// Main thing
Projects(true);

obExit(null, null, true);

?>