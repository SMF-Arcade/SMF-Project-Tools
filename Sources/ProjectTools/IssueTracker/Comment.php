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
class ProjectTools_IssueTracker_Comment
{
	/**
	 *
	 */
	public static function Reply()
	{
		global $context, $project, $issue, $txt, $smcFunc, $user_info;
		
		if (!ProjectTools_IssueTracker_Issue::getCurrent() || !ProjectTools::allowedTo('issue_comment'))
			fatal_lang_error('issue_not_found', false);
			
		$context['can_subscribe'] = !$user_info['is_guest'];
			
		$context['comment_form'] = new ProjectTools_IssueTracker_Form_Comment($project, $issue);
		if ($context['comment_form']->is_post)
		{
			$id = $context['comment_form']->Save();
			
			if ($id_comment !== false)
				redirectexit(ProjectTools::get_url(array('issue' => $issue . '.com' . $id[1])) . '#com' . $id[0]);
		}
		
		// Linktree
		$context['linktree'][] = array(
			'name' => $txt['issue_reply'],
			'url' => ProjectTools::get_url(array('issue' => $issue, 'area' => 'issues', 'sa' => 'reply')),
		);
		
		// Template
		$context['sub_template'] = 'issue_reply';
		$context['page_title'] = sprintf($txt['project_comment_issue'], ProjectTools_Project::getCurrent()->name, ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_IssueTracker_Issue::getCurrent()->name);
	
		loadTemplate('ProjectTools/IssueTracker_Report');
	}
	
	/**
	 *
	 */
	public static function Edit()
	{
		global $context, $project, $issue, $txt, $smcFunc, $user_info;
		
		if (!ProjectTools_IssueTracker_Issue::getCurrent() || !ProjectTools::allowedTo('issue_comment'))
			fatal_lang_error('issue_not_found', false);
			
		$context['can_subscribe'] = !$user_info['is_guest'];
			
		$context['comment_form'] = new ProjectTools_IssueTracker_Form_Comment($project, $issue, $_REQUEST['com']);
		if ($context['comment_form']->is_post)
		{
			$id = $context['comment_form']->Save();
			
			if ($id_comment !== false)
				redirectexit(ProjectTools::get_url(array('issue' => $issue . '.com' . $id[1])) . '#com' . $id[0]);
		}
		
		// Linktree
		$context['linktree'][] = array(
			'name' => $txt['edit_comment'],
			'url' => ProjectTools::get_url(array('issue' => $issue, 'area' => 'issues', 'sa' => 'edit', 'com' => $_REQUEST['com'])),
		);
		
		// Template
		$context['sub_template'] = 'issue_reply';
		$context['page_title'] = sprintf($txt['project_comment_issue'], ProjectTools_Project::getCurrent()->name, ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_IssueTracker_Issue::getCurrent()->name);
	
		loadTemplate('ProjectTools/IssueTracker_Report');
	}
	
	/**
	 * Delete Comment
	 */
	public static function _Delete()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent() || empty($_REQUEST['com']))
			fatal_lang_error('issue_not_found', false);
	
		ProjectTools::isAllowedTo('edit_comment_own');
		require_once($sourcedir . '/Subs-Post.php');
	
		$request = $smcFunc['db_query']('', '
			SELECT iv.id_issue_event, iv.changes, iv.id_comment, c.id_event, iv.poster_name, iv.id_member
			FROM {db_prefix}issue_events AS iv
			WHERE iv.id_comment = {int:comment}' . (!ProjectTools::allowedTo('edit_comment_any') ? '
				AND iv.id_member = {int:current_user}' : '') . '
				AND iv.id_issue = {int:issue}
			ORDER BY id_comment',
			array(
				'current_user' => $user_info['id'],
				'comment' => (int) $_REQUEST['com'],
				'issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id,
			)
		);
	
		$row = $smcFunc['db_fetch_assoc']($request);
		if (!$row)
			fatal_lang_error('comment_not_found', false);
		$smcFunc['db_free_result']($request);
	
		if ($row['id_comment'] == ProjectTools_IssueTracker_Issue::getCurrent()->details['id'])
			fatal_lang_error('comment_cant_remove_first', false);
	
		// Delete comment
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_comments
			WHERE id_comment = {int:comment}',
			array(
				'comment' => $row['id_comment'],
			)
		);
	
		$event_data = unserialize($row['changes']);
		
		// By default remove event too
		$removeEvent = true;
	
		if (!empty($event_data))
			$removeEvent = false;
	
		if ($removeEvent)
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}issue_events
				WHERE id_issue_event = {int:issue_event}',
				array(
					'issue_event' => $row['id_issue_event'],
				)
			);
		else
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issue_events
				SET id_comment = 0
				WHERE id_issue_event = {int:issue_event}',
				array(
					'issue_event' => $row['id_issue_event'],
				)
			);
	
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}issues
				SET replies = {int:replies}
				WHERE id_issue = {int:issue}',
				array(
					'issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id,
					'replies' => $num_replies,
				)
			);
		}
		
		// CreateTimeline?/project Adminlog?
		logAction('project_remove_comment', array('comment' => $row['id_comment']));
	
		redirectexit(ProjectTools::get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
	}
}

?>
