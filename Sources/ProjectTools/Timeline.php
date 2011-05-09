<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

/**
 * 
 */
class ProjectTools_Timeline
{
	/**
	 *
	 */
	protected $events;
	
	/**
	 *
	 */
	function __construct(ProjectTools_Project $project = null)
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
		// Load timeline
		$request = $smcFunc['db_query']('', '
			SELECT
				i.id_issue, i.id_tracker, i.subject, i.priority, i.status,
				tl.id_project, tl.event, tl.event_data, tl.event_time,
				mem.id_member, IFNULL(mem.real_name, tl.poster_name) AS user, p.id_project, p.name
			FROM {db_prefix}project_timeline AS tl
				INNER JOIN {db_prefix}projects AS p ON (p.id_project = tl.id_project)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = tl.id_member)
				LEFT JOIN {db_prefix}issues AS i ON (i.id_issue = tl.id_issue)
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_project}' . (!empty($project) ? '
				AND {query_project_see_issue}
				AND tl.id_project = {int:project}' : '') . '
				AND {query_see_version_timeline}
			ORDER BY tl.event_time DESC
			LIMIT 12',
			array(
				'project' => $project,
				'current_member' => $user_info['id'],
				'empty' => ''
			)
		);
	
		$this->events = array();
	
		$nowtime = forum_time();
		$now = @getdate($nowtime);
		$clockFromat = strpos($user_info['time_format'], '%I') === false ? '%H:%M' : '%I:%M %p';
	
		$members = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$data = unserialize($row['event_data']);
			
			// Fix: Some events in past have had double serialised array
			if (is_string($data))
				$data = unserialize($data);
				
			$index = date('Ymd', forum_time(true, $row['event_time']));
			$date = @getdate(forum_time(true, $row['event_time']));
	
			if (!isset($this->events[$index]))
			{
				$this->events[$index] = array(
					'date' => '',
					'events' => array(),
				);
	
				if ($date['yday'] == $now['yday'] && $date['year'] == $now['year'])
					$this->events[$index]['date'] = $txt['project_today'];
				elseif (($date['yday'] == $now['yday'] - 1 && $date['year'] == $now['year']) || ($now['yday'] == 0 && $date['year'] == $now['year'] - 1) && $date['mon'] == 12 && $date['mday'] == 31)
					$this->events[$index]['date'] = $txt['project_yesterday'];
				else
					$this->events[$index]['date'] = $date['mday'] . '. ' . $txt['months'][$date['mon']] . ' ' . $date['year'];
			}
	
			$extra = '';
	
			if (isset($data['changes']))
			{
				$changes = ProjectTools_ChangesParser::Parse($row['id_project'], $data['changes'], true);
	
				if (!empty($changes))
					$extra = implode(', ', $changes);
			}
	
			$this->events[$index]['events'][] = array(
				'event' => $row['event'],
				'project_link' => '<a href="' . ProjectTools::get_url(array('project' => $row['id_project'])) . '">' . $row['name'] . '</a>',
				'member_link' => !empty($row['id_member']) ? '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['user'] . '</a>' : $txt['issue_guest'],
				'link' => !empty($row['subject']) ? '<a href="' . ProjectTools::get_url(array('issue' => $row['id_issue'] . '.0'), $row['id_project']) . '">' . $row['subject'] . '</a>' : (!empty($data['subject']) ? $data['subject'] : ''),
				'time' => strftime($clockFromat, forum_time(true, $row['event_time'])),
				'extra' => $extra,
			);
		}
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	function getEvents()
	{
		return $this->events;
	}
}

?>