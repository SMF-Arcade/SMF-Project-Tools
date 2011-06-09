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
	public static function getIssue($id, $project = null)
	{
		if (!isset(self::$_instances[$id]))
			self::$_instances[$id] = new self($id);
		
		if ($project !== null && self::$_instances[$id])
		{
			if (self::$_instances[$id]->project->id !== $project)
				return false;
		}
		
		return self::$_instances[$id];
	}
	
	/**
	 * Returns current project
	 * 
	 * @return ProjectTools_IssueTracker_Issue 
	 */
	public static function getCurrent()
	{
		global $issue, $project;
		
		if (isset($issue) && isset($project))
			return self::getIssue($issue, $project);
		
		return false;
	}
	
	/**
	 *
	 */
	public static function getNew(ProjectTools_Project $project)
	{
		global $context;
		
		$new = new ProjectTools_IssueTracker_Issue();
		$new->project = $project;
		return $new;
	}
	
	/**
	 *
	 */
	public static function getDefaults()
	{
		global $context;
		
		return array('status' => 1, 'priority' => 2);
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
	 * @var ProjectTools_Project
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
	public $versions = array();
	
	/**
	 *
	 */
	public $versions_fixed = array();
	
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
	protected $customData = array();
	
	/**
	 *
	 */
	protected function __construct($issue = null)
	{
		global $context;
		
		if (!isset($context['issue_status']))
			trigger_error(__CLASS__ . '::' . __METHOD__ . ': Issue Tracker not loaded', E_USER_ERROR);
			
		$this->id = $issue;
		
		if ($this->id !== null)
			$this->loadIssue();
	}
	
	/**
	 *
	 */
	protected function loadIssue()
	{
		global $smcFunc, $user_info, $memberContext, $context;
		
		$request = $smcFunc['db_query']('', '
			SELECT
				i.id_project, i.id_issue, i.subject, i.priority, i.status, i.created, i.updated, i.id_tracker,
				i.id_issue_event_first, i.id_issue_event_last, i.id_event_mod, i.id_reporter, i.replies, i.private_issue,
				i.versions, i.versions_fixed,
				mem.id_member, mem.real_name, cat.id_category, cat.category_name,
				' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . ',
				iv.id_event, iv.poster_ip, com.id_comment, com.body, com.edit_name, com.edit_time
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
				'issue' => $this->id,
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
		
		$this->project = ProjectTools_Project::getProject($row['id_project']);
		
		$this->details = array(
			'id' => $row['id_issue_event_first'],
			'id_event' => $row['id_event'],
			'id_comment' => $row['id_comment'],
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
		
		// Load Custom Fields
		$request = $smcFunc['db_query']('', '
			SELECT variable, value
			FROM {db_prefix}issue_custom_data
			WHERE id_issue = {int:project}',
			array(
				'project' => $this->id,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$this->customData[$row['variable']] = $row['value'];
		$smcFunc['db_free_result']($request);
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
		if (!empty($this->versions) && count(array_intersect(array_keys($this->versions), $this->project->versions_id)) == 0)
			return false;
		
		// Private
		if ($this->is_private && !$this->is_mine && !$this->project->allowedTo('issue_view_private'))
			return false;
		
		return true;
	}
	
	/**
	 * Returns data for use in report form
	 */
	public function getData()
	{
		return array(
			'title' => $this->name,
			'private' => $this->is_private,
			'tracker' => $this->tracker['id'],
			'versions' => array_keys($this->versions),
			'details' => $this->details['body'],
		);
	}
	
	/**
	 * Saves changes to issue
	 *
	 * 
	 */
	public function Save($issueOptions, $posterOptions)
	{
		if ($this->id === null)
			$this->create($issueOptions, $posterOptions);
		else
			$this->update($issueOptions, $posterOptions);
		
		return $this->id;
	}
	
	/**
	 * Inserts new issue to database
	 * 
	 * @param array $issueOptions
	 * @param array &$posterOptions
	 * @return int ID of issue created
	 */
	private function create($issueOptions, &$posterOptions)
	{
		global $smcFunc;
	
		if (empty($issueOptions['created']))
			$issueOptions['created'] = time();
	
		$smcFunc['db_insert']('insert',
			'{db_prefix}issues',
			array(
				'id_project' => 'int',
				'subject' => 'string-100',
				'created' => 'int',
				'id_reporter' => 'int',
			),
			array(
				$this->project->id,
				$issueOptions['title'],
				$issueOptions['created'],
				$posterOptions['id'],
			),
			array()
		);
	
		$this->id = $smcFunc['db_insert_id']('{db_prefix}issues', 'id_issue');
	
		$id_event = createTimelineEvent($this->id, $this->project->id, 'new_issue', array('subject' => $issueOptions['title']), $posterOptions,
			array(
				'time' => $issueOptions['created'],
				'mark_read' => !empty($issueOptions['mark_read']),
			)
		);
	
		list ($id_comment, $issueOptions['event_first']) = ProjectTools_IssueTracker::createComment(
			$this->project->id,
			$this->id,
			array(
				'id_event' => $id_event,
				'no_log' => true,
				'comment' => $issueOptions['details'],
				'mark_read' => !empty($issueOptions['mark_read']),
			),
			$posterOptions,
			array()
		);
	
		unset($issueOptions['project'], $issueOptions['subject'], $issueOptions['details'], $issueOptions['created']);
		$issueOptions['no_log'] = true;
	
		if (!empty($issueOptions))
			$this->update($issueOptions + self::getDefaults(), $posterOptions);
	
		return true;
	}

	/**
	 * Returns value for field to display in issue details
	 *
	 * @param string $field Field to get value
	 * @param bool $raw Return raw value for editing
	 */
	public function getFieldValue($field, $raw = false)
	{
		global $txt;
		
		if (substr($field, 0, 7) == 'custom_')
			return $this->getCustomFieldValue(substr($field, 8), $raw);
		
		if ($field == 'reported')
		{
			if ($raw)
				trigger_error('Invalid value!', E_USER_ERROR);
			else
				return $this->created;
		}
		elseif ($field == 'updated')
		{
			if ($raw)
				trigger_error('Invalid value!', E_USER_ERROR);
			else
				return $this->updated;
		}
		elseif ($field == 'private')
		{
			if ($raw)
				return $this->is_private ? 1 : 0;
			else
				return $this->is_private ?  $txt['issue_view_status_private'] : $txt['issue_view_status_public'];
		}
		elseif ($field == 'tracker')
		{
			if ($raw)
				return $this->tracker['id'];
			else
				return $this->tracker['name'];
		}
		elseif ($field == 'status')
		{
			if ($raw)
				return $this->status['id'];
			else
				return $this->status['text'];
		}
		elseif ($field == 'priority')
		{
			if ($raw)
				return $this->priority_num;
			else
				return $txt[$this->priority];
		}
		elseif ($field == 'versions')
		{
			if ($raw)
				return array_keys($this->versions);
			elseif (empty($this->versions))
				return $txt['issue_none'];
			else
			{
				$return = '';
				$first = true;
				
				foreach ($this->versions as $version)
				{
					if ($first)
						$first = false;
					else
						$return .= ', ';
						
					$return .= $version['name'];
				}
				
				return $return;
			}
		}
		elseif ($field == 'versions_fixed')
		{
			if ($raw)
				return array_keys($this->versions_fixed);
			elseif (empty($this->versions_fixed))
				return $txt['issue_none'];
			else
			{
				$return = '';
				$first = true;
				
				foreach ($this->versions_fixed as $version)
				{
					if ($first)
						$first = false;
					else
						$return .= ', ';
						
					$return .= $version['name'];
				}
				
				return $return;
			}
		}
		elseif ($field == 'assignee')
		{
			if ($raw)
				return $this->assignee['id'];
			else
				return !empty($this->assignee['id']) ? $this->assignee['link'] : $txt['issue_none'];
		}
		elseif ($field == 'category')
		{
			if ($raw)
				return $this->category['id'];
			else
				return !empty($this->category['id']) ? $this->category['link'] : $txt['issue_none'];
		}
		
		trigger_error('Unknown field: ' . $field, E_USER_ERROR);
	}
	
	/**
	 * Returns value for custom field
	 */
	public function getCustomFieldValue($field, $raw = false)
	{
		if (isset($this->customData[$field]))
			return $this->customData[$field];
		else
		{
			
		}
		
		trigger_error('Unknown custom field: ' . $field, E_USER_ERROR);
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
		
		$event_data = array(
			'changes' => array(),
		);
	
		$issueUpdates = array();

		if (!empty($this->status['id']))
			$oldStatus = $context['issue_status'][$this->status['id']]['type'];
		else
			$oldStatus = '';
			
		// Which tracker it belonged to and will belong in future?
		if (!empty($this->tracker))
			$oldTracker = $context['issue_trackers'][$this->tracker['id']]['column_' . $oldStatus];
			
		// Make sure project exists always
		if (!isset($issueOptions['project']))
			$issueOptions['project'] = $this->project->id;
	
		if (isset($issueOptions['project']) && $issueOptions['project'] != $this->project->id)
		{
			$issueUpdates[] = 'id_project = {int:project}';
			$issueOptions['project'] = $issueOptions['project'];
	
			$event_data['changes'][] = array(
				'project', $this->project->id, $issueOptions['project']
			);
			
			//
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_timeline
				SET id_project = {int:project}
				WHERE id_issue = {int:issue}',
				array(
					'project' => $issueOptions['project'],
					'issue' => $this->id,
				)
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
	
		if (!empty($issueOptions['title']) && $issueOptions['title'] != $this->name)
		{
			$issueUpdates[] = 'subject = {string:title}';
	
			$event_data['changes'][] = array(
				'rename', $this->name, $issueOptions['title']
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
	
		if (isset($issueOptions['details']))
		{
			$commentOptions = array('comment' => $issueOptions['details']);
			unset($issueOptions['details']);
			
			ProjectTools_IssueTracker::modifyComment($this->details['id_comment'], $this->id, $commentOptions, $posterOptions);
			$event_data['changes'][] = array(
				'details', 'old', 'new',
			);
		}
	
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
	
		//
		$newTracker = $context['issue_trackers'][$issueOptions['tracker']]['column_' . $newStatus];
			
		if (!empty($issueOptions['tracker']) && ($issueOptions['tracker'] != $this->tracker['id'] || $oldStatus != $newStatus))
		{
			if (!empty($oldStatus) && !empty($this->tracker))
				$projectUpdates[$this->project->id][] = "$oldTracker = $oldTracker - 1";
	
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
			
		// Refresh data (for ajax!)
		$this->loadIssue();
	
		if ($return_log)
			return $event_data;
	
		if (empty($issueOptions['no_log']))
		{
			$id_issue_event = ProjectTools_IssueTracker::createIssueEvent($this->id, 0, $posterOptions, $event_data);
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
			
			return $id_issue_event;
		}
		
		return true;
	}
	
	/**
	 * Delete issue from database
	 * @param array $posterOptions posterOptions for user deleting issue
	 * @param boolean $log_delete Whatever to log delete
	 * @return mixed ID of event or true if not logged in success. False on error
	 */
	function delete($posterOptions, $log_delete = true)
	{
		global $smcFunc, $db_prefix, $context;
	
		$event_data = array(
			'subject' => $this->name,
			'changes' => array(),
		);
	
		if (!empty($this->status['id']))
			$status = $context['issue_status'][$this->status['id']]['type'];
		else
			$status = '';
	
		$curTracker = $context['issue_trackers'][$this->tracker['id']]['column_' . $status];
	
		$projectUpdates = array(
			"$curTracker = $curTracker - 1"
		);
	
		if (!empty($projectUpdates) && !empty($status))
			$smcFunc['db_query']('', "
				UPDATE {$db_prefix}projects
				SET
					" . implode(',
					', $projectUpdates) . "
				WHERE id_project = {int:project}",
				array(
					'project' => $this->project->id,
				)
			);
			
		// Remove issue from versions too
		$versions = array_merge(array_keys($this->versions), array_keys($this->versions_fixed));
			
		if (!empty($versions))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET {raw:tracker} = {raw:tracker} - 1
				WHERE id_version IN({array_int:versions})',
				array(
					'tracker' => $curTracker,
					'versions' => $versions,
				)
			);
	
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issues
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $this->id,
			)
		);
		
		$comments = array();
		
		// Remove comments
		$request = $smcFunc['db_query']('', '
			SELECT id_comment
			FROM {db_prefix}issue_events
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $this->id,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$comments[] = $row['id_comment'];
		
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_comments
			WHERE id_comment IN({array_int:comments})',
			array(
				'comments' => $comments,
			)
		);
		// Remove changes related to this issue
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_events
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $this->id,
			)
		);
		// Cleanup timeline
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_timeline
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $this->id,
			)
		);
	
		// Remove notifications of this Issue
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}',
			array(
				'issue' => $this->id,
			)
		);
	
		if ($log_delete && $posterOptions !== false)
			$id_event = createTimelineEvent($this->id, $this->project->id, 'delete_issue', $event_data, $posterOptions, array('time' => time()));
		else
			return true;
		
		return $id_event;
	}
}

?>