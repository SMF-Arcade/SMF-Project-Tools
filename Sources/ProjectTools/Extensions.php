<?php
/**
 * 
 *
 * @package core
 * @version 0.5
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
	 */
	static public function getModule($module)
	{
		if (isset(self::$modules[$module]))
			return self::$modules[$module];
		return false;
	}

	/**
	 *
	 * @return ProjectTools_ExtensionBase
	 */
	static public function loadExtension($extension, $register = true)
	{
		if (isset(self::$extensions[$extension]))
			return self::$extensions[$extension];
			
		$mod = 'ProjectTools_' . $extension . '_Extension';
		
		if (class_exists($mod))
		{
			self::$extensions[$extension] = new $mod();
			
			self::$modules = array_merge(self::$modules, self::$extensions[$extension]->getModules());
			
			return self::$extensions[$extension];
		}
		
		/*	// Prevent extensionInformation from previous extension coming up
	$extensionInformation = array();
	
	if (!isset($context['project_extensions'][$name]))
	{
		$projectModules = array();
		
		loadClassFile('ProjectModule-' . $smcFunc['ucwords']($name) . '.php');
		$context['project_extensions'][$name] = $extensionInformation;
		$context['project_extensions'][$name]['modules'] = $projectModules;
		
		unset($projectModules);
		unset($extensionInformation);
	}
	
	if (!$active)
		return $context['project_extensions'][$name];
	
	foreach ($context['project_extensions'][$name]['modules'] as $id => $module)
		$context['project_modules'][$id] = $module;
		
	return $context['project_extensions'][$name];*/
		
		return false;
	}
	
	/**
	 *
	 */
	static public function runHooks($hook, $params)
	{
		/*foreach ()
		{
			
		}*/
	}
}

?>