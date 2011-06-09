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
	 * Main admin function
	 */
	public static function Main()
	{
		global $context, $smcFunc, $sourcedir, $user_info, $txt;
	
		require_once($sourcedir . '/Subs-Project.php');
		require_once($sourcedir . '/ManageServer.php');
	
		isAllowedTo('project_admin');
		ProjectTools_Main::loadPage('admin');
	
		$context[$context['admin_menu_name']]['tab_data']['title'] = $txt['project_tools_admin'];
		$context[$context['admin_menu_name']]['tab_data']['description'] = $txt['project_tools_admin_desc'];
	
		$context['page_title'] = $txt['project_tools_admin'];
	
		$subActions = array(
			'main' => array('Frontpage'),
			'settings' => array('Settings'),
			'maintenance' => array('Maintenance'),
			'extensions' => array('Extensions'),
		);
	
		$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';
	
		if (isset($subActions[$_REQUEST['sa']][1]))
			isAllowedTo($subActions[$_REQUEST['sa']][1]);
	
		self::$subActions[$_REQUEST['sa']][0]();
	}

	/**
	 * Information view
	 */
	function Frontpage()
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
		$context['sub_template'] = 'project_admin_main';
	}
	
	/**
	 * Project Tools settings page
	 */
	function Settings($return_config = false)
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
		$config_vars = array(
				array('check', 'projectAttachments'),
			'',
				array('int', 'issuesPerPage'),
				array('int', 'commentsPerPage'),
			'',
				array('permissions', 'project_access', 0, $txt['setting_project_access'], 'subtext' => $txt['setting_project_access_subtext']),
				array('permissions', 'project_admin', 0, $txt['setting_project_admin']),
		);
	
		if ($return_config)
			return $config_vars;
	
		if (isset($_GET['save']))
		{
			checkSession('post');
			saveDBSettings($config_vars);
	
			writeLog();
	
			redirectexit('action=admin;area=projectsadmin;sa=settings');
		}
	
		$context['post_url'] = $scripturl . '?action=admin;area=projectsadmin;sa=settings;save';
		$context['page_title'] = $txt['project_settings_title'];
		$context['settings_title'] = $txt['project_settings'];
		$context['sub_template'] = 'show_settings';
	
		prepareDBSettingContext($config_vars);
	}
	
	/**
	 * Maintenance
	 */
	function Maintenance()
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $txt;
	
		require_once($sourcedir . '/Subs-ProjectMaintenance.php');
	
		$maintenaceActions = array(
			'repair' => 'ProjectTools_Maintenance_Repair',
			'upgrade' => 'ProjectTools_Maintenance_Upgrade',
		);
		
		//
		if (!empty($_REQUEST['done']))
		{
			$context['maintenance_finished'] = true;
			$context['maintenance_action'] = $txt['project_maintenance_' . $_REQUEST['done']];
		}
	
		$context['sub_template'] = 'project_admin_maintenance';
	
		if (isset($_REQUEST['activity']) && isset($maintenaceActions[$_REQUEST['activity']]))
		{
			$context['maintenance_action_title'] = $txt['project_maintenance_' . $_REQUEST['activity']];
			
			/**
			 * @var ProjectTools_Maintenance_Action
			 */
			$context['maintenance_action'] = new $maintenaceActions[$_REQUEST['activity']]();
			
			if (!isset($_REQUEST['step']))
				$_REQUEST['step'] = 1;
			
			while ($_REQUEST['step'] <= $context['maintenance_action']->total_steps)
			{
				$_REQUEST['step'] = $context['maintenance_action']->run($_REQUEST['step']);
				self::pauseProjectMaintenance(true);
			}
			
			redirectexit('action=admin;area=projectsadmin;sa=maintenance;done=' . $_REQUEST['activity']);
		}
	}
	
	/**
	 * function to handle pausing maintenancr action
	 */
	function pauseProjectMaintenance($force)
	{
		global $context, $txt, $time_start;
	
		// Errr, wait.  How much time has this taken already?
		if (!$force && time() - array_sum(explode(' ', $time_start)) < 3)
			return;
	
		$context['continue_get_data'] = '?action=admin;area=projectsadmin;sa=maintenance;step=' . $_REQUEST['step'] . ';activity=' . $_REQUEST['activity'] . ';' . $context['session_var'] . '=' . $context['session_id'];
		$context['page_title'] = $txt['not_done_title'];
		$context['continue_post_data'] = '';
		$context['continue_countdown'] = '2';
		$context['sub_template'] = 'not_done';
	
		$context['continue_percent'] = round(($context['maintenance_action']->current_step * 100) / $context['maintenance_action']->total_steps);
	
		// Never more than 100%!
		$context['continue_percent'] = min($context['continue_percent'], 100);
	
		obExit();
	}
	
	/**
	 * Installed extensions page
	 * @return ProjectTools_ExtensionBase
	 */
	function Extensions()
	{
		global $context, $smcFunc, $sourcedir, $scripturl, $user_info, $modSettings, $txt;
		
		if (isset($_REQUEST['save']))
		{
			foreach (ProjectTools_Extensions::getInstalledExtensions() as $ext)
			{
				if (empty($ext['can_disable']))
					$_POST['extension'][] = $ext['id'];
			}
			
			$disabled = array_diff($modSettings['projectExtensions'], $_POST['extension']);
			
			// Call onActive
			foreach (array_diff($_POST['extension'], $modSettings['projectExtensions']) as $extension)
			{
				$ext = ProjectTools_Extensions::loadExtension($extension, false);
				
				if (method_exists($ext, 'onActivate'))
					$ext->onActivate();
			}
			
			// Call onDisable
			foreach (array_diff($modSettings['projectExtensions'], $_POST['extension']) as $extension)
			{
				$ext = ProjectTools_Extensions::loadExtension($extension, false);
				
				if (method_exists($ext, 'onDisable'))
					$ext->onDisable();
			}
			
			updateSettings(array('projectExtensions' => implode(',', $_POST['extension'])));
			
			redirectexit('action=admin;area=projectsadmin;sa=extensions');
		}
		
		$context['installed_extensions'] = ProjectTools_Extensions::getInstalledExtensions();
	
		$context['sub_template'] = 'project_admin_extensions';
	}

	/**
	 * Returns list of all possible permissions
	 */
	function getAllPTPermissions()
	{
		// List of all possible permissions
		// 'perm' => array(own/any, [guest = true])
	
		return array(
			'issue_view' => array(false),
			'issue_view_private' => array(false),
			'issue_report' => array(false),
			'issue_comment' => array(false),
			'issue_update' => array(true, false),
			'issue_attach' => array(false),
			'issue_moderate' => array(false, false),
			// Comments
			'edit_comment' => array(true, false),
			'delete_comment' => array(true, false),
		);
	}
	
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
				'link' => '<a href="' . ProjectTools::get_admin_url(array('project' => $project, 'area' => 'categories', 'sa' => 'edit', 'category' => $row['id_category'])) . '">' . $row['category_name'] . '</a>',
			);
		}
		$smcFunc['db_free_result']($request);
	
		return $categories;
	}
}

?>