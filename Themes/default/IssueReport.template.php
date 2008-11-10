<?php
// Version: 0.2; IssueReport

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
	<form action="', project_get_url(array('sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['report_issue'], '</h4>
			<div class="windowbg">
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
					<dt>', $txt['private_issue'], '</dt>
					<dd>
						<input type="checkbox" name="private" value="1" tabindex="', $context['tabindex']++, '"', !empty($context['issue']['private']) ? ' checked="checked"' : '', '/>
					</dd>';

	if (count($context['possible_types']) > 1)
	{
		echo '
					<dt>', $txt['issue_type'], ':</dt>
					<dd>';

		foreach ($context['possible_types'] as $id => $type)
		{
			echo '
						<div class="toggle">
							<input type="radio" id="type_', $id, '" name="type" value="', $id, '" ', $type['selected'] ? ' checked="checked"' : '', '/> <label for="type_', $id, '">', $type['name'], '</label>
						</div>';
		}

		echo '
					</dd>';
	}

	if ($context['show_version'])
	{
		echo '
					<dt>', $txt['issue_version'], ':</dt>
					<dd>
						<select name="version">
							<option value=""></option>';


		foreach ($context['versions'] as $v)
		{
			echo '
							<option value="', $v['id'], '" style="font-weight: bold"', $context['issue']['version'] == $v['id'] ? ' selected="selected"' : '', '>', $v['name'], '</option>';

			foreach ($v['sub_versions'] as $subv)
				echo '
							<option value="', $subv['id'], '"', $context['issue']['version'] == $subv['id'] ? ' selected="selected"' : '', '>', $subv['name'], '</option>';
		}
	}

	echo '
						</select>
					</dd>';

	if ($context['show_category'])
	{
		echo '
					<dt>', $txt['issue_category'], ':</dt>
					<dd>
						<select name="category">
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
						', template_control_richedit($context['post_box_name'], 'bbc'), '
					</dd>
					<dd>
						', template_control_richedit($context['post_box_name'], 'message'), '
					</dd>';


	echo '
					<dd class="full center">
						<span class="smalltext"><br />', $txt['shortcuts'], '</span><br />
						', template_control_richedit($context['post_box_name'], 'buttons'), '
					</dd>
					<dd class="clear"></dd>
				</dl>
			</div>
		</div>

		<input type="hidden" name="project" value="', $context['project']['id'], '" />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
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
	<form action="', project_get_url(array('sa' => $context['destination'])), '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['issue_reply'], '</h4>
			<div class="windowbg">
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
						', template_control_richedit($context['post_box_name'], 'bbc'), '
					</dd>
					<dd>
						', template_control_richedit($context['post_box_name'], 'message'), '
					</dd>
					<dd class="full center">
						<span class="smalltext"><br />', $txt['shortcuts'], '</span><br />
						', template_control_richedit($context['post_box_name'], 'buttons'), '
					</dd>
					<dd class="clear"></dd>
				</dl>
			</div>
		</div>';

	if (!empty($context['can_issue_update']))
	{
		echo '
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['update_issue'], '</div>
			<div class="smallpadding windowbg">
				<table width="100%">';

		// Version
		echo '
					<tr>
						<td width="30%">', $txt['issue_version'], '</td>
						<td>
							<select name="version">
								<option></option>';


		foreach ($context['versions'] as $v)
		{
			echo '
								<option value="', $v['id'], '" style="font-weight: bold"', $context['current_issue']['version']['id'] == $v['id'] ? ' selected="selected"' : '', '>', $v['name'], '</option>';

			foreach ($v['sub_versions'] as $subv)
				echo '
								<option value="', $subv['id'], '"', $context['current_issue']['version']['id'] == $subv['id'] ? ' selected="selected"' : '', '>', $subv['name'], '</option>';
		}

		echo '
							</select>
						</td>
					</tr>';

		// Type
		echo '
					<tr>
						<td>', $txt['issue_type'], '</td>
						<td>
							<select name="type">';

		foreach ($context['possible_types'] as $id => $type)
			echo '
								<option value="', $id, '" ', !empty($type['selected']) ? ' selected="selected"' : '', '>', $type['name'], '</option>';

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
						<td>
							<select name="version_fixed">
								<option></option>';


			foreach ($context['versions'] as $v)
			{
				echo '
								<option value="', $v['id'], '" style="font-weight: bold"', $context['current_issue']['version_fixed']['id'] == $v['id'] ? ' selected="selected"' : '', '>', $v['name'], '</option>';

				foreach ($v['sub_versions'] as $subv)
					echo '
								<option value="', $subv['id'], '"', $context['current_issue']['version_fixed']['id'] == $subv['id'] ? ' selected="selected"' : '', '>', $subv['name'], '</option>';
			}

			echo '
							</select>
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
			</div>
		</div>';

	}

	echo '
		<input type="hidden" name="issue" value="', $context['current_issue']['id'], '" />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>