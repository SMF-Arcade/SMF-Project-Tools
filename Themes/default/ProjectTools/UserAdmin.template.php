<?php
/**
 * Template for UserAdmin.php
 *
 * @package UserAdmin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 * @see UserAdmin.php
 */

/**
 *
 */
function template_select_project()
{
	global $context, $settings, $options, $txt, $modSettings;
	
		echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['project'], '
		</h3>
	</div>
	<div id="projects_table">
		<table class="table_list">
			<thead>
				<tr><th colspan="4"></th></tr>
			</thead>
			<tfoot>
				<tr><td colspan="4"></td></tr>
			</tfoot>
			<tbody class="content">';

		foreach ($context['admin_projects'] as $i => $project)
		{
			echo '
				<tr>
					<td class="windowbg2 info">
						<h4><a href="', $project['href'], '">', $project['name'], '</a></h4>
					</td>
				</tr>';
		}

		echo '
			</tbody>
		</table>
	</div>';
}

/**
 *
 */
function template_admin_frontpage()
{
	global $context, $settings, $options, $txt, $modSettings;
	
}


/**
 *
 */
function template_versions_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('versions_list');
}

/**
 *
 */
function template_edit_version()
{
	global $context, $settings, $options, $scripturl, $txt, $project, $modSettings;

	echo '
<form action="', ProjectTools::get_admin_url(array('project' => $project, 'area' => 'version', 'sa' => 'edit2')), '" method="post" accept-charset="', $context['character_set'], '">
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
				<input type="text" name="version_name" value="', $context['version']['name'], '" size="30" tabindex="', $context['tabindex']++, '" />
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_description'], ':</b><br />
				<span class="smalltext">', $txt['version_description_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<textarea name="desc" rows="3" cols="35" tabindex="', $context['tabindex']++, '">', $context['version']['description'], '</textarea>
			</td>
		</tr>
		<tr valign="top" class="windowbg2">
			<td>
				<b>', $txt['version_release_date'], ':</b><br />
				<span class="smalltext">', $txt['version_release_date_desc'], '</span><br />
			</td>
			<td valign="top" align="left">
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['day'], '" size="3" tabindex="', $context['tabindex']++, '" />.
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['month'], '" size="3" tabindex="', $context['tabindex']++, '" />.
				<input type="text" name="release_date[]" value="', $context['version']['release_date']['year'], '" size="5" tabindex="', $context['tabindex']++, '" />
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
			<td valign="top" align="left">
				<input type="checkbox" id="permission_inherit" name="permission_inherit" value="1"', $context['version']['permission_inherit'] ? ' checked="checked"' : '', ' onclick="refreshOptions();" class="check" /> <label for="permission_inherit">', $txt['version_inherit_permission'], '</label><br />
				<div id="section_membergroups">';

	foreach ($context['groups'] as $group)
		echo '
					<label for="groups_', $group['id'], '"><input type="checkbox" name="groups[]" value="', $group['id'], '" id="groups_', $group['id'], '"', $group['checked'] ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '" /><span', $group['is_post_group'] ? ' style="border-bottom: 1px dotted;" title="' . $txt['pgroups_post_group'] . '"' : '', '>', $group['name'], '</span></label><br />';
	echo '
					<i>', $txt['check_all'], '</i> <input type="checkbox" onclick="invertAll(this, this.form, \'groups[]\');" tabindex="', $context['tabindex']++, '" /><br />
				</div>
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="2" align="right">
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />';
	if (isset($context['version']['is_new']))
		echo '
				<input class="button_submit" type="submit" name="add" value="', $txt['new_version'], '" onclick="return !isEmptyText(this.form.version_name);" tabindex="', $context['tabindex']++, '" />';
	else
		echo '
				<input class="button_submit" type="submit" name="edit" value="', $txt['edit_version'], '" onclick="return !isEmptyText(this.form.version_name);" tabindex="', $context['tabindex']++, '" />
				<input class="button_submit" type="submit" name="delete" value="', $txt['delete_version'], '" onclick="return confirm(\'', $txt['vdelete_warning'], '\');" tabindex="', $context['tabindex']++, '" />';
	echo '
			</td>
		</tr>
	</table>
</form>';

	// Script for showing / hiding elements
	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		function refreshOptions()
		{
			var inheritEnabled = document.getElementById("permission_inherit").checked;

			// What to show?
			document.getElementById("section_membergroups").style.display = inheritEnabled ? "none" : "";
		}
		refreshOptions();
	// ]]></script>';
}

/**
 *
 */
function template_categories_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	template_show_list('categories_list');
}

/**
 *
 */
function template_edit_category()
{
	global $context, $settings, $options, $txt, $modSettings, $project;

	echo '
<form action="', ProjectTools::get_admin_url(array('project' => $project, 'area' => 'category', 'sa' => 'edit2')), '" method="post" accept-charset="', $context['character_set'], '">
	<input type="hidden" name="category" value="', $context['category']['id'], '" />

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