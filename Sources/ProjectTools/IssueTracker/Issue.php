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
		
		if (isset($issue) && isset($project))
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
	public $name;
	
	/**
	 *
	 */
	public $href;
	
	/**
	 *
	*/
	public $details;
	
	/**
	 *
	 */
	public $category;

	/**
	 *
	 */
	public $versions;
	
	/**
	 *
	 */
	public $versions_fixed;
	
	/**
	 *
	 */
	public $reporter;

	/**
	 *
	 */
	public $assignee;
	
	/**
	 *
	 */
	public $is_mine;
	
	/**
	 *
	 */
	public $tracker;
	
	/**
	 *
	 */
	public $status;
	
	/**
	 *
	 */
	public $priority_num;
	
	/**
	 *
	 */
	public $priority;
	
	/**
	 *
	 */
	public $created;
	
	/**
	 *
	 */
	public $updated;
	
	/**
	 *
	 */
	public $new_from;
	
	/**
	 *
	 */
	public $comment_first;
	
	/**
	 *
	 */
	public $comment_last;
	
	/**
	 *
	 */
	public $id_event_mod;
	
	/**
	 *
	 */
	public $replies;
	
	/**
	 *
	 */
	public $is_private;
			
	/**
	 *
	 */
	public function __construct($issue)
	{
		global $smcFunc, $user_info, $memberContext, $context;
		
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
			LIMIT 1',
			array(
				'current_member' => $user_info['id'],
				'issue' => $issue,
			)
		);
		
		if ($smcFunc['db_num_rows']($request) == 0)
		{
			$this->id = false;
	
			return;
		}
		
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
	
		// Load reporter and assignee
		if (!empty($row['id_member']))
		{
			loadMemberData(array($row['id_reporter'], $row['id_member']));
			loadMemberContext($row['id_member']);
		}
		else
			loadMemberData(array($row['id_reporter']));
		loadMemberContext($row['id_reporter']);
	
		$memberContext[$row['id_reporter']]['can_view_profile'] = allowedTo('profile_view_any') || ($row['id_member'] == $user_info['id'] && allowedTo('profile_view_own'));
	
		$type = !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'] ? 'own' : 'any';
	
		$this->id = $row['id_issue'];
		$this->name = $row['subject'];
		$this->href = project_get_url(array('issue' => $row['id_issue'] . '.0'));
		
		$this->project = $row['id_project'];
		
		$this->details = array(
			'id' => $row['id_comment_first'],
			'id_event' => $row['id_event'],
			'time' => timeformat($row['post_time']),
			'body' => parse_bbc($row['body']),
			'ip' => $row['poster_ip'],
			'modified' => array(
				'time' => timeformat($row['edit_time']),
				'timestamp' => forum_time(true, $row['edit_time']),
				'name' => $row['edit_name'],
			),
			'can_see_ip' => allowedTo('moderate_forum') || ($row['id_member'] == $user_info['id'] && !empty($user_info['id'])),
			'can_remove' => projectAllowedTo('delete_comment_' . $type),
			'can_edit' => projectAllowedTo('edit_comment_' . $type),
			'first_new' => $row['id_event_mod'] > $row['new_from'],
		);
		
		$this->category = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'link' => '<a href="' . project_get_url(array('project' => $row['id_project'], 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>',
		);
		
		$this->versions = getVersions(explode(',', $row['versions']), $row['id_project']);
		$this->versions_fixed = getVersions(explode(',', $row['versions_fixed']), $row['id_project']);
	
		$this->reporter = &$memberContext[$row['id_reporter']];
		
		if (!empty($row['id_member']))
			$this->assignee = &$memberContext[$row['id_member']];
		
		$this->tracker = &$context['issue_trackers'][$row['id_tracker']];
		
		$this->status = &$context['issue_status'][$row['status']];
		
		$this->priority_num = $row['priority'];
		$this->priority = $context['issue']['priority'][$row['priority']];
		
		$this->created = timeformat($row['created']);
		$this->updated = timeformat($row['updated']);
		
		$this->new_from = $row['new_from'];
		
		$this->comment_first = $row['id_comment_first'];
		$this->comment_last = $row['id_comment_last'];
		
		$this->id_event_mod = $row['id_event_mod'];
		
		$this->replies = $row['replies'];
		
		$this->is_private = !empty($row['private_issue']);
		$this->is_mine = !$user_info['is_guest'] && $row['id_reporter'] == $user_info['id'];
	}
	
	/**
	 * 
	 */
	public function canSee()
	{
		global $user_info;
		
		if (allowedTo('project_admin'))
			return true;
			
		// Check that user can see at least one of versions
		if (!empty($this->versions) && count(array_intersect(array_keys($this->versions), ProjectTools_Project::getProject($this->project)->versions_id)) == 0)
			return false;
		
		// Private
		if ($this->is_private && !$this->is_mine && !ProjectTools_Project::getProject($this->project)->allowedTo('issue_view_private'))
			return false;
		
		return true;
	}
}

?>