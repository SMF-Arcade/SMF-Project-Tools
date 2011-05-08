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
}

?>