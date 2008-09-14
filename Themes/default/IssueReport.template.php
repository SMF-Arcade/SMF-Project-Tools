<?php
// Version: 0.1 Alpha; IssueReport

function template_report_issue()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

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
	<form action="', $scripturl, '?sa=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
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

?>