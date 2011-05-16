<?php
/**
 * This script is standalone installer for SMF Project Tools
 *
 * @package ProjectTools
 * @subpackage Installer
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(dirname(__FILE__)) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please upload ptinstall directory to SMF directory.');
// Make sure we have access to install packages
if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');
	
ProjectTools_Install::install();

?>