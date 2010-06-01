<?php
/**
 * Contains code for profile pages.
 *
 * @package profile
 * @version 0.5
 * @license htttp://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*
	!!!
*/

function projectProfile($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
	global $user_info, $smcFunc, $sourcedir;

	require_once($sourcedir . '/Project.php');
	loadProjectToolsPage('profile');

	$subActions = array(
		'main' => array('projectProfileMain'),
		'assigned' => array('projectProfileIssues'),
		'reported' => array('projectProfileIssues'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$context[$context['profile_menu_name']]['tab_data']['title'] = $txt['project_tools_profile'];
	$context[$context['profile_menu_name']]['tab_data']['description'] = $txt['project_tools_profile_desc'];

	// Check permission if needed
	if (isset($subActions[$_REQUEST['sa']][1]))
		isAllowedTo($subActions[$_REQUEST['sa']][1]);

	$subActions[$_REQUEST['sa']][0]($memID);
}

function projectProfileMain($memID)
{
	global $db_prefix, $scripturl, $txt, $modSettings, $context, $settings;
	global $user_info, $smcFunc, $sourcedir;

	$context['statistics'] = array();

	// Reported Issues
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues
		WHERE id_reporter = {int:member}',
		array(
			'member' => $memID,
		)
	);

	list ($context['statistics']['reported_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Assigned Issues
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}issues
		WHERE id_assigned = {int:member}',
		array(
			'member' => $memID,
		)
	);

	list ($context['statistics']['assigned_issues']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Format
	$context['statistics']['reported_issues'] = comma_format($context['statistics']['reported_issues']);
	$context['statistics']['assigned_issues'] = comma_format($context['statistics']['assigned_issues']);

	$context['reported_issues'] = getIssueList(0, 10, 'i.updated DESC', 'i.id_reporter = {int:profile_member}', array('profile_member' => $memID));

	// Template
	$context['sub_template'] = 'project_profile_main';
}

function projectProfileIssues($memID)
{
	global $smcFunc, $scripturl, $txt, $modSettings, $context, $settings, $user_info;

	// Load Versions
	$request = $smcFunc['db_query']('', '
		SELECT id_version, id_parent, version_name, release_date, status
		FROM {db_prefix}project_versions AS ver
		ORDER BY id_parent, version_name',
		array(
		)
	);

	$context['versions'] = array();
	$context['versions_id'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if ($row['id_parent'] == 0)
		{
			$context['versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'sub_versions' => array(),
			);
		}
		else
		{
			if (!isset($context['versions'][$row['id_parent']]))
				continue;

			$context['versions'][$row['id_parent']]['sub_versions'][$row['id_version']] = array(
				'id' => $row['id_version'],
				'name' => $row['version_name'],
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
				'released' => $row['status'] >= 4,
			);
		}

		$context['versions_id'][$row['id_version']] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);
	
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
			'link' => '<a href="' . project_get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>',
			'href' => project_get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']),
			'project' => array(
				'id' => $row['id_project'],
				'name' => $row['project_name'],
				'link' => !empty($row['project_name']) ? '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['project_name'] . '</a>' : '',
			),
			'category' => array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => !empty($row['category_name']) ? '<a href="' . project_get_url(array('project' =>  $row['id_project'], 'sa' => 'issues', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>' : '',
			),
			'versions' => getVersions(explode(',', $row['versions'])),
			'versions_fixed' => getVersions(explode(',', $row['versions_fixed'])),
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
			'new_href' => project_get_url(array('issue' => $row['id_issue'] . '.com' . $row['new_from']), $row['id_project']) . '#new',
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

?>