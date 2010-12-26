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
	function RegisterProjectArea()
	{
		return array(
			'area' => 'issues', 'tab' => 'issues',
		);
	}

	/**
	 *
	 */
	function RegisterProjectTabs(&$tabs)
	{
		global $txt, $project;

		$tabs['issues'] = array(
			'href' => project_get_url(array('project' => $project, 'area' => 'issues')),
			'title' => $txt['issues'],
			'is_selected' => false,
			'order' => 10,
		);
	}

	/**
	 *
	 */
	function RegisterProjectFrontpageBlocks(&$frontpage_blocks)
	{
		global $context, $project, $user_info;
		
		$issues_num = 5;

		$issue_list = array(
			'recent_issues' => array(
				'title' => 'recent_issues',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues')),
				'order' => 'i.updated DESC',
				'where' => '1 = 1',
				'show' => projectAllowedTo('issue_view'),
			),
			'my_reports' => array(
				'title' => 'reported_by_me',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'reporter' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_reporter = {int:current_member}',
				'show' => projectAllowedTo('issue_report'),
			),
			'assigned' => array(
				'title' => 'assigned_to_me',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'assignee' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_assigned = {int:current_member} AND NOT (i.status IN ({array_int:closed_status}))',
				'show' => projectAllowedTo('issue_resolve'),
			),
			'new_issues' => array(
				'title' => 'new_issues',
				'href' => project_get_url(array('project' => $project, 'area' => 'issues', 'status' => 1,)),
				'order' => 'i.created DESC',
				'where' => 'i.status = 1',
				'show' => projectAllowedTo('issue_view'),
			),
		);
	
		foreach ($issue_list as $block_id => $information)
			$frontpage_blocks[$block_id] = array(
				'title' => $information['title'],
				'href' => $information['href'],
				'data_function' => 'getIssueList',
				'data_parameters' => array(0, $issues_num, $information['order'], $information['where']),
				'template' => 'issue_list_block',
				'show' => $information['show'],
			);
	}

	/**
	 *
	 */
	function main($subaction)
	{
		global $context;
		
		if (isset($context['current_issue']))
			$context['linktree'][] = array(
				'name' => $context['current_issue']['name'],
				'url' => $context['current_issue']['href'],
			);
			
		call_user_func($this->subActions[$subaction]['callback']);
	}
}

?>