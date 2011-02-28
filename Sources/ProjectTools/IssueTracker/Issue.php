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
class ProjectTools_IssueTracker_Issue
{
	/**
	 * Contains project instances
	 */
	private static $_instances = array();
	
	/**
	 *
	 * @return ProjectTools_IssueTracker_Issue Issue Instance
	 */
	static function getIssue($id, $project = null)
	{
		if (!isset(self::$_instances[$id]))
			self::$_instances[$id] = new self($id);
		
		if ($project !== null && self::$_instances[$id])
		{
			if (self::$_instances[$id]->project !== $project)
				return false;
		}
		
		return self::$_instances[$id];
	}
	
	/**
	 * Returns current project
	 * 
	 * @return ProjectTools_IssueTracker_Issue 
	 */
	static function getCurrent()
	{
		global $issue, $project;
			
		if (isset($issue))
			return self::getIssue($issue, $project);
		
		return false;
	}
		
	/**
	 * Issue ID
	 * 
	 * @var boolean|int ID Of Issue. False if not found
	 */
	public $id;
	
	/**
	 * Project ID
	 * 
	 * @var boolean|int ID Of Project. False if not found
	 */
	public $project;
	
	/**
	 *
	 */
	public function __construct()
	{
		global $smcFunc;
		
		$request = $smcFunc['db_query']('', '
			SELECT
				i.id_project, i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.id_tracker,
				i.id_comment_first, i.id_comment_last, i.id_event_mod, i.id_reporter, i.replies, i.private_issue,
				i.versions, i.versions_fixed,
				mem.id_member, mem.real_name, cat.id_category, cat.category_name,
				' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . ',
				com.id_event, com.id_event_mod AS id_event_mod_com, com.post_time, com.edit_time, com.body, com.edit_name, com.edit_time,
				com.poster_ip
			FROM {db_prefix}issues AS i
				INNER JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)' . ($user_info['is_guest'] ? '' : '
				LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
				LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = i.id_project AND lmr.id_member = {int:current_member})') . '
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = i.id_assigned)
				LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
			WHERE i.id_issue = {int:issue}
				AND i.id_project = {int:project}
			LIMIT 1',
			array(
				'current_member' => $user_info['id'],
				'issue' => $issue,
				'project' => $project,
				'any' => '*',
			)
		);		
	}
}

?>