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
	public function Main()
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
	public function ListVersions()
	{	
		global $sourcedir, $context, $txt, $project;
		
		$listOptions = array(
			'id' => 'versions_list',
			'base_href' => ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions')),
			'get_items' => array(
				'function' => 'list_getVersions',
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
							return (empty($list_item[\'level\']) ? \'<a href="\' .  ProjectTools::get_url(array(\'project\' => $project, \'area\' => \'admin\', \'sa\' => \'versions\', \'version\' => \'new\', \'parent\' => $list_item[\'id\'])) . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
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
				array(
					'position' => 'bottom_of_list',
					'value' => '
						<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions', 'sa' => 'new')) . '">
							' . $txt['new_version_group'] . '
						</a>',
					'class' => 'catbg',
					'align' => 'right',
				),
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
	public function Edit()
	{	
		global $smcFunc, $sourcedir, $context, $txt, $project;

		if ($_REQUEST['sa'] == 'new')
		{
			$member_groups = array('-1', '0');
	
			$context['version'] = array(
				'is_new' => true,
				'id' => 0,
				'name' => '',
				'description' => '',
				'parent' => !empty($_REQUEST['parent']) && isset($context['versions_id'][$_REQUEST['parent']]) ? $_REQUEST['parent'] : 0,
				'status' => 0,
				'release_date' => array('day' => 0, 'month' => 0, 'year' => 0),
				'permission_inherit' => true,
			);
			
			$context['page_title'] = sprintf($txt['title_versions_new'], ProjectTools_Project::getCurrent()->name);
		}
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT
					v.id_version, v.id_project, v.id_parent, v.version_name,
					v.status, v.member_groups, v.description, v.release_date, v.permission_inherit
				FROM {db_prefix}project_versions AS v
				WHERE id_version = {int:version}
					AND id_project = {int:project}',
				array(
					'version' => (int) $_REQUEST['version'],
					'project' => $project,
				)
			);
	
			if ($smcFunc['db_num_rows']($request) == 0)
				fatal_lang_error('version_not_found', false);
	
			$row = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);
	
			$member_groups = explode(',', $row['member_groups']);
	
			$context['version'] = array(
				'id' => $row['id_version'],
				'name' => htmlspecialchars($row['version_name']),
				'description' => htmlspecialchars($row['description']),
				'parent' => isset($context['versions_id'][$row['id_parent']]) ? $row['id_parent'] : 0,
				'status' => $row['status'],
				'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array('day' => 0, 'month' => 0, 'year' => 0),
				'permission_inherit' => !empty($row['permission_inherit']),
			);
			
			$context['page_title'] = sprintf($txt['title_versions_edit'], ProjectTools_Project::getCurrent()->name, htmlspecialchars($row['version_name']));
		}
	
		// Default membergroups.
		$context['groups'] = array(
			-1 => array(
				'id' => '-1',
				'name' => $txt['guests'],
				'checked' => in_array('-1', $member_groups),
				'is_post_group' => false,
			),
			0 => array(
				'id' => '0',
				'name' => $txt['regular_members'],
				'checked' => in_array('0', $member_groups),
				'is_post_group' => false,
			)
		);
	
		// Load membergroups.
		$request = $smcFunc['db_query']('', '
			SELECT group_name, id_group, min_posts
			FROM {db_prefix}membergroups
			WHERE id_group > 3 OR id_group = 2
			ORDER BY min_posts, id_group != 2, group_name');
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if ($_REQUEST['sa'] == 'new' && $row['min_posts'] == -1)
				$member_groups[] = $row['id_group'];
	
			$context['groups'][(int) $row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => trim($row['group_name']),
				'checked' => in_array($row['id_group'], $member_groups),
				'is_post_group' => $row['min_posts'] != -1,
			);
		}
		$smcFunc['db_free_result']($request);
	
		// Template
		$context['sub_template'] = 'edit_version';
	}
	
	/**
	 *
	 */
	public function Edit2()
	{	
		global $sourcedir, $context, $txt, $project;
		
		checkSession();
	
		$_POST['version'] = (int) $_POST['version'];
	
		if (isset($_POST['edit']) || isset($_POST['add']))
		{
			$versionOptions = array();
	
			$versionOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['version_name']);
			$versionOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);
	
			if (!empty($_POST['parent']))
				$versionOptions['parent'] = $_POST['parent'];
				
			if (!empty($_POST['release_date'][0]))
			{
				$date = (int) $_POST['release_date'][0];
				
				// Note: This is meant to allow 0 as "not decided"
				if ($date < 0 && $date > 31)
					$date = 0;
			}
			else
				$date = 0;
				
			if (!empty($_POST['release_date'][1]))
			{
				$month = (int) $_POST['release_date'][1];
				
				// Note: This is meant to allow 0 as "not decided"
				if ($month < 0 && $month > 12)
					$month = 0;
			}
			else
				$month = 0;
				
			if (!empty($_POST['release_date'][2]))
				$year = (int) $_POST['release_date'][2];
			else
				$year = 0;
				
			// Check that date is really valid
			if (!empty($date) && !empty($month) && !empty($year) && !checkdate($month, $date, $year))
			{
				$date = 0;
				$month = 0;
				$year = 0;
			}
	
			$versionOptions['release_date'] = serialize(array(
				'day' => $date,
				'month' => $month,
				'year' => $year,
			));
	
			$versionOptions['status'] = (int) $_POST['status'];
	
			if ($versionOptions['status'] < 0 || $versionOptions['status'] > 6)
				$versionOptions['status'] = 0;
	
			$versionOptions['member_groups'] = array();
			if (!empty($_POST['groups']))
				foreach ($_POST['groups'] as $group)
					$versionOptions['member_groups'][] = $group;
					
			$versionOptions['permission_inherit'] = !empty($_POST['permission_inherit']);
	
			if (isset($_POST['add']))
				createVersion($project, $versionOptions);
			else
				updateVersion($project, $_POST['version'], $versionOptions);
		}
		elseif (isset($_POST['delete']))
		{
			// Todo: Add confmation
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}project_versions
				WHERE id_version = {int:version}
					AND id_project = {int:project}',
				array(
					'version' => $_POST['version'],
					'project' => $project,
				)
			);
		}
	
		redirectexit(ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')));
	}
}

?>