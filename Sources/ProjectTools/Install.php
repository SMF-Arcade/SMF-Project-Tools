<?php
/**
 * Hooks for SMF Project
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Install
{
	/**
	 *
	 */
	static protected $adminFeature = 'pj';
	
	/**
	 *
	 */
	static protected $settings = array(
		'issuesPerPage' => array(25, false),
		'commentsPerPage' => array(20, false),
		'projectEnabled' => array(true, false),
		'projectExtensions' => array('Frontpage,Roadmap,IssueTracker', true),
		'projectAttachments' => array(true, false),
		'linkIssues' => array(true, false),
	);
	
	/**
	 *
	 */
	static protected $permissions = array(
		'project_access' => array(-1, 0, 2),
		'project_admin' => array(),
	);
	
	/**
	 *
	 */
	static public function run()
	{
		Madjoki_Install_Helper::updateAdminFeatures(self::$adminFeature, true);
		Madjoki_Install_Helper::doSettings(self::$settings);
		Madjoki_Install_Helper::doPermission(self::$permissions);
	}
}

?>