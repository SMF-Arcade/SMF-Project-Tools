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
class ProjectTools_IssueTracker_Profile
{
	/**
	 * Issues list
	 *
	 * @todo Use createIssueList
	 */
	public static function projectProfileIssues($memID)
	{
		global $smcFunc, $scripturl, $txt, $modSettings, $context, $settings, $user_info;
	
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
	
		$type = $_REQUEST['sa'] == 'assigned' ? 'assigned' : 'reported';
	
		$where = array();
	
		if ($type == 'assigned')
			$where[] = 'i.id_assigned = {int:profile_member}';
		else
			$where[] = 'i.id_reporter = {int:profile_member}';
	
		// How many issues?
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues AS i
				INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_project}
				AND {query_see_issue}' . (!empty($where) ? '
				AND ' . implode('
				AND ', $where) : '') . '',
			array(
				'empty' => '',
				'start' => $_REQUEST['start'],
				'current_member' => $user_info['id'],
				'profile_member' => $memID,
				'closed_status' => $context['closed_status'],
			)
		);
	
		list ($issueCount) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	
		$tags_url = $scripturl . '?action=profile;u=' . $memID . ';sa=' . $type;
		$context['page_index'] = constructPageIndex($scripturl . '?action=profile;area=project;sa=' . $type . ';u=' . $memID, $_REQUEST['start'], $issueCount, $context['issues_per_page']);
	
		$request = $smcFunc['db_query']('', '
			SELECT
				p.id_project, p.name AS project_name,
				i.id_issue, i.id_tracker, i.subject, i.priority,
				i.status, i.created, i.updated, i.id_event_mod, i.replies,
				rep.id_member AS id_reporter, IFNULL(rep.real_name, com.poster_name) AS reporter_name,
				i.id_category, IFNULL(cat.category_name, {string:empty}) AS category_name,
				i.id_updater, IFNULL(mu.real_name, {string:empty}) AS updater,
				i.versions, i.versions_fixed,
				GROUP_CONCAT(tags.tag SEPARATOR \', \') AS tags,
				' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
			FROM {db_prefix}issues AS i
				INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)' . ($user_info['is_guest'] ? '' : '
				LEFT JOIN {db_prefix}log_issues AS log ON (log.id_member = {int:current_member} AND log.id_issue = i.id_issue)
				LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
				LEFT JOIN {db_prefix}issue_comments AS com ON (com.id_comment = i.id_comment_first)
				LEFT JOIN {db_prefix}members AS rep ON (rep.id_member = i.id_reporter)
				LEFT JOIN {db_prefix}members AS mu ON (mu.id_member = i.id_updater)
				LEFT JOIN {db_prefix}issue_category AS cat ON (cat.id_category = i.id_category)
				LEFT JOIN {db_prefix}issue_tags AS tags ON (tags.id_issue = i.id_issue)
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_project}
				AND {query_see_issue}' . (!empty($where) ? '
				AND ' . implode('
				AND ', $where) : '') . '
			GROUP BY i.id_issue
			ORDER BY ' . $_REQUEST['sort']. (!$ascending ? ' DESC' : '') . '
			LIMIT {int:start},' . $context['issues_per_page'],
			array(
				'empty' => '',
				'start' => $_REQUEST['start'],
				'current_member' => $user_info['id'],
				'profile_member' => $memID,
				'closed_status' => $context['closed_status'],
			)
		);
	
		$context['issues'] = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$row['tags'] = explode(', ', $row['tags']);
			array_walk($row['tags'], 'link_tags', $tags_url);
	
			$context['issues'][$row['id_issue']] = array(
				'id' => $row['id_issue'],
				'name' => $row['subject'],
				'link' => '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>',
				'href' => ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']),
				'project' => array(
					'id' => $row['id_project'],
					'name' => $row['project_name'],
					'link' => !empty($row['project_name']) ? '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'])) . '">' . $row['project_name'] . '</a>' : '',
				),
				'category' => array(
					'id' => $row['id_category'],
					'name' => $row['category_name'],
					'link' => !empty($row['category_name']) ? '<a href="' . ProjectTools::get_url(array('project' =>  $row['id_project'], 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
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
	
		// Template
		$context['sub_template'] = 'issue_list_profile';
	
		if ($type == 'assigned')
			$context['page_title'] = sprintf($txt['issues_assigned_to'], $context['member']['name']);
		else
			$context['page_title'] = sprintf($txt['issues_reported_by'], $context['member']['name']);
	}	
}

?>