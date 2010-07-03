<?php
/**
 * Manage Project
 *
 * @package admin
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function ManageProjects()
{
	global $context, $sourcedir, $user_info, $txt;

	require_once($sourcedir . '/Subs-Project.php');

	isAllowedTo('project_admin');
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_projects'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_projects_description'];

	$context['page_title'] = $txt['manage_projects'];

	$sections = array(
		// Projects
		'project' => array(
			'id' => 'project',
			'template' => 'ManageProjects',
			'subActions' => array(
				'list' => array('ManageProjectsList', 'list'),
				'new' => array('EditProject', 'new'),
				'edit' => array('EditProject', 'list'),
				'edit2' => array('EditProject2', 'list'),
			),
		),
		// Versions
		/*'versions' => array(
			'id' => 'versions',
			'template' => 'ManageProjects',
			'subActions' => array(
				'list' => array('ManageVersionsList'),
				'new' => array('EditVersion'),
				'edit' => array('EditVersion'),
				'edit2' => array('EditVersion2'),
			),
		),*/
		// Categories
		'categories' => array(
			'id' => 'categories',
			'template' => 'ManageProjects',
			'subActions' => array(
				'list' =>  array('ManageCategoriesList'),
				'new' => array('EditCategory'),
				'edit' => array('EditCategory'),
				'edit2' => array('EditCategory2'),
			),
		),
	);

	$section = 'project';

	if (isset($_REQUEST['sa']) && isset($sections[$_REQUEST['sa']]))
		$section = $_REQUEST['sa'];
	elseif (isset($_REQUEST['section']) && isset($sections[$_REQUEST['section']]))
		$section = $_REQUEST['section'];

	$section = $sections[$section];
	$subActions = $section['subActions'];

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];
	else
		$context[$context['admin_menu_name']]['current_subsection'] = $section['id'];

	// Load file if needed
	if (!empty($section['file']))
		require_once($sourcedir . '/' . $section['file']);

	// Load template if needed
	if (!empty($section['template']))
		loadTemplate($section['template']);

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageProjectsList()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	$listOptions = array(
		'id' => 'projects_list',
		'base_href' => $scripturl . '?action=admin;area=manageprojects',
		'get_items' => array(
			'function' => 'list_getProjects',
		),
		'default_sort_col' => 'name',
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="projects[]" value="%1$d" class="check" />',
						'params' => array(
							'id' => false,
						),
					),
					'style' => 'text-align: center;',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['header_project'],
				),
				'data' => array(
					'db' => 'link',
				),
				'sort' => array(
					'default' => 'p.name',
					'reverse' => 'p.name DESC',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=manageprojects',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				$context['session_var'] => $context['session_id'],
			),
		),
		'no_items_label' => $txt['no_projects'],
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'projects_list';
}

function EditProject()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	$_REQUEST['project'] = isset($_REQUEST['project']) ? (int) $_REQUEST['project'] : 0;
	if (!isset($context['project']) && empty($_REQUEST['project']) || !$project = loadProjectAdmin($_REQUEST['project']))
		$_REQUEST['sa'] = 'new';

	$context['profiles'] = list_getProfiles();

	if ($_REQUEST['sa'] == 'new')
	{
		$curProject = array(
			'member_groups' => array('-1', '0'),
		);

		$context['project'] = array(
			'is_new' => true,
			'id' => 0,
			'name' => '',
			'description' => '',
			'long_description' => '',
			'profile' => 1,
			'trackers' => array_keys($context['issue_trackers']),
			'modules' => array('general', 'admin'),
			'developers' => array(),
			'theme' => 0,
			'override_theme' => false,
			'category' => 0,
			'category_position' => '',
		);
	}
	else
	{
		$curProject = array(
			'member_groups' => $project['groups'],
		);

		$context['project'] = array(
			'id' => $project['id'],
			'name' => htmlspecialchars($project['name']),
			'description' => htmlspecialchars($project['description']),
			'long_description' => htmlspecialchars($project['long_description']),
			'profile' => $project['profile'],
			'trackers' => array_keys($project['trackers']),
			'modules' => $project['modules'],
			'groups' => $project['groups'],
			'developers' => $project['developers'],
			'theme' => $project['theme'],
			'override_theme' => $project['override_theme'],
			'category' => $project['id_category'],
			'category_position' => $project['category_position'],
		);
	}
	
	// Load Possible modules
	$context['installed_modules'] = getInstalledModules();

	// Default membergroups.
	$context['groups'] = array(
		-1 => array(
			'id' => '-1',
			'name' => $txt['guests'],
			'checked' => in_array('-1', $curProject['member_groups']),
			'is_post_group' => false,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'checked' => in_array('0', $curProject['member_groups']),
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
			$curProject['member_groups'][] = $row['id_group'];

		$context['groups'][(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'checked' => in_array($row['id_group'], $curProject['member_groups']),
			'is_post_group' => $row['min_posts'] != -1,
		);
	}

	$smcFunc['db_free_result']($request);

	// Load Board Categories
	$context['board_categories'] = array();

	$request = $smcFunc['db_query']('', '
		SELECT id_cat, name
		FROM {db_prefix}categories
		ORDER BY cat_order');

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['board_categories'][] = array(
			'id' => $row['id_cat'],
			'name' => $row['name'],
		);
	$smcFunc['db_free_result']($request);

	// Get all the themes...
	$request = $smcFunc['db_query']('', '
		SELECT id_theme AS id, value AS name
		FROM {db_prefix}themes
		WHERE variable = {string:name}',
		array(
			'name' => 'name',
		)
	);
	$context['themes'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$context['themes'][] = $row;
	$smcFunc['db_free_result']($request);

	if (!isset($_REQUEST['delete']))
	{
		$context['sub_template'] = 'edit_project';

		if (!empty($context['project']['is_new']))
			$context['page_title'] = $txt['new_project'];
		else
			$context['page_title'] = $txt['edit_project'];
	}
	else
	{
		$context['sub_template'] = 'confirm_project_delete';
		$context['page_title'] = $txt['confirm_project_delete'];
	}
}

function EditProject2()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	checkSession();

	$_POST['project'] = (int) $_POST['project'];

	if (!empty($_POST['project']) && !$project = loadProjectAdmin($_POST['project']))
		fatal_lang_error('project_not_found', false);
		
	// Load Possible modules
	$context['installed_modules'] = getInstalledModules();

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$projectOptions = array();

		$projectOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['project_name']);
		$projectOptions['description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['desc']);
		$projectOptions['long_description'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['long_desc']);

		$projectOptions['category'] = (int) $_POST['category'];
		$projectOptions['category_position'] = $_POST['category_position'];

		$projectOptions['project_theme'] = (int) $_POST['project_theme'];
		$projectOptions['override_theme'] = !empty($_POST['override_theme']);

		$projectOptions['profile'] = (int) $_POST['project_profile'];

		$projectOptions['trackers'] = array();
		if (!empty($_POST['trackers']))
			foreach ($_POST['trackers'] as $tracker)
				if (isset($context['issue_trackers'][$tracker]))
					$projectOptions['trackers'][] = $tracker;

		$projectOptions['modules'] = array();
		if (!empty($_POST['modules']))
			foreach ($_POST['modules'] as $module)
				if (isset($context['installed_modules'][$module]))
					$projectOptions['modules'][] = $module;
					
		$projectOptions['member_groups'] = array();
		if (!empty($_POST['groups']))
			foreach ($_POST['groups'] as $group)
				$projectOptions['member_groups'][] = $group;

		if (count($projectOptions['trackers']) == 0)
			fatal_lang_error('no_issue_types', false);

		if (!isset($_POST['developer_list']) || !is_array($_POST['developer_list']))
			$_POST['developer_list'] = array();

		$projectOptions['developers'] = $_POST['developer_list'];

		if (isset($_POST['add']))
			$_POST['project'] = createProject($projectOptions);
		else
			updateProject($_POST['project'], $projectOptions);
	}
	elseif (isset($_POST['delete']) && !isset($_POST['confirmation']))
	{
		EditProject();
		return;
	}
	elseif (isset($_POST['delete']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}projects
			WHERE id_project = {int:project}
			LIMIT 1',
			array(
				'project' => $_POST['project']
			)
		);
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_versions
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project']
			)
		);
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issues
			WHERE id_project = {int:project}',
			array(
				'project' => $_POST['project']
			)
		);
	}

	redirectexit('action=admin;area=manageprojects');
}

function ManageCategories()
{
	global $context, $sourcedir, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_category'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_category_description'];

	$context['page_title'] = $txt['manage_project_category'];

	$subActions = array(
		'list' => array('ManageCategoriesList'),
		'new' => array('EditCategory'),
		'edit' => array('EditCategory'),
		'edit2' => array('EditCategory2'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'list';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageCategoriesList()
{
	global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;

	list ($id_project, $projectsHtml) = loadAdminProjects();

	$listOptions = array(
		'id' => 'categories_list',
		'base_href' => $scripturl . '?action=admin;area=manageprojects;section=categories',
		'get_items' => array(
			'function' => 'list_getCategories',
			'params' => array(
				$id_project,
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
			'href' => $scripturl . '?action=admin;area=manageprojects;section=categories',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				$context['session_var'] => $context['session_id'],
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'top_of_list',
				'value' => '
					<select name="project">' . $projectsHtml . '</select>
					<input type="submit" name="go" value="' . $txt['go'] . '" />',
				'class' => 'catbg',
				'align' => 'right',
			),
			array(
				'position' => 'bottom_of_list',
				'value' => '
					<a href="' . $scripturl . '?action=admin;area=manageprojects;section=categories;sa=new;project=' . $id_project . '">
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
	$context['sub_template'] = 'categories_list';
}

function EditCategory()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	if ($_REQUEST['sa'] == 'new')
	{
		$context['category'] = array(
			'is_new' => true,
			'id' => 0,
			'project' => (int) $_REQUEST['project'],
			'name' => '',
		);
	}
	else
	{
		if (empty($_REQUEST['category']) || !is_numeric($_REQUEST['category']))
			fatal_lang_error('category_not_found');

		$request = $smcFunc['db_query']('', '
			SELECT id_category, id_project, category_name
			FROM {db_prefix}issue_category
			WHERE id_category = {int:category}',
			array(
				'category' => $_REQUEST['category']
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('category_not_found');

		$context['category'] = array(
			'id' => $row['id_category'],
			'project' => $row['id_project'],
			'name' => htmlspecialchars($row['category_name']),
		);

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

function EditCategory2()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt;

	checkSession();

	$_POST['category'] = (int) $_POST['category'];
	$_POST['project'] = (int) $_POST['project'];

	if (isset($_POST['edit']) || isset($_POST['add']))
	{
		$categoryOptions = array();

		$categoryOptions['name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['category_name']);

		if (isset($_POST['add']))
			createPTCategory($_POST['project'], $categoryOptions);
		else
			updatePTCategory($_POST['project'], $_POST['category'], $categoryOptions);
	}
	elseif (isset($_POST['delete']))
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}issue_category
			WHERE id_category = {int:category}',
			array(
				'category' => $_POST['category']
			)
		);
	}

	redirectexit('action=admin;area=manageprojects;section=categories');
}

?>