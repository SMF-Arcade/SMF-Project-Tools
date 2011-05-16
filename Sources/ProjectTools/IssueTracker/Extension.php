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
class ProjectTools_IssueTracker_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'IssueTracker',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_IssueTracker_Module';
	}
	
	/**
	 *
	 */
	function load()
	{
		global $context, $project, $issue, $user_info, $smcFunc;
		
		// Setup queries
		if ($user_info['is_admin'] || allowedTo('project_admin'))
		{
			$see_version = '1 = 1';
			$see_version_timeline = '1 = 1';
			$see_issue = '1 = 1';
		}
		else
		{
			// Version 0 can be always seen
			$user_info['project_allowed_versions'] = array(0);
			
			// Get versions that can be seen
			$request = $smcFunc['db_query']('', '
				SELECT id_version
				FROM {db_prefix}project_versions AS ver
				WHERE (FIND_IN_SET(' . implode(', ver.member_groups) OR FIND_IN_SET(', $user_info['groups']) . ', ver.member_groups))'
			);
			
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$user_info['project_allowed_versions'][] = $row['id_version'];
			$smcFunc['db_free_result']($request);
			
			// See version
			if (!empty($user_info['project_allowed_versions']))
				$see_version = '(id_version IN(' . implode(',', $user_info['project_allowed_versions']) . '))';
			else
				$see_version = '(0=1)';
				
			// See version in timeline query
			if (!empty($user_info['project_allowed_versions']))
				$see_version_timeline = '(FIND_IN_SET(' . implode(', IFNULL(i.versions, tl.versions)) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', IFNULL(i.versions, tl.versions)))';
			else
				$see_version_timeline = '(0=1)';
				
			// See private issues code
			$my_issue = $user_info['is_guest'] ? '(0=1)' : '(i.id_reporter = ' . $user_info['id'] . ')';
			
			// See version in issue query
			if (!empty($user_info['project_allowed_versions']))
				$see_version_issue = '(FIND_IN_SET(' . implode(', i.versions) OR FIND_IN_SET(', $user_info['project_allowed_versions']) . ', i.versions))';
			else
				$see_version_issue = '(0=1)';
				
			// Private issues
			$see_private_profiles = getPrivateProfiles();
			if (!empty($see_private_profiles))
				$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR (' . $my_issue . ' OR p.id_profile IN(' . implode(', ', $see_private_profiles) . ')))';
			else
				$see_private = '(i.private_issue = 0 OR NOT ISNULL(dev.id_member) OR ' . $my_issue . ')';
				
			$see_issue = '((' . $see_version_issue . ') AND ' . $see_private . ')';
		}
		
		// See version
		$user_info['query_see_version'] = $see_version;
		// See version timeline
		$user_info['query_see_version_timeline'] = $see_version_timeline;
		// Issue of any project
		$user_info['query_see_issue'] = $see_issue;
		
		// Load trackers
		self::loadTrackers();
		
		// Issue with start
		if (isset($_REQUEST['issue']) && strpos($_REQUEST['issue'], '.') !== false)
		{
			list ($_REQUEST['issue'], $_REQUEST['start']) = explode('.', $_REQUEST['issue'], 2);
			$issue = (int) $_REQUEST['issue'];
			
			// This is for Who's online
			$_GET['issue'] = $issue;
		}
		elseif (isset($_REQUEST['issue']))
		{
			$issue = (int) $_REQUEST['issue'];
			
			// This is for Who's online
			$_GET['issue'] = $issue;
		}
		else
			$issue = 0;
			
		// Detect project from issue
		if (!empty($issue))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_project
				FROM {db_prefix}issues
				WHERE id_issue = {int:issue}',
				array(
					'issue' => (int) $issue
				)
			);
	
			list ($project) = $smcFunc['db_fetch_row']($request);
			$smcFunc['db_free_result']($request);
	
			if (empty($project))
			{
				$context['project_error'] = 'issue_not_found';
				return;
			}
		}
	}
	
	/**
	 *
	 */
	static protected function loadTrackers()
	{
		global $smcFunc, $context;
		
		// Trackers
		$context['issue_trackers'] = array();
		$context['tracker_columns'] = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT id_tracker, short_name, tracker_name, plural_name
			FROM {db_prefix}project_trackers',
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$context['issue_trackers'][$row['id_tracker']] = array(
				'id' => $row['id_tracker'],
				'name' => $row['tracker_name'],
				'short' => $row['short_name'],
				'plural' => $row['plural_name'],
				'image' => $row['short_name'] . '.png',
				'column_open' => 'open_' . $row['short_name'],
				'column_closed' => 'closed_' . $row['short_name'],
			);
	
			$context['tracker_columns'][] = "open_$row[short_name]";
			$context['tracker_columns'][] = "closed_$row[short_name]";
		}
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	function Profile_subActions(&$subActions)
	{
		$subActions['assigned'] = array(array('ProjectTools_IssueTracker_Profile', 'projectProfileIssues'));
		$subActions['reported'] = array(array('ProjectTools_IssueTracker_Profile', 'projectProfileIssues'));
	}
	
	/**
	 *
	 */
	function Profile_subSections(&$subSections)
	{
		global $txt;
		
		$subSections['reported'] = array($txt['project_profile_reported']);
		$subSections['assigned'] = array($txt['project_profile_assigned']);		
	}
	
	/**
	 *
	 */
	function Profile_addStatistics(&$statistics)
	{
		global $smcFunc, $txt;
	
		$statistics['reported_issues'] = array(
			'text' => $txt['profile_reported_issues'],
			'href' => $scripturl . '?action=profile;u=' . $context['member']['id'] . ';area=project;sa=reported',
		);
		$statistics['assigned_issues'] = array(
			'text' => $txt['profile_assigned_issues'],
			'href' => $scripturl . '?action=profile;u=' . $context['member']['id'] . ';area=project;sa=assigned',
		);
		
		// Reported Issues
		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}issues
			WHERE id_reporter = {int:member}',
			array(
				'member' => $memID,
			)
		);
	
		list ($statistics['reported_issues']['number']) = $smcFunc['db_fetch_row']($request);
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
	
		list ($statistics['assigned_issues']['number']) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);		
	}
}

?>