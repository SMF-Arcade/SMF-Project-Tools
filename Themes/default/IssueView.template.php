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
			<h3 class="catbg3 headerpadding clearfix">
				<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
				<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
			</h3>
			<div class="clearfix windowbg">
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
			<div class="windowbg">
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_assigned_to'], '</span>
						', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
					</div>
				</div>
			</div>
		</div><br />';

	$alternate = false;

	echo '
		<div class="tborder">
			<h3 class="catbg3 headerpadding">
				', $txt['issue_comments'], '
			</h3>
			<div class="bordercolor">';

	$remove_button = create_button('delete.gif', 'remove_comment_alt', 'remove_comment', 'align="middle"');

	while ($comment = getComment())
	{
		echo '
				<div class="clearfix topborder windowbg', $alternate ? '2' : '', ' largepadding">
					<div class="floatleft poster">
						<h4>', $comment['member']['link'], '</h4>
						<ul class="smalltext">';

		// Show the member's custom title, if they have one.
		if (isset($comment['member']['title']) && $comment['member']['title'] != '')
			echo '
							<li>', $comment['member']['title'], '</li>';

		// Show the member's primary group (like 'Administrator') if they have one.
		if (isset($comment['member']['group']) && $comment['member']['group'] != '')
			echo '
							<li>', $comment['member']['group'], '</li>';

		echo '
						</ul>
					</div>
					<div class="postarea">
						<div class="keyinfo">
							<h5>
							</h5>
							<div class="smalltext">&#171; <strong>', !empty($comment['counter']) ? $txt['reply'] . ' #' . $comment['counter'] : '', ' ', $txt['on'], ':</strong> ', $comment['time'], ' &#187;</div>
						</div>
						<ul class="smalltext postingbuttons">';

		if ($comment['can_remove'])
			echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=removeComment;comment=', $comment['id'], ';sesc=', $context['session_id'], '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

		echo '
						</ul>
						<hr width="100%" size="1" class="hrcolor" />
						<div class="post">
							', $comment['body'], '
						</div>
					</div>
					<div class="moderatorbar">
						<div class="smalltext floatleft">
						</div>
						<div class="smalltext floatright">';
		echo '
							<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
		if (allowedTo('moderate_forum') && !empty($comment['ip']))
			echo '
							<a href="', $scripturl, '?action=trackip;searchip=', $comment['ip'], '">', $comment['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
		// Or, should we show it because this is you?
		elseif ($comment['can_see_ip'])
			echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $comment['ip'], '</a>';
		// Okay, are you at least logged in?  Then we can show something about why IPs are logged...
		elseif (!$context['user']['is_guest'])
			echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $txt['logged'], '</a>';
		// Otherwise, you see NOTHING!
		else
			echo '
							', $txt['logged'];

		echo '
						</div>
					</div>
				</div>';

			$alternate = !$alternate;
	}

	echo '
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

	if (!empty($context['show_comment']))
	{
		echo '
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['comment_issue'], '</div>
			<div class="smallpadding windowbg" style="text-align: center">
				<textarea id="comment" name="comment" rows="7" cols="75"></textarea>';

		echo '
				<div style="text-align: right">
					<input type="submit" name="add_comment" value="', $txt['add_comment'], '" />
				</div>
			</div>
		</div><br />';
	}

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
					<input name="update_issue" value="', $txt['update_issue_save'], '" type="submit" />
					<input name="update_issue2" value="', $txt['update_issue_comment'], '" type="submit" />
				</div>
			</div>
		</div>
	</form>';

	}
}

function template_issue_reply()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<form action="', $scripturl, '?sa=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['report_issue'], '</h4>
			<div class="windowbg">
				<dl>
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
		</div>

		<input type="hidden" name="issue" value="', $context['current_issue']['id'], '" />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>