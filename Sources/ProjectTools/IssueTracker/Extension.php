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
	public function getModule()
	{
		return 'ProjectTools_IssueTracker_Module';
	}
	
	/**
	 *
	 */
	function Profile_subActions(&$subActions)
	{
		$subActions['assigned'] = array(array('ProjectTools_IssueTracker_Profile', 'projectProfileIssues'));
		$subActions['reported'] = array(array('ProjectTools_IssueTracker_Profile', 'projectProfileIssues'));
	}
	
	/**
	 *
	 */
	function Profile_subSections(&$subSections)
	{
		global $txt;
		
		$subSections['reported'] = array($txt['project_profile_reported']);
		$subSections['assigned'] = array($txt['project_profile_assigned']);		
	}
}

?>