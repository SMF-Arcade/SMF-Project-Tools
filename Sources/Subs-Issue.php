<?php
/**
 * Functions for issue tracker
 *
 * @package issuetracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

/**
 * Load issue based on global variable $issue
 */
function loadIssue()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $issue, $project, $memberContext;

	if (empty($project) || empty($issue))
		return;

	if (!ProjectTools_IssueTracker_Issue::getCurrent() || !ProjectTools_IssueTracker_Issue::getCurrent()->canSee())
	{
		$context['project_error'] = 'issue_not_found';
		return;
	}

	if (!$user_info['is_guest'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT sent
			FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}
			LIMIT 1',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
		$context['is_subscribed'] = $smcFunc['db_num_rows']($request) != 0;
		if ($context['is_subscribed'])
		{
			list ($sent) = $smcFunc['db_fetch_row']($request);
			if (!empty($sent))
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}log_notify_projects
					SET sent = {int:is_sent}
					WHERE id_issue = {int:issue}
						AND id_member = {int:current_member}',
					array(
						'issue' => $issue,
						'current_member' => $user_info['id'],
						'is_sent' => 0,
					)
				);
			}
		}
		$smcFunc['db_free_result']($request);
	}
}

/**
 * Creates event in timeline
 * @param int $id_issue ID of issue
 * @param int $id_project ID of project issue is in
 * @param string $event_name Name of event
 * @param array $event_data 
 * @param array $posterOptions
 * @param array $issueOptions
 */
function createTimelineEvent($id_issue, $id_project, $event_name, $event_data, $posterOptions, $issueOptions)
{
	global $smcFunc, $context, $user_info;

	$id_event = 0;

	if ($posterOptions['id'] != 0 && ($event_name == 'update_issue' || $event_name == 'new_comment'))
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_event, event, event_data
			FROM {db_prefix}project_timeline
			WHERE id_project = {int:project}
				AND id_issue = {int:issue}
				AND event IN({array_string:event})
				AND id_member = {int:member}
				AND event_time > {int:event_time}
			ORDER BY id_event DESC
			LIMIT 1',
			array(
				'issue' => $id_issue,
				'project' => $id_project,
				'member' => $posterOptions['id'],
				// Update issue can be merged with new_comment and update_issue
				// new comment can be merged with update_issue
				'event' => $event_name == 'update_issue' ? array('new_comment', 'update_issue') : array('update_issue'),
				'event_time' => time() - 120,
			)
		);

		if ($smcFunc['db_num_rows']($request) > 0)
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}project_timeline
				WHERE id_event = {int:event}',
				array(
					'event' => $id_event,
				)
			);

			// 'new_comment' > 'update_issue'
			if ($event_name2 == 'new_comment' || $event_name == 'new_comment')
				$event_name = 'new_comment';
		}
		$smcFunc['db_free_result']($request);
	}

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_timeline',
		array(
			'id_project' => 'int',
			'id_issue' => 'int',
			'id_member' => 'int',
			'poster_name' => 'string',
			'poster_email' => 'string',
			'poster_ip' => 'string-60',
			'event' => 'string',
			'event_time' => 'int',
			'event_data' => 'string',
		),
		array(
			$id_project,
			$id_issue,
			$posterOptions['id'],
			$posterOptions['username'],
			$posterOptions['email'],
			$posterOptions['ip'],
			$event_name,
			$issueOptions['time'],
			serialize($event_data)
		),
		array()
	);

	$id_event_new = $smcFunc['db_insert_id']('{db_prefix}project_timeline', 'id_event');

	// Update latest event id
	updateSettings(array('project_maxEventID' => $id_event_new));

	// Update Issues table too
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET id_event_mod = {int:event}, updated = {int:time}
		WHERE id_issue = {int:issue}',
		array(
			'event' => $id_event_new,
			'issue' => $id_issue,
			'time' => $issueOptions['time'],
		)
	);

	// And projects
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}projects
		SET id_event_mod = {int:event}
		WHERE id_project = {int:project}',
		array(
			'event' => $id_event_new,
			'project' => $id_project,
		)
	);

	// Mark read if asked to
	if (!empty($issueOptions['mark_read']) && !$user_info['is_guest'])
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}log_issues',
			array(
				'id_project' => 'int',
				'id_issue' => 'int',
				'id_member' => 'int',
				'id_event' => 'int',
			),
			array(
				$id_project,
				$id_issue,
				$user_info['id'],
				$id_event_new,
			),
			array('id_issue', 'id_member')
		);
		
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues AS i
				LEFT JOIN {db_prefix}log_projects AS log ON (log.id_member = {int:current_member}
					AND log.id_project = {int:current_project})
			WHERE i.id_project = {int:current_project}
				AND i.id_event_mod > log.id_event',
			array(
				'current_project' => $id_project,
				'current_member' => $user_info['id'],
			)
		);
			
		list ($num_unread_issues) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
		
		// Mark project read if there's only one unread issue
		if ($num_unread_issues <= 1)
			$smcFunc['db_insert']('replace',
				'{db_prefix}log_projects',
				array(
					'id_project' => 'int',
					'id_member' => 'int',
					'id_event' => 'int',
				),
				array(
					$id_project,
					$user_info['id'],
					$id_event_new,
				),
				array('id_project', 'id_member')
			);
	}

	if ($event_name == 'update_issue')
		sendIssueNotification(array('id' => $id_issue, 'project' => ProjectTools_Project::getCurrent()->id,), array(), $event_data, $event_name, $posterOptions['id']);

	if (empty($id_event))
		return $id_event_new;

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issue_comments
		SET id_event = {int:new_event}
		WHERE id_event = {int:event}',
		array(
			'new_event' => $id_event_new,
			'event' => $id_event,
		)
	);

	return $id_event_new;
}

/**
 * Creates issue list for current project. Will be replaced in 0.6
 * @param int $start Offset to start from
 * @param int $num_issues Number ofissues per page
 * @param string $order SQL to order by
 * @param string $where SQL for WHERE condition
 * @param array $queryArray
 * @return array issue list
 * @deprecated since 0.5
 * @see createIssueList
 */
function getIssueList($start = 0, $num_issues, $order = 'i.updated DESC', $where = '1 = 1', $queryArray = array())
{
	global $context, $project, $user_info, $smcFunc, $scripturl, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.id_tracker, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			i.id_reporter, IFNULL(mr.real_name, {string:empty}) AS reporter,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater, i.versions, i.versions_fixed,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}members AS mr ON (mr.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)' . (empty($project) ? '
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})' : '') . '
		WHERE ' . (!empty($project) ? '{query_project_see_issue}
			AND i.id_project = {int:project}' : '{query_see_project}
			AND {query_see_issue}') . '
			AND ('. $where . ')
		ORDER BY ' . $order . '
		LIMIT {int:start}, {int:num_issues}',
		array_merge(array(
			'project' => $project,
			'empty' => '',
			'start' => 0,
			'num_issues' => $num_issues,
			'current_member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
		), $queryArray)
	);

	$return = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$return[] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>',
			'href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'], 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions']), $row['id_project']),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed']), $row['id_project']),
			'tracker' => &$context['issue_trackers'][$row['id_tracker']],
			'updated' => timeformat($row['updated']),
			'created' => timeformat($row['created']),
			'status' => &$context['issue_status'][$row['status']],
			'reporter' => array(
				'id' => $row['id_reporter'],
				'name' => empty($row['reporter']) ? $txt['issue_guest'] : $row['reporter'],
				'link' => empty($row['reporter']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter'] . '</a>',
			),
			'is_assigned' => !empty($row['id_assigned']),
			'assigned' => array(
				'id' => $row['id_assigned'],
				'name' => $row['assigned_name'],
				'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_assigned'] . '">' . $row['assigned_name'] . '</a>',
			),
			'updater' => array(
				'id' => $row['id_updater'],
				'name' => empty($row['updater']) ? $txt['issue_guest'] : $row['updater'],
				'link' => empty($row['updater']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_updater'] . '">' . $row['updater'] . '</a>',
			),
			'replies' => comma_format($row['replies']),
			'priority' => $row['priority'],
			'new' => $row['new_from'] <= $row['id_event_mod'],
			'new_href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from']), $row['id_project']) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);

	return $return;
}

/**
 * Creates filter for createIssueList
 * @param string $mode Default to get default filter. Reguest to get filter based on reguest parametrs
 * @param array $options
 * @return array Filter array
 */
function getIssuesFilter($mode = 'default', $options = array())
{
	global $context, $smcFunc;
	
	$filter = array(
		'title' => '',
		'status' => 'all',
		'tag' => '',
		'tracker' => '',
		'version' => null,
		'version_fixed' => null,
		'category' => null,
		'reporter' => null,
		'assignee' => null,
	);
	
	if ($mode == 'request')
	{
		if (!empty($_REQUEST['title']))
			$filter['title'] = $smcFunc['htmlspecialchars']($_REQUEST['title']);
	
		if (!empty($_REQUEST['tracker']) && isset($context['possible_types'][$_REQUEST['tracker']]))
			$filter['tracker'] = $context['possible_types'][$_REQUEST['tracker']];
	
		if (isset($_REQUEST['category']))
			$filter['category'] = $_REQUEST['category'];
	
		if (isset($_REQUEST['reporter']))
			$filter['reporter'] = $_REQUEST['reporter'];
	
		if (isset($_REQUEST['assignee']))
			$filter['assignee'] = $_REQUEST['assignee'];
	
		if (isset($_REQUEST['version']))
		{
			$_REQUEST['version'] = (int) trim($_REQUEST['version']);
			$filter['version'] = $_REQUEST['version'];
		}
	
		if (isset($_REQUEST['version_fixed']))
		{
			$_REQUEST['version_fixed'] = (int) trim($_REQUEST['version_fixed']);
			$filter['version_fixed'] = $_REQUEST['version_fixed'];
		}
	
		if (!empty($_REQUEST['status']))
			$filter['status'] = $_REQUEST['status'];
			
		if (!empty($_REQUEST['tag']))
			$filter['tag'] = $_REQUEST['tag'];
	}
	
	return $filter;
}

/**
 * Creates issue list
 * @param array $issueListOptions
 * @retrun string Key Used for issue list in context array
 */
function createIssueList($issueListOptions)
{
	global $smcFunc, $context, $user_info, $scripturl, $txt;
	
	assert(isset($issueListOptions['id']));
	
	$key = 'issue_list_' . $issueListOptions['id'];
	
	$context[$key] = array();

	if (!isset($issueListOptions['filter']))
		$issueListOptions['filter'] = getIssuesFilter();
		
	if (!isset($issueListOptions['sort']))
		$issueListOptions['sort'] = 'i.updated DESC';
	if (!isset($issueListOptions['ascending']))
		$issueListOptions['ascending'] = true;
	
	// Build where clause
	$where = array();

	if ($issueListOptions['filter']['status'] == 'open')
		$where[] = 'NOT (i.status IN ({array_int:closed_status}))';
	elseif ($issueListOptions['filter']['status'] == 'closed')
		$where[] = 'i.status IN ({array_int:closed_status})';
	elseif (is_numeric($issueListOptions['filter']['status']))
		$where[] = 'i.status IN ({int:search_status})';

	if (!empty($issueListOptions['filter']['title']))
		$where[] = 'i.subject LIKE {string:search_title}';

	if (!empty($issueListOptions['filter']['tracker']))
		$where[] = 'i.id_tracker = {int:search_tracker}';

	if (isset($issueListOptions['filter']['category']))
		$where[] = 'i.id_category = {int:search_category}';

	if (isset($issueListOptions['filter']['reporter']))
		$where[] = 'i.id_reporter = {int:search_reporter}';

	if (isset($issueListOptions['filter']['assignee']))
		$where[] = 'i.id_assigned = {int:search_assignee}';

	if (isset($issueListOptions['filter']['version']))
		$where[] = '(FIND_IN_SET({int:search_version}, i.versions) OR FIND_IN_SET({int:search_version}, i.versions_fixed))';

	if (isset($issueListOptions['filter']['version_fixed']))
		$where[] = '(FIND_IN_SET({int:search_version_f}, i.versions_fixed))';

	// How many issues?
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($issueListOptions['filter']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . '
		WHERE {query_current_project_see_issue}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND (' . implode(')
			AND (', $where) . ')' : '') . '',
		array(
			'project' => ProjectTools_Project::getCurrent()->id,
			'closed_status' => $context['closed_status'],
			'search_status' => $issueListOptions['filter']['status'],
			'search_title' => '%' . $issueListOptions['filter']['title'] . '%',
			'search_version' => $issueListOptions['filter']['version'],
			'search_version_f' => $issueListOptions['filter']['version_fixed'],
			'search_category' => $issueListOptions['filter']['category'],
			'search_assignee' => $issueListOptions['filter']['assignee'],
			'search_reporter' => $issueListOptions['filter']['reporter'],
			'search_tracker' => $issueListOptions['filter']['tracker'],
			'search_tag' => $issueListOptions['filter']['tag'],
		)
	);

	list ($context[$key]['num_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);
	
	$context[$key]['start'] = isset($issueListOptions['start']) ? $issueListOptions['start'] : 0;
	
	if (isset($issueListOptions['page_index']))
		$context[$key]['page_index'] = constructPageIndex(ProjectTools::get_url($issueListOptions['base_url']), $context[$key]['start'], $context[$key]['num_issues'], !empty($issueListOptions['issues_per_page']) ? $issueListOptions['issues_per_page'] : $context['issues_per_page']);
	
	// Canonical url for search engines
	if (!empty($issueListOptions['page_index']) && !empty($return['start']))
		$context[$key]['canonical_url'] = ProjectTools::get_url(array_merge($issueListOptions['base_url'], array('start' => $context[$key]['start'])));
	elseif (!empty($issueListOptions['page_index']))
		$context[$key]['canonical_url'] = ProjectTools::get_url($issueListOptions['base_url']);
	
	$request = $smcFunc['db_query']('', '
		SELECT
			i.id_issue, p.id_project, i.id_tracker, i.subject, i.priority,
			i.status, i.created, i.updated, i.id_event_mod, i.replies,
			rep.id_member AS id_reporter, IFNULL(rep.real_name, iv.poster_name) AS reporter_name,
			asg.id_member AS id_assigned, asg.real_name AS assigned_name,
			i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
			i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
			i.versions, i.versions_fixed,
			GROUP_CONCAT(tags.tag SEPARATOR \', \') AS tags,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}issues AS i
			INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . (!empty($issueListOptions['filter']['tag']) ? '
			INNER JOIN {db_prefix}issue_tags AS stag ON (stag.id_issue = i.id_issue
				AND stag.tag = {string:search_tag})' : '') . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}issue_events AS iv ON (iv.id_issue_event = i.id_issue_event_first)
			LEFT JOIN {db_prefix}members AS rep ON (rep.id_member = i.id_reporter)
			LEFT JOIN {db_prefix}members AS asg ON (asg.id_member = i.id_assigned)
			LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
			LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
			LEFT JOIN {db_prefix}issue_tags AS tags ON (tags.id_issue = i.id_issue)
		WHERE {query_current_project_see_issue}
			AND i.id_project = {int:project}' . (!empty($where) ? '
			AND ' . implode('
			AND ', $where) : '') . '
		GROUP BY i.id_issue
		ORDER BY ' . $issueListOptions['sort']. (!$issueListOptions['ascending'] ? ' DESC' : '') . '
		LIMIT {int:start},' . (!empty($issueListOptions['issues_per_page']) ? $issueListOptions['issues_per_page'] : $context['issues_per_page']),
		array(
			'project' => ProjectTools_Project::getCurrent()->id,
			'empty' => '',
			'start' => $context[$key]['start'],
			'current_member' => $user_info['id'],
			'closed_status' => $context['closed_status'],
			'search_version' => $issueListOptions['filter']['version'],
			'search_version_f' => $issueListOptions['filter']['version_fixed'],
			'search_status' => $issueListOptions['filter']['status'],
			'search_title' => '%' . $issueListOptions['filter']['title'] . '%',
			'search_category' => $issueListOptions['filter']['category'],
			'search_assignee' => $issueListOptions['filter']['assignee'],
			'search_reporter' => $issueListOptions['filter']['reporter'],
			'search_tracker' => $issueListOptions['filter']['tracker'],
			'search_tag' => $issueListOptions['filter']['tag'],
		)
	);
	
	$context[$key]['filter'] = $issueListOptions['filter'];

	$context[$key]['issues'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$row['tags'] = explode(', ', $row['tags']);
		array_walk($row['tags'], 'link_tags', $issueListOptions['base_url']);

		$context[$key]['issues'][] = array(
			'id' => $row['id_issue'],
			'name' => $row['subject'],
			'link' => '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0')) . '">' . $row['subject'] . '</a>',
			'href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0')),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions']), $row['id_project']),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed']), $row['id_project']),
			'tags' => $row['tags'],
			'tracker' => &$context['issue_trackers'][$row['id_tracker']],
			'updated' => timeformat($row['updated']),
			'created' => timeformat($row['created']),
			'status' => &$context['issue_status'][$row['status']],
			'reporter' => array(
				'id' => $row['id_reporter'],
				'name' => $row['reporter_name'],
				'link' => !empty($row['id_reporter']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_reporter'] . '">' . $row['reporter_name'] . '</a>' : $row['reporter_name'],
			),
			'is_assigned' => !empty($row['id_assigned']),
			'assigned' => array(
				'id' => $row['id_assigned'],
				'name' => $row['assigned_name'],
				'link' => '<a href="' . $scripturl . '?action=profile;u=' . $row['id_assigned'] . '">' . $row['assigned_name'] . '</a>',
			),
			'updater' => array(
				'id' => $row['id_updater'],
				'name' => empty($row['updater']) ? $txt['issue_guest'] : $row['updater'],
				'link' => empty($row['updater']) ? $txt['issue_guest'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_updater'] . '">' . $row['updater'] . '</a>',
			),
			'replies' => comma_format($row['replies']),
			'priority' => $row['priority'],
			'new' => $row['new_from'] <= $row['id_event_mod'],
			'new_href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from'])) . '#new',
		);
	}
	$smcFunc['db_free_result']($request);
	
	return $key;
}

/**
 * Links tag of arrays. Used in array walk
 */
function link_tags(&$tag, $key, $baseurl)
{
	if (is_array($baseurl))
		$tag = '<a href="' . ProjectTools::get_url(array_merge($baseurl, array('tag' => urlencode($tag)))). '">' . $tag . '</a>';
	else
		$tag = '<a href="' . $baseurl . (strpos($baseurl,'?') !== false ? ';' : '?') . 'tag=' . urlencode($tag) . '">' . $tag . '</a>';
	
}

/**
 * Creates list of versions from array of ids
 * @param array $versions array of version ids
 * @param boolean $as_string return as comma separated string instead of array
 */
function getVersions($versions, $project = null, $as_string = false)
{
	global $context;
	
	// Versions might be comma separated list from database
	if (!is_array($versions))
		$versions = explode(',', $versions);
		
	if ($project === null)
		$project = ProjectTools_Project::getCurrent()->id;
	
	$return = array();
	
	foreach ($versions as $ver)
	{
		if (!empty(ProjectTools_Project::getProject($project)->versions_id[$ver]))
			$return[$ver] = $as_string ?
				ProjectTools_Project::getProject($project)->versions['versions'][ ProjectTools_Project::getProject($project)->versions_id[$ver] ]['sub_versions'][$ver]['name'] :
				ProjectTools_Project::getProject($project)->versions['versions'][ ProjectTools_Project::getProject($project)->versions_id[$ver] ]['sub_versions'][$ver];
		elseif (!empty(ProjectTools_Project::getProject($project)->versions[$ver]))
			$return[$ver] = $as_string ?
				ProjectTools_Project::getProject($project)->versions[$ver]['name'] :
				ProjectTools_Project::getProject($project)->versions[$ver];
	}
	
	return $as_string ? implode(', ', $return) : $return;
}

?>