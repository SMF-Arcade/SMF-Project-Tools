<?php
// Version: 0.3; ProjectPermissions

function template_profiles_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('profiles_list');
	
	echo '
	<br /><br />
	
	<form action="', $scripturl, '?action=admin;area=projectpermissions" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="headerpadding titlebg">', $txt['new_profile'], '</div>
			<div class="windowbg2 clearfix">
				<span class="floatleft"><label for="profile_name">', $txt['profile_name'], '</label></span>
				<span class="floatright"><input id="profile_name" name="profile_name" value="" tabindex="', $context['tabindex']++, '" /></span>
			</div>
			<div class="windowbg2 clearfix">
				<span class="floatleft"><label for="profile_base">', $txt['profile_name'], '</label></span>
				<span class="floatright">
					<select id="profile_base" name="profile_base">';

	foreach ($context['profiles'] as $profile)
		echo '
						<option value="', $profile['id'], '">', $profile['name'], '</option>';

	echo '
					</select>
				</span>
			</div>
			<div style="text-align: right">
				<input type="submit" name="create" value="', $txt['profile_create'], '" tabindex="', $context['tabindex']++, '" />
			</div>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
}

function template_profile_edit()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<form action="', $scripturl, '?action=admin;area=projectpermissions;sa=edit" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="headerpadding titlebg">', sprintf($txt['edit_profile'], $context['profile']['name']), '</div>
			<div class="windowbg2">
				<table border="0" width="100%" cellspacing="0" cellpadding="4" class="bordercolor">
					<tr>
						<th class="catbg3">', $txt['header_group_name'], '</th>
						<th class="catbg3"></th>
					</tr>';

	foreach ($context['groups'] as $group)
	{
		echo '
				<tr>
					<td class="windowbg2">
						', $group['name'], $group['id'] == -1 ? ' (<a href="' . $scripturl . '?action=helpadmin;help=membergroup_guests" onclick="return reqWin(this.href);">?</a>)' : ($group['id'] == 0 ? ' (<a href="' . $scripturl . '?action=helpadmin;help=membergroup_regular_members" onclick="return reqWin(this.href);">?</a>)' : ($group['id'] == 1 ? ' (<a href="' . $scripturl . '?action=helpadmin;help=membergroup_administrator" onclick="return reqWin(this.href);">?</a>)' : ($group['id'] == 3 ? ' (<a href="' . $scripturl . '?action=helpadmin;help=membergroup_moderator" onclick="return reqWin(this.href);">?</a>)' : '')));

		if (!empty($group['children']))
			echo '
						<br /><span class="smalltext">', $txt['permissions_includes_inherited'], ': &quot;', implode('&quot;, &quot;', $group['children']), '&quot;</span>';

		echo '
					</td>
					<td class="windowbg" align="center">', $group['can_search'] ? $group['link'] : $group['num_members'], '</td>
					<td class="windowbg2" align="center"', $group['id'] == 1 ? ' style="font-style: italic;"' : '', '>';
		
		/*if (empty($modSettings['permission_enable_deny']))
			echo '
						', $group['num_permissions']['allowed'];
		else
			echo '
						<div style="float: left; width: 50%;">', $group['num_permissions']['allowed'], '</div> ', empty($group['num_permissions']['denied']) || $group['id'] == 1 ? $group['num_permissions']['denied'] : ($group['id'] == -1 ? '<span style="font-style: italic;">' . $group['num_permissions']['denied'] . '</span>' : '<span style="color: red;">' . $group['num_permissions']['denied'] . '</span>');*/
		echo '
					</td>
					<td class="windowbg2" align="center">', $group['allow_modify'] ? '<a href="' . $group['edit_href'] . '">' . ($context['can_modify'] ? $txt['permissions_modify'] : $txt['permissions_view']). '</a>' : '', '</td>
					<td class="windowbg" align="center">', $group['allow_modify'] ? '<input type="checkbox" name="group[]" value="' . $group['id'] . '" class="check" />' : '', '</td>
				</tr>';
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
			<div class="headerpadding catbg3">', sprintf($txt['edit_profile_group'], $context['group']['name']), '</div>
			<div class="windowbg2">';

	$alternate = true;

	foreach ($context['permissions'] as $id => $permission)
	{
		echo '
				<div class="windowbg', $alternate ? '2' : '', ' headerpadding clearfix">
					<span class="floatleft"><label for="', $id, '">', $permission['text'], '</label></span>
					<span class="floatright">
						<input type="hidden" name="permission[', $id,']" value="0" />
						<input type="checkbox" id="', $id, '" name="permission[', $id,']" value="1"', $permission['checked'] ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" />
					</span>
				</div>';

		$alternate = !$alternate;
	}

	echo '
				<div style="text-align: right">
					<input type="submit" name="save" value="', $txt['permission_save'], '" tabindex="', $context['tabindex']++, '" />
				</div>
			</div>
		</div>

		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="profile" value="', $context['profile']['id'], '" />
		<input type="hidden" name="group" value="', $context['group']['id'], '" />
	</form>';
}

?>
