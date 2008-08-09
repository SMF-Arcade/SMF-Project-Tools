<?php
// Version: 0.1 Alpha; ManageProjects

function template_projects_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

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
				<b>', $txt['project_developers'], ':</b><br />
			</td>
			<td valign="top" align="left">
				', template_control_autosuggest('developer'), '';

	foreach ($context['project']['developers'] as $member)
		echo '
				<div id="suggest_template_developer_', $member['id'], '">
					<input type="hidden" name="developer[', $member['id'], '][id]" value="', $member['id'], '" />
					<a href="', $scripturl, '?action=profile;u=', $member['id'], '" id="developer_link_to_', $member['id'], '" class="extern" onclick="window.open(this.href, \'_blank\'); return false;">', $member['name'], '</a>
					<input type="image" name="delete_developer" value="', $member['id'], '" onclick="return suggestHandledeveloper.deleteItem(', $member['id'], ');" src="', $settings['images_url'], '/pm_recipient_delete.gif" alt="', $txt['developer_remove'], '" />', '
				</div>';

		echo '
				<div id="suggest_template_developer" style="visibility: hidden; display: none;">
					<input type="hidden" name="developer[::MEMBER_ID::][id]" value="::MEMBER_ID::" />
					<a href="', $scripturl, '?action=profile;u=::MEMBER_ID::" id="developer_link_to_::MEMBER_ID::" class="extern" onclick="window.open(this.href, \'_blank\'); return false;">::MEMBER_NAME::</a>
					<input type="image" onclick="return \'::DELETE_MEMBER_URL::\'" src="', $settings['images_url'], '/pm_recipient_delete.gif" alt="', $txt['developer_remove'], '" /></a>
				</div>
				<br />
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

	foreach ($context['project_tools']['issue_types'] as $key => $type)
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
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=project2" method="post" accept-charset="', $context['character_set'], '">
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
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	template_show_list('categories_list');
}

function template_edit_category()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=managecategories;sa=edit2" method="post" accept-charset="', $context['character_set'], '">
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

// Permissions

function template_profiles_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	template_show_list('profiles_list');
}

function template_profile_edit()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<form action="', $scripturl, '?action=admin;area=projectpermissions;sa=edit" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="headerpadding titlebg">', sprintf($txt['edit_profile'], $context['profile']['name']), '</div>
			<div class="windowbg2">
				<table border="0" width="100%" cellspacing="0" cellpadding="4" class="tborder">
					<tr>
						<th class="catbg3">', $txt['header_group_name'], '</th>
						<th class="catbg3"></th>
					</tr>';

	foreach ($context['groups'] as $group)
	{
		if ($group['can_edit'])
		{
			echo '
					<tr>
						<td class="windowbg2"><a href="', $group['href'], '">', $group['name'], '</a></td>
						<td class="windowbg"></td>
					</tr>';
		}
	}


	echo '
				</table>
			</div>
		</div>
		<input type="hidden" name="profile" value="', $context['profile']['id'], '" />
	</form>';
}

function template_profile_permissions()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<form action="', $scripturl, '?action=admin;area=projectpermissions;sa=permissions2" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="headerpadding titlebg">', sprintf($txt['edit_profile'], $context['profile']['name']), '</div>
			<div class="headerpadding titlebg">', sprintf($txt['edit_profile_group'], $context['group']['name']), '</div>
			<div class="windowbg2">';

	$alternate = true;

	foreach ($context['permissions'] as $id => $permission)
	{
		echo '
				<div class="windowbg', $alternate ? '2' : '', ' clearfix">
					<span class="floatleft"><label for="', $id, '">', $permission['text'], '</label></span>
					<span class="floatright">
						<input type="checkbox" name="permission[', $id,']" value="1"', $permission['checked'] ? ' checked="checked"' : '', ' />
					</span>
				</div>';

		$alternate = !$alternate;
	}

	echo '
			</div>
		</div>
		<input type="hidden" name="profile" value="', $context['profile']['id'], '" />
	</form>';
}

?>