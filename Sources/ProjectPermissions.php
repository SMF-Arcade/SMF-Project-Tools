<?php
/**********************************************************************************
* ProjectPermissions.php                                                          *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.4                               *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007-2009 by:     Niko Pahajoki (http://www.madjoki.com)              *
* Support, News, Updates at:  http://www.madjoki.com                              *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
* The latest version can always be found at http://www.simplemachines.org.        *
**********************************************************************************/

function ManageProjectPermissions()
{
	global $context, $sourcedir, $user_info, $txt;

	require_once($sourcedir . '/Subs-Project.php');

	isAllowedTo('project_admin');
	
	loadLanguage('ManagePermissions');
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_permissions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_permissions_description'];

	$context['page_title'] = $txt['manage_project_permissions'];

	$subActions = array(
		'main' => array('ManageProjectPermissionsMain'),
		'edit' => array('EditProjectProfile'),
		'permissions' => array('EditProfilePermissions'),
		'permissions2' => array('EditProfilePermissions2'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ProjectPermissions');

	// Call action
	$subActions[$_REQUEST['sa']][0]();
}

function ManageProjectPermissionsMain()
{
	global $smcFunc, $context, $sourcedir, $scripturl, $user_info, $txt;

	if (isset($_POST['create']) && !empty($_REQUEST['profile_name']))
	{
		checkSession();
	
		$_POST['profile_name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['profile_name']);
	
		$smcFunc['db_insert']('insert',
			'{db_prefix}project_profiles',
			array('profile_name' => 'string-255',),
			array($_POST['profile_name'],),
			array('id_profile')
		);
	
		$id_profile = $smcFunc['db_insert_id']('{db_prefix}project_profiles', 'id_profile');
	
		$request = $smcFunc['db_query']('', '
			SELECT id_group, permission
			FROM {db_prefix}project_permissions
			WHERE id_profile = {int:profile}',
			array(
				'profile' => (int) $_POST['profile_base'],
			)
		);
	
		$permissions = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$permissions[] = array($id_profile, $row['id_group'], $row['permission']);
		$smcFunc['db_free_result']($request);
	
		if (!empty($permissions))
		{
			$smcFunc['db_insert']('insert',
				'{db_prefix}project_permissions',
				array('id_profile' => 'int', 'id_group' => 'int', 'permission' => 'string',),
				$permissions,
				array('id_profile', 'id_group', 'permission')
			);
		}
	
		redirectexit('action=admin;area=projectpermissions');
	}
	elseif (isset($_REQUEST['delete_profiles']) && !empty($_REQUEST['profiles']) && is_array($_REQUEST['profiles']))
	{
		$request = $smcFunc['db_query']('', '
			SELECT pr.id_profile, COUNT(p.id_project) AS num_project
			FROM {db_prefix}project_profiles AS pr
				LEFT JOIN {db_prefix}projects AS p ON (p.id_profile = pr.id_profile)
			WHERE pr.id_profile IN({array_int:profiles})
			GROUP BY pr.id_profile',
			array(
				'profiles' => $_REQUEST['profiles'],
			)
		);
		
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if ($row['id_profile'] == 1 || $row['num_project'] != 0)
				fatal_lang_error('profile_in_use', false);
		}
		$smcFunc['db_free_result']($request);
		
		// Delete permissions
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_permissions
			WHERE id_profile IN({array_int:profiles})',
			array(
				'profiles' => $_REQUEST['profiles'],
			)
		);
		
		// and profiles
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_profiles
			WHERE id_profile IN({array_int:profiles})',
			array(
				'profiles' => $_REQUEST['profiles'],
			)
		);
	}
	
	$listOptions = array(
		'id' => 'profiles_list',
		'base_href' => $scripturl . '?action=admin;area=projectpermissions',
		'get_items' => array(
			'function' => 'list_getProfiles',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['header_profile'],
				),
				'data' => array(
					'db' => 'link',
				),
				'sort' => array(
					'default' => 'pr.profile_name',
					'reverse' => 'pr.profile_name DESC',
				),
			),
			'used' => array(
				'header' => array(
					'value' => $txt['header_used_by'],
				),
				'data' => array(
					'function' => create_function('$list_item', '
						global $txt, $scripturl;
						return (empty($list_item[\'projects\']) ? $txt[\'not_in_use\'] : sprintf($txt[\'used_by_projects\'], $list_item[\'projects\']));
					'),
				),
				'sort' => array(
					'default' => 'num_project DESC',
					'reverse' => 'num_project',
				),
			),
			'check' => array(
				'header' => array(
					'value' => $txt['header_delete'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="profiles[]" value="%1$d" class="check" %2$s/>',
						'params' => array(
							'id' => false,
							'disabled' => false,
						),
					),
					'style' => 'text-align: center;',
				),
			),
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=projectpermissions',
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
					<input class="button_submit" type="submit" name="delete_profiles" value="' . $txt['profiles_delete_selected'] . '" />',
				'class' => 'titlebg',
				'align' => 'right',
			),
		),
		'no_items_label' => '', // Not possible
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);
	
	// Load List of Profiles
	$context['profiles'] = list_getProfiles();

	// Template
	$context['sub_template'] = 'profiles_list';
}

function EditProjectProfile()
{
	global $smcFunc, $context, $sourcedir, $scripturl, $user_info, $txt, $modSettings, $settings;

	$request = $smcFunc['db_query']('', '
		SELECT id_profile, profile_name
		FROM {db_prefix}project_profiles
		WHERE id_profile = {int:profile}',
		array(
			'profile' => (int) $_REQUEST['profile'],
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('profile_not_found', false);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['profile'] = array(
		'id' => $row['id_profile'],
		'name' => $row['profile_name'],
	);
	
	// Determine the number of ungrouped members.
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}members
		WHERE id_group = {int:regular_group}',
		array(
			'regular_group' => 0,
		)
	);
	list ($num_members) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Fill the context variable with 'Guests' and 'Regular Members'.
	$context['groups'] = array(
		-1 => array(
			'id' => -1,
			'name' => $txt['membergroups_guests'],
			'num_members' => $txt['membergroups_guests_na'],
			'allow_delete' => false,
			'allow_modify' => true,
			'can_search' => false,
			'edit_href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=-1',
			'href' => '',
			'link' => '',
			'is_post_group' => false,
			'color' => '',
			'stars' => '',
			'children' => array(),
			'num_permissions' => array(
				'allowed' => 0,
				// Can't deny guest permissions!
				'denied' => '(' . $txt['permissions_none'] . ')'
			),
			'access' => false
		),
		0 => array(
			'id' => 0,
			'name' => $txt['membergroups_members'],
			'num_members' => $num_members,
			'allow_delete' => false,
			'allow_modify' => true,
			'can_search' => false,
			'href' => $scripturl . '?action=moderate;area=viewgroups;sa=members;group=0',
			'edit_href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=0',
			'is_post_group' => false,
			'color' => '',
			'stars' => '',
			'children' => array(),
			'num_permissions' => array(
				'allowed' => 0,
				'denied' => 0
			),
			'access' => false
		),
	);

	// Query the database defined membergroups.
	$query = $smcFunc['db_query']('', '
		SELECT id_group, id_parent, group_name, min_posts, online_color, stars
		FROM {db_prefix}membergroups
		WHERE id_group != {int:moderator_group}' . (empty($modSettings['permission_enable_postgroups']) ? '
			AND	min_posts = {int:min_posts}' : '') . '
		ORDER BY id_parent = {int:not_inherited} DESC, min_posts, CASE WHEN id_group < {int:newbie_group} THEN id_group ELSE 4 END, group_name',
		array(
			'min_posts' => -1,
			'not_inherited' => -2,
			'moderator_group' => 3,
			'newbie_group' => 4,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($query))
	{
		// If it's inherited just add it as a child.
		if ($row['id_parent'] != -2)
		{
			if (isset($context['groups'][$row['id_parent']]))
				$context['groups'][$row['id_parent']]['children'][$row['id_group']] = $row['group_name'];

			continue;
		}

		$row['stars'] = explode('#', $row['stars']);
		$context['groups'][$row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => $row['group_name'],
			'num_members' => $row['id_group'] != 3 ? 0 : $txt['membergroups_guests_na'],
			'allow_delete' => $row['id_group'] > 4,
			'allow_modify' => $row['id_group'] > 1,
			'can_search' => $row['id_group'] != 3,
			'href' => $scripturl . '?action=moderate;area=viewgroups;sa=members;group=' . $row['id_group'],
			'edit_href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=' . $row['id_group'],
			'is_post_group' => $row['min_posts'] != -1,
			'color' => empty($row['online_color']) ? '' : $row['online_color'],
			'stars' => !empty($row['stars'][0]) && !empty($row['stars'][1]) ? str_repeat('<img src="' . $settings['images_url'] . '/' . $row['stars'][1] . '" alt="*" border="0" />', $row['stars'][0]) : '',
			'children' => array(),
			'num_permissions' => array(
				'allowed' => $row['id_group'] == 1 ? '(' . $txt['permissions_all'] . ')' : 0,
				'denied' => $row['id_group'] == 1 ? '(' . $txt['permissions_none'] . ')' : 0
			),
			'access' => false,
		);

		if ($row['min_posts'] == -1)
			$normalGroups[$row['id_group']] = $row['id_group'];
		else
			$postGroups[$row['id_group']] = $row['id_group'];
	}
	$smcFunc['db_free_result']($query);

	// This code is borrowed from SMF
	// Get the number of members in this post group.
	if (!empty($postGroups))
	{
		$query = $smcFunc['db_query']('', '
			SELECT id_post_group AS id_group, COUNT(*) AS num_members
			FROM {db_prefix}members
			WHERE id_post_group IN ({array_int:post_group_list})
			GROUP BY id_post_group',
			array(
				'post_group_list' => $postGroups,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$context['groups'][$row['id_group']]['num_members'] += $row['num_members'];
		$smcFunc['db_free_result']($query);
	}

	if (!empty($normalGroups))
	{
		// First, the easy one!
		$query = $smcFunc['db_query']('', '
			SELECT id_group, COUNT(*) AS num_members
			FROM {db_prefix}members
			WHERE id_group IN ({array_int:normal_group_list})
			GROUP BY id_group',
			array(
				'normal_group_list' => $normalGroups,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$context['groups'][$row['id_group']]['num_members'] += $row['num_members'];
		$smcFunc['db_free_result']($query);

		// This one is slower, but it's okay... careful not to count twice!
		$query = $smcFunc['db_query']('', '
			SELECT mg.id_group, COUNT(*) AS num_members
			FROM {db_prefix}membergroups AS mg
				INNER JOIN {db_prefix}members AS mem ON (mem.additional_groups != {string:blank_string}
					AND mem.id_group != mg.id_group
					AND FIND_IN_SET(mg.id_group, mem.additional_groups))
			WHERE mg.id_group IN ({array_int:normal_group_list})
			GROUP BY mg.id_group',
			array(
				'normal_group_list' => $normalGroups,
				'blank_string' => '',
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$context['groups'][$row['id_group']]['num_members'] += $row['num_members'];
		$smcFunc['db_free_result']($query);
	}

	foreach ($context['groups'] as $id => $data)
	{
		if ($data['href'] != '')
			$context['groups'][$id]['link'] = '<a href="' . $data['href'] . '">' . $data['num_members'] . '</a>';
	}
	
	// All permissions can be edited
	$context['can_modify'] = true;

	// Template
	$context['page_title'] = sprintf($txt['title_edit_profile'], $context['profile']['name']);
	$context['sub_template'] = 'profile_edit';
}

function PTloadProfile()
{
	global $smcFunc, $context, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT id_profile, profile_name
		FROM {db_prefix}project_profiles
		WHERE id_profile = {int:profile}',
		array(
			'profile' => (int) $_REQUEST['profile'],
		)
	);

	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('profile_not_found', false);

	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$context['profile'] = array(
		'id' => $row['id_profile'],
		'name' => $row['profile_name'],
	);

	if (!isset($_REQUEST['group']))
		fatal_lang_error('profile_group_not_found', false);

	if ($_REQUEST['group'] == -1)
	{
		$context['group'] = array(
			'id' => '-1',
			'name' => $txt['guests'],
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=perm;group=-1',
			'is_post_group' => false,
			'can_edit' => true,
		);
	}
	elseif ($_REQUEST['group'] == 0)
	{
		$context['group'] = array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=perm;group=0',
			'is_post_group' => false,
			'can_edit' => true,
		);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT id_group, id_parent, group_name, min_posts
			FROM {db_prefix}membergroups
			WHERE id_group = {int:group}' . (empty($modSettings['permission_enable_postgroups']) ? '
				AND min_posts = {int:min_posts}' : '') . '
			ORDER BY min_posts, id_group != 2, group_name',
			array(
				'group' => (int) $_REQUEST['group'],
				'min_posts' => -1,
			)
		);

		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('profile_group_not_found', false);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if ($row['id_parent'] != -2)
			fatal_lang_error('cannot_edit_permissions_inherited');

		$context['group'] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=' . $row['id_group'],
			'is_post_group' => $row['min_posts'] != -1,
			'can_edit' => $row['id_group'] != 1 && $row['id_group'] != 3,
		);
	}

	if (!$context['group']['can_edit'])
		fatal_lang_error('profile_group_not_found', false);
}

function EditProfilePermissions()
{
	global $smcFunc, $context, $sourcedir, $user_info, $txt, $modSettings;

	PTloadProfile();

	$allPermissions = getAllPTPermissions();

	$request = $smcFunc['db_query']('', '
		SELECT permission
		FROM {db_prefix}project_permissions
		WHERE id_profile = {int:profile}
			AND id_group = {int:group}',
		array(
			'profile' => $context['profile']['id'],
			'group' => $context['group']['id'],
		)
	);

	$permissions = array();

	while ($row = $smcFunc['db_fetch_assoc']($request))
		$permissions[] = $row['permission'];
	$smcFunc['db_free_result']($request);

	$context['permissions'] = array();

	foreach ($allPermissions as $permission => $opt)
	{
		if (!isset($opt[1]))
			$opt[1] = true;

		list ($group, $guest) = $opt;

		if (!$guest && $context['group']['id'] == -1)
			continue;

		if ($group)
		{
			$context['permissions'][$permission . '_own'] = array(
				'text' => $txt['permissionname_project_' . $permission . '_own'],
				'checked' => in_array($permission . '_own', $permissions),
			);
			$context['permissions'][$permission . '_any'] = array(
				'text' => $txt['permissionname_project_' . $permission . '_any'],
				'checked' => in_array($permission . '_any', $permissions),
			);
		}
		else
		{
			$context['permissions'][$permission] = array(
				'text' => $txt['permissionname_project_' . $permission],
				'checked' => in_array($permission, $permissions),
			);
		}
	}

	// Template
	$context['page_title'] = sprintf($txt['title_edit_profile_group'], $context['profile']['name'], $context['group']['name']);
	$context['sub_template'] = 'profile_permissions';
}

function EditProfilePermissions2()
{
	global $smcFunc, $context, $sourcedir, $user_info, $txt, $modSettings;

	PTloadProfile();

	checkSession();

	$allPermissions = getAllPTPermissions();

	$permissions = array();
	$delete = array();

	foreach ($allPermissions as $perm => $opt)
	{
		if (!isset($opt[1]))
			$opt[1] = true;

		list ($is_group, $can_guest) = $opt;

		if (!$can_guest && $context['group']['id'] == -1)
			continue;

		if ($is_group)
		{
			if (!empty($_POST['permission'][$perm . '_own']))
				$permissions[] = array($context['profile']['id'], $context['group']['id'], $perm . '_own');

			if (!empty($_POST['permission'][$perm . '_any']))
				$permissions[] = array($context['profile']['id'], $context['group']['id'], $perm . '_any');
		}
		elseif (!empty($_POST['permission'][$perm]))
			$permissions[] = array($context['profile']['id'], $context['group']['id'], $perm);
	}

	if (!empty($delete))
		$smcFunc['db_query']('' , '
			DELETE FROM {db_prefix}project_permissions
			WHERE id_group = {int:group}
				AND id_profile = {int:profile}',
			array(
				'permissions' => $delete,
				'group' => $context['group']['id'],
				'profile' => $context['profile']['id'],
			)
		);

	if (!empty($permissions))
		$smcFunc['db_insert']('replace',
			'{db_prefix}project_permissions',
			array('id_profile' => 'int', 'id_group' => 'int', 'permission' => 'string',),
			$permissions,
			array('id_profile', 'id_group', 'permission')
		);

	// Update inherited groups
	updatePTChildPermissions($context['group']['id'], $context['profile']['id']);
	
	// Make sure cached permissions doesn't get used
	updateSettings(array('settings_updated' => time()));

	redirectexit('action=admin;area=projectpermissions;sa=edit;profile=' . $context['profile']['id']);
}

function updatePTChildPermissions($parents, $profile)
{
	global $smcFunc, $context, $sourcedir, $user_info, $txt, $modSettings;

	// All the parent groups to sort out.
	if (!is_array($parents))
		$parents = array($parents);

	// Find all the children for parents
	$request = $smcFunc['db_query']('', '
		SELECT id_parent, id_group
		FROM {db_prefix}membergroups
		WHERE id_parent != {int:not_inherited}
			' . (empty($parents) ? '' : 'AND id_parent IN ({array_int:parent_list})'),
		array(
			'parent_list' => $parents,
			'not_inherited' => -2,
		)
	);
	$children = array();
	$parents = array();
	$child_groups = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$children[$row['id_parent']][] = $row['id_group'];
		$child_groups[] = $row['id_group'];
		$parents[] = $row['id_parent'];
	}
	$smcFunc['db_free_result']($request);

	// No children?
	if (empty($children))
		return;

	// Fetch all the parent permissions.
	$request = $smcFunc['db_query']('', '
		SELECT id_profile, id_group, permission
		FROM {db_prefix}project_permissions
		WHERE id_group IN ({array_int:parent_list})
			AND id_profile = {int:profile}',
		array(
			'parent_list' => $parents,
			'profile' => $profile,
		)
	);
	$permissions = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		foreach ($children[$row['id_group']] as $child)
			$permissions[] = array($row['id_profile'], $child, $row['permission']);
	$smcFunc['db_free_result']($request);

	// Delete current permissions
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}project_permissions
		WHERE id_group IN ({array_int:child_groups})
			AND id_profile = {int:profile}',
		array(
			'child_groups' => $child_groups,
			'profile' => $profile,
		)
	);

	// Insert new permissions
	if (!empty($permissions))
		$smcFunc['db_insert']('insert',
			'{db_prefix}project_permissions',
			array('id_profile' => 'int', 'id_group' => 'int', 'permission' => 'string'),
			$permissions,
			array('id_profile', 'id_group', 'permission')
		);
}

?>