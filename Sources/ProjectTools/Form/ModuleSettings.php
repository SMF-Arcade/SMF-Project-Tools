<?php
/**
 *
 * 
 * @package ProjectTools
 * @subpackage Afmin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Form_ModuleSettings extends ProjectTools_Form_Settings
{
	/**
	 *
	 */
	protected $name = 'ModuleSettings';
	
	/**
	 *
	 */
	public function addFields()
	{
		global $txt;
		
		new Madjoki_Form_Element_Header($this, $txt['pt_ua_modules']);
		
		//
		$modules = new ProjectTools_Form_ModulesElement($this, 'modules', $txt['pt_ua_modules']);
		$modules->setSubtext($txt['pt_ua_modules_desc']);
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		new Madjoki_Form_Element_Submit($this, $txt['save']);
	}
}

?>