<?php
/**
 *
 * 
 * @package ProjectTools
 * @subpackage Form
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 * 
 */
abstract class ProjectTools_Form_Project extends Madjoki_Form_Base
{
	/**
	 * @var ProjectTools_Project
	 */
	protected $project;
	
	public function __construct($project, $is_fatal = true, $is_post = null)
	{
		if (is_numeric($project))
			$this->project = ProjectTools_Project::getProject($project);
		elseif ($project instanceof ProjectTools_Project)
			$this->project = $project;
		else
			trigger_error('Invalid project', E_FATAL_ERROR);
			
		if (!$this->project || !$this->project->canAccess())
			fatal_lang_error('project_not_found');
		
		parent::__construct($is_post);
	}
	
	
	/**
	 * Returns project assigned to this form
	 * 
	 * @return ProjectTools_Project
	 */
	function getProject()
	{
		return $this->project;
	}
}

?>