<?php
/**
 * 
 *
 * @package IssueTracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_Form_ProjectEdit extends Madjoki_Form_Database
{
	/**
	 * 
	 */
	protected $id_field = 'id_project';
	
	/**
	 *
	 */
	protected $tableName = '{db_prefix}projects';
	
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
	protected $error = 'project_not_found';
	
	/**
	 *
	 */
	protected $fields = array(
		'id_project' => array('type' => 'int'),
		'name' => array('type' => 'string'),
		'description' => array('type' => 'string'),
		'long_description' => array('type' => 'string'),
		'trackers' => array('type' => 'string'),
		'modules' => array('type' => 'string'),
		'member_groups' => array('type' => 'string'),
		'id_category' => array('type' => 'int'),
		'cat_position' => array('type' => 'string'),
		'project_theme' => array('type' => 'int'),
		'override_theme' => array('type' => 'int'),
		'id_profile' => array('type' => 'int'),
	);
	
	/**
	 *
	 */
	protected $keyFields = array('id_project');
	
	/**
	 *
	 */
	function addFields()
	{
		global $scripturl, $txt;
		
		if ($this->id === 'new')
		{
			$this->action_url = $scripturl . '?action=admin;area=manageprojects;sa=new';
			new Madjoki_Form_Element_Header($this, $txt['new_project']);
		}
		else
		{
			$this->action_url = $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $this->id;
			new Madjoki_Form_Element_Header($this, $txt['edit_project']);
		}
		
		//
		new Madjoki_Form_Element_Text($this, 'name', $txt['project_name'], new Madjoki_Form_Validator_Text);
		
		//
		$desc = new Madjoki_Form_Element_TextArea(
			$this, 'description', $txt['project_description'], new Madjoki_Form_Validator_BBC
		);
		$desc->setSubtext($txt['project_description_desc']);
		
		//
		$ldesc = new Madjoki_Form_Element_TextArea(
			$this, 'long_description', $txt['project_description_long'], new Madjoki_Form_Validator_BBC
		);
		$ldesc->setSubtext($txt['project_description_long_desc']);
		
		//
		$profile = new Madjoki_Form_Element_Select($this, 'id_profile', $txt['project_profile']);
		foreach (list_getProfiles() as $p)
			$profile->addOption($p['id'], $p['name']);
			
		//
		$theme = new Madjoki_Form_Element_Select($this, 'project_theme', $txt['project_theme']);
		$theme->addOption('0', $txt['project_theme_default']);

		// Get all the themes...
		$request = $smcFunc['db_query']('', '
			SELECT id_theme AS id, value AS name
			FROM {db_prefix}themes
			WHERE variable = {string:name}',
			array(
				'name' => 'name',
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$theme->addOption($row['id'], $row['name']);
		$smcFunc['db_free_result']($request);
		
		// Override theme
		new Madjoki_Form_Element_Check($this, 'override_theme', $txt['project_theme_override']);
		
		//
		new Madjoki_Form_Element_Divider($this);
		new Madjoki_Form_Element_Submit($this);
	}
}

?>