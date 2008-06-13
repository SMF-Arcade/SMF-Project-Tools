<?php
// Version: 0.1 Alpha; ManageProjects

function template_projects_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<table class="bordercolor" align="center" border="0" cellpadding="4" cellspacing="1" width="100%">
		<tr class="titlebg">
			<td><span style="float: left">', $txt['edit_projects'], '</span><span style="float: right"><a href="', $scripturl, '?action=admin;area=manageprojects;sa=newproject">', $txt['new_project'], '</a></span></td>
		</tr>';

	if (count($context['projects']) > 0)
	{
		foreach ($context['projects'] as $i => $project)
		{
			echo '
		<tr>
			<td class="catbg" height="18">', $project['name'], ' (<a href="', $project['link'], '">', $txt['edit_project'], '</a>)</td>
		</tr>
		<tr>
			<td class="windowbg2" valign="top" width="100%">
				<form action="', $scripturl, '?action=admin;area=manageprojects;sa=newversion;project=', $project['id'], '" method="post" accept-charset="', $context['character_set'], '">
					<table width="100%" border="0" cellpadding="1" cellspacing="0">
						<tr>
							<td style="padding-left: 1ex;" colspan="3"><b>', $txt['version_name'], '</b></td>
						</tr>';

			$alternate = false;

			foreach ($project['versions'] as $version)
			{
				echo '
						<tr class="windowbg', $alternate ? '2' : '', '">
							<td style="padding-left: 5px;">', $version['name'], '</td>
							<td width="10%" align="right">
								<a href="', $scripturl, '?action=admin;area=manageprojects;sa=version;version=', $version['id'], '">', $txt['edit_version'], '</a>
							</td>
							<td width="10%" style="padding-right: 1ex;" align="right">
								<a href="', $scripturl, '?action=admin;area=manageprojects;sa=newversion;project=', $project['id'], ';parent=', $version['id'], '">', $txt['new_version'], '</a>
							</td>
						</tr>';

				$alternate = !$alternate;

				foreach ($version['sub_versions'] as $subversion)
				{
					echo '
						<tr class="windowbg', $alternate ? '2' : '', '">
							<td style="padding-left: 35px;">', $subversion['name'], '</td>
							<td width="10%" align="right">
								<a href="', $scripturl, '?action=admin;area=manageprojects;sa=version;version=', $subversion['id'], '">', $txt['edit_version'], '</a>
							</td>
							<td width="10%" style="padding-right: 1ex;" align="right"></td>
						</tr>';

					$alternate = !$alternate;
				}
			}

			echo '
						<tr>
							<td colspan="3" align="right"><br /><input type="submit" value="', $txt['new_version_group'], '" /></td>
						</tr>
					</table>
					<input type="hidden" name="sc" value="', $context['session_id'], '" />
				</form>

				<form action="', $scripturl, '?action=admin;area=manageprojects;sa=newcategory;project=', $project['id'], '" method="post" accept-charset="', $context['character_set'], '">
					<table width="100%" border="0" cellpadding="1" cellspacing="0">
						<tr>
							<td style="padding-left: 1ex;" colspan="3"><b>', $txt['category_name'], '</b></td>
						</tr>';

			$alternate = false;

			foreach ($project['categories'] as $cat)
			{
				echo '
						<tr class="windowbg', $alternate ? '2' : '', '">
							<td style="padding-left: 5px;">', $cat['name'], '</td>

							<td width="10%" style="padding-right: 1ex;" align="right">
								<a href="', $scripturl, '?action=admin;area=manageprojects;sa=category;category=', $cat['id'], '">', $txt['edit_category'], '</a>
							</td>
						</tr>';

				$alternate = !$alternate;
			}

			echo '
						<tr>
							<td colspan="3" align="right"><br /><input type="submit" value="', $txt['new_category'], '" /></td>
						</tr>
					</table>
					<input type="hidden" name="sc" value="', $context['session_id'], '" />

				</form>
			</td>
		</tr>';
		}
	}
	else
	{
		echo '
		<tr>
			<td class="catbg3" colspan="3"><b>', $txt['no_projects'], '</b></td>
		</tr>';
	}

	echo '
		</table>
	</div>
</form>';
}

function template_edit_project()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=project2" method="post" accept-charset="', $context['character_set'], '">
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
				<b>', $txt['project_public_access'], ':</b><br />
				<span class="smalltext">', $txt['project_public_access_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<select name="public_access">
					<option value="0"', $context['project']['public_access'] == 0 ? ' selected="selected"' : '', '>', $txt['access_level_none'], '</a>
					<option value="1"', $context['project']['public_access'] == 1 ? ' selected="selected"' : '', '>', $txt['access_level_viewer'], '</a>
					<option value="5"', $context['project']['public_access'] == 5 ? ' selected="selected"' : '', '>', $txt['access_level_report'], '</a>
				</select>
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
					<select name="developer[', $member['id'], '][level]">
						<option value="50"', $member['level'] == 50 ? ' selected="selected"' : '', '>', $txt['access_level_owner'], '</a>
						<option value="45"', $member['level'] == 45 ? ' selected="selected"' : '', '>', $txt['access_level_admin'], '</a>
						<option value="40"', $member['level'] == 40 ? ' selected="selected"' : '', '>', $txt['access_level_developer'], '</a>
						<option value="35"', $member['level'] == 35 ? ' selected="selected"' : '', '>', $txt['access_level_member'], '</a>
						<option value="30"', $member['level'] == 30 ? ' selected="selected"' : '', '>', $txt['access_level_beta'], '</a>
					</select>
					<input type="image" name="delete_developer" value="', $member['id'], '" onclick="return suggestHandledeveloper.deleteItem(', $member['id'], ');" src="', $settings['images_url'], '/pm_recipient_delete.gif" alt="', $txt['developer_remove'], '" />', '
				</div>';

		echo '
				<div id="suggest_template_developer" style="visibility: hidden; display: none;">
					<input type="hidden" name="developer[{MEMBER_ID}][id]" value="{MEMBER_ID}" />
					<a href="', $scripturl, '?action=profile;u={MEMBER_ID}" id="developer_link_to_{MEMBER_ID}" class="extern" onclick="window.open(this.href, \'_blank\'); return false;">{MEMBER_NAME}</a>
					<select name="developer[{MEMBER_ID}][level]">
						<option value="50">', $txt['access_level_owner'], '</option>
						<option value="45">', $txt['access_level_admin'], '</option>
						<option value="40">', $txt['access_level_developer'], '</option>
						<option value="35">', $txt['access_level_member'], '</option>
						<option value="30">', $txt['access_level_beta'], '</option>
							</select>
					<input type="image" onclick="return \'{DELETE_MEMBER_URL}\'" src="', $settings['images_url'], '/pm_recipient_delete.gif" alt="', $txt['developer_remove'], '" /></a>
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
				<label for="groups_', $group['id'], '"><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label>
				<select name="groups[', $group['id'], ']">
					<option value="0"', $group['level'] == 0 ? ' selected="selected"' : '', '>', $txt['access_level_no_group'], '</option>
					<option value="1"', $group['level'] == 1 ? ' selected="selected"' : '', '>', $txt['access_level_viewer'], '</option>
					<option value="5"', $group['level'] == 5 ? ' selected="selected"' : '', '>', $txt['access_level_report'], '</option>
					<option value="30"', $group['level'] == 30 ? ' selected="selected"' : '', '>', $txt['access_level_beta'], '</option>
				</select>';

echo '
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['project_trackers'], ':</b><br />
				<span class="smalltext">', $txt['project_trackers_desc'], '</span><br />
			</td>
			<td valign="top" align="left">';

	foreach ($context['project_tools']['issue_types'] as $key => $type)
		echo '
				<label for="tracker_', $key, '"><input type="checkbox" name="trackers[]" value="', $key, '" id="tracker_', $key, '"', in_array($key, $context['project']['trackers']) ? ' checked="checked"' : '', ' /><span>', $type['name'], '</span></label><br />';

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

function template_edit_version()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=version2" method="post" accept-charset="', $context['character_set'], '">
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
		</tr>';

	if (!empty($context['version']['parent']))
		echo '
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
					<option value="1"', $context['version']['status'] == 1 ? ' selected="selected"' : '', '>', $txt['version_alpha'], '</option>
					<option value="2"', $context['version']['status'] == 2 ? ' selected="selected"' : '', '>', $txt['version_beta'], '</option>
					<option value="3"', $context['version']['status'] == 3 ? ' selected="selected"' : '', '>', $txt['version_rc'], '</option>
					<option value="4"', $context['version']['status'] == 4 ? ' selected="selected"' : '', '>', $txt['version_stable'], '</option>
					<option value="5"', $context['version']['status'] == 5 ? ' selected="selected"' : '', '>', $txt['version_stable_rec'], '</option>
					<option value="6"', $context['version']['status'] == 6 ? ' selected="selected"' : '', '>', $txt['version_obsolute'], '</option>
				</select>
			</td>
		</tr>';

	echo '
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

function template_edit_category()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
<form action="', $scripturl, '?action=admin;area=manageprojects;sa=category2" method="post" accept-charset="', $context['character_set'], '">
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

?>