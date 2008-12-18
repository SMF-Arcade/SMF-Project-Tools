<?php

// Build info
$build_info = array(
	'branch' => '0.1',
	'version' => '0.1.3',
	'version_str' => '0.1.3',
	'build_replaces' => 'build_replaces_project01',
	'extra_files' => array(
		'changelog.txt',
		'modification.xsl' => 'Package/modification.xsl',
		'package-info.xsl' => 'Package/package-info.xsl',
		'extra/Themes/default/languages/Modifications.english.php' => 'Themes/default/languages/Modifications.english.php',
	),
);

if (!function_exists('build_replaces_project01'))
{
	function build_replaces_project01(&$content, $filename, $rev, $svnInfo)
	{
		global $build_info;

		if ($rev && ($filename == 'Sources/Project.php' || $filename == 'Sources/ProjectDatabase.php'))
			$content = str_replace('$project_version = \'' . $build_info['version_str'] . '\';', '$project_version = \'' . $build_info['version_str'] . ' rev' . $rev . '\';', $content);
		elseif (in_array($filename, array('readme.txt', 'install.xml',  'package-info.xml')))
			$content = strtr($content, array(
				'{version}' => $rev ? $build_info['version_str'] . ' rev' . $rev : $build_info['version_str']
			));
	}
}

?>