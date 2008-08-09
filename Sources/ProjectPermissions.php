<?php
/**********************************************************************************
* ProjectPermissions.php                                                          *
***********************************************************************************
* SMF Project Tools                                                               *
* =============================================================================== *
* Software Version:           SMF Project Tools 0.1 Alpha                         *
* Software by:                Niko Pahajoki (http://www.madjoki.com)              *
* Copyright 2007 by:          Niko Pahajoki (http://www.madjoki.com)              *
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
	global $context, $sourcedir, $scripturl, $user_info, $txt;

	require_once($sourcedir . '/Project.php');

	isAllowedTo('project_admin');
	loadProjectTools('admin');

	$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['manage_project_permissions'];
	$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['manage_project_permissions_description'];

	$context['page_title'] = $txt['manage_project_permissions'];

	$subActions = array(
		'main' => array('ManageProjectPermissionsMain'),
		'new' => array('NewProjectProfile'),
		'edit' => array('EditProjectProfile'),
		'permissions' => array('EditProfilePermissions'),
		'permissions2' => array('EditProfilePermissions2'),
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	if (isset($subActions[$_REQUEST['sa']][1]))
		$context[$context['admin_menu_name']]['current_subsection'] = $subActions[$_REQUEST['sa']][1];

	loadTemplate('ManageProjects');

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

function EditProfilePermissions()
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

	// List of all possible permissions
	// 'perm' => array(own/any, [guest = true])
	$allPermissions = array(
		'issue_view' => array(false),
		'issue_report' => array(false),
		'issue_comment' => array(false),
		'issue_update' => array(true, false),
		'issue_attach' => array(false),
		'issue_moderate' => array(false, false),
		'delete_comment' => array(true, false),
	);

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
	global $smcFunc, $context, $sourcedir, $scripturl, $user_info, $txt, $modSettings;

	print_r($_POST);
	die();
}

function list_getProfiles($start, $items_per_page, $sort)
{
	global $smcFunc, $scripturl;

	$profiles = array();

	$request = $smcFunc['db_query']('', '
		SELECT pr.id_profile, pr.profile_name, COUNT(*) AS num_project
		FROM {db_prefix}project_profiles AS pr
			LEFT JOIN {db_prefix}projects AS p ON (p.id_profile = pr.id_profile)
		GROUP BY pr.id_profile');

	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$profiles[] = array(
			'id' => $row['id_profile'],
			'link' => '<a href="' . $scripturl . '?action=admin;area=projectpermissions;sa=edit;profile=' . $row['id_profile'] . '">' . $row['profile_name'] . '</a>',
			'href' => $scripturl . '?action=admin;area=projectpermissions;sa=edit;profile=' . $row['id_profile'],
			'name' => $row['profile_name'],
			'projects' => comma_format($row['num_project']),
			'disabled' => ($row['num_project'] > 0 || $row['id_profile'] == 1) ? 'disabled="disabled" ' : '',
		);
	}
	$smcFunc['db_free_result']($request);

	return $profiles;
}

?>