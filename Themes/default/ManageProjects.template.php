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