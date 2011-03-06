<?php
/**
 * Template for ManageProjects.php
 *
 * @package admin
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 * @see ManageProjects.php
 */

function template_projects_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('projects_list');
}

function template_edit_project()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	$context['project_form']->render();

	/*echo '
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_developers'], ':</b><br />
			</td>
			<td valign="top" align="left">
				<input type="text" name="developer" id="developer" size="25" tabindex="', $context['tabindex']++, '" />
				<div id="developer_container"></div>
				<script language="JavaScript" type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?rc1"></script>
				<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
					var oDeveloperSuggest = new smc_AutoSuggest({
						sSelf: \'oDeveloperSuggest\',
						sSessionVar: \'', $context['session_var'], '\',
						sSessionId: \'', $context['session_id'], '\',
						sSuggestId: \'developer\',
						sControlId: \'developer\',
						sSearchType: \'member\',
						bItemList: true,
						sPostName: \'developer_list\',
						sURLMask: \'action=profile;u=%item_id%\',
						sItemListContainerId: \'developer_container\',
						aListItems: [';

	foreach (ProjectTools_Project::getCurrent()->developers as $member)
		echo '
							{
								sItemId: ', JavaScriptEscape($member['id']), ',
								sItemName: ', JavaScriptEscape($member['name']), '
							}', $member['last'] ? '' : ',';


		echo '
						]
					});
				// ]]></script>
			</td>
		</tr>

		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_modules'], ':</b><br />
				<span class="smalltext">', $txt['project_modules_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['installed_modules'] as $key => $module)
		echo '
				<input type="checkbox" name="modules[]" value="', $key, '" id="module_', $key, '"', in_array($key, ProjectTools_Project::getCurrent()->modules) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /> <label for="module_', $key, '">', $module['name'], '</label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'modules[]\');" tabindex="', $context['tabindex']++, '" /><br />
				<br />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_trackers'], ':</b><br />
				<span class="smalltext">', $txt['project_trackers_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['issue_trackers'] as $key => $type)
		echo '
				<input type="checkbox" name="trackers[]" value="', $key, '" id="tracker_', $key, '"', in_array($key, ProjectTools_Project::getCurrent()->trackers) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /> <label for="tracker_', $key, '">', $type['name'], '</label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'trackers[]\');" tabindex="', $context['tabindex']++, '" /><br />
				<br />
			</td>
		</tr>

				<input class="button_submit" type="submit" name="delete" value="', $txt['delete_project'], '" onclick="return confirm(\'', $txt['pdelete_warning'], '\');" tabindex="', $context['tabindex']++, '" />';
	echo '
			</td>
		</tr>
	</table>
</form>';*/
}

function template_confirm_project_delete()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="project" value="', ProjectTools_Project::getCurrent()->id, '" />

	<table width="600" cellpadding="4" cellspacing="0" border="0" align="center" class="tborder">
		<tr class="titlebg">
			<td>', $txt['delete_project'], ':</td>
		</tr>
		<tr>
			<td>', $txt['pdelete_warning'], '</td>
		</tr>
		<tr>
			<td align="center" class="windowbg2">
				<input class="button_submit" type="submit" name="delete" value="', $txt['confirm_delete'], '" tabindex="', $context['tabindex']++, '" />
				<input class="button_submit" type="submit" name="cancel" value="', $txt['cancel_delete'], '" tabindex="', $context['tabindex']++, '" />
			</td>
		</tr>
	</table>

	<input type="hidden" name="confirmation" value="1" />
	<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
</form>';
}

?>