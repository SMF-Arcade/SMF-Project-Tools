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
class ProjectTools_UserAdmin_Modules
{
	/**
	 *
	 */
	public static function Main()
	{
		global $context, $txt;
			
		$subActions = array(
			'main' => array('ProjectTools_UserAdmin_Modules', 'Modules'),
		);
		
		if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
			$_REQUEST['sa'] = 'main';
			
		call_user_func($subActions[$_REQUEST['sa']]);
			
		$context['project_tabs']['description'] = $txt['project_admin_versions_description'];
	}
	
	/**
	 *
	 */
	public static function Modules()
	{	
		global $sourcedir, $context, $txt;
		
		$context['modules_form'] = new ProjectTools_Form_ModuleSettings(ProjectTools_Project::getCurrent());
		if ($context['modules_form']->is_post && $context['modules_form']->Save())
			redirectexit(ProjectTools::get_admin_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'modules')));
			
		// Template
		$context['page_title'] = sprintf($txt['title_project_modules'], ProjectTools_Project::getCurrent()->name);
		$context['sub_template'] = 'modules_form';
	}
}

?>