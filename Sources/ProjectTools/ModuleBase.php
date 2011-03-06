<?php
/**
 * Main handler for Project Tools
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
abstract class ProjectTools_ModuleBase
{
	/**
	 * Defines subactions handled by this module
	 */
	public $subActions = array();
	
	/**
	 * Defines sub tabs of module
	 */
	public $subTabs = array();
	
	/**
	 * Default constructor
	 */
	function __construct()
	{
		$this->subActions = array('main' => array('callback' => array($this, 'mainView')));
	}
	
	public function registersubAction($sa, $data)
	{
		$this->subActions[$sa] = $data;
	}
	
	public function beforeSubaction(&$subaction)
	{
		global $sourcedir;
		
		// Check that subaction exists, if not use "main"
		if (!isset($this->subActions[$subaction]))
			$subaction = 'main';
		
		// No main subaction? Use first then
		if (!isset($this->subActions[$subaction]) && count($this->subActions) > 0)
			list ($subaction, ) = array_keys($this->subActions);
		elseif (!isset($this->subActions[$subaction]))
			trigger_error(__CLASS__ . ' doesn\'t contain any subaction', E_USER_ERROR);
			
		// Check permission to subaction?
		if (isset($this->subActions[$subaction]['permission']))
			isAllowedTo($this->subActions[$subaction]['permission']);
		if (isset($this->subActions[$subaction]['project_permission']))
			projectIsAllowedTo($this->subActions[$subaction]['project_permission']);
		
		// File required?
		if (isset($this->subActions[$subaction]['file']))
			require_once($sourcedir . '/' . $this->subActions[$subaction]['file']);
	}
	
	public function main($subaction)
	{
		call_user_func($this->subActions[$subaction]['callback']);
	}
}

?>