<?php
/**
 * 
 *
 * @package admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * 
 */
class ProjectTools_Maintenance_Upgrade extends ProjectTools_Maintenance_Action
{
	/**
	 *
	 */
	protected $actions = array(
		array('ProjectTools_Maintenance_Upgrade', 'log_issues'),
		array('ProjectTools_Maintenance_Upgrade', 'trackers'),
		array('ProjectTools_Maintenance_Upgrade', 'versionFields'),
		array('ProjectTools_Maintenance_Upgrade', 'database06'),
		array('ProjectTools_Maintenance_Repair', 'MaintenanceIssues2'),
		array('ProjectTools_Maintenance_Repair', 'MaintenanceIssueCounts'),
	);
	
	/**
	 * Maintenance function for adding id_project to log_issues
	 */
	function log_issues($check = false)
	{
		global $smcFunc;
	
		// Is this step required to run?
		if ($check)
			return true;
	
		$request = $smcFunc['db_query']('', '
			SELECT log.id_issue, i.id_project
			FROM {db_prefix}log_issues AS log
				INNER JOIN {db_prefix}issues AS i ON (i.id_issue = log.id_issue)
			WHERE log.id_project = {int:no_project}
			GROUP BY log.id_issue',
			array(
				'no_project' => 0,
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}log_issues
				SET id_project = {int:project}
				WHERE id_issue = {int:issue}',
				array(
					'project' => $row['id_project'],
					'issue' => $row['id_issue'],
				)
			);
		}
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 * Maintenance function for uprading tracker columns
	 */
	function trackers($check = false)
	{
		global $smcFunc;
	
		// Is this step required to run?
		if ($check)
			return true;
	
		db_extend('packages');
	
		$trackers = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT id_tracker, short_name
			FROM {db_prefix}project_trackers',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$trackers[$row['short_name']] = $row['id_tracker'];
		$smcFunc['db_free_result']($request);
	
		$request = $smcFunc['db_query']('', '
			SELECT id_project, trackers
			FROM {db_prefix}projects',
			array(
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$update = false;
			$currentTrackers = explode(',', $row['trackers']);
			foreach ($currentTrackers as $key => $shortName)
			{
				if (!is_numeric($shortName))
				{
					$update = true;
					if (!isset($trackers[$shortName]))
						fatal_lang_error('upgrade_no_tracker', null, $shortName);
	
					$currentTrackers[$key] = $trackers[$shortName];
				}
			}
	
			if ($update)
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}projects
					SET trackers = {string:trackers}
					WHERE id_project = {int:project}',
					array(
						'project' => $row['id_project'],
						'trackers' => implode(',', $currentTrackers),
					)
				);
		}
		$smcFunc['db_free_result']($request);
	
		if (in_array('issue_type', $smcFunc['db_list_columns']('{db_prefix}issues')))
		{
			$request = $smcFunc['db_query']('', '
				SELECT DISTINCT issue_type
				FROM {db_prefix}issues',
				array(
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if (!isset($trackers[$row['issue_type']]))
					fatal_lang_error('upgrade_no_tracker', null, $row['issue_type']);
	
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}issues
					SET id_tracker = {int:tracker}
					WHERE issue_type = {string:shortname}',
					array(
						'shortname' => $row['issue_type'],
						'tracker' => $trackers[$row['issue_type']],
					)
				);
			}
			$smcFunc['db_free_result']($request);
	
			$smcFunc['db_remove_column']('issues', 'issue_type');
		}
	}
	
	/**
	 * Maintenance function for upgrading version fields
	 */
	function versionFields($check = false)
	{
		global $smcFunc;
	
		// Is this step required to run?
		if ($check)
			return true;
	
		db_extend('packages');
	
		if (in_array('id_version', $smcFunc['db_list_columns']('{db_prefix}issues')))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET versions = id_version'
			);
	
			$smcFunc['db_remove_column']('issues', 'id_version');
		}
		
		if (in_array('id_version_fixed', $smcFunc['db_list_columns']('{db_prefix}issues')))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET versions_fixed = id_version_fixed'
			);
	
			$smcFunc['db_remove_column']('issues', 'id_version_fixed');
		}
		
		if (in_array('id_version', $smcFunc['db_list_columns']('{db_prefix}project_timeline')))
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_timeline
				SET versions = id_version'
			);
	
			$smcFunc['db_remove_column']('project_timeline', 'id_version');
		}
	}
	
	/**
	 * Maintenance function for upgrading project modules
	 */
	function database06($check = false)
	{
		global $smcFunc;
	
		// Is this step required to run?
		if ($check)
			return true;
	
		db_extend('packages');
	
		// Moving issue event to new table '{db_prefix}issue_events'
		if (in_array('id_event_mod', $smcFunc['db_list_columns']('{db_prefix}issue_comments')))
		{
			$smcFunc['db_query']('', '
				TRUNCATE TABLE {db_prefix}issue_events'
			);
			
			$request = $smcFunc['db_query']('', '
				SELECT
					tl.*,
					c.id_comment, IFNULL(c.id_event_mod, tl.id_event) AS id_event_mod
				FROM {db_prefix}project_timeline AS tl
					LEFT JOIN {db_prefix}issue_comments AS c ON (c.id_event = tl.id_event)
				WHERE tl.id_issue > 0',
				array(
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$changes = unserialize($row['event_data']);
				
				if (isset($changes['changes']) && is_array($changes['changes']))
					$newchanges = array('changes' => $changes['changes']);
				else
					$newchanges = array();
					
				if (isset($changes['attachments']))
					$newchanges['attachments'] = $changes['attachments'];
					
				if ($row['id_comment'] == NULL)
					$row['id_comment'] = 0;
	
				$smcFunc['db_insert']('',
					'{db_prefix}issue_events', 
					array(
						'id_issue' => 'int',
						'id_member' => 'int',
						'id_comment' => 'int',
						'id_event' => 'int',
						'id_event_mod' => 'int',
						'event_time' => 'int',
						'poster_name' => 'string-255',
						'poster_email' => 'string-255',
						'poster_ip' => 'string-60',
						'changes' => 'string',
					),
					array(
						$row['id_issue'],
						$row['id_member'],
						$row['id_comment'],
						$row['id_event'],
						$row['id_event_mod'],
						$row['event_time'],
						$row['poster_name'],
						$row['poster_email'],
						$row['poster_ip'],
						serialize($newchanges),
					),
					array('id_issue_event')
				);
				
				$id_issue_event = $smcFunc['db_insert_id']('{db_prefix}issue_events', 'id_issue_event');
				
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}issues
					SET id_issue_event_first = {int:event}
					WHERE id_comment_first = {int:comment}',
					array(
						'event' => $id_issue_event,
						'comment' => $row['id_comment'],
					)
				);
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}issues
					SET id_issue_event_last = {int:event}
					WHERE id_comment_last = {int:comment}',
					array(
						'event' => $id_issue_event,
						'comment' => $row['id_comment'],
					)
				);
			}
			$smcFunc['db_free_result']($request);
			
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_issue');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_member');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_event');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'id_event_mod');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'post_time');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_name');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_email');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_ip');
			$smcFunc['db_remove_column']('{db_prefix}issue_comments', 'poster_ip');
			$smcFunc['db_remove_column']('{db_prefix}issues', 'id_comment_first');
			$smcFunc['db_remove_column']('{db_prefix}issues', 'id_comment_last');
		}
	
		// Move modules to settings table
		if (in_array('modules', $smcFunc['db_list_columns']('{db_prefix}projects')))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_project, modules	
				FROM {db_prefix}projects',
				array(
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				$modules = explode(',', $row['modules']);
				
				$newModules = array('Frontpage');
				
				if (in_array('issues', $modules))
					$newModules[] = 'IssueTracker';
				if (in_array('roadmap', $modules))
					$newModules[] = 'Roadmap';
					
				$smcFunc['db_insert']('ignore',
					'{db_prefix}project_settings',
					array(
						'id_project' => 'int',
						'id_member' => 'int',
						'variable' => 'string-255',
						'value' => 'string',
					),
					array(
						$row['id_project'],
						0,
						'modules',
						implode(',', $newModules),
					),
					array('id_project', 'variable')
				);
			}
			
			$smcFunc['db_remove_column']('{db_prefix}projects', 'modules');
		}
		
	}
}

?>