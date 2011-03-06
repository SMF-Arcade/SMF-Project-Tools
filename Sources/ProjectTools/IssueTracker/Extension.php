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
class ProjectTools_IssueTracker_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'IssueTracker',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModules()
	{
		return array(
			'issues' => array(
				'class_name' => 'ProjectTools_IssueTracker_Module',
			),
		);
	}
}

?>