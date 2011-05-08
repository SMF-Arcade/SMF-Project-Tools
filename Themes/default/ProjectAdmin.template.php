<?php
/**
 * Template for ProjectAdmin.php
 *
 * @package admin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 * @see ProjectAdmin.php
 */

function template_project_admin_above()
{
	global $txt, $context;

}

function template_project_admin_main()
{
	global $context, $settings, $options, $txt, $modSettings, $forum_version;

	echo '
	<div class="tborder floatleft" style="width: 69%;">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['project_latest_news'], '
			</h3>
		</div>
		<div class="windowbg2 smallpadding">
			<span class="topslice"><span></span></span>
			<div id="project_news" style="overflow: auto; height: 18ex;" class="windowbg2 smallpadding">
			', $txt['project_news_unable_to_connect'], '
			</div>
			<span class="botslice"><span></span></span>
		</div>
	</div>
	<div class="tborder floatright" style="width: 30%;">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['project_version_info'], '
			</h3>
		</div>
		<div class="windowbg2 smallpadding">
			<span class="topslice"><span></span></span>
			<div style="overflow: auto; height: 18ex;" class="windowbg2 smallpadding">
				', $txt['project_installed_version'], ': <span id="project_installed_version">', ProjectTools::$version, '</span><br />
				', $txt['project_latest_version'], ': <span id="project_latest_version">???</span>
			</div>
			<span class="botslice"><span></span></span>
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
	<script language="JavaScript" type="text/javascript" src="http://service.smfproject.net/news.js?v=', urlencode(ProjectTools::$version), ';smf_version=', urlencode($forum_version), '" defer="defer"></script>';
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
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['project_maintenance_repair'], '
		</h3>
	</div>
	<div class="windowbg2 smallpadding">
		<span class="topslice"><span></span></span>
			<form action="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance;activity=repair" method="post" accept-charset="', $context['character_set'], '">
				<p>', $txt['project_maintenance_repair_info'], '</p>
				<p><input class="button_submit" type="submit" value="', $txt['project_maintain_run_now'], '" tabindex="', $context['tabindex']++, '" /></p>
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			</form>
		<span class="botslice"><span></span></span>
	</div>';
}

function template_project_admin_maintenance_repair_list()
{
	global $scripturl, $context, $settings, $options, $txt, $modSettings;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['errors_list'], '
		</h3>
	</div>
	<div class="windowbg2 smallpadding">
		<span class="topslice"><span></span></span>
			<ul>
					<li>', implode('</li>
					<li>', $context['project_errors']), '</li>
				</ul>
				', $txt['fix_errors'], '
				<b><a href="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance;activity=repair;fix;', $context['session_var'], '=', $context['session_id'], '">', $txt['yes'], '</a> - <a href="', $scripturl, '?action=admin;area=projectsadmin;sa=maintenance">', $txt['no'], '</a></b>
		<span class="botslice"><span></span></span>
	</div>';
}

function template_project_admin_extensions()
{
	global $scripturl, $context, $settings, $options, $txt, $modSettings;
	
	echo '
	<form action="', $scripturl, '?action=admin;area=projectsadmin;sa=extensions;save" method="post">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['project_general_extensions'], '
			</h3>
		</div>
		<table class="table_grid" cellspacing="0" width="100%">
			<thead>
				<tr class="catbg">
					<th scope="col" class="first_th">', $txt['extension_enable'], '</th>
					<th scope="col">', $txt['extension_name'], '</th>
					<th scope="col" class="last_th">', $txt['extension_version'], '</th>
				</tr>
			</thead>
			<tbody>';

	foreach ($context['installed_extensions'] as $id => $extension)
	{
		echo '
				<tr>
					<td><input type="checkbox" name="extension[]" value="', $id, '"', $extension['enabled'] ? ' checked="checked"' : '', '', !$extension['can_enable'] || !$extension['can_disable'] ? ' disabled="disabled"' : '', ' /></td>
					<td>', $extension['name'], ' (', $extension['filename'], ')<br />';
				
		if (!empty($extension['modules']))
		{
			echo '
						', $txt['extension_modules'], ':', '
						<ul>';
					
		foreach ($extension['modules'] as $module)
			echo '
							<li>', $module['class_name'], '</li>';
					
		echo '
					
						</ul>';
		}
					
		echo '
				</td>
				<td>', $extension['version'], ' (', $txt['extension_api_version'], ': ', $extension['api_version'], ')
			</tr>';
	}
	
	echo '
			</tbody>
		</table>
		<input type="submit" name="save" value="', $txt['save'], '" />
	</form>';
}

function template_project_admin_below()
{
	global $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project Tools ', ProjectTools::$version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2011
	</div>';
}

?>