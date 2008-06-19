<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');
	$modify_button = create_button('modify.gif', 'issue_edit', 'issue_edit');

	$reporter = &$context['current_issue']['reporter'];

	echo '
	<form action="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=updateIssue" method="post">
		<input type="hidden" name="sc" value="', $context['session_id'], '" />

		<div id="issueinfo" class="tborder">
			<h3 class="catbg3 clearfix">
				<div class="floatleft" style="width: 50%">
					<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
					<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
				</div>
				<div class="floatright">
					', $txt['project'], ': ', $context['project']['link'], '
				</div>
			</h3>
			<div class="clearfix windowbg2">
				<div class="floatleft halfwidth">
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_type'], '</span>
							', $context['current_issue']['type']['name'], '
						</div>
					</div>
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_category'], '</span>
							', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '
						</div>
					</div>
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_status'], '</span>
							', $context['current_issue']['status']['text'], '
						</div>
					</div>
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_priority'], '</span>
							', $txt[$context['current_issue']['priority']], '
						</div>
					</div>
				</div>
				<div class="floatright halfwidth">
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_reported'], '</span>
							', $context['current_issue']['created'], '
						</div>
					</div>
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_updated'], '</span>
							', $context['current_issue']['updated'], '
						</div>
					</div>
					<div>
						<div class="display">
								<span class="dark">', $txt['issue_version'], '</span>
								', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
						</div>
					</div>
					<div>
						<div class="display">
							<span class="dark">', $txt['issue_version_fixed'], '</span>
							', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
						</div>
					</div>
				</div>
			</div>
			<div class="windowbg2">
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_assigned_to'], '</span>
						', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
					</div>
				</div>
			</div>
			<div class="bordercolor">
				<div class="clearfix topborder windowbg largepadding">
					<div class="floatleft poster">
						<h4>', $reporter['link'], '</h4>
					</div>
					<div class="postarea">
						<div class="keyinfo">
							<div class="messageicon floatleft"><img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" /></div>
							<h5>', $context['current_issue']['name'], '</h5>
							<div class="smalltext">&#171; <strong>', $txt['reported_on'], ':</strong> ', $context['current_issue']['created'], ' &#187;</div>
						</div>
						<hr width="100%" size="1" class="hrcolor" />
						<div class="post">
							', $context['current_issue']['body'], '
						</div>
					</div>
					<div class="moderatorbar">
						<div class="smalltext floatleft">

						</div>
						<div class="smalltext floatright">

						</div>
					</div>
				</div>
			</div>
		</div><br />';

	$mod_buttons = array(
		'delete' => array('test' => 'can_issue_moderate', 'text' => 'issue_delete', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['issue_delete_confirm'] . '\');"', 'url' => $scripturl . '?issue=' . $context['current_issue']['id'] . ';sa=deleteIssue;sesc=' . $context['session_id']),
	);

	echo '
	<div id="moderationbuttons">', 	template_button_strip($mod_buttons, 'bottom'), '</div>
		<input type="hidden" name="sc" value="', $context['session_id'], '" />';


	echo '
		<div class="tborder">
			<div class="titlebg2" style="padding: 4px;" align="', !$context['right_to_left'] ? 'right' : 'left', '">&nbsp;</div>
	</div><br />';

	if ($context['can_issue_update'])
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


			foreach ($context['issue']['status'] as $status)

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
				<div style="text-align: right">
					<input type="submit" />
				</div>
			</div>
		</div>
	</form>';

	}
}

?>