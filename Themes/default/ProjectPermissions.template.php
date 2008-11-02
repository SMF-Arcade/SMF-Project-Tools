<?php
// Version: 0.1; ProjectPermissions

function template_profiles_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	template_show_list('profiles_list');
}

function template_profile_new()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<form action="', $scripturl, '?action=admin;area=projectpermissions;sa=new2" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="headerpadding titlebg">', $txt['new_profile'], '</div>
			<div class="windowbg2 clearfix">
				<span class="floatleft"><label for="profile_name">', $txt['profile_name'], '</label></span>
				<span class="floatright"><input id="profile_name" name="profile_name" value="', $context['profile']['name'], '" /></span>
			</div>
			<div class="windowbg2 clearfix">
				<span class="floatleft"><label for="profile_base">', $txt['profile_name'], '</label></span>
				<span class="floatright">
					<select id="profile_base" name="profile_name">';

	foreach ($context['profiles'] as $profile)
		echo '
						<option value="', $profile['id'], '">', $profile['name'], '</option>';

	echo '
					</select>
				</span>
			</div>
			<div style="text-align: right">
				<input type="submit" name="save" value="', $txt['profile_create'], '" />
			</div>
		</div>
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
						<input type="checkbox" id="', $id, '" name="permission[', $id,']" value="1"', $permission['checked'] ? ' checked="checked"' : '', ' />
					</span>
				</div>';

		$alternate = !$alternate;
	}

	echo '
				<div style="text-align: right">
					<input type="submit" name="save" value="', $txt['permission_save'], '" />
				</div>
			</div>
		</div>
		<input type="hidden" name="profile" value="', $context['profile']['id'], '" />
		<input type="hidden" name="group" value="', $context['group']['id'], '" />
	</form>';
}

?>
