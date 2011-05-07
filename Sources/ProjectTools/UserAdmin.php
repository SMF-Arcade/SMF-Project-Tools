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
class ProjectTools_UserAdmin
{
	/**
	 * Main Project Tools functions, handles calling correct module and action
	 */
	static public function Main()
	{
		global $context, $smcFunc, $user_info, $txt;
		
		loadLanguage('ProjectTools/UserAdmin');
		loadTemplate('ProjectTools/UserAdmin');
		
		is_not_guest($txt['pt_ua_no_guest']);
	
		// Check that user can access Project Tools
		isAllowedTo('project_access');
		
		if (!ProjectTools_Project::getCurrent())
			return self::SelectProject();
		else
			return self::AdminProject();
		
		/*// Changing project?
		if (!isset($_REQUEST['area']) && isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'change')
		{
			unset($_SESSION['ptAdmin_project']);
			$_SESSION['ptAdmin_project'] = (int) $_REQUEST['project'];
			
			redirectexit(ProjectTools::get_admin_url());
		}
			
		// Which project to show?
		if ($standalone && isset($standalone['project']))
			$context['admin_project'] = $standalone['project'];
		elseif (isset($_SESSION['ptAdmin_project']))
			$context['admin_project'] = (int) $_SESSION['ptAdmin_project'];
		else
			$context['admin_project'] = 0;*/
	}
	
	/**
	 *
	 */
	static public function SelectProject()
	{
		global $context, $smcFunc, $user_info, $txt;
		
		// 
		$context['admin_projects'] = array();

		// Which projects I can admin?
		$request = $smcFunc['db_query']('', '
			SELECT p.id_project, p.name
			FROM {db_prefix}projects AS p' . (!allowedTo('project_admin') ? '
				INNER JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})' : ''),
			array(
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$context['admin_projects'][$row['id_project']] = array(
				'id' => $row['id_project'],
				'name' => $row['name'],
				'href' => ProjectTools::get_admin_url(array('project' => $row['id_project'])),
			);
		$smcFunc['db_free_result']($request);
		
		if (count($context['admin_projects']) == 1)
		{
			$project = array_pop($context['admin_projects']);
			
			redirectexit(ProjectTools::get_admin_url(array('project' => $project['id'])));
		}
		elseif (count($context['admin_projects']) == 0)
			fatal_lang_error('pt_admin_not_allowed', false);
			
		$context['sub_template'] = 'select_project';
	}
	
	/**
	 *
	 */
	static public function AdminProject()
	{
	}
}

?>