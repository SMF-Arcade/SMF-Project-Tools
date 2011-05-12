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
class ProjectTools_Form_Category extends Madjoki_Form_Database
{
	/**
	 * 
	 */
	protected $id_field = 'id_category';
	
	/**
	 *
	 */
	protected $tableName = '{db_prefix}issue_category';
	
	/**
	 *
	 */
	protected $where = array(
	);

	/**
	 *
	 */
	protected $defaultValues = array(
	);
	
	/**
	 *
	 */
	protected $error = 'category_not_found';
	
	/**
	 *
	 */
	protected $fields = array(
		'id_category' => array('type' => 'int'),
		'id_project' => array('type' => 'int', 'get_value' => 'getProject'),
		'category_name' => array('type' => 'string'),
	);
	
	/**
	 *
	 */
	protected $keyFields = array('id_version');
	
	/**
	 *
	 */
	public function getProject()
	{
		return $this->extra['project'];
	}

	/**
	 *
	 */
	public function addFields()
	{
		global $scripturl, $txt, $context, $smcFunc;

		if (!isset($this->extra['project']))
			trigger_error('Project not specified', E_FATAL_ERROR);
		
		if ($this->id === 'new')
		{
			$this->action_url = ProjectTools::get_admin_url(array('project' => $this->getProject(), 'area' => 'categories', 'sa' => 'new'));
			new Madjoki_Form_Element_Header($this, $txt['new_category']);
		}
		else
		{
			$this->action_url = ProjectTools::get_admin_url(array('project' => $this->getProject(), 'area' => 'categories', 'sa' => 'edit', 'category' => $this->id));
			new Madjoki_Form_Element_Header($this, $txt['edit_category']);
		}
		
		//
		new Madjoki_Form_Element_Text($this, 'category_name', $txt['category_name'], new Madjoki_Form_Validator_Text());

		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($this->id !== 'new')
			new Madjoki_Form_Element_Submit($this, $txt['edit_category']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['new_category']);
	}
	
	/**
	 *
	 */
	protected function onNew($id, $data)
	{
		global $smcFunc;
		
		cache_put_data('project-' . $this->getProject(), null, 120);
		cache_put_data('project-version-' . $this->getProject(), null, 120);
	}
	
	/**
	 *
	 */
	protected function onUpdated($id, $data)
	{
		global $smcFunc;
		
		cache_put_data('project-' . $this->getProject(), null, 120);
		cache_put_data('project-version-' . $this->getProject(), null, 120);
	}
}

?>