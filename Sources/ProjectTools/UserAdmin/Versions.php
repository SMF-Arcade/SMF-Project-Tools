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
class ProjectTools_UserAdmin_Versions
{
	/**
	 *
	 */
	public static function Main()
	{
		global $context, $txt;
			
		$subActions = array(
			'main' => array('ProjectTools_UserAdmin_Versions', 'ListVersions'),
			'new' => array('ProjectTools_UserAdmin_Versions', 'Edit'),
			'edit' => array('ProjectTools_UserAdmin_Versions', 'Edit'),
			'edit2' => array('ProjectTools_UserAdmin_Versions', 'Edit2'),
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		call_user_func($subActions[$_REQUEST['sa']]);
			
		$context['project_tabs']['description'] = $txt['project_admin_versions_description'];
	}
	
	/**
	 *
	 */
	public static function ListVersions()
	{	
		global $sourcedir, $context, $txt, $project;
		
		$listOptions = array(
			'id' => 'versions_list',
			'base_href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions')),
			'get_items' => array(
				'function' => array('ProjectTools_Admin', 'list_getVersions'),
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
							'format' => '<input type="checkbox" name="versions[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['header_version'],
					),
					'data' => array(
						'function' => create_function('$list_item', '
							return str_repeat(\'&nbsp;\', $list_item[\'level\'] * 5) . $list_item[\'link\'];
						'),
					),
					'sort' => array(
						'default' => 'ver.version_name',
						'reverse' => 'ver.version_name DESC',
					),
				),
				'actions' => array(
					'header' => array(
						'value' => $txt['new_version'],
						'style' => 'width: 16%; text-align: right;',
					),
					'data' => array(
						'function' => create_function('$list_item', '
							global $txt, $project;
							return (empty($list_item[\'level\']) ? \'<a href="\' .  ProjectTools::get_admin_url(array(\'project\' => $project, \'area\' => \'versions\', \'sa\' => \'new\', \'parent\' => $list_item[\'id\'])) . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
						'),
						'style' => 'text-align: right;',
					),
					'sort' => array(
						'default' => 'ver.version_name',
						'reverse' => 'ver.version_name DESC',
					),
				),
			),
			'form' => array(
				'href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions')),
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => array(
					$context['session_var'] => $context['session_id'],
				),
			),
			'additional_rows' => array(
			),
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['page_title'] = sprintf($txt['title_versions_list'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'versions_list';
	}
	
	/**
	 *
	 */
	public static function Edit()
	{	
		global $smcFunc, $sourcedir, $context, $txt, $project;
		
		$context['version_form'] = new ProjectTools_Form_Version(
			isset($_REQUEST['version']) ? (int) $_REQUEST['version'] : null, null, null, array('project' => $project)
		);
		
		if ($context['version_form']->is_post && $context['version_form']->Save())
			redirectexit(ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions')));
			
		// Template
		$context['sub_template'] = 'edit_version';
	}
}

?>