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
class ProjectTools_IssueTracker_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	public function Main()
	{
		global $context;
		
		$subActions = array(
			'main' => array('ProjectTools_IssueTracker_List', 'Main'),
			'view' => array('ProjectTools_IssueTracker_View', 'Main'),
			'tags' => array('ProjectTools_IssueTracker_Tags', 'Main'),
			'update' => array('ProjectTools_IssueTracker_Report', 'Update'),
			'upload' => array('ProjectTools_IssueTracker_Edit', 'Upload'),
			'move' => array('ProjectTools_IssueTracker_Edit', 'Move'),
			'delete' => array('ProjectTools_IssueTracker_Edit', 'Delete'),
			// Comment
			'reply' => array('ProjectTools_IssueTracker_Comment', 'Reply'),
			'edit' => array('ProjectTools_IssueTracker_Edit', 'Edit'),
			'removeComment' => array('ProjectTools_IssueTracker_Edit', 'CommentDelete'),
			// Report
			'report' => array('ProjectTools_IssueTracker_Report', 'Report'),
		);
		
		ProjectTools_Extensions::runProjectHooks('IssueTracker_subActions', array(&$subActions));
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		// Linktree
		if (ProjectTools_IssueTracker_Issue::getCurrent())
			$context['linktree'][] = array(
				'name' => ProjectTools_IssueTracker_Issue::getCurrent()->name,
				'url' => ProjectTools_IssueTracker_Issue::getCurrent()->href,
			);
		
			
		call_user_func($subActions[$_REQUEST['sa']], $this->project);
	}

	/**
	 *
	 */
	public function RegisterAreas(&$project_areas)
	{
		global $txt;
		
		$project_areas['issues'] = array(
			'id' => 'issues',
			'title' => $txt['issues'],
			'callback' => array($this, 'Main'),
			'hide_linktree' => false,
			'order' => 10,
		);
	}
	
	/**
	 *
	 */
	function Frontpage_RegsterBlocks(&$frontpage_blocks)
	{
		global $context, $project, $user_info;
		
		$issues_num = 5;

		$issue_list = array(
			'IssueTracker:recent_issues' => array(
				'title' => 'recent_issues',
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'issues')),
				'order' => 'i.updated DESC',
				'where' => '1 = 1',
				'show' => ProjectTools::allowedTo('issue_view'),
			),
			'IssueTracker:my_reports' => array(
				'title' => 'reported_by_me',
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'issues', 'reporter' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_reporter = {int:current_member}',
				'show' => ProjectTools::allowedTo('issue_report'),
			),
			'IssueTracker:assigned' => array(
				'title' => 'assigned_to_me',
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'issues', 'assignee' => $user_info['id'])),
				'order' => 'i.updated DESC',
				'where' => 'i.id_assigned = {int:current_member} AND NOT (i.status IN ({array_int:closed_status}))',
				'show' => ProjectTools::allowedTo('issue_resolve'),
			),
			'IssueTracker:new_issues' => array(
				'title' => 'new_issues',
				'href' => ProjectTools::get_url(array('project' => $project, 'area' => 'issues', 'status' => 1,)),
				'order' => 'i.created DESC',
				'where' => 'i.status = 1',
				'show' => ProjectTools::allowedTo('issue_view'),
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
}

?>