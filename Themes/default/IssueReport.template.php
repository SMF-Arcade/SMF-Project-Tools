<?php
// Version: 0.5; IssueReport

function template_report_issue()
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
	<form action="', project_get_url(array('project' => $context['project']['id'], 'area' => 'issues', 'sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="titlebg"><span class="left"></span>', $txt['report_issue'], '</h4>
			<div class="windowbg">
				<span class="topslice"><span><!-- // --></span></span>
				<dl>
					<dd ', empty($context['post_error']['messages']) ? ' style="display: none"' : '', ' id="errors">
						<div style="padding: 0px; font-weight: bold;', empty($context['post_error']['messages']) ? ' display: none;' : '', '" id="error_serious">
							', $txt['error_while_submitting_issue'], '
						</div>
						<div style="color: red; margin: 1ex 0 2ex 3ex;" id="error_list">
							', empty($context['post_error']['messages']) ? '' : implode('<br />', $context['post_error']['messages']), '
						</div>
					</dd>
					<dt>', $txt['issue_title'], ':</dt>
					<dd>
						<input type="text" name="title" value="', $context['issue']['title'], '" tabindex="', $context['tabindex']++, '" size="80" maxlength="80" />
					</dd>
					<dt>', $txt['private_issue'], ':</dt>
					<dd>
						<input type="checkbox" name="private" value="1" tabindex="', $context['tabindex']++, '"', !empty($context['issue']['private']) ? ' checked="checked"' : '', '/>
					</dd>';

	if (count($context['project']['trackers']) > 1)
	{
		echo '
					<dt>', $txt['issue_type'], ':</dt>
					<dd>';

		foreach ($context['project']['trackers'] as $id => $tracker)
		{
			echo '
						<div class="toggle">
							<input type="radio" id="type_', $id, '" name="tracker" value="', $id, '" tabindex="', $context['tabindex']++, '"', $id == $context['issue']['tracker'] ? ' checked="checked"' : '', '/> <label for="type_', $id, '">', $tracker['tracker']['name'], '</label>
						</div>';
		}

		echo '
					</dd>';
	}

	if ($context['show_version'])
	{
		echo '
					<dt>', $txt['issue_version'], ':</dt>
					<dd>';


		foreach ($context['versions'] as $v)
		{
			echo '
							<input type="checkbox" id="version_', $v['id'], '" name="version[]" value="', $v['id'], '"', in_array($v['id'], $context['issue']['version'])  ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $v['id'], '" style="font-weight: bold">', $v['name'], '</label><br />';

			foreach ($v['sub_versions'] as $subv)
				echo '
							&nbsp;&nbsp;&nbsp; <input type="checkbox" id="version_', $subv['id'], '" name="version[]" value="', $subv['id'], '"', in_array($subv['id'], $context['issue']['version']) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $subv['id'], '" style="font-weight: bold">', $subv['name'], '</label><br />';
		}
		
		echo '
					</dd>';

	}

	if ($context['show_category'])
	{
		echo '
					<dt>', $txt['issue_category'], ':</dt>
					<dd>
						<select name="category" tabindex="', $context['tabindex']++, '">
							<option></option>';

		foreach ($context['project']['category'] as $c)
			echo '
							<option value="', $c['id'], '" ', $context['issue']['category'] == $c['id'] ? ' selected="selected"' : '', '>', $c['name'], '</option>';
		echo '
						</select>
					</dd>';
	}

	echo '
					<dd>
						<div id="bbcBox_message"></div>
						<div id="smileyBox_message"></div>
						', template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message'), '
					</dd>';

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
				<span class="botslice"><span><!-- // --></span></span>
			</div>
		</div>

		<input type="hidden" name="project" value="', $context['project']['id'], '" />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
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
	<form action="', project_get_url(array('area' => 'issues', 'sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="titlebg"><span class="left"><!-- // --></span>', $txt['issue_reply'], '</h4>
			<div class="windowbg">
				<span class="topslice"><span><!-- // --></span></span>
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
				<span class="botslice"><span><!-- // --></span></span>
			</div>
		</div>';

	if (!empty($context['can_issue_update']))
	{
		echo '
		<div class="tborder">
			<h3 class="catbg"><span class="left"><span><!-- // --></span></span>', $txt['update_issue'], '</h3>
			<div class="smallpadding windowbg">
				<span class="topslice"><span><!-- // --></span></span>
				<table width="100%">';

		// Title
		echo '
					<tr>
						<td width="30%">', $txt['issue_title'], '</td>
						<td>
							<input name="title" value="', $context['current_issue']['name'], '" tabindex="', $context['tabindex']++, '" />
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
							<input type="checkbox" id="version_', $v['id'], '" name="version[]" value="', $v['id'], '"', isset($context['current_issue']['versions'][$v['id']])  ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $v['id'], '" style="font-weight: bold">', $v['name'], '</label><br />';

			foreach ($v['sub_versions'] as $subv)
				echo '
							&nbsp;&nbsp;&nbsp; <input type="checkbox" id="version_', $subv['id'], '" name="version[]" value="', $subv['id'], '"', isset($context['current_issue']['versions'][$subv['id']]) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_', $subv['id'], '" style="font-weight: bold">', $subv['name'], '</label><br />';
		}

		echo '
						</td>
					</tr>';

		// Type
		echo '
					<tr>
						<td>', $txt['issue_type'], '</td>
						<td>
							<select name="type">';

		foreach ($context['project']['trackers'] as $id => $tracker)
			echo '
								<option value="', $id, '" ', $id == $context['current_issue']['tracker']['id'] ? ' selected="selected"' : '', '>', $tracker['tracker']['name'], '</option>';

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

		foreach ($context['project']['category'] as $c)
			echo '
								<option value="', $c['id'], '" ', $context['current_issue']['category']['id'] == $c['id'] ? ' selected="selected"' : '', '>', $c['name'], '</option>';
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
								<option value="', $status['id'], '"', $context['current_issue']['status']['id'] == $status['id'] ? ' selected="selected"' : '', '>', $status['text'], '</option>';

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
							<input type="checkbox" id="version_fixed_', $v['id'], '" name="version_fixed[]" value="', $v['id'], '"', isset($context['current_issue']['versions_fixed'][$v['id']])  ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_fixed', $v['id'], '" style="font-weight: bold">', $v['name'], '</label><br />';

			foreach ($v['sub_versions'] as $subv)
				echo '
							&nbsp;&nbsp;&nbsp; <input type="checkbox" id="version_fixed_', $subv['id'], '" name="version_fixed[]" value="', $subv['id'], '"', isset($context['current_issue']['versions_fixed'][$subv['id']]) ? ' checked="checked"' : '', ' tabindex="', $context['tabindex']++, '"> <label for="version_fixed', $subv['id'], '" style="font-weight: bold">', $subv['name'], '</label><br />';
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
								<option value="', $mem['id'], '"',$context['current_issue']['assignee']['id'] == $mem['id'] ? ' selected="selected"' : '', '>', $mem['name'], '</option>';

			echo '
							</select>
						</td>
					</tr>';
		}


		echo '
				</table>
				<span class="botslice"><span><!-- // --></span></span>
			</div>
		</div>';

	}

	echo '
		<input type="hidden" name="issue" value="', $context['current_issue']['id'], '" />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>