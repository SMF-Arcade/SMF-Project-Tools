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
	public function addFields()
	{
		global $scripturl, $txt, $smcFunc;
		
		if ($this->id === 'new')
		{
			$this->action_url = $scripturl . '?action=admin;area=manageprojects;sa=new';
			new Madjoki_Form_Element_Header($this, $txt['new_project']);
		}
		else
		{
			if (defined('PT_IN_ADMIN'))
			{
				$this->action_url = $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $this->id;
			}
			else
			{
				$this->action_url = ProjectTools::get_admin_url(array('project' => $this->id));
			}
			
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
			
		// Theme
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
		
		// Load Board Categories
		$options = array(0 => $txt['project_board_index_dont_show']);
	
		$request = $smcFunc['db_query']('', '
			SELECT id_cat, name
			FROM {db_prefix}categories
			ORDER BY cat_order');
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$options[$row['id_cat']] = $row['name'];
		$smcFunc['db_free_result']($request);
		
		//
		$categories = new Madjoki_Form_Element_Select($this, 'id_category', $txt['project_board_index']);
		$categories->setSubtext($txt['project_board_index_desc']);
		$categories->setOptions($options);
		
		//
		$pos = new Madjoki_Form_Element_Select($this, 'cat_position', $txt['project_board_position']);
		$pos->addOption('first', $txt['project_board_index_before']);
		$pos->addOption('last', $txt['project_board_index_after']);
		
		// Permissions editor
		new Madjoki_Form_Element_Header($this, $txt['project_permissions']);
		
		// Permissions Profile
		if (defined('PT_IN_ADMIN'))
		{
			$profile = new Madjoki_Form_Element_Select($this, 'id_profile', $txt['project_profile']);
			foreach (ProjectTools_Admin::list_getProfiles() as $p)
				$profile->addOption($p['id'], $p['name']);
		}
			
		//
		$memgroups = new Madjoki_Form_Element_MemberGroups($this, 'member_groups', $txt['project_membergroups']);
		$memgroups->setSubtext($txt['project_membergroups_desc']);
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($this->id !== 'new')
			new Madjoki_Form_Element_Submit($this, $txt['edit_project']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['new_project']);
	}
	
	/**
	 *
	 */
	protected function onUpdated($data)
	{
		if (isset($data['member_groups']))
		{
			// Update versions with permission inherited
			$request = $smcFunc['db_query']('', '
				SELECT id_version
				FROM {db_prefix}project_versions
				WHERE id_project = {int:project}
					AND permission_inherit = {int:inherit}
					AND id_parent = {int:no_parent}',
				array(
					'project' => $data['id_field'],
					'inherit' => 1,
					'no_parent' => 0,
				)
			);
			while ($row = $smcFunc['db_fetch_assoc']($request))
				updateVersion($data['id_field'], $row['id_version'], array('member_groups' => explode(',', $data['member_groups'])));
			$smcFunc['db_free_result']($request);
		}
		
		cache_put_data('project-' . $data['id_field'], null, 120);
		cache_put_data('project-version-' . $data['id_field'], null, 120);
	}
}

?>