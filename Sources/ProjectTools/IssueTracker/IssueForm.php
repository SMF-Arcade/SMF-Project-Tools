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
class ProjectTools_IssueTracker_IssueForm extends Madjoki_Form_Base
{
	/**
	 * @var ProjectTools_Project
	 */
	protected $project;

	/**
	 * @var ProjectTools_IssueTracker_Issue
	 */
	protected $issue;
	
	/**
	 *
	 */
	final public function __construct($id_project, $id_issue = null, $is_fatal = true, $is_post = null)
	{
		if ($is_post == null)
			$this->is_post = !empty($_POST['save']);
		else
			$this->is_post = $is_post;
			
		if ($this->is_post)
			checkSession('post', '');
			
		//
		$this->project = ProjectTools_Project::getProject($id_project);
		
		//
		if ($id_issue !== null)
			$this->issue = ProjectTools_IssueTracker_Issue::getIssue($id_issue);
		else
		{
			$this->issue = new ProjectTools_IssueTracker_Issue();
			$this->issue->project = $id_project;
		}
		
		$this->data = $this->issue->getData();
		
		$textValidator = new Madjoki_Form_Validator_Text();
		
		//
		new Madjoki_Form_Element_Text($this, 'title', $txt['issue_title'], $textValidator);
		
		
	}
}

?>