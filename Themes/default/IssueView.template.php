<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$reply_button = create_button('quote.gif', 'reply_quote', 'quote', 'align="middle"');
	$modify_button = create_button('modify.gif', 'modify_msg', 'modify', 'align="middle"');
	$remove_button = create_button('delete.gif', 'remove_comment_alt', 'remove_comment', 'align="middle"');

	$buttons = array(
		'reply' => array(
			'text' => 'reply',
			'test' => 'can_comment',
			'image' => 'reply_issue.gif',
			'url' => $scripturl . '?issue=' . $context['current_issue']['id'] . '.0;sa=reply',
			'lang' => true
		),
	);

	$issueDetails = getComment();
	$alternate = false;

	if ($issueDetails['first_new'])
		echo '
	<a name="new"></a>';

	// Issue Info table
	echo '
	<a name="com', $context['current_issue']['comment_first'], '"></a>
	<div id="issueinfo" class="floatright tborder">
		<h3 class="catbg3 headerpadding clearfix">', $txt['issue_details'], '</h3>
		<div id="issueinfot" class="clearfix topborder windowbg smalltext">
			<ul class="details">
				<li>
					<dl class="clearfix">
						<dt>', $txt['issue_reported'], '</dt>
						<dd>', $context['current_issue']['created'], '</dd>
					</dl>
				</li>
				<li>
					<dl class="clearfix">
						<dt>', $txt['issue_updated'], '</dt>
						<dd>', $context['current_issue']['updated'], '</dd>
					</dl>
				</li>
				<li id="issue_type" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_type'], '</dt>
						<dd>', $context['current_issue']['type']['name'], '</dd>
					</dl>
				</li>
				<li id="issue_status" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_status'], '</dt>
						<dd>', $context['current_issue']['status']['text'], '</dd>
					</dl>
				</li>
				<li id="issue_priority" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_priority'], '</dt>
						<dd>', $txt[$context['current_issue']['priority']], '</dd>
					</dl>
				</li>
				<li id="issue_version" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_version'], '</dt>
						<dd>', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '</dd>
					</dl>
				</li>
				<li id="issue_verfix" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_version_fixed'], '</dt>
						<dd>', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '</dd>
					</dl>
				</li>
				<li id="issue_assign" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_assigned_to'], '</dt>
						<dd>', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '</dd>
					</dl>
				</li>
				<li id="issue_category" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_category'], '</dt>
						<dd>', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '</dd>
					</dl>
				</li>
			</ul>
		</div>
	</div>';

	// Issue Details
	echo '
	<div id="firstcomment" class="tborder">
		<h3 class="catbg3 headerpadding">
			<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
		</h3>
		<div class="bordercolor">
			<div class="clearfix topborder windowbg2 largepadding"', !$issueDetails['first'] ? ' id="com' . $issueDetails['id'] . '"' : '', '>
				<div class="floatleft poster">
					<h4>', $issueDetails['member']['link'], '</h4>
					<ul class="smalltext">';

		// Show the member's custom title, if they have one.
		if (isset($issueDetails['member']['title']) && $issueDetails['member']['title'] != '')
			echo '
						<li>', $issueDetails['member']['title'], '</li>';

		// Show the member's primary group (like 'Administrator') if they have one.
		if (isset($issueDetails['member']['group']) && $issueDetails['member']['group'] != '')
			echo '
						<li>', $issueDetails['member']['group'], '</li>';

		echo '
					</ul>
				</div>
				<div class="postarea">
					<div class="keyinfo">
						<div class="messageicon floatleft">
							<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" style="padding: 6px 3px" />
						</div>
						<h5><a href="', $scripturl , '?issue=', $context['current_issue']['id'], '.0#com', $issueDetails['id'], '" rel="nofollow">', $context['current_issue']['name'], '</a></h5>							<div class="smalltext">&#171; <strong>', !empty($issueDetails['counter']) ? $txt['reply'] . ' #' . $issueDetails['counter'] : '', ' ', $txt['on'], ':</strong> ', $issueDetails['time'], ' &#187;</div>
					</div>
					<ul class="smalltext postingbuttons">';

		if ($context['can_comment'])
			echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=reply;quote=', $issueDetails['id'], ';sesc=', $context['session_id'], '">', $reply_button, '</a></li>';

		if ($context['can_comment'])
			echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=edit;com=', $issueDetails['id'], ';sesc=', $context['session_id'], '">', $modify_button, '</a></li>';

		if ($issueDetails['can_remove'])
			echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=removeComment;com=', $issueDetails['id'], ';sesc=', $context['session_id'], '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

		echo '
					</ul>
					<div id="com_', $issueDetails['id'], '" class="post">
						', $issueDetails['body'], '
					</div>';

		// Show attachments
		if (!empty($context['attachments']))
		{
			echo '
					<hr width="100%" size="1" class="hrcolor" />
					<div style="overflow: auto; width: 100%;">';

			foreach ($context['attachments'] as $attachment)
			{
				if ($attachment['is_image'])
				{
					if ($attachment['thumbnail']['has_thumb'])
						echo '
							<a href="', $attachment['href'], ';image" id="link_', $attachment['id'], '" onclick="', $attachment['thumbnail']['javascript'], '"><img src="', $attachment['thumbnail']['href'], '" alt="" id="thumb_', $attachment['id'], '" border="0" /></a><br />';
					else
						echo '
							<img src="' . $attachment['href'] . ';image" alt="" width="' . $attachment['width'] . '" height="' . $attachment['height'] . '" border="0" /><br />';
				}
				echo '
							<a href="' . $attachment['href'] . '"><img src="' . $settings['images_url'] . '/icons/clip.gif" align="middle" alt="*" border="0" />&nbsp;' . $attachment['name'] . '</a>
									(', $attachment['size'], ($attachment['is_image'] ? ', ' . $attachment['real_width'] . 'x' . $attachment['real_height'] . ' - ' . $txt['attach_viewed'] : ' - ' . $txt['attach_downloaded']) . ' ' . $attachment['downloads'] . ' ' . $txt['attach_times'] . '.)<br />';
			}

			echo '
					</div>';
		}

		echo '
				</div>
				<div class="moderatorbar">
					<div class="smalltext floatleft">';

		// Show "« Last Edit: Time by Person »" if this post was edited.
		if ($settings['show_modify'] && !empty($issueDetails['modified']['name']))
			echo '
						&#171; <em>', $txt['last_edit'], ': ', $issueDetails['modified']['time'], ' ', $txt['by'], ' ', $issueDetails['modified']['name'], '</em> &#187;';

		echo '
					</div>
					<div class="smalltext floatright">';
		echo '
						<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
		if (allowedTo('moderate_forum') && !empty($issueDetails['ip']))
			echo '
						<a href="', $scripturl, '?action=trackip;searchip=', $issueDetails['ip'], '">', $issueDetails['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
		// Or, should we show it because this is you?
		elseif ($issueDetails['can_see_ip'])
			echo '
						<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $issueDetails['ip'], '</a>';
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
			</div>
		</div>
	</div><br />';

	// Javascript for Dropdowns
	if (!empty($context['can_issue_update']))
	{
		echo '
	<script language="JavaScript" type="text/javascript">
		var ddIssueType = new PTDropdown("issue_type", "type", "', $context['current_issue']['type']['id'], '", ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		var ddIssueCate = new PTDropdown("issue_category", "category", ', (int) $context['current_issue']['category']['id'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		var ddIssueVers = new PTDropdown("issue_version", "version", ', (int) $context['current_issue']['version']['id'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		ddIssueCate.addOption(0, "', $txt['issue_none'], '");
		ddIssueVers.addOption(0, "', $txt['issue_none'], '");';

		// Types
		foreach ($context['possible_types'] as $id => $type)
			echo '
		ddIssueType.addOption("', $id, '", "', $type['name'], '");';

		// Categories
		foreach ($context['project']['category'] as $c)
			echo '
		ddIssueCate.addOption(', $c['id'], ', "', $c['name'], '");';

		// Versions
		foreach ($context['versions'] as $v)
		{
			echo '
		ddIssueVers.addOption(', $v['id'], ', "', $v['name'], '", "font-weight: bold");';

			foreach ($v['sub_versions'] as $subv)
				echo '
		ddIssueVers.addOption(', $subv['id'], ', "', $subv['name'], '");';
		}

		if (!empty($context['can_issue_moderate']))
		{
			echo '
		var ddIssueStat = new PTDropdown("issue_status", "status", ', (int) $context['current_issue']['status']['id'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		var ddIssueAssi = new PTDropdown("issue_assign", "assign", ', (int) $context['current_issue']['assignee']['id'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		var ddIssueFixv = new PTDropdown("issue_verfix", "version_fixed", ', (int) $context['current_issue']['version_fixed']['id'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '")
		var ddIssuePrio = new PTDropdown("issue_priority", "priority", ', (int) $context['current_issue']['priority_num'], ', ', $context['current_issue']['id'], ', "', $context['session_id'], '");
		ddIssueFixv.addOption(0, "', $txt['issue_none'], '");';

			// Status
			foreach ($context['issue']['status'] as $status)
				echo '
		ddIssueStat.addOption(', $status['id'], ', "', $status['text'], '");';

			// Members
			foreach ($context['assign_members'] as $mem)
				echo '
		ddIssueAssi.addOption(', $mem['id'], ', "', $mem['name'], '");';

			// Versions
			foreach ($context['versions'] as $v)
			{
				echo '
		ddIssueFixv.addOption(', $v['id'], ', "', $v['name'], '", "font-weight: bold");';

				foreach ($v['sub_versions'] as $subv)
					echo '
		ddIssueFixv.addOption(', $subv['id'], ', "', $subv['name'], '");';
			}

			// Priorities
			foreach ($context['issue']['priority'] as $id => $text)
				echo '
		ddIssuePrio.addOption(', $id, ', "', $txt[$text], '");';

		}

		echo '
	</script>';
	}

	// Print out comments
	if ($context['num_comments'] > 0)
	{
		echo '
	<div class="modbuttons clearfix margintop">
		<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
		', template_button_strip($buttons, 'bottom'), '
	</div>
	<div class="tborder">
		<h3 class="catbg3 headerpadding">
			', $txt['issue_comments'], '
		</h3>
		<div class="bordercolor">';

		while ($comment = getComment())
		{
			echo '
			<div class="clearfix topborder windowbg', $alternate ? '' : '2', ' largepadding"', !$comment['first'] ? ' id="com' . $comment['id'] . '"' : '', '>
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
						<div class="smalltext">&#171; <strong>', !empty($comment['counter']) ? $txt['reply'] . ' #' . $comment['counter'] : '', ' ', $txt['on'], ':</strong> ', $comment['time'], ' &#187;</div>
					</div>
					<ul class="smalltext postingbuttons">';

			if ($context['can_comment'])
				echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=reply;quote=', $comment['id'], ';sesc=', $context['session_id'], '">', $reply_button, '</a></li>';

			if ($context['can_comment'])
				echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=edit;com=', $comment['id'], ';sesc=', $context['session_id'], '">', $modify_button, '</a></li>';

			if ($comment['can_remove'])
				echo '
						<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=removeComment;com=', $comment['id'], ';sesc=', $context['session_id'], '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

			echo '
					</ul>
					<div id="com_', $comment['id'], '" class="post">
						', $comment['body'], '
					</div>
				</div>
				<div class="moderatorbar">
					<div class="smalltext floatleft">';

			// Show "« Last Edit: Time by Person »" if this post was edited.
			if ($settings['show_modify'] && !empty($comment['modified']['name']))
				echo '
						&#171; <em>', $txt['last_edit'], ': ', $comment['modified']['time'], ' ', $txt['by'], ' ', $comment['modified']['name'], '</em> &#187;';

			echo '
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
	</div>
	<div class="modbuttons clearfix marginbottom">
		<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
		', template_button_strip($buttons, 'top'), '
	</div><br />';
	}

	$mod_buttons = array(
		'delete' => array('test' => 'can_issue_moderate', 'text' => 'issue_delete', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['issue_delete_confirm'] . '\');"', 'url' => $scripturl . '?issue=' . $context['current_issue']['id'] . '.0;sa=delete;sesc=' . $context['session_id']),
	);

	echo '
	<div id="moderationbuttons">', 	template_button_strip($mod_buttons, 'bottom'), '</div>';

	echo '
	<div class="tborder">
		<div class="titlebg2" style="padding: 4px;" align="', !$context['right_to_left'] ? 'right' : 'left', '">&nbsp;</div>
	</div><br />';

	if ($context['can_comment'])
	{
		echo '
	<form action="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=reply2" method="post">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['comment_issue'], '</div>
			<div class="smallpadding windowbg" style="text-align: center">
				<textarea id="comment" name="comment" rows="7" cols="75"></textarea>';

		echo '
				<div style="text-align: right">
					<input type="submit" name="add_comment" value="', $txt['add_comment'], '" />
				</div>
			</div>
		</div><br />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
	</form><br />';
	}

	if ($context['can_issue_attach'])
	{
		echo '
	<form action="', $scripturl , '?issue=', $context['current_issue']['id'], '.0;sa=upload" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['issue_attach'], '</div>
			<div class="smallpadding windowbg">
				<input type="file" size="48" name="attachment[]" /><br />';

		if (!empty($modSettings['attachmentCheckExtensions']))
			echo '
					', $txt['allowed_types'], ': ', $context['allowed_extensions'], '<br />';
		echo '
					', $txt['max_size'], ': ', $modSettings['attachmentSizeLimit'], ' ' . $txt['kilobyte'], '<br />';

		echo '
				<div style="text-align: right">
					<input type="submit" name="add_comment" value="', $txt['add_attach'], '" />
				</div>
			</div>
		</div>
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
	</form>';
	}

}

function template_issue_reply()
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
	<form action="', $scripturl, '?sa=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['issue_reply'], '</h4>
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