<?php
/**
 * Template for IssueReport.php
 *
 * @package issuetracker
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 * @see IssueReport.php
 */

function template_report_issue()
{
	global $context, $settings, $options, $txt, $modSettings;
	
	$context['report_form']->render();

/*

	echo '
	<div id="preview_section"', isset($context['preview_details']) ? '' : ' style="display: none;"', '>
		<table border="0" width="100%" cellspacing="1" cellpadding="3" class="bordercolor" align="center" style="table-layout: fixed;">
			<tr class="titlebg">
				<td id="preview_subject">', empty($context['preview_title']) ? '' : $context['preview_title'], '</td>
			</tr>
			<tr class="windowbg">
				<td class="post" width="100%" id="preview_body">
					', empty($context['preview_details']) ? str_repeat('<br />', 5) : $context['preview_details'], '
				</td>
			</tr>
		</table><br />
	</div>';

	echo '
	<form action="', ProjectTools::get_url(array('project' => ProjectTools_Project::getCurrent()->id, 'area' => 'issues', 'sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<div class="title_bar">
				<h4 class="titlebg">', , '</h4>
			</div>
			<div class="windowbg">
				<span class="topslice"><span></span></span>
				<dl>
					<dd ', empty($context['post_error']['messages']) ? ' style="display: none"' : '', ' id="errors">
						<div style="padding: 0px; font-weight: bold;', empty($context['post_error']['messages']) ? ' display: none;' : '', '" id="error_serious">
							', $txt['error_while_submitting_issue'], '
						</div>
						<div style="color: red; margin: 1ex 0 2ex 3ex;" id="error_list">
							', empty($context['post_error']['messages']) ? '' : implode('<br />', $context['post_error']['messages']), '
						</div>
					</dd>

	if (!empty($context['can_subscribe']))
		echo '
					<dd>
						<input type="hidden" name="issue_subscribe" value="0" />
						<input type="checkbox" id="issue_subscribe" name="issue_subscribe" value="1"', ($context['notify'] || !empty($options['auto_notify']) ? ' checked="checked"' : ''), ' class="check" tabindex="', $context['tabindex']++, '" />
						<label for="issue_subscribe">', $txt['subscribe_to_issue'], '</label>
					</dd>';

	echo '
					<dd class="full center">
						<span class="smalltext">', $context['browser']['is_firefox'] ? $txt['shortcuts_firefox'] : $txt['shortcuts'], '</span><br />
						', template_control_richedit_buttons($context['post_box_name']), '
					</dd>';

	echo '
					<dd class="clear"></dd>
				</dl>
				<span class="botslice"><span></span></span>
			</div>
		</div>

		<input type="hidden" name="project" value="', ProjectTools_Project::getCurrent()->id, '" />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';*/
}

function template_issue_reply()
{
	global $context, $settings, $options, $txt, $modSettings;

	echo '
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		function saveEntities()
		{
			var textFields = ["title", "', $context['post_box_name'], '"];
			for (i in textFields)
				if (document.forms.reportissue.elements[textFields[i]])
					document.forms.reportissue[textFields[i]].value = document.forms.reportissue[textFields[i]].value.replace(/&#/g, "&#38;#");
			for (var i = document.forms.reportissue.elements.length - 1; i >= 0; i--)
				if (document.forms.reportissue.elements[i].name.indexOf("options") == 0)
					document.forms.reportissue.elements[i].value = document.forms.reportissue.elements[i].value.replace(/&#/g, "&#38;#");
		}
	// ]]></script>';

	echo '
	<div id="preview_section"', isset($context['preview_comment']) ? '' : ' style="display: none;"', '>
		<table border="0" width="100%" cellspacing="1" cellpadding="3" class="bordercolor" align="center" style="table-layout: fixed;">
			<tr class="titlebg">
				<td id="preview_subject">', empty($context['preview_title']) ? '' : $context['preview_title'], '</td>
			</tr>
			<tr class="windowbg">
				<td class="post" width="100%" id="preview_body">
					', empty($context['preview_comment']) ? str_repeat('<br />', 5) : $context['preview_comment'], '
				</td>
			</tr>
		</table><br />
	</div>';

	echo '
	<form action="', ProjectTools::get_url(array('area' => 'issues', 'sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<div class="title_bar">
				<h4 class="titlebg">', $txt['issue_reply'], '</h4>
			</div>
			<div class="windowbg">
				<span class="topslice"><span></span></span>
				<dl>
					<dd ', empty($context['post_error']['messages']) ? ' style="display: none"' : '', ' id="errors">
						<div style="padding: 0px; font-weight: bold;', empty($context['post_error']['messages']) ? ' display: none;' : '', '" id="error_serious">
							', $txt['error_while_submitting_issue'], '
						</div>
						<div style="color: red; margin: 1ex 0 2ex 3ex;" id="error_list">
							', empty($context['post_error']['messages']) ? '' : implode('<br />', $context['post_error']['messages']), '
						</div>
					</dd>
					<dd>
						<div id="bbcBox_message"></div>
						<div id="smileyBox_message"></div>
						', template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message'), '
					</dd>';

	if (!empty($context['can_subscribe']))
		echo '
					<dd>
						<input type="hidden" name="issue_subscribe" value="0" />
						<input type="checkbox" id="issue_subscribe" name="issue_subscribe" value="1"', $context['notify'] ? ' checked="checked"' : '', ' class="check" tabindex="', $context['tabindex']++, '" />
						<label for="issue_subscribe">', $txt['subscribe_to_issue'], '</label>
					</dd>';

	echo '
					<dd class="full center">
						<span class="smalltext">', $context['browser']['is_firefox'] ? $txt['shortcuts_firefox'] : $txt['shortcuts'], '</span><br />
						', template_control_richedit_buttons($context['post_box_name']), '
					</dd>
					<dd class="clear"></dd>
				</dl>
				<span class="botslice"><span></span></span>
			</div>
		</div>';

	if (!empty($context['can_issue_update']))
	{
		echo '
		<div class="tborder">
			<div class="cat_bar">
				<h3 class="catbg">
					', $txt['update_issue'], '
				</h3>
			</div>
			<div class="smallpadding windowbg">
				<span class="topslice"><span></span></span>
				<table width="100%">';

		// Title
		echo '
					<tr>
						<td width="30%">', $txt['issue_title'], '</td>
						<td>
							<input name="title" value="', ProjectTools_IssueTracker_Issue::getCurrent()->name, '" tabindex="', $context['tabindex']++, '" />
						</td>
					</tr>';

		// Version
		echo '
					<tr>
						<td width="30%">', $txt['issue_version'], '</td>
						<td>';

		foreach ($context['versions'] as $v)
		{
			echo '
							<input type="checkbox" id="version_', $v['id'], '" name="version[]" value="', $v['id'], '"', isset(ProjectTools_IssueTracker_Issue::getCurrent()->versions[$v['id']])  ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $v['id'], '" style="font-weight: bold">', $v['name'], '</label><br />';

			foreach ($v['sub_versions'] as $subv)
				echo '
							&nbsp;&nbsp;&nbsp; <input type="checkbox" id="version_', $subv['id'], '" name="version[]" value="', $subv['id'], '"', isset(ProjectTools_IssueTracker_Issue::getCurrent()->versions[$subv['id']]) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $subv['id'], '" style="font-weight: bold">', $subv['name'], '</label><br />';
		}

		echo '
						</td>
					</tr>';

		// Type
		echo '
					<tr>
						<td>', $txt['issue_type'], '</td>
						<td>
							<select name="tracker">';

		foreach (ProjectTools_Project::getCurrent()->trackers as $id => $tracker)
			echo '
								<option value="', $id, '" ', $id == ProjectTools_IssueTracker_Issue::getCurrent()->tracker['id'] ? ' selected="selected"' : '', '>', $tracker['tracker']['name'], '</option>';

		echo '
							</select>
						</td>
					</tr>';

		// Category
		echo '
					<tr>
						<td>', $txt['issue_category'], '</td>
						<td>
							<select name="category">
								<option></option>';

		foreach (ProjectTools_Project::getCurrent()->category as $c)
			echo '
								<option value="', $c['id'], '" ', ProjectTools_IssueTracker_Issue::getCurrent()->category['id'] == $c['id'] ? ' selected="selected"' : '', '>', $c['name'], '</option>';
		echo '
							</select>
						</td>
					</tr>';

		if ($context['can_issue_moderate'])
		{
			// Change Status
			echo '
					<tr>
						<td>', $txt['issue_status'], '</td>
						<td>
							<select name="status">';


			foreach ($context['issue_status'] as $status)

				echo '
								<option value="', $status['id'], '"', ProjectTools_IssueTracker_Issue::getCurrent()->status['id'] == $status['id'] ? ' selected="selected"' : '', '>', $status['text'], '</option>';

			echo '
							</select>
						</td>
					</tr>';

			// Target Version
			echo '
					<tr>
						<td>', $txt['issue_version_fixed'], '</td>
						<td>';

		foreach ($context['versions'] as $v)
		{
			echo '
							<input type="checkbox" id="version_fixed_', $v['id'], '" name="version_fixed[]" value="', $v['id'], '"', isset(ProjectTools_IssueTracker_Issue::getCurrent()->versions_fixed[$v['id']])  ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_fixed', $v['id'], '" style="font-weight: bold">', $v['name'], '</label><br />';

			foreach ($v['sub_versions'] as $subv)
				echo '
							&nbsp;&nbsp;&nbsp; <input type="checkbox" id="version_fixed_', $subv['id'], '" name="version_fixed[]" value="', $subv['id'], '"', isset(ProjectTools_IssueTracker_Issue::getCurrent()->versions_fixed[$subv['id']]) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_fixed', $subv['id'], '" style="font-weight: bold">', $subv['name'], '</label><br />';
		}
		
		echo '
						</td>
					</tr>';

			// Assign
			echo '
					<tr>
						<td>', $txt['issue_assigned_to'], '</td>
						<td>
							<select name="assign">
								<option></option>';

			foreach ($context['assign_members'] as $mem)
				echo '
								<option value="', $mem['id'], '"', ProjectTools_IssueTracker_Issue::getCurrent()->assignee['id'] == $mem['id'] ? ' selected="selected"' : '', '>', $mem['name'], '</option>';

			echo '
							</select>
						</td>
					</tr>';
		}


		echo '
				</table>
				<span class="botslice"><span></span></span>
			</div>
		</div>';

	}

	echo '
		<input type="hidden" name="issue" value="', ProjectTools_IssueTracker_Issue::getCurrent()->id, '" />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>