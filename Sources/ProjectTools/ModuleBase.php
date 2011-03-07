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
	 * @var ProjectTools_Project
	 */
	protected $project;
	
	/**
	 * Defines subactions handled by this module
	 */
	public $subActions = array();
	
	/**
	 * Defines sub tabs of module
	 */
	public $subTabs = array();
	
	/**
	 *
	 */
	function __construct(ProjectTools_Project $project)
	{
		$this->project = $project;
	}
	
	/**
	 *
	 */
	abstract public function RegisterArea();
}

?>