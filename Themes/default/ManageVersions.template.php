<?php
// Version: 0.1; ManageVersions

function template_versions_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	template_show_list('versions_list');
}

function template_edit_version()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageversions;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
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