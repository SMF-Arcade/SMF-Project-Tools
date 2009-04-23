<?php
// Version: 0.3; ManageProjects

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
				<input type="text" name="project_name" value="', $context['project']['name'], '" size="30" />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_description'], ':</b><br />
				<span class="smalltext">', $txt['project_description_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="desc" rows="3" cols="35">', $context['project']['description'], '</textarea>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_description_long'], ':</b><br />
				<span class="smalltext">', $txt['project_description_long_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="long_desc" rows="3" cols="35">', $context['project']['long_description'], '</textarea>
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
				<input type="checkbox" id="override_theme" name="override_theme" value="1" ', $context['project']['override_theme'] ? ' checked="checked"' : '', ' /> <label for="override_theme">', $txt['project_theme_override'], '</label>
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
				<input type="text" name="developer" id="developer" size="25" />
				<div id="developer_container"></div>
				<script language="JavaScript" type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?rc1"></script>
				<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
					var oDeveloperSuggest = new smc_AutoSuggest({
						sSelf: \'oDeveloperSuggest\',
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
				<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" /><br />
				<br />
			</td>
		</tr>';

	echo '
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_trackers'], ':</b><br />
				<span class="smalltext">', $txt['project_trackers_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['issue_trackers'] as $key => $type)
		echo '
				<input type="checkbox" name="trackers[]" value="', $key, '" id="tracker_', $key, '"', in_array($key, $context['project']['trackers']) ? ' checked="checked"' : '', ' /> <label for="tracker_', $key, '">', $type['name'], '</label><br />';

	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'trackers[]\');" /><br />
				<br />
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="sc" value="', $context['session_id'], '" />';
	if (isset($context['project']['is_new']))
		echo '
				<input type="submit" name="add" value="', $txt['new_project'], '" onclick="return !isEmptyText(this.form.project_name);" />';
	else
		echo '
				<input type="submit" name="edit" value="', $txt['edit_project'], '" onclick="return !isEmptyText(this.form.project_name);" />
				<input type="submit" name="delete" value="', $txt['delete_project'], '" onclick="return confirm(\'', $txt['pdelete_warning'], '\');" />';
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
				<input type="submit" name="delete" value="', $txt['confirm_delete'], '" />
				<input type="submit" name="cancel" value="', $txt['cancel_delete'], '" />
			</td>
		</tr>
	</table>

	<input type="hidden" name="confirmation" value="1" />
	<input type="hidden" name="sc" value="', $context['session_id'], '" />
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
				<input type="text" name="category_name" value="', $context['category']['name'], '" size="30" />
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="sc" value="', $context['session_id'], '" />';

	if (isset($context['category']['is_new']))
		echo '
				<input type="submit" name="add" value="', $txt['new_category'], '" onclick="return !isEmptyText(this.form.category_name);" />';
	else
		echo '
				<input type="submit" name="edit" value="', $txt['edit_category'], '" onclick="return !isEmptyText(this.form.category_name);" />
				<input type="submit" name="delete" value="', $txt['delete_category'], '" onclick="return confirm(\'', $txt['cdelete_warning'], '\');" />';
	echo '
			</td>
		</tr>
	</table>
</form>';
}

function template_versions_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('versions_list');
}

function template_edit_version()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;section=versions;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="project" value="', $context['version']['project'], '" />
	<input type="hidden" name="version" value="', $context['version']['id'], '" />
	<input type="hidden" name="parent" value="', $context['version']['parent'], '" />

	<table border="0" width="80%" cellspacing="0" cellpadding="4" class="tborder" align="center">
		<tr class="titlebg">
			<td colspan="2">', isset($context['version']['is_new']) ? (!empty($context['version']['parent']) ? $txt['new_version'] : $txt['new_version_group']) : $txt['edit_version'], '</td>
		</tr>
		<tr class="windowbg2">
			<td>
				<b>', $txt['version_name'], ':</b>
			</td>
			<td valign="top" align="left">
				<input type="text" name="version_name" value="', $context['version']['name'], '" size="30" />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_description'], ':</b><br />
				<span class="smalltext">', $txt['version_description_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="desc" rows="3" cols="35">', $context['version']['description'], '</textarea>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_release_date'], ':</b><br />
				<span class="smalltext">', $txt['version_release_date_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['day'], '" size="3" />.
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['month'], '" size="3" />.
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['year'], '" size="5" />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_status'], ':</b><br />
			</td>
			<td valign="top" align="left">
				<select name="status">
					<option value="0"', $context['version']['status'] == 0 ? ' selected="selected"' : '', '>', $txt['version_future'], '</option>
					<option value="1"', $context['version']['status'] == 1 ? ' selected="selected"' : '', '>', $txt['version_testing'], '</option>
					<option value="2"', $context['version']['status'] == 2 ? ' selected="selected"' : '', '>', $txt['version_current'], '</option>
					<option value="3"', $context['version']['status'] == 3 ? ' selected="selected"' : '', '>', $txt['version_obsolete'], '</option>
				</select>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_membergroups'], ':</b><br />
				<span class="smalltext">', $txt['version_membergroups_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['groups'] as $group)
		echo '
				<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';
	echo '
				<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" /><br />
				<br />
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="sc" value="', $context['session_id'], '" />';
	if (isset($context['version']['is_new']))
		echo '
				<input type="submit" name="add" value="', $txt['new_version'], '" onclick="return !isEmptyText(this.form.version_name);" />';
	else
		echo '
				<input type="submit" name="edit" value="', $txt['edit_version'], '" onclick="return !isEmptyText(this.form.version_name);" />
				<input type="submit" name="delete" value="', $txt['delete_version'], '" onclick="return confirm(\'', $txt['vdelete_warning'], '\');" />';
	echo '
			</td>
		</tr>
	</table>
</form>';
}

?>