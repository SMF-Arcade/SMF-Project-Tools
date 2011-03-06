<?php
/**
 * 
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_Extensions
{
	/**
	 *
	 */
	static protected $extensions = array();
	
	/**
	 *
	 */
	static public function loadExtension($extension)
	{
		$mod = 'ProjectTools_' . $extension . '_Extension';
		
		self::$extensions[$extension] = new $mod();
	}
	
	/**
	 *
	 */
	static public function runHooks($hook, $params)
	{
		
	}
}

?>