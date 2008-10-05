<?php

function template_project_admin_above()
{
	global $scripturl, $txt, $context, $project_version;

}

function template_project_admin_below()
{
	global $scripturl, $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project ', $project_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2008
	</div>';
}

?>