<?php
// Version: 0.5; ManageProjects

function template_projects_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('projects_list');
}

function template_edit_project()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="project" value="', $context['project']['id'], '" />

	<table border="0" width="80%" cellspacing="0" cellpadding="4" class="tborder" align="center">
		<tr class="titlebg">
			<td colspan="2">', isset($context['project']['is_new']) ? $txt['new_project'] : $txt['edit_project'], '</td>
		</tr>
		<tr class="windowbg2">
			<td>
				<b>', $txt['project_name'], ':</b>
			</td>
			<td valign="top" align="left">
				<input type="text" name="project_name" value="', $context['project']['name'], '" size="30" tabindex="', $context['tabindex']++, '" />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_description'], ':</b><br />
				<span class="smalltext">', $txt['project_description_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="desc" rows="3" cols="35" tabindex="', $context['tabindex']++, '">', $context['project']['description'], '</textarea>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_description_long'], ':</b><br />
				<span class="smalltext">', $txt['project_description_long_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="long_desc" rows="3" cols="35" tabindex="', $context['tabindex']++, '">', $context['project']['long_description'], '</textarea>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_profile'], ':</b><br />
			</td>
			<td valign="top" align="left">
				<select id="profile_profile" name="project_profile">';

	foreach ($context['profiles'] as $profile)
		echo '
					<option value="', $profile['id'], '"', $profile['id'] == $context['project']['profile'] ? ' selected="selected"' : '', '>', $profile['name'], '</option>';

	echo '
				</select>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_theme'], ':</b><br />
			</td>
			<td valign="top" align="left">
				<select name="project_theme">
					<option value="0">', $txt['project_theme_default'], '</option>';

	foreach ($context['themes'] as $theme)
		echo '
					<option value="', $theme['id'], '"', $context['project']['theme'] == $theme['id'] ? ' selected="selected"' : '', '>', $theme['name'], '</option>';

	echo '
				</select><br />
				<input type="checkbox" id="override_theme" name="override_theme" value="1" ', $context['project']['override_theme'] ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /> <label for="override_theme">', $txt['project_theme_override'], '</label>
			</td>
		</tr>

		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_board_index'], ':</b><br />
				<span class="smalltext">', $txt['project_board_index_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<select name="category">
					<option value="0">', $txt['project_board_index_dont_show'], '</option>';

	foreach ($context['board_categories'] as $cat)
		echo '
					<option value="', $cat['id'], '"', $context['project']['category'] == $cat['id'] ? ' selected="selected"' : '', '>', $cat['name'], '</option>';

	echo '
				</select>
				<select name="category_position">
					<option value="first"', $context['project']['category_position'] == 'first' ? ' selected="selected"' : '', '>', $txt['project_board_index_before'], '</option>
					<option value="last"', $context['project']['category_position'] == 'last' ? ' selected="selected"' : '', '>', $txt['project_board_index_after'], '</option>
				</select>
			</td>
		</tr>
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

	foreach ($context['project']['developers'] as $member)
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
				<b>', $txt['project_membergroups'], ':</b><br />
				<span class="smalltext">', $txt['project_membergroups_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['groups'] as $group)
		echo '
				<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" tabindex="', $context['tabindex']++, '" /><br />
				<br />
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
				<input type="checkbox" name="modules[]" value="', $key, '" id="module_', $key, '"', in_array($key, $context['project']['modules']) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /> <label for="module_', $key, '">', $module['name'], '</label><br />';

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
				<input type="checkbox" name="trackers[]" value="', $key, '" id="tracker_', $key, '"', in_array($key, $context['project']['trackers']) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /> <label for="tracker_', $key, '">', $type['name'], '</label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'trackers[]\');" tabindex="', $context['tabindex']++, '" /><br />
				<br />
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';
	if (isset($context['project']['is_new']))
		echo '
				<input class="button_submit" type="submit" name="add" value="', $txt['new_project'], '" onclick="return !isEmptyText(this.form.project_name);" tabindex="', $context['tabindex']++, '" />';
	else
		echo '
				<input class="button_submit" type="submit" name="edit" value="', $txt['edit_project'], '" onclick="return !isEmptyText(this.form.project_name);" tabindex="', $context['tabindex']++, '" />
				<input class="button_submit" type="submit" name="delete" value="', $txt['delete_project'], '" onclick="return confirm(\'', $txt['pdelete_warning'], '\');" tabindex="', $context['tabindex']++, '" />';
	echo '
			</td>
		</tr>
	</table>
</form>';
}

function template_confirm_project_delete()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="project" value="', $context['project']['id'], '" />

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

function template_categories_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('categories_list');
}

function template_edit_category()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;section=categories;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="category" value="', $context['category']['id'], '" />
	<input type="hidden" name="project" value="', $context['category']['project'], '" />

	<table border="0" width="80%" cellspacing="0" cellpadding="4" class="tborder" align="center">
		<tr class="titlebg">
			<td colspan="2">', isset($context['category']['is_new']) ? $txt['new_category'] : $txt['edit_category'], '</td>
		</tr>
		<tr class="windowbg2">
			<td>
				<b>', $txt['category_name'], ':</b>
			</td>
			<td valign="top" align="left">
				<input type="text" name="category_name" value="', $context['category']['name'], '" size="30" tabindex="', $context['tabindex']++, '" />
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';

	if (isset($context['category']['is_new']))
		echo '
				<input class="button_submit" type="submit" name="add" value="', $txt['new_category'], '" onclick="return !isEmptyText(this.form.category_name);" tabindex="', $context['tabindex']++, '" />';
	else
		echo '
				<input class="button_submit" type="submit" name="edit" value="', $txt['edit_category'], '" onclick="return !isEmptyText(this.form.category_name);" tabindex="', $context['tabindex']++, '" />
				<input class="button_submit" type="submit" name="delete" value="', $txt['delete_category'], '" onclick="return confirm(\'', $txt['cdelete_warning'], '\');" tabindex="', $context['tabindex']++, '" />';
	echo '
			</td>
		</tr>
	</table>
</form>';
}

?>