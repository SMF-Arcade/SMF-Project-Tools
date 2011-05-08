<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools_Extensions
{
	/**
	 *
	 */
	static protected $extensions = array();
	
	/**
	 *
	 */
	static protected $modules = array();
	
	/**
	 * Returns list of installed extensions
	 * @return array List of extensions
	 */
	function getInstalledExtensions()
	{
		global $sourcedir, $smcFunc, $modSettings;
	
		$extensions = array();
		if ($dh = opendir($sourcedir . '/ProjectTools/'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if ($file[0] == '.')
					continue;
				
				if (is_dir($sourcedir . '/ProjectTools/' . $file . '/') && file_exists($sourcedir . '/ProjectTools/' . $file . '/Extension.php'))
				{
					$extension = self::loadExtension($file, false);
					
					$extInfo = $extension->getExtensionInfo();
					
					$extensions[$file] = array(
						'id' => $file,
						'name' => $extInfo['title'],
						'version' => $extInfo['version'],
						'api_version' => $extInfo['api_version'],
						'filename' => $file,
						'enabled' => in_array($file, $modSettings['projectExtensions']),
						'can_enable' => $extInfo['api_version'] === 2,
						'can_disable' => !in_array($file, array('Admin', 'IssueTracker')),
					);
				}
			}
		}
		closedir($dh);
		
		return $extensions;
	}
	
	/**
	 * 
	 * @return ProjectTools_ExtensionBase
	 */
	static public function getExtension($extension)
	{
		if (isset(self::$extensions[$extension]))
			return self::$extensions[$extension];
		return false;
	}

	/**
	 * Loads extension
	 * @return ProjectTools_ExtensionBase
	 */
	static public function loadExtension($extension, $register = true)
	{
		if (isset(self::$extensions[$extension]))
			return self::$extensions[$extension];
			
		$mod = 'ProjectTools_' . $extension . '_Extension';
		
		// Create new instance of extension
		if (class_exists($mod))
		{
			self::$extensions[$extension] = new $mod();
			return self::$extensions[$extension];
		}
		
		return false;
	}
	
	/**
	 * Run extension hooks
	 * @param string $hook Name of Hook
	 * @param array $params Parameters of hooks 
	 */
	static public function runHooks($hook, $params)
	{
		// Run hooks in all extensions
		foreach (self::$extensions as $extension)
		{
			if (method_exists($extension, $hook))
				call_user_func_array(array($extension, $hook), $params);
		}
	}
	
	/**
	 * Run module hooks (project hooks)
	 * @param string $hook Name of Hook
	 * @param array $params Parameters of hooks
	 * @param ProjectTools_Project Project (current is default)
	 */
	static public function runProjectHooks($hook, $params, ProjectTools_Project $project = null)
	{
		// Get current project if not specified
		if ($project === null)
			$project = ProjectTools_Project::getCurrent();
		
		// Run hooks for each module
		foreach ($project->getModules() as $module)
		{
			if (method_exists($module, $hook))
				call_user_func_array(array($module, $hook), $params);
		}
	}
}

?>