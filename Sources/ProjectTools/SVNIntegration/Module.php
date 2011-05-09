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
class ProjectTools_SVNIntegration_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	
	/**
	 *
	 */
	public function Main()
	{
		$subActions = array(

		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		call_user_func($subActions[$_REQUEST['sa']], $this->project);
	}
	
	/**
	 *
	 */
	public function Admin()
	{
	}
	
	/**
	 *
	 */
	public function RegisterAdminSubactions()
	{
		global $txt;
		
		return array(
			'SVN' => array(
				'title' => $txt['admin_svn_integration'],
				'callback' => array($this, 'Admin'),
			),
		);
	}
}

?>