<?php
/**
 *
 * @package LibMadjoki
 * @subpackage Form
 */

/**
 *
 */
class ProjectTools_Form_DateElement extends Madjoki_Form_Element_Field
{
	/**
	 *
	 */
	protected function setupValue()
	{
		// Use value from post
		if ($this->form->is_post && isset($_POST[$this->field_name]))
			$this->value = $_POST[$this->field_name];
		elseif (!$this->form->is_post)
			$this->value = unserialize($this->form->getValue($this->data_field));
		else
			$this->value = array('day' => 0, 'month' => 0, 'year' => 0);		
	}
	
	/**
	 *
	 */
	public function getValue()
	{
		return serialize($this->value);
	}
	
	/**
	 *
	 */
	public function render_field()
	{
		global $context;
		
		echo '
		<input type="text" name="', $this->field_name, '[day]"  value="', $this->value['day'], '" size="3" tabindex="', $context['tabindex']++, '" />.
		<input type="text" name="', $this->field_name, '[month]" value="', $this->value['month'], '" size="3" tabindex="', $context['tabindex']++, '" />.
		<input type="text" name="', $this->field_name, '[year]" value="', $this->value['year'], '" size="5" tabindex="', $context['tabindex']++, '" />';
	}
}

?>