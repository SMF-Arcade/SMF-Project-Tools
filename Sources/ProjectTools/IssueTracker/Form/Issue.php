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
class ProjectTools_IssueTracker_Form_Issue extends ProjectTools_Form_Project
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
		
		parent::__construct((int) $id_project, $is_fatal, $is_post);
		
		//
		if ($id_issue !== null)
		{
			$this->issue = ProjectTools_IssueTracker_Issue::getIssue($id_issue);
			new Madjoki_Form_Element_Header($this, sprintf($txt['edit_issue'], $this->issue->id, $this->issue->name));
		}
		else
		{
			$this->issue = ProjectTools_IssueTracker_Issue::getNew(ProjectTools_Project::getCurrent());
			new Madjoki_Form_Element_Header($this, $txt['report_issue']);
		}
		//
		$this->data = $this->issue->getData();
		
		$textValidator = new Madjoki_Form_Validator_Text(array('no_empty' => true));
		
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
		new Madjoki_Form_Element_BBCEditor($this, 'details', '', new Madjoki_Form_Validator_BBC(array('no_empty' => true)));
		
		$this->saveEntities = array('title', 'details');
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($id_issue !== null)
			new Madjoki_Form_Element_Submit($this, $txt['save']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['report']);
	}
	
	/**
	 *
	 */
	final public function Save()
	{
		global $smcFunc, $user_info;
		
		if (!$this->is_post)
			return false;
		
		if (!$this->Validate())
			return false;
		
		$issueOptions = array();
		
		foreach ($this->elements as $element)
		{
			if ($element instanceof Madjoki_Form_Element_Field)
				$issueOptions[$element->getDataField()] = $element->getValue();
		}
		
		$_POST['guestname'] = $user_info['username'];
		$_POST['email'] = $user_info['email'];
		
		$posterOptions = array(
			'id' => $user_info['id'],
			'ip' => $user_info['ip'],
			'name' => $user_info['is_guest'] ? $_POST['guestname'] : $user_info['name'],
			'username' => $_POST['guestname'],
			'email' => $_POST['email'],
		);
		
		$this->issue->Save($issueOptions, $posterOptions);
		
		return $this->issue->id;
	}
}

?>