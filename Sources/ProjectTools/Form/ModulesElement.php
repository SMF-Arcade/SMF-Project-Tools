<?php
/**
 *
 * @package LibMadjoki
 * @subpackage Form
 */

/**
 *
 */
class ProjectTools_Form_ModulesElement extends Madjoki_Form_Element_CheckList
{
	/**
	 *
	 */
	public function __construct(Madjoki_Form_Base $form, $field_name, $text, $data_field = null, $id = null, $sep = ',')
	{
		parent::__construct($form, $field_name, $text, $data_field, $id, $sep);
		
		$this->options = array();
		foreach (ProjectTools_Extensions::getInstalledExtensions() as $id => $ext)
			$this->options[$id] = $ext['name'];
	}
	
	/**
	 *
	 */
	public function addOption($value, $text)
	{
		trigger_error('Can\'t add to membergroups list', E_FATAL_ERROR);
	}

	/**
	 *
	 */
	public function setOptions($array)
	{
		trigger_error('Can\'t add to membergroups list', E_FATAL_ERROR);
	}
	
	/**
	 *
	 */
	/*protected function render_input($value, $text)
	{
		global $context;
		static $i = 1;
		
		echo '
		<input type="checkbox" name="', $this->field_name, '[]" id="', $this->id, '_', $i, '" value="', $value, '"',
			in_array($value, $this->value) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" />
		<label for="', $this->id, '_', $i, '">', $text['name'], '</label>
		<br />';
		
		$i++;		
	}*/
}

?>