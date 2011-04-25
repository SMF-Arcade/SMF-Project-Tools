<?php
/**
 * 
 *
 * @package SVNIntegration
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_SVNIntegration_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'SVN Integration',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_SVNIntegration_Module';
	}
	
	/**
	 *
	 */
	public function onActivate()
	{
		log_error('testing [onActivate]');
	}
	
	/**
	 *
	 */
	public function onDisable()
	{
		log_error('testing [onDisable]');
	}
}

?>