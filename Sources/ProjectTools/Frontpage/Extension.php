<?php
/**
 * 
 *
 * @package Admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Frontpage_Extension extends ProjectTools_ExtensionBase
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
	public function getModules()
	{
		return array(
			'frontpage' => array(
				'class_name' => 'ProjectTools_Frontpage_Module',
			),
		);
	}
}

?>