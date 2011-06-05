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
class ProjectTools_IssueTracker_Edit
{
	/**
	 * Displayis Issue Edit Form
	 */
	static public function Edit()
	{
		global $smcFunc, $context, $user_info, $txt, $modSettings, $sourcedir, $project, $issue, $options;
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent())
			fatal_lang_error('issue_not_found', false);
			
		if (isset($_REQUEST['com']) && $_REQUEST['com'] != ProjectTools_IssueTracker_Issue::getCurrent()->details['id_comment'])
		{
			self::Comment();
			return;
		}
			
		$type = ProjectTools_IssueTracker_Issue::getCurrent()->is_mine ? 'own' : 'any';
		
		if (!ProjectTools::allowedTo('issue_update_' . $type))
			ProjectTools::isAllowedTo('issue_moderate');
		
		$context['report_form'] = new ProjectTools_IssueTracker_Form_Issue($project, $issue);
		if ($context['report_form']->is_post && $context['report_form']->Save())
			redirectexit(ProjectTools::get_url(array('issue' => $issue)));
		
		$context['linktree'][] = array(
			'name' => $txt['linktree_edit_issue'],
			'url' => ProjectTools::get_url(array('issue' => $issue, 'area' => 'issues', 'sa' => 'edit')),
		);
		
		// Template
		loadTemplate('IssueReport');
	
		$context['sub_template'] = 'report_issue';
		$context['page_title'] = sprintf($txt['project_edit_issue'], ProjectTools_Project::getCurrent()->name, ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_IssueTracker_Issue::getCurrent()->name);
	}
	
	/**
	 * Takes request to delete issue
	 */
	public static function Delete()
	{
		global $context, $user_info, $smcFunc;
	
		checkSession('get');
	
		if (!ProjectTools_IssueTracker_Issue::getCurrent())
			fatal_lang_error('issue_not_found', false);
	
		ProjectTools::isAllowedTo('issue_moderate');
	
		$posterOptions = array(
			'id' => $user_info['id'],
			'ip' => $user_info['ip'],
			'name' => htmlspecialchars($user_info['name']),
			'username' => htmlspecialchars($user_info['username']),
			'email' => htmlspecialchars($user_info['email']),
		);
	
		// Send Notifications
		sendIssueNotification(array('id' => ProjectTools_IssueTracker_Issue::getCurrent()->id, 'project' => ProjectTools_Project::getCurrent()->id), array(), array(), 'issue_delete', $user_info['id']);
	
		ProjectTools_IssueTracker_Issue::getCurrent()->delete($posterOptions);
	
		redirectexit(ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'issues')));
	}
}

?>