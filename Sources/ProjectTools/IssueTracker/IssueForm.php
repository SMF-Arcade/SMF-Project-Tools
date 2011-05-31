<?php
/**
 * 
 *
 * @package ProjectTools
 * @subpackage IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_IssueTracker_IssueForm extends ProjectTools_Form_Project
{
	/**
	 *
	 *
	 */
	public $formid = 'reportissue';

	/**
	 * @var ProjectTools_IssueTracker_Issue
	 */
	protected $issue;
	
	/**
	 *
	 */
	final public function __construct($id_project, $id_issue = null, $is_fatal = true, $is_post = null)
	{
		global $txt;
		
		if ($is_post == null)
			$this->is_post = !empty($_POST['save']);
		else
			$this->is_post = $is_post;
			
		if ($this->is_post)
			checkSession('post', '');
			
		parent::__construct((int) $id_project, $is_fatal, $is_post);
		
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
		
		//
		new Madjoki_Form_Element_Check($this, 'private', $txt['private_issue']);
		
		//
		new ProjectTools_Form_TrackersElement($this, 'tracker', $txt['issue_type']);
		
		// Show version selection if project has at least one
		if (!empty($this->project->versions))
		{
			new ProjectTools_Form_Element_Versions($this, 'versions', $txt['issue_version']);
		}
		
		// Show category selection if project has at least one
		if (!empty($this->project->categories))
		{
			$category = new Madjoki_Form_Element_Select($this, 'category', $txt['issue_category']);
			foreach ($this->project->categories as $cat)
				$category->addOption($cat['id'], $cat['name']);
		}
		
		// BBC Editor
		new Madjoki_Form_Element_BBCEditor($this, 'details', '', new Madjoki_Form_Validator_BBC());
		
		$this->saveEntities = array('title', 'details');
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($id_issue !== null)
			new Madjoki_Form_Element_Submit($this, $txt['edit_project']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['report_issue']);
	}
	
	/**
	 *
	 */
	final public function Save()
	{
		global $smcFunc;
		
		if (!$this->is_post)
			return false;
		
		if (!$this->Validate())
			return false;
		
		$elm = array();
		
		foreach ($this->elements as $element)
		{
			if ($element instanceof Madjoki_Form_Element_Field)
				$elm[$element->getDataField()] = $element->getValue();
		}
		
		var_dump($elm);
	}
}

?>