<?php
/**
 * Project list page
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*
 * Projects list
 */
function ProjectList()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
	// Canonical url for search engines
	$context['canonical_url'] = project_get_url();

	$request = $smcFunc['db_query']('', '
		SELECT
			p.id_project, p.name, p.description, p.trackers, p.' . implode(', p.', $context['tracker_columns']) . ', p.id_event_mod,
			mem.id_member, mem.real_name,
			' . ($user_info['is_guest'] ? '0 AS new_from' : 'IFNULL(log.id_event, IFNULL(lmr.id_event, -1)) + 1 AS new_from') . '
		FROM {db_prefix}projects AS p' . ($user_info['is_guest'] ? '' : '
			LEFT JOIN {db_prefix}log_projects AS log ON (log.id_member = {int:current_member}
				AND log.id_project = p.id_project)
			LEFT JOIN {db_prefix}log_project_mark_read AS lmr ON (lmr.id_project = p.id_project AND lmr.id_member = {int:current_member})') . '
			LEFT JOIN {db_prefix}project_developer AS pdev ON (pdev.id_project = p.id_project)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = pdev.id_member)
			LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
				AND dev.id_member = {int:current_member})
		WHERE {query_see_project}
		ORDER BY p.name',
		array(
			'current_member' => $user_info['id'],
		)
	);

	$context['projects'] = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (isset($context['projects'][$row['id_project']]))
		{
			if (empty($row['id_member']))
				continue;

			$context['projects'][$row['id_project']]['developers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';

			continue;
		}

		$context['projects'][$row['id_project']] = array(
			'id' => $row['id_project'],
			'link' => '<a href="' . project_get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
			'href' => project_get_url(array('project' => $row['id_project'])),
			'name' => $row['name'],
			'description' => $row['description'],
			'new' => $row['new_from'] <= $row['id_event_mod'] && !$user_info['is_guest'],
			'trackers' => array(),
			'developers' => array(),
		);

		if (!empty($row['id_member']))
			$context['projects'][$row['id_project']]['developers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['real_name'] . '</a>';

		$trackers = explode(',', $row['trackers']);

		foreach ($trackers as $id)
		{
			$tracker = &$context['issue_trackers'][$id];
			$context['projects'][$row['id_project']]['trackers'][$id] = array(
				'tracker' => &$context['issue_trackers'][$id],
				'open' => $row['open_' . $tracker['short']],
				'closed' => $row['closed_' . $tracker['short']],
				'total' => $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']],
				'progress' => round(($row['closed_' . $tracker['short']] / max(1, $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']])) * 100, 2),
				'link' => project_get_url(array('project' => $row['id_project'], 'area' => 'issues', 'tracker' => $tracker['short'])),
			);
			unset($tracker);
		}
	}
	$smcFunc['db_free_result']($request);

	loadTimeline();

	// Template
	$context['sub_template'] = 'project_list';
	$context['page_title'] = sprintf($txt['project_list_title'], $context['forum_name']);
}

?>