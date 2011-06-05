<?php
/**
 *
 * @package LibMadjoki
 * @subpackage Form
 */

/**
 * Base Class for Project Settings froms
 */
abstract class ProjectTools_Form_Settings extends Madjoki_Form_Base
{
	/**
	 * Form name for extending
	 */
	protected $name = 'default';
	
	/**
	 * 
	 * @var ProjectTools_Project
	 */
	protected $project;
		
	/**
	 *
	 */
	final public function __construct(ProjectTools_Project $project, $is_post = null)
	{
		$this->project = $project;
		
		if ($is_post == null)
			$this->is_post = !empty($_POST['save']);
		else
			$this->is_post = $is_post;
			
		if ($this->is_post)
			checkSession('post', '');
			
		
		$this->addFields();
		
		//
		ProjectTools_Extensions::runProjectHooks('ExtendProjectSettingsForm', array($this->name, &$this));
		
		parent::__construct($is_post);
	}
	
	/**
	 *
	 */
	public function getValue($data_field)
	{
		return $this->project->getSetting($data_field);
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
		
		$settings = array();
		
		foreach ($this->elements as $element)
		{
			if ($element instanceof Madjoki_Form_Element_Field)
				$settings[$element->getDataField()] = $element->getValue();
		}
		
		$this->project->updateSettings($settings);
	}
	
	/**
	 *
	 */
	abstract function addFields();
}

?>