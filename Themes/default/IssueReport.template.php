<?php
// Version: 0.1 Alpha; IssueReport

function template_report_issue()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<form action="', $scripturl, '?sa=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['report_issue'], '</h4>
			<div class="windowbg">
				<dl>
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


		foreach ($context['project']['versions'] as $v)
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
							<option value=""></option>';

		foreach ($context['project']['category'] as $c)
			echo '
							<option value="', $c['id'], '" ', $context['category'] == $c['id'] ? ' selected="selected"' : '', '>', $c['name'], '</option>';
		echo '
						</select>
					</dd>';
	}

	echo '
					<dd>
						', template_control_richedit($context['post_box_name'], 'bbc'), '
					</dd>

					<dd>
						', template_control_richedit($context['post_box_name'], 'smileys'), '
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