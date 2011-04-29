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
	public $event_first;
	
	/**
	 *
	 */
	public $event_last;
	
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
				i.id_issue_event_first, i.id_issue_event_last, i.id_event_mod, i.id_reporter, i.replies, i.private_issue,
				i.versions, i.versions_fixed,
				mem.id_member, mem.real_name, cat.id_category, cat.category_name,
				' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . ',
				iv.id_event, iv.poster_ip, com.body, com.edit_name, com.edit_time
			FROM {db_prefix}issues AS i
				INNER JOIN {db_prefix}issue_events AS iv ON (iv.id_issue_event = id_issue_event_first)
				INNER JOIN {db_prefix}issue_comments AS com ON (com.id_comment = iv.id_comment)' . ($user_info['is_guest'] ? '' : '
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
		$this->href = ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'));
		
		$this->project = $row['id_project'];
		
		$this->details = array(
			'id' => $row['id_issue_event_first'],
			'id_event' => $row['id_event'],
			'time' => timeformat($row['created']),
			'body' => parse_bbc($row['body']),
			'ip' => $row['poster_ip'],
			'modified' => array(
				'time' => timeformat($row['edit_time']),
				'timestamp' => forum_time(true, $row['edit_time']),
				'name' => $row['edit_name'],
			),
			'can_see_ip' => allowedTo('moderate_forum') || ($row['id_member'] == $user_info['id'] && !empty($user_info['id'])),
			'can_remove' => ProjectTools::allowedTo('delete_comment_' . $type),
			'can_edit' => ProjectTools::allowedTo('edit_comment_' . $type),
			'first_new' => $row['id_event_mod'] > $row['new_from'],
		);
		
		$this->category = array(
			'id' => $row['id_category'],
			'name' => $row['category_name'],
			'link' => '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'], 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>',
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
		
		$this->event_first = $row['id_issue_event_first'];
		$this->event_last = $row['id_issue_event_last'];
		
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
	
	/**
	 * Updates issue in database
	 * @param array $issueOptions
	 * @param array &$posterOptions
	 * @param boolean $return_log Return event data instead of inserting into database
	 */
	public function update($issueOptions, $posterOptions, $return_log = false)
	{
		global $smcFunc, $context;
	
		//if (!isset($context['issue_status']))
		//	trigger_error('updateIssue: issue tracker not loaded', E_USER_ERROR);
	
		$event_data = array(
			'changes' => array(),
		);
	
		$issueUpdates = array();
	
		// Make sure project exists always
		if (!isset($issueOptions['project']))
			$issueOptions['project'] = $this->project;
	
		if (isset($issueOptions['project']) && $issueOptions['project'] != $this->project)
		{
			$issueUpdates[] = 'id_project = {int:project}';
			$issueOptions['project'] = $issueOptions['project'];
	
			$event_data['changes'][] = array(
				'project', $this->project, $issueOptions['project']
			);
		}
	
		if (isset($issueOptions['private']) && $issueOptions['private'] != $this->is_private)
		{
			$issueUpdates[] = 'private_issue = {int:private}';
			$issueOptions['private'] = !empty($issueOptions['private']) ? 1 : 0;
	
			$event_data['changes'][] = array(
				'view_status', $this->is_private ? 1 : 0, $issueOptions['private']
			);
		}
	
		if (!empty($issueOptions['subject']) && $issueOptions['subject'] != $this->name)
		{
			$issueUpdates[] = 'subject = {string:subject}';
	
			$event_data['changes'][] = array(
				'rename', $this->name, $issueOptions['subject']
			);
		}
	
		if (!empty($issueOptions['status']) && $issueOptions['status'] != $this->status['id'])
		{
			$issueUpdates[] = 'status = {int:status}';
	
			$event_data['changes'][] = array(
				'status', $this->status['id'], $issueOptions['status'],
			);
		}
	
		if (isset($issueOptions['assignee']) && $issueOptions['assignee'] != $this->assignee['id'])
		{
			$issueUpdates[] = 'id_assigned = {int:assignee}';
	
			$event_data['changes'][] = array(
				'assign', $this->assignee['id'], $issueOptions['assignee'],
			);
		}
	
		if (!empty($issueOptions['priority']) && $issueOptions['priority'] != $this->priority_num)
		{
			$issueUpdates[] = 'priority = {int:priority}';
	
			$event_data['changes'][] = array(
				'priority', $this->priority_num, $issueOptions['priority'],
			);
		}
		
		$oldVersions = array_merge(array_keys($this->versions), array_keys($this->versions_fixed));
		$newVersions = array();
	
		if (isset($issueOptions['versions']) && $issueOptions['versions'] != array_keys($this->versions))
		{
			$issueUpdates[] = 'versions = {string:versions}';
			
			if (empty($issueOptions['versions']))
				$issueOptions['versions'] = array(0);
		
			$newVersions = array_merge($newVersions, $issueOptions['versions']);
			$issueOptions['versions'] = implode(',', $issueOptions['versions']);
	
			$event_data['changes'][] = array(
				'version', implode(',', array_keys($this->versions)), $issueOptions['versions'],
			);
		}
		else
			$newVersions = array_merge($newVersions, array_keys($this->versions));
	
		if (isset($issueOptions['versions_fixed']) && $issueOptions['versions_fixed'] != array_keys($this->versions_fixed))
		{
			$issueUpdates[] = 'versions_fixed = {string:versions_fixed}';
			
			if (empty($issueOptions['versions_fixed']))
				$issueOptions['versions_fixed'] = array(0);
		
			$newVersions = array_merge($newVersions, $issueOptions['versions_fixed']);
			$issueOptions['versions_fixed'] = implode(',', $issueOptions['versions_fixed']);
	
			$event_data['changes'][] = array(
				'target_version', implode(',', array_keys($this->versions_fixed)), $issueOptions['versions_fixed'],
			);
		}
		else
			$newVersions = array_merge($newVersions, array_keys($this->versions_fixed));
	
		if (isset($issueOptions['event_first']))
			$issueUpdates[] = 'id_issue_event_first = {int:event_first}';
	
		if (isset($issueOptions['category']) && $issueOptions['category'] != $this->category['id'])
		{
			$issueUpdates[] = 'id_category = {int:category}';
	
			$event_data['changes'][] = array(
				'category', $this->category['id'], $issueOptions['category'],
			);
		}
	
		if (!empty($issueOptions['tracker']) && $issueOptions['tracker'] != $this->tracker['id'])
		{
			$issueUpdates[] = 'id_tracker = {int:tracker}';
	
			$event_data['changes'][] = array(
				'tracker', $this->tracker['id'], $issueOptions['tracker'],
			);
		}
	
		if (!empty($this->status['id']))
			$oldStatus = $context['issue_status'][$this->status['id']]['type'];
		else
			$oldStatus = '';
	
		if (!empty($issueOptions['status']))
			$newStatus = $context['issue_status'][$issueOptions['status']]['type'];
		else
			$newStatus = $oldStatus;
	
		if (!isset($issueOptions['tracker']))
			$issueOptions['tracker'] = $this->tracker['id'];
	
		// Updates needed?
		if (empty($issueUpdates))
			return !$return_log ? true : $event_data;
	
		$issueUpdates[] = 'updated = {int:time}';
		$issueOptions['time'] = time();
		$issueUpdates[] = 'id_updater = {int:updater}';
		$issueOptions['updater'] = $posterOptions['id'];
	
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}issues
			SET
				' . implode(',
				', $issueUpdates) . '
			WHERE id_issue = {int:issue}',
			array_merge($issueOptions ,array(
				'issue' => $this->id,
			))
		);
	
		// Update Issue Counts from project
		$projectUpdates = array();
	
		// Which tracker it belonged to and will belong in future?
		if (!empty($this->tracker['id']))
			$oldTracker = $context['issue_trackers'][$this->tracker['id']]['column_' . $oldStatus];
		$newTracker = $context['issue_trackers'][$issueOptions['tracker']]['column_' . $newStatus];
			
		if (!empty($issueOptions['tracker']) && ($issueOptions['tracker'] != $this->tracker['id'] || $oldStatus != $newStatus))
		{
			if (!empty($oldStatus))
				$projectUpdates[$this->project][] = "$oldTracker = $oldTracker - 1";
	
			$projectUpdates[$issueOptions['project']][] = "$newTracker = $newTracker + 1";
		}
	
		if (!empty($projectUpdates))
			foreach ($projectUpdates as $id => $updates)
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}projects
					SET
						' . implode(',
						', $updates) . '
					WHERE id_project = {int:project}',
					array(
						'project' => $id,
					)
				);
				
		// If tracker hasn't changed remove values that doesn't need to be changed
		if (isset($oldTracker) && $oldTracker == $newTracker)
		{
			$oldVersions = array_diff($oldVersions, $newVersions);
			$newVersions = array_diff($newVersions, $oldVersions);
		}
				
		// Update issue counts in versions
		if (isset($oldTracker) && !empty($oldVersions))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET {raw:tracker} = {raw:tracker} - 1
				WHERE id_version IN({array_int:versions})',
				array(
					'tracker' => $oldTracker,
					'versions' => $oldVersions,
				)
			);
		if (!empty($newVersions))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET {raw:tracker} = {raw:tracker} + 1
				WHERE id_version IN({array_int:versions})',
				array(
					'tracker' => $newTracker,
					'versions' => $newVersions,
				)
			);
			
		// Update id_project in timeline if needed
		if ($this->project != $issueOptions['project'])
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_timeline
				SET id_project = {int:project}
				WHERE id_issue = {int:issue}',
				array(
					'project' => $issueOptions['project'],
					'issue' => $this->id,
				)
			);
	
		if ($return_log)
			return $event_data;
		
		$id_issue_event = createIssueEvent($this->id, 0, $posterOptions, $event_data);
	
		if (empty($issueOptions['no_log']))
		{
			$id_event = createTimelineEvent($this->id, $issueOptions['project'], 'update_issue', array('subject' => isset($issueOptions['subject']) ? $issueOptions['subject'] : $this->name), $posterOptions, $issueOptions);
			
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issue_events
				SET id_event = {int:event}
				WHERE id_issue_event = {int:issue_event}',
				array(
					'issue_event' => $id_issue_event,
					'event' => $id_event
				)
			);
			
			return $id_event;
		}
		
		return true;
	}
}

?>