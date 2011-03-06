<?php
/**
 * Contains class for Issue Tracker pages
 *
 * @package issuetracker
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.5
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
	!!!
*/

global $extensionInformation;

$extensionInformation = array(
	'title' => 'Issue Tracker',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('issues', 'ProjectModule_Issues');

/**
 * Project Module Issues
 */
class ProjectModule_Issues extends ProjectModule_Base
{
	/**
	 *
	 */
	function __construct()
	{
		$this->subActions = array(
			'main' => array(
				'file' => 'IssueList.php',
				'callback' => 'IssueList',
			),
			'view' => array(
				'file' => 'IssueView.php',
				'callback' => 'IssueView',
			),
			'tags' => array(
				'file' => 'IssueView.php',
				'callback' => 'IssueTag',
			),
			'update' => array(
				'file' => 'IssueReport.php',
				'callback' => 'IssueUpdate',
			),
			'upload' => array(
				'file' => 'IssueReport.php',
				'callback' => 'IssueUpload',
			),
			'delete' => array(
				'file' => 'IssueView.php',
				'callback' => 'IssueDelete',
			),
			'move' => array(
				'file' => 'IssueView.php',
				'callback' => 'IssueMove',
			),
			// Reply
			'reply' => array(
				'file' => 'IssueComment.php',
				'callback' => 'IssueReply',
			),
			'reply2' => array(
				'file' => 'IssueComment.php',
				'callback' => 'IssueReply2',
			),
			// Edit
			'edit' => array(
				'file' => 'IssueComment.php',
				'callback' => 'IssueReply',
			),
			'edit2' => array(
				'file' => 'IssueComment.php',
				'callback' => 'IssueReply2',
			),
			// Remove comment
			'removeComment' => array(
				'file' => 'IssueComment.php',
				'callback' => 'IssueDeleteComment',
			),
			// Report Issue
			'report' => array(
				'file' => 'IssueReport.php',
				'callback' => 'ReportIssue',
			),
			'report2' => array(
				'file' => 'IssueReport.php',
				'callback' => 'ReportIssue2',
			),
		);	
	}

	/**
	 *
	 */
	function main($subaction)
	{
		global $context;
		
		if (ProjectTools_IssueTracker_Issue::getCurrent())
			$context['linktree'][] = array(
				'name' => ProjectTools_IssueTracker_Issue::getCurrent()->name,
				'url' => ProjectTools_IssueTracker_Issue::getCurrent()->href,
			);
			
		call_user_func($this->subActions[$subaction]['callback']);
	}
}

?>