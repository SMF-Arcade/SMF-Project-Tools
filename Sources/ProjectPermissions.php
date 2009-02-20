<?php
/**********************************************************************************
* ProjectPermissions.php                                                          *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.3                               *
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
	loadProjectToolsPage('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_permissions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_permissions_description'];

	$context['page_title'] = $txt['manage_project_permissions'];

	$subActions = array(
		'main' => array('ManageProjectPermissionsMain'),
		'new' => array('NewProjectProfile'),
		'new2' => array('NewProjectProfile2'),
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
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	$listOptions = array(
		'id' => 'profiles_list',
		'base_href' => $scripturl . '?action=admin;area=projectpermissions',
		'get_items' => array(
			'function' => 'list_getProfiles',
		),
		'columns' => array(
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" class="check" onclick="invertAll(this, this.form);" />',
					'style' => 'width: 4%;',
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
		),
		'form' => array(
			'href' => $scripturl . '?action=admin;area=projectpermissions',
			'include_sort' => true,
			'include_start' => true,
			'hidden_fields' => array(
				'sc' => $context['session_id'],
			),
		),
		'no_items_label' => '', // Not possible
	);

	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	// Template
	$context['sub_template'] = 'profiles_list';
}

function NewProjectProfile()
{
	global $smcFunc, $context, $sourcedir, $user_info, $txt, $modSettings;

	$context['profiles'] = list_getProfiles();

	$context['profile'] = array(
		'name' => '',
		'copy_from' => 0,
	);

	// Template
	$context['page_title'] = $txt['title_new_profile'];
	$context['sub_template'] = 'profile_new';
}

function NewProjectProfile2()
{
	global $smcFunc, $context, $sourcedir, $user_info, $txt, $modSettings;

	if (empty($_REQUEST['profile_name']))
		return NewProjectProfile();

	checkSession();

	$_POST['profile_name'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['profile_name']);

	$smcFunc['db_insert']('insert',
		'{db_prefix}project_profiles',
		array(
			'profile_name' => 'string-255',
		),
		array(
			$_POST['profile_name'],
		),
		array()
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
			array(
				'id_profile' => 'int',
				'id_group' => 'int',
				'permission' => 'string',
			),
			$permissions,
			array('id_profile', 'id_group', 'permission')
		);
	}

	redirectexit('action=admin;area=projectpermissions');
}

function EditProjectProfile()
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

	// Gropus
	$context['groups'] = array(
		-1 => array(
			'id' => '-1',
			'name' => $txt['guests'],
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=-1',
			'is_post_group' => false,
			'can_edit' => true,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['regular_members'],
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=0',
			'is_post_group' => false,
			'can_edit' => true,
		)
	);

	// Load membergroups.
	$request = $smcFunc['db_query']('', '
		SELECT group_name, id_group, min_posts
		FROM {db_prefix}membergroups' . (empty($modSettings['permission_enable_postgroups']) ? '
		WHERE min_posts = {int:min_posts}' : '') . '
		ORDER BY min_posts, id_group != 2, group_name',
		array(
			'min_posts' => -1,
		)
	);

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$context['groups'][(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=permissions;profile=' . $context['profile']['id'] . ';group=' . $row['id_group'],
			'is_post_group' => $row['min_posts'] != -1,
			'can_edit' => $row['id_group'] != 1 && $row['id_group'] != 3,
		);
	}
	$smcFunc['db_free_result']($request);

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
			SELECT group_name, id_group, min_posts
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
			else
				$delete[] = $perm . '_own';
			if (!empty($_POST['permission'][$perm . '_any']))
				$permissions[] = array($context['profile']['id'], $context['group']['id'], $perm . '_any');
			else
				$delete[] = $perm . '_any';
		}
		elseif (!empty($_POST['permission'][$perm]))
			$permissions[] = array($context['profile']['id'], $context['group']['id'], $perm);
		else
			$delete[] = $perm;
	}

	if (!empty($delete))
	{
		$smcFunc['db_query']('' , '
			DELETE FROM {db_prefix}project_permissions
			WHERE permission IN({array_string:permissions})
				AND id_group = {int:group}
				AND id_profile = {int:profile}',
			array(
				'permissions' => $delete,
				'group' => $context['group']['id'],
				'profile' => $context['profile']['id'],
			)
		);
	}

	if (!empty($permissions))
	{
		$smcFunc['db_insert']('replace',
			'{db_prefix}project_permissions',
			array(
				'id_profile' => 'int',
				'id_group' => 'int',
				'permission' => 'string',
			),
			$permissions,
			array('id_profile', 'id_group', 'permission')
		);
	}

	redirectexit('action=admin;area=projectpermissions;sa=edit;profile=' . $context['profile']['id']);
}

?>