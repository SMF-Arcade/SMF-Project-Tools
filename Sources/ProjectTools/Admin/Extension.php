<?php
/**
 * 
 *
 * @package ProjectTools
 * @subpackage Admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

/**
 *
 */
class ProjectTools_Admin_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'Admin',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_Admin_Module';
	}
}

?>