<?php
/**
 *
 * @package LibMadjoki
 * @subpackage Form
 */

/**
 *
 */
class ProjectTools_Form_TrackersElement extends Madjoki_Form_Element_RadioList
{
	/**
	 *
	 */
	public function __construct(ProjectTools_Form_Project $form, $field_name, $text, $data_field = null, $id = null)
	{
		parent::__construct($form, $field_name, $text, $data_field, $id);
		
		$this->options = array();		
		foreach ($form->getProject()->trackers as $id => $tracker)
			$this->options[$id] = $tracker['tracker']['name'];
	}
	
	/**
	 *
	 */
	public function addOption($value, $text)
	{
		trigger_error('Can\'t add to trackers list', E_FATAL_ERROR);
	}

	/**
	 *
	 */
	public function setOptions($array)
	{
		trigger_error('Can\'t add to trackers list', E_FATAL_ERROR);
	}
}

?>