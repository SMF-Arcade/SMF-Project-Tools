<?php
// Version: 0.2; ProjectRoadmap

function template_project_admin_above()
{
	global $txt, $context, $project_version;

	echo '
	<div>', sprintf($txt['project_status_desc'], $project_version, '???'), '</div>';

}

function template_project_admin_below()
{
	global $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project ', $project_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2008
	</div>
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		function setProjectNews()
		{

		}

		function setProjectVersion()
		{
			if (typeof(window.projectCurrentVersion) == "undefined")
				return;

			setInnerHTML(document.getElementById("project_latest_version"), window.projectCurrentVersion);
		}
	// ]]></script>
	<script language="JavaScript" type="text/javascript" src="http://service.smfarcade.info/project/news.js?v=', urlencode($project_version), '" defer="defer"></script>';
}

?>