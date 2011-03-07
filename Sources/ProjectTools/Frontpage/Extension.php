<?php
/**
 * 
 *
 * @package ProjectTools
 * @subpackage Frontpage
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
			'title' => 'Frontpage',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_Frontpage_Module';
	}
}

?>