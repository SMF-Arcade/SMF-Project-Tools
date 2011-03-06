<?php
/**
 * 
 *
 * @package IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_IssueTracker_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	public function RegisterArea()
	{
		global $txt;
		
		return array(
			'id' => 'issues',
			'title' => $txt['issues'],
			'callback' => array(get_class(), 'Main'),
			'hide_linktree' => true,
			'order' => 10,
		);
	}
}

?>