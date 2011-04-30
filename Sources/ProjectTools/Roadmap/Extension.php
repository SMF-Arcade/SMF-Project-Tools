<?php
/**
 * 
 *
 * @package Roadmap
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Roadmap_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'Roadmap',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_Roadmap_Module';
	}
	
	/**
	 *
	 */
	public function onActivate()
	{
	}
	
	/**
	 *
	 */
	public function onDisable()
	{
	}
}

?>