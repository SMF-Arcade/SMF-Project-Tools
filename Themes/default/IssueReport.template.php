<?php
// Version: 0.1 Alpha; IssueReport

function template_issue_type()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $sc;

	echo '
	<div style="padding: 3px;">', theme_linktree(), '</div>
	<div style="text-align: center;">
		<div class="tborder" style="text-align: left; width: 80%; margin: 0 auto">
			<table class="bordercolor" border="0" cellpadding="4" cellspacing="0" width="100%">
				<tr class="catbg"><td colspan="2">', $txt['report_issue'], '</td></tr>';

	foreach ($context['project_tools']['issue_types'] as $id => $type)
		echo '
				<tr class="windowbg2">
					<td width="18">
						<img src="', $settings['images_url'], '/', $id, '.png" />
					</td>
					<td>
						<a href="', $scripturl, '?action=report;project=', $context['project']['id'], ';type=', $id, '"><b>', $txt[$type['text']], '</b></a><br />
						<span class="smalltext">', $txt[$type['help']], '</span><br /><br />
					</td>
				</tr>';

	echo '
			</table>
		</div>
	</div>';
}

function template_report_issue()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<form action="', $scripturl, '?action=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
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

function template_report_issue_old()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	// Submit buttons
	echo '
					<tr>
						<td align="center" colspan="2">
							<input type="submit" name="post" value="', $context['submit_label'], '" tabindex="', $context['tabindex']++, '" />
							<input type="submit" name="preview" value="', $txt['preview'], '" tabindex="', $context['tabindex']++, '" />';

	// Spell check button if the option is enabled.
	if ($context['show_spellchecking'])
		echo '
							<input type="button" value="', $txt['spell_check'], '" tabindex="', $context['tabindex']++, '" onclick="editorHandle', $context['post_box_name'], '.spellCheckStart();" />';

	echo '
							</td>
						</tr>
						<tr>
							<td colspan="2"></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<input type="hidden" name="type" value="', $context['type'], '" />
		<input type="hidden" name="project" value="', $context['project']['id'], '" />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>