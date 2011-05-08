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
class ProjectTools_Admin
{
	/**
	 * Returns list of projects for createList
	 */
	function list_getProjects($start, $items_per_page, $sort)
	{
		global $smcFunc, $scripturl;
	
		$projects = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT p.id_project, p.name
			FROM {db_prefix}projects AS p
			ORDER BY ' . $sort);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$projects[] = array(
				'id' => $row['id_project'],
				'link' => '<a href="' . $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $row['id_project'] . '">' . $row['name'] . '</a>',
				'href' => $scripturl . '?action=admin;area=manageprojects;sa=edit;project=' . $row['id_project'],
				'name' => $row['name'],
			);
		}
		$smcFunc['db_free_result']($request);
	
		return $projects;
	}
	
	/**
	 * Returns list of permission profiles for createList
	 */
	function list_getProfiles($start = 0, $items_per_page = -1, $sort = '')
	{
		global $smcFunc, $scripturl;
	
		$profiles = array();
	
		$request = $smcFunc['db_query']('', '
			SELECT pr.id_profile, pr.profile_name, COUNT(p.id_project) AS num_project
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

	/**
	 *
	 */
	function list_getMembers($start, $items_per_page, $sort, $project)
	{
		global $smcFunc;
	
		$request = $smcFunc['db_query']('', '
			SELECT dev.id_member, mem.real_name
			FROM {db_prefix}project_developer AS dev
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
			WHERE dev.id_project = {int:project}
			ORDER BY ' . $sort,
			array(
				'project' => $project
			)
		);
	
		$members = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$members[] = array(
				'id' => $row['id_member'],
				'name' => $row['real_name'],
				'link' => '<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'members', 'sa' => 'edit', 'member' => $row['id_member'])) . '">' . $row['real_name'] . '</a>',
			);
		}
		$smcFunc['db_free_result']($request);
	
		return $members;
	}
	
	/**
	 * Returns list of versions for createList
	 */
	function list_getVersions($start, $items_per_page, $sort, $project)
	{
		global $smcFunc;
	
		$request = $smcFunc['db_query']('', '
			SELECT ver.id_version, ver.version_name, ver.id_parent
			FROM {db_prefix}project_versions AS ver
			WHERE ver.id_project = {int:project}
			ORDER BY ver.id_parent, ver.version_name',
			array(
				'project' => $project
			)
		);
	
		$versionsTemp = array();
		$children = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (empty($row['id_parent']))
			{
				$versionsTemp[] = array(
					'id' => $row['id_version'],
					'name' => $row['version_name'],
					'link' => '<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions', 'sa' => 'edit', 'version' => $row['id_version'])) . '">' . $row['version_name'] . '</a>',
					'level' => 0,
				);
			}
			else
			{
				if (!isset($children[$row['id_parent']]))
					$children[$row['id_parent']] = array();
	
				$children[$row['id_parent']][] = array(
					'id' => $row['id_version'],
					'name' => $row['version_name'],
					'link' => '<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'versions', 'sa' => 'edit', 'version' => $row['id_version'])) . '">' . $row['version_name'] . '</a>',
					'level' => 1,
				);
			}
		}
		$smcFunc['db_free_result']($request);
	
		$versions = array();
	
		foreach ($versionsTemp as $ver)
		{
			$versions[] = $ver;
	
			if (isset($children[$ver['id']]))
				$versions = array_merge($versions, $children[$ver['id']]);
		}
	
		return $versions;
	}
	
	/**
	 * Returns list of categories for createList
	 */
	function list_getCategories($start, $items_per_page, $sort, $project)
	{
		global $smcFunc;
	
		$request = $smcFunc['db_query']('', '
			SELECT cat.id_category, cat.category_name
			FROM {db_prefix}issue_category AS cat
			WHERE cat.id_project = {int:project}
			ORDER BY cat.category_name',
			array(
				'project' => $project
			)
		);
	
		$categories = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			$categories[] = array(
				'id' => $row['id_category'],
				'name' => $row['category_name'],
				'link' => '<a href="' . ProjectTools::get_url(array('project' => $project, 'area' => 'admin', 'sa' => 'category', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>',
			);
		}
		$smcFunc['db_free_result']($request);
	
		return $categories;
	}
}

?>