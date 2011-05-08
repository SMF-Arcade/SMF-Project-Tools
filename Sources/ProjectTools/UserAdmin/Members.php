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
	public function Main()
	{
		global $context, $txt;
			
		$subActions = array(
			'main' => array('ProjectTools_UserAdmin_Members', 'ListMembers'),
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
		global $sourcedir, $context, $txt, $project;
		
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
				/*'actions' => array(
					'header' => array(
						'value' => $txt['new_version'],
						'style' => 'width: 16%; text-align: right;',
					),
					'data' => array(
						'function' => create_function('$list_item', '
							global $txt, $project;
							return (empty($list_item[\'level\']) ? \'<a href="\' .  ProjectTools::get_url(array(\'project\' => $project, \'area\' => \'admin\', \'sa\' => \'versions\', \'version\' => \'new\', \'parent\' => $list_item[\'id\'])) . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
						'),
						'style' => 'text-align: right;',
					),
					'sort' => array(
						'default' => 'ver.version_name',
						'reverse' => 'ver.version_name DESC',
					),
				),*/
			),
			'form' => array(
				'href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'members')),
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			/*'additional_rows' => array(
				array(
					'position' => 'bottom_of_list',
					'value' => '
						<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'm', 'sa' => 'new')) . '">
							' . $txt['new_version_group'] . '
						</a>',
					'class' => 'catbg',
					'align' => 'right',
				),
			),*/
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['page_title'] = sprintf($txt['title_members_list'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'members_list';
	}
}