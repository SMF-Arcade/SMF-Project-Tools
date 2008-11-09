<?php
// Version: 0.2; ProjectRoadmap

function template_project_admin_above()
{
	global $txt, $context, $project_version;

}

function template_project_admin_main()
{
	global $context, $settings, $options, $txt, $modSettings, $project_version;

	echo '
	<div class="tborder floatleft" style="width: 69%;">
		<h3 class="catbg headerpadding">', $txt['project_latest_news'], '</h3>
		<div id="project_news" style="overflow: auto; height: 18ex;" class="windowbg2 smallpadding">
			', $txt['project_news_unable_to_connect'], '
		</div>
	</div>
	<div class="tborder floatright" style="width: 30%;">
		<h3 class="catbg headerpadding">', $txt['project_version_info'], '</h3>
		<div style="overflow: auto; height: 18ex;" class="windowbg2 smallpadding">
			', $txt['project_installed_version'], ': <span id="project_installed_version">', $project_version, '</span><br />
			', $txt['project_latest_version'], ': <span id="project_latest_version">???</span>
		</div>
	</div>
	<div style="clear: both"></div>
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		function setProjectNews()
		{
			if (typeof(window.projectNews) == "undefined" || typeof(window.projectNews.length) == "undefined")
					return;

				var str = "<div style=\"margin: 4px; font-size: 0.85em;\">";

				for (var i = 0; i < window.projectNews.length; i++)
				{
					str += "\n	<div style=\"padding-bottom: 2px;\"><a href=\"" + window.projectNews[i].url + "\">" + window.projectNews[i].subject + "</a> on " + window.projectNews[i].time + "</div>";
					str += "\n	<div style=\"padding-left: 2ex; margin-bottom: 1.5ex; border-top: 1px dashed;\">"
					str += "\n		" + window.projectNews[i].message;
					str += "\n	</div>";
				}

				setInnerHTML(document.getElementById("project_news"), str + "</div>");
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

function template_project_admin_below()
{
	global $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project Tools ', $project_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2008
	</div>';
}

?>