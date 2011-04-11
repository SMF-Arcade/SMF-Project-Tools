<?php
/**
 * 
 *
 * @package IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_IssueTracker_List
{
	/**
	 *
	 */
	static public function Main()
	{
		global $context, $txt;
	
		ProjectTools::isAllowedTo('issue_view');
	
		// Sorting methods
		$sort_methods = array(
			'updated' => 'i.updated',
			'title' => 'i.subject',
			'id' => 'i.id_issue',
			'priority' => 'i.priority',
			'status' => 'i.status',
			'assigned' => 'i.id_assigned',
			'reporter' => 't.id_member_started'
		);
	
		// How user wants to sort issues?
		if (!isset($_REQUEST['sort']) || !isset($sort_methods[$_REQUEST['sort']]))
		{
			$context['sort_by'] = 'updated';
			$_REQUEST['sort'] = 'i.updated';
	
			$ascending = false;
			$context['sort_direction'] = 'down';
		}
		else
		{
			$context['sort_by'] = $_REQUEST['sort'];
			$_REQUEST['sort'] = $sort_methods[$_REQUEST['sort']];
	
			$ascending = !isset($_REQUEST['desc']);
			$context['sort_direction'] = $ascending ? 'up' : 'down';
		}
			
		// Get default filter for comparsion purposes
		$defaultFilter = getIssuesFilter();
		
		// Build Issue list options
		$issueListOptions = array(
			'id' => 'issue_list',
			'base_url' => array(
				'project' => ProjectTools_Project::getCurrent()->id,
				'area' => 'issues'
			),
			'filter' => getIssuesFilter('request'),
			'start' => $_REQUEST['start'],
			'page_index' => true,
			'sort' => $_REQUEST['sort'],
			'ascending' => $ascending,
		);
		$context['issue_search'] = $issueListOptions['filter'];
		
		// Add filter's to url if it's non-default
		if ($defaultFilter['title'] != $issueListOptions['filter']['title'])
			$issueListOptions['base_url']['tilte'] = $issueListOptions['filter']['title'];
	
		if ($defaultFilter['tracker'] != $issueListOptions['filter']['tracker'])
			$issueListOptions['base_url']['tracker'] = $context['issue_trackers'][$issueListOptions['filter']['tracker']];
	
		if ($defaultFilter['category'] != $issueListOptions['filter']['category'])
			$issueListOptions['base_url']['category'] = $issueListOptions['filter']['category'];
	
		if ($defaultFilter['reporter'] != $issueListOptions['filter']['reporter'])
			$issueListOptions['base_url']['reporter'] = $issueListOptions['filter']['reporter'];
	
		if ($defaultFilter['assignee'] != $issueListOptions['filter']['assignee'])
			$issueListOptions['base_url']['assignee'] = $issueListOptions['filter']['assignee'];
	
		if ($defaultFilter['version'] != $issueListOptions['filter']['version'])
			$issueListOptions['base_url']['version'] = $issueListOptions['filter']['version'];
	
		if ($defaultFilter['version_fixed'] != $issueListOptions['filter']['version_fixed'])
			$issueListOptions['base_url']['version_fixed'] = $issueListOptions['filter']['version_fixed'];
	
		if ($defaultFilter['status'] != $issueListOptions['filter']['status'])
			$issueListOptions['base_url']['status'] = $issueListOptions['filter']['status'];
	
		if ($defaultFilter['tag'] != $issueListOptions['filter']['tag'])
			$issueListOptions['base_url']['tag'] = $issueListOptions['filter']['tag'];
			
		$context['issue_list_id'] = createIssueList($issueListOptions);
		
		$context['canonical_url'] = $context[$context['issue_list_id']]['canonical_url'];
	
		$context['show_checkboxes'] = ProjectTools::allowedTo('issue_moderate');
		$context['can_report_issues'] = ProjectTools::allowedTo('issue_report');
		
		// Template
		$context['sub_template'] = 'issue_list';
		$context['page_title'] = sprintf($txt['project_title_issues'], ProjectTools_Project::getCurrent()->name);
	
		loadTemplate('IssueList');
	}
}

?>