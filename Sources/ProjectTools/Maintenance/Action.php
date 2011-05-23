<?php
/**
 * 
 *
 * @package admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Base class for maintenance actions.
 *
 * Handles pausing for overload prevntion
 */
abstract class ProjectTools_Maintenance_Action
{
	/**
	 *
	 */
	protected $actions = array();
	
	/**
	 *
	 */
	public $current_step = 0;

	/**
	 *
	 */
	public $total_steps = 0;
	
	/**
	 *
	 */
	function __construct()
	{
		$this->total_steps = count($this->actions);
	}
	
	/**
	 *
	 * @param 
	 * @return bool|string Returns true if done, otherwise state string for continuing
	 */
	function run($step = 0)
	{
		$this->current_step = $step;
		
		$action = $this->actions[$this->current_step - 1];
		
		call_user_func($action);
		
		return $step + 1;
	}
}

?>