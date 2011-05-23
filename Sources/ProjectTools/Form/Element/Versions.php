<?php
/**
 *
 * @package LibMadjoki
 * @subpackage Form
 */

/**
 *
 */
class ProjectTools_Form_Element_Versions extends Madjoki_Form_Element_CheckList
{
	/**
	 *
	 */
	public function __construct(ProjectTools_Form_Project $form, $field_name, $text, $data_field = null, $id = null, $sep = ',')
	{
		parent::__construct($form, $field_name, $text, $data_field, $id, $sep);
		
		$this->options = array();
		foreach ($form->getProject()->versions as $id => $ver)
		{
			$this->options[$id] = array($ver['name'], 0);
			
			foreach ($ver['sub_versions'] as $subid => $subver)
				$this->options[$subid] = array($subver['name'], 1);
		}
	}
	
	/**
	 *
	 */
	public function addOption($value, $text)
	{
		trigger_error('Can\'t add to versions list', E_FATAL_ERROR);
	}

	/**
	 *
	 */
	public function setOptions($array)
	{
		trigger_error('Can\'t add to versions list', E_FATAL_ERROR);
	}
	
	/**
	 *
	 */
	protected function render_input($value, $version_info)
	{
		global $context;
		static $i = 1;
		
		list ($name, $level) = $version_info;
		
		echo '
		
		', str_repeat('&nbsp;&nbsp;&nbsp; ', $level), '<input type="checkbox" name="', $this->field_name, '[]" id="', $this->id, '_', $i, '" value="', $value, '"',
			in_array($value, $this->value) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" />
			<label for="', $this->id, '_', $i, '">', $name, '</label>
		<br />';
		
		$i++;		
	}
}

?>