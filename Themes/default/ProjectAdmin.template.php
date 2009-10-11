<?php
// Version: 0.4; ProjectRoadmap

function template_project_admin_above()
{
	global $txt, $context;

}

function template_project_admin_main()
{
	global $context, $settings, $options, $txt, $modSettings, $project_version, $forum_version;

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
	<script language="JavaScript" type="text/javascript" src="http://service.smfproject.net/news.js?v=', urlencode($project_version), ';smf_version=', urlencode($forum_version), '" defer="defer"></script>';
}

function template_project_admin_maintenance()
{
	global $scripturl, $context, $settings, $options, $txt, $modSettings, $project_version;

	if (!empty($context['maintenance_finished']))
		echo '
	<div class="windowbg" style="margin: 1ex; padding: 1ex 2ex; border: 1px dashed green; color: green;">
		', sprintf($txt['project_maintain_done'], $context['maintenance_action']), !empty($context['maintenance_message']) ? '<div class="smalltext">' . $context['maintenance_message'] . '</div>' : '', '
	</div>';

	echo '
	<table width="100%" cellpadding="4" cellspacing="1" border="0" class="bordercolor">
		<tr class="titlebg">
			<td>', $txt['project_maintenance_repair'], '</td>
		</tr>
		<tr class="windowbg">
			<td>
				<form action="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance;activity=repair" method="post" accept-charset="', $context['character_set'], '">
					<p>', $txt['project_maintenance_repair_info'], '</p>
					<p><input class="button_submit" type="submit" value="', $txt['project_maintain_run_now'], '" tabindex="', $context['tabindex']++, '" /></p>
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</form>
			</td>
		</tr>
	</table>';
}

function template_project_admin_maintenance_repair_list()
{
	global $scripturl, $context, $settings, $options, $txt, $modSettings;

	echo '
	<table width="100%" border="0" cellspacing="0" cellpadding="4" class="tborder">
		<tr class="titlebg">
			<td>', $txt['errors_list'], '</td>
		</tr><tr>
			<td class="windowbg">
				<ul>
					<li>', implode('</li>
					<li>', $context['project_errors']), '</li>
				</ul>
				', $txt['fix_errors'], '
				<b><a href="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance;activity=repair;fix;', $context['session_var'], '=', $context['session_id'], '">', $txt['yes'], '</a> - <a href="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance">', $txt['no'], '</a></b>
			</td>
		</tr>
	</table>';
}

function template_project_admin_below()
{
	global $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project Tools ', $project_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2009
	</div>';
}

?>