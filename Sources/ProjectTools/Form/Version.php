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
class ProjectTools_Form_Version extends Madjoki_Form_Database
{
	/**
	 * 
	 */
	protected $id_field = 'id_version';
	
	/**
	 *
	 */
	protected $tableName = '{db_prefix}project_versions';
	
	/**
	 *
	 */
	protected $where = array(
	);

	/**
	 *
	 */
	protected $defaultValues = array(
		'permission_inherit' => 1,
	);
	
	/**
	 *
	 */
	protected $error = 'version_not_found';
	
	/**
	 *
	 */
	protected $fields = array(
		'id_version' => array('type' => 'int'),
		'id_project' => array('type' => 'int', 'get_value' => 'getProject'),
		'version_name' => array('type' => 'string'),
		'description' => array('type' => 'string'),
		'status' => array('type' => 'int'),
		'permission_inherit' => array('type' => 'int'),
		'member_groups' => array('type' => 'string'),
		'id_parent' => array('type' => 'int', 'get_value' => 'getParent'),
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
	public function getParent()
	{
		if ($this->id === 'new')
		{
			return isset($_REQUEST['parent']) ? $_REQUEST['parent'] : 0;
		}
		else
			return $this->data['id_parent'];
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
			$this->action_url = ProjectTools::get_admin_url(array('project' => $this->getProject(), 'area' => 'versions', 'sa' => 'new'));
			new Madjoki_Form_Element_Header($this, $txt['new_version']);
		}
		else
		{
			$this->action_url = ProjectTools::get_admin_url(array('project' => $this->getProject(), 'area' => 'versions', 'sa' => 'edit', 'version' => $this->id));
			new Madjoki_Form_Element_Header($this, $txt['edit_version']);
		}
		
		$this->hidden['parent'] = $this->getParent();
		
		//
		new Madjoki_Form_Element_Text($this, 'version_name', $txt['version_name'], new Madjoki_Form_Validator_Text());
		
		//
		$version_desc = new Madjoki_Form_Element_TextArea($this, 'description', $txt['version_description'], new Madjoki_Form_Validator_BBC());
		$version_desc->setSubtext($txt['version_description_desc']);
		
		//
		$status = new Madjoki_Form_Element_Select($this, 'status', $txt['version_status']);
		$status->setOptions(
			array($txt['version_future'], $txt['version_testing'], $txt['version_current'], $txt['version_obsolete'])
		);
		
		$inherit = new Madjoki_Form_Element_Check($this, 'permission_inherit', $txt['version_inherit_permission']);
		$inherit->addJS('click', 'refreshOptions();');
		
		$this->addJS('
		function refreshOptions()
		{
			var inheritEnabled = document.getElementById("permission_inherit").checked;

			// What to show?
			document.getElementById("dt_member_groups").style.display = inheritEnabled ? "none" : "";
			document.getElementById("dd_member_groups").style.display = inheritEnabled ? "none" : "";
		}
		refreshOptions();');
		
		$memg = new Madjoki_Form_Element_MemberGroups($this, 'member_groups', $txt['version_membergroups']);
		$memg->setSubtext($txt['version_membergroups_desc']);
		
		$date = new ProjectTools_Form_DateElement($this, 'release_date', $txt['version_release_date']);
		$date->setSubtext($txt['version_release_date_desc']);
		
		//
		new Madjoki_Form_Element_Divider($this);
		
		if ($this->id !== 'new')
			new Madjoki_Form_Element_Submit($this, $txt['edit_version']);
		else
			new Madjoki_Form_Element_Submit($this, $txt['new_version']);
	}
	
	/**
	 *
	 */
	protected function onNew($id, $data)
	{
		global $smcFunc;
		
		if ($data['permission_inherit'])
		{
			if ($this->getParent() == 0)
			{
				$request = $smcFunc['db_query']('', '
					SELECT member_groups
					FROM {db_prefix}projects
					WHERE id_project = {int:project}',
					array(
						'project' => $this->getProject(),
					)
				);
				list ($member_groups) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);
				
			}
			else
			{
				$request = $smcFunc['db_query']('', '
					SELECT member_groups
					FROM {db_prefix}project_versions
					WHERE id_version = {int:version}',
					array(
						'version' => $this->getParent(),
					)
				);
				list ($member_groups) = $smcFunc['db_fetch_row']($request);
				$smcFunc['db_free_result']($request);				
			}
			
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}project_versions
				SET member_groups = {string:groups}
				WHERE id_version = {int:version}',
				array(
					'groups' => $member_groups,
					'version' => $id,
				)
			);
		}
		
		cache_put_data('project-' . $this->getProject(), null, 120);
		cache_put_data('project-version-' . $this->getProject(), null, 120);
	}
	
	/**
	 *
	 */
	protected function onUpdated($id, $data)
	{
		global $smcFunc;
		
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_versions
			SET member_groups = {string:groups}
			WHERE id_parent = {int:version}
				AND permission_inherit = 1',
			array(
				'groups' => $data['member_groups'],
				'version' => $id,
			)
		);
		
		cache_put_data('project-' . $this->getProject(), null, 120);
		cache_put_data('project-version-' . $this->getProject(), null, 120);
	}
}

?>