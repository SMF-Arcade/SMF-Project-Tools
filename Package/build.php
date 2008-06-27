<?php
// Build info
$build_info = array(
	'branch' => 'trunk',
	'version' => '0.1 Alpha',
	'version_str' => '0.1 Alpha',
	'build_replaces' => 'build_replaces_project01',
	'extra_files' => array(
		'changelog.txt',
		'readme.txt',
		'modification.xsl' => 'Package/modification.xsl',
		'package-info.xsl' => 'Package/package-info.xsl',
		'Themes/default/languages/Modifications.english.php',
	),
);

function build_replaces_project01(&$content, $filename, $rev, $svnInfo)
{
	if ($rev === 0)
		return;

	if ($filename == 'Sources/Project.php' || $filename == 'Sources/ProjectDatabase.php')
		$content = str_replace('$project_version = \'0.1 Alpha\';', '$project_version = \'0.1 Alpha rev' . $rev . '\';', $content);
}

?>