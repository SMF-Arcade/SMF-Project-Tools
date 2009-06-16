<?php

// Build info
$build_info = array(
	'branch' => 'trunk',
	'version' => '0.4',
	'version_str' => '0.4',
	'version_int' => '0.4',
	'build_replaces' => 'build_replaces_project01',
	'extra_files' => array(
		'changelog.txt',
		'modification.xsl' => 'Package/modification.xsl',
		'package-info.xsl' => 'Package/package-info.xsl',
		'ptinstall/index.php',
		'extra/Themes/default/languages/Modifications.english.php' => 'Themes/default/languages/Modifications.english.php',
	),
);

function build_replaces_project01(&$content, $filename, $rev, $svnInfo)
{
	global $build_info;

	if ($rev && ($filename == 'Sources/Subs-Project.php' || $filename == 'ptinstall/Database.php'))
		$content = str_replace('$project_version = \'' . $build_info['version_str']. '\';', '$project_version = \'' . $build_info['version_str']. ' rev' . $rev . '\';', $content);
	elseif (in_array($filename, array('readme.txt')))
	{
		$content = strtr($content, array(
			'{version}' => $build_info['version_str'] . ($rev ? ' rev' . $rev : ''),
		));
	}
	elseif (in_array($filename, array('install.xml', 'package-info.xml')))
	{
		$content = strtr($content, array(
			'{version}' => $build_info['version_int'] . ($rev ? ' rev' . $rev : ''),
		));
	}
}

?>