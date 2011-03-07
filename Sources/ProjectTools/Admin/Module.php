<?php
/**
 * Admin pages for Projects 
 *
 * @package ProjectTools
 * @subpackage Admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Project Module Admin
 */
class ProjectTools_Admin_Module extends ProjectTools_ModuleBase
{
	/**
	 *
	 */
	public function Main()
	{
		global $sourcedir, $context, $project, $txt;
		
		require_once($sourcedir . '/Subs-ProjectAdmin.php');
		
		loadTemplate('ProjectModule-Admin');
		loadLanguage('ProjectAdmin');
		
		/*$this->subTabs = array(
			'main' => array(
				'href' => project_get_url(array('project' => $project, 'area' => 'admin')),
				'title' => $txt['project'],
				'is_selected' => false,
				'order' => 'first',
				'hide_linktree' => true,
			),
			'versions' => array(
				'href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')),
				'title' => $txt['manage_versions'],
				'is_selected' => false,
				'order' => 10,
			),
			'category' => array(
				'href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
				'title' => $txt['manage_project_category'],
				'is_selected' => false,
				'order' => 10,
			),
		);*/
		
		$subActions = array(
			'main' => array($this, 'ProjectAdminMain'),
			'versions' => array($this, 'ProjectAdminVersions'),
			'category' => array($this, 'ProjectAdminCategory'),
			
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		call_user_func($subActions[$_REQUEST['sa']], $this->project);
	}
	
	/**
	 *
	 */
	public function RegisterArea()
	{
		global $txt;
		
		return array(
			'id' => 'admin',
			'title' => $txt['project_admin'],
			'callback' => 'Main',
			'hide_linktree' => true,
			'order' => 50,
			'project_permission' => 'admin',
		);
	}
	
	/**
	 *
	 */
	public function ProjectAdminMain()
	{
		global $context, $txt;
		
		$context['page_title'] = $txt['title_project_admin'];
	}
	
	/**
	 *
	 */
	public function ProjectAdminVersions()
	{
		global $context, $txt;
		
		if (!isset($_REQUEST['version']))
			$this->ProjectAdminVersionList();
		elseif (isset($_REQUEST['save']))
			$this->ProjectAdminVersionEdit2();
		else
			$this->ProjectAdminVersionEdit();
			
		$context['project_tabs']['description'] = $txt['project_admin_versions_description'];
	}
	
	/**
	 *
	 */
	public function ProjectAdminVersionList()
	{	
		global $sourcedir, $context, $txt, $project;
		
		$listOptions = array(
			'id' => 'versions_list',
			'base_href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')),
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
							return (empty($list_item[\'level\']) ? \'<a href="\' .  project_get_url(array(\'project\' => $project, \'area\' => \'admin\', \'sa\' => \'versions\', \'version\' => \'new\', \'parent\' => $list_item[\'id\'])) . \'">\' . $txt[\'new_version\'] . \'</a>\' : \'\');
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
				'href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')),
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
						<a href="' . project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions', 'version' => 'new')) . '">
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
	public function ProjectAdminVersionEdit()
	{	
		global $smcFunc, $sourcedir, $context, $txt, $project;

		if ($_REQUEST['version'] == 'new')
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
	public function ProjectAdminVersionEdit2()
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
	
		redirectexit(project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'versions')));
	}
	
	/**
	 *
	 */
	public function ProjectAdminCategory()
	{
		global $txt;
		
		if (!isset($_REQUEST['category']))
			$this->ProjectAdminCategoryList();
		elseif (isset($_REQUEST['save']))
			$this->ProjectAdminCategoryEdit2();
		else
			$this->ProjectAdminCategoryEdit();
			
		$context['project_tabs']['description'] = $txt['project_admin_category_description'];
	}
	
	/**
	 *
	 */
	public function ProjectAdminCategoryList()
	{
		global $sourcedir, $context, $txt, $project;

		$listOptions = array(
			'id' => 'categories_list',
			'base_href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
			'get_items' => array(
				'function' => 'list_getCategories',
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
							'format' => '<input type="checkbox" name="categories[]" value="%1$d" class="check" />',
							'params' => array(
								'id' => false,
							),
						),
						'style' => 'text-align: center;',
					),
				),
				'name' => array(
					'header' => array(
						'value' => $txt['header_category'],
					),
					'data' => array(
						'db' => 'link',
					),
					'sort' => array(
						'default' => 'cat.category_name',
						'reverse' => 'cat.category_name DESC',
					),
				),
			),
			'form' => array(
				'href' => project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category')),
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
						<a href="' . project_get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category', 'category' => 'new')) . '">
							' . $txt['new_category'] . '
						</a>',
					'class' => 'catbg',
					'align' => 'right',
				),
			),
		);
	
		require_once($sourcedir . '/Subs-List.php');
		createList($listOptions);
	
		// Template
		$context['page_title'] = sprintf($txt['title_category_list'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'categories_list';
	}
	
	/**
	 *
	 */
	function ProjectAdminCategoryEdit()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		if ($_REQUEST['category'] == 'new')
		{
			$context['category'] = array(
				'is_new' => true,
				'id' => 0,
				'name' => '',
			);
			
			$context['page_title'] = sprintf($txt['title_category_new'], ProjectTools_Project::getCurrent()->name);
		}
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_category, id_project, category_name
				FROM {db_prefix}issue_category
				WHERE id_category = {int:category}
					AND id_project = {int:project}',
				array(
					'category' => (int) $_REQUEST['category'],
					'project' => $project,
				)
			);
			$row = $smcFunc['db_fetch_assoc']($request);
			$smcFunc['db_free_result']($request);
	
			if (!$row)
				fatal_lang_error('category_not_found');
	
			$context['category'] = array(
				'id' => $row['id_category'],
				'name' => htmlspecialchars($row['category_name']),
			);
			
			$context['page_title'] = sprintf($txt['title_category_edit'], ProjectTools_Project::getCurrent()->name, htmlspecialchars($row['category_name']));
	
			unset($row);
		}
	
		if (!isset($_REQUEST['delete']))
		{
			$context['sub_template'] = 'edit_category';
	
			if (!empty($context['category']['is_new']))
				$context['page_title'] = $txt['new_category'];
			else
				$context['page_title'] = $txt['edit_category'];
		}
		else
		{
			$context['sub_template'] = 'confirm_category_delete';
			$context['page_title'] = $txt['confirm_category_delete'];
		}
	}
	
	/**
	 *
	 */
	function ProjectAdminCategoryEdit2()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt, $project;
	
		checkSession();
	
		$_POST['category'] = (int) $_POST['category'];
	
		if (isset($_POST['edit']) || isset($_POST['add']))
		{
			$categoryOptions = array();
	
			$categoryOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['category_name']);
	
			if (isset($_POST['add']))
				createPTCategory($project, $categoryOptions);
			else
				updatePTCategory($project, $_POST['category'], $categoryOptions);
		}
		elseif (isset($_POST['delete']))
		{
			$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}issue_category
				WHERE id_category = {int:category}
					AND id_project = {int:project}',
				array(
					'category' => $_POST['category'],
					'project' => $project,
				)
			);
		}
	
		redirectexit('action=admin;area=manageprojects;section=categories');
	}
}

?>