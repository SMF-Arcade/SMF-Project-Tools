<?php
/**
 * Main handler for Project Tools User Admin
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Project Admin
 */
class ProjectTools_UserAdmin_Members
{
	/**
	 *
	 */
	public static function Main()
	{
		global $context, $txt;
			
		$subActions = array(
			'main' => array('ProjectTools_UserAdmin_Members', 'ListMembers'),
			'add' => array('ProjectTools_UserAdmin_Members', 'AddMembers'),
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		//
		loadTemplate('ProjectTools/UserAdmin');
			
		call_user_func($subActions[$_REQUEST['sa']]);
			
		$context['project_tabs']['description'] = $txt['pt_ua_members_desc'];
	}
	
	/**
	 *
	 */
	public function ListMembers()
	{	
		global $sourcedir, $context, $txt, $project, $user_info, $smcFunc;
		
		// Delete members
		if (!empty($_POST['delete_members']) && !empty($_POST['members']))
		{
			$toRemove = array();
			
			foreach ($_POST['members'] as $member)
			{
				$member = (int) $member;
				
				if (allowedTo('project_admin') || $user_info['id'] != $member)
					$toRemove[] = $member;
			}
			
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}project_developer
				WHERE id_project = {int:project}
					AND id_member IN({array_int:to_remove})',
				array(
					'project' => $project,
					'to_remove' => $toRemove,
				)
			);
		}
		
		$listOptions = array(
			'id' => 'members_list',
			'base_href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'members')),
			'get_items' => array(
				'function' => array('ProjectTools_Admin', 'list_getMembers'),
				'params' => array(
					$project,
				),
			),
			'columns' => array(
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
						'style' => 'width: 4%;',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="members[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['pt_user_name'],
					),
					'data' => array(
						'sprintf' => array(
							'format' => '%1$s',
							'params' => array(
								'link' => false,
							),
						),
					),
					'sort' => array(
						'default' => 'mem.real_name',
						'reverse' => 'mem.real_name DESC',
					),
				),
			),
			'form' => array(
				'href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'members')),
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '<input type="submit" name="delete_members" value="' . $txt['pt_delete_members'] . '" class="button_submit" />',
					'style' => 'text-align: right;',
				),
			),
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['page_title'] = sprintf($txt['title_members_list'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'members_list';
	}
	
	/**
	 *
	 */
	public function AddMembers()
	{
		global $sourcedir, $context, $txt, $project, $smcFunc;
		
		$rows = array();

		foreach ($_POST['member_container'] as $id_member)
			if (!empty($id_member))
				$rows[] = array(ProjectTools_Project::getCurrent()->id, (int) $id_member);

		$smcFunc['db_insert']('ignore',
			'{db_prefix}project_developer',
			array(
				'id_project' => 'int',
				'id_member' => 'int',
			),
			$rows,
			array('id_project', 'id_member')
		);	
		
		redirectexit(ProjectTools::get_admin_url(array('project' => $project, 'area' => 'members')));
	}
	
}