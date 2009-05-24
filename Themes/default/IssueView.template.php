<?php
// Version: 0.3; IssueView

function template_issue_view_above()
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
			'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0' , 'sa' => 'reply')),
			'lang' => true
		),
	);

	$issueDetails = &$context['current_issue']['details'];
	$alternate = false;

	if ($issueDetails['first_new'])
		echo '
	<a name="new"></a>';

	// Issue Details
	echo '
	<a name="com', $context['current_issue']['comment_first'], '"></a>
	<div id="firstcomment" class="floatleft tborder">
		<h3 class="catbg3 headerpadding">
			<img src="', $settings['images_url'], '/', $context['current_issue']['tracker']['image'], '" align="bottom" alt="', $context['current_issue']['tracker']['name'], '" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
		</h3>
		<div class="bordercolor">
			<div class="clearfix topborder windowbg largepadding">
				<div class="floatleft poster">
					<h4>', $context['current_issue']['reporter']['link'], '</h4>
					<ul class="reset smalltext">';

	// Show the member's custom title, if they have one.
	if (isset($context['current_issue']['reporter']['title']) && $context['current_issue']['reporter']['title'] != '')
		echo '
						<li>', $context['current_issue']['reporter']['title'], '</li>';

	// Show the member's primary group (like 'Administrator') if they have one.
	if (isset($context['current_issue']['reporter']['group']) && $context['current_issue']['reporter']['group'] != '')
		echo '
						<li>', $context['current_issue']['reporter']['group'], '</li>';

	// Don't show these things for guests.
	if (!$context['current_issue']['reporter']['is_guest'])
	{
		// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
		if ((empty($settings['hide_post_group']) || $context['current_issue']['reporter']['group'] == '') && $context['current_issue']['reporter']['post_group'] != '')
			echo '
						<li>', $context['current_issue']['reporter']['post_group'], '</li>';
		echo '
						<li>', $context['current_issue']['reporter']['group_stars'], '</li>';

		// Is karma display enabled?  Total or +/-?
		if ($modSettings['karmaMode'] == '1')
			echo '
						<li class="margintop">', $modSettings['karmaLabel'], ' ', $context['current_issue']['reporter']['karma']['good'] - $context['current_issue']['reporter']['karma']['bad'], '</li>';
		elseif ($modSettings['karmaMode'] == '2')
			echo '
						<li class="margintop">', $modSettings['karmaLabel'], ' +', $context['current_issue']['reporter']['karma']['good'], '/-', $context['current_issue']['reporter']['karma']['bad'], '</li>';

		// Is this user allowed to modify this member's karma?
		if ($context['current_issue']['reporter']['karma']['allow'])
			echo '
						<li>
								<a href="', $scripturl, '?action=modifykarma;sa=applaud;uid=', $context['current_issue']['reporter']['id'], ';issue=', $context['current_issue']['id'], '.' . $context['start'], ';com=', $issueDetails['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaApplaudLabel'], '</a>
								<a href="', $scripturl, '?action=modifykarma;sa=smite;uid=', $context['current_issue']['reporter']['id'], ';issue=', $context['current_issue']['id'], '.', $context['start'], ';com=', $issueDetails['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaSmiteLabel'], '</a>
						</li>';

		// Show online and offline buttons?
		if (!empty($modSettings['onlineEnable']))
			echo '
						<li>', $context['can_send_pm'] ? '<a href="' . $context['current_issue']['reporter']['online']['href'] . '" title="' . $context['current_issue']['reporter']['online']['label'] . '">' : '', $settings['use_image_buttons'] ? '<img src="' . $context['current_issue']['reporter']['online']['image_href'] . '" alt="' . $context['current_issue']['reporter']['online']['text'] . '" border="0" style="margin-top: 2px;" />' : $context['current_issue']['reporter']['online']['text'], $context['can_send_pm'] ? '</a>' : '', $settings['use_image_buttons'] ? '<span class="smalltext"> ' . $context['current_issue']['reporter']['online']['text'] . '</span>' : '', '</li>';

		// Show the member's gender icon?
		if (!empty($settings['show_gender']) && $context['current_issue']['reporter']['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
			echo '
						<li>', $txt['gender'], ': ', $context['current_issue']['reporter']['gender']['image'], '</li>';

		// Show how many posts they have made.
		if (!isset($context['disabled_fields']['posts']))
			echo '
						<li>', $txt['member_postcount'], ': ', $context['current_issue']['reporter']['posts'], '</li>';

		// Any custom fields?
		if (!empty($context['current_issue']['reporter']['custom_fields']))
		{
			foreach ($context['current_issue']['reporter']['custom_fields'] as $custom)
				echo '
						<li>', $custom['title'], ': ', $custom['value'], '</li>';
		}

		// Show avatars, images, etc.?
		if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($context['current_issue']['reporter']['avatar']['image']))
			echo '
						<li class="margintop" style="overflow: auto;">', $context['current_issue']['reporter']['avatar']['image'], '</li>';

		// Show their personal text?
		if (!empty($settings['show_blurb']) && $context['current_issue']['reporter']['blurb'] != '')
			echo '
						<li>', $context['current_issue']['reporter']['blurb'], '</li>';

		// This shows the popular messaging icons.
		if ($context['current_issue']['reporter']['has_messenger'] && $context['current_issue']['reporter']['can_view_profile'])
			echo '
						<li>
							<ul class="reset nolist">
								', !isset($context['disabled_fields']['icq']) && !empty($context['current_issue']['reporter']['icq']['link']) ? '<li>' . $context['current_issue']['reporter']['icq']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['msn']) && !empty($context['current_issue']['reporter']['msn']['link']) ? '<li>' . $context['current_issue']['reporter']['msn']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['aim']) && !empty($context['current_issue']['reporter']['aim']['link']) ? '<li>' . $context['current_issue']['reporter']['aim']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['yim']) && !empty($context['current_issue']['reporter']['yim']['link']) ? '<li>' . $context['current_issue']['reporter']['yim']['link'] . '</li>' : '', '
							</ul>
						</li>';

		// Show the profile, website, email address, and personal message buttons.
		if ($settings['show_profile_buttons'])
		{
			echo '
						<li>
							<ul class="reset nolist">';
			// Don't show the profile button if you're not allowed to view the profile.
			if ($context['current_issue']['reporter']['can_view_profile'])
				echo '
								<li><a href="', $context['current_issue']['reporter']['href'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/icons/profile_sm.gif" alt="' . $txt['view_profile'] . '" title="' . $txt['view_profile'] . '" border="0" />' : $txt['view_profile']), '</a></li>';

			// Don't show an icon if they haven't specified a website.
			if ($context['current_issue']['reporter']['website']['url'] != '' && !isset($context['disabled_fields']['website']))
				echo '
								<li><a href="', $context['current_issue']['reporter']['website']['url'], '" title="' . $context['current_issue']['reporter']['website']['title'] . '" target="_blank" class="new_win">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/www_sm.gif" alt="' . $txt['www'] . '" border="0" />' : $txt['www']), '</a></li>';

			// Don't show the email address if they want it hidden.
			if (in_array($context['current_issue']['reporter']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
				echo '
								<li><a href="', $scripturl, '?action=emailuser;sa=email;com=', $issueDetails['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']), '</a></li>';

			// Since we know this person isn't a guest, you *can* message them.
			if ($context['can_send_pm'])
				echo '
								<li><a href="', $scripturl, '?action=pm;sa=send;u=', $context['current_issue']['reporter']['id'], '" title="', $context['current_issue']['reporter']['online']['label'], '">', $settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/im_' . ($context['current_issue']['reporter']['online']['is_online'] ? 'on' : 'off') . '.gif" alt="' . $context['current_issue']['reporter']['online']['label'] . '" border="0" />' : $context['current_issue']['reporter']['online']['label'], '</a></li>';

			echo '
							</ul>
						</li>';
		}

		// Are we showing the warning status?
		if (!isset($context['disabled_fields']['warning_status']) && $context['current_issue']['reporter']['warning_status'] && ($context['user']['can_mod'] || (!empty($modSettings['warning_show']) && ($modSettings['warning_show'] > 1 || $context['current_issue']['reporter']['id'] == $context['user']))))
			echo '
						<li>', $context['can_issue_warning'] ? '<a href="' . $scripturl . '?action=profile;u=' . $context['current_issue']['reporter']['id'] . ';sa=issueWarning">' : '', '<img src="', $settings['images_url'], '/warning_', $context['current_issue']['reporter']['warning_status'], '.gif" alt="', $txt['user_warn_' . $context['current_issue']['reporter']['warning_status']], '" />', $context['can_issue_warning'] ? '</a>' : '', '<span class="warn_', $context['current_issue']['reporter']['warning_status'], '">', $txt['warn_' . $context['current_issue']['reporter']['warning_status']], '</span></li>';
	}
	// Otherwise, show the guest's email.
	elseif (in_array($context['current_issue']['reporter']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
		echo '
						<li><a href="', $scripturl, '?action=emailuser;sa=email;com=', $issueDetails['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" border="0" />' : $txt['email']), '</a></li>';

	echo '
					</ul>
				</div>
				<div class="postarea">
					<div class="keyinfo">
						<div class="messageicon floatleft">
							<img src="', $settings['images_url'], '/', $context['current_issue']['tracker']['image'], '" align="bottom" alt="', $context['current_issue']['tracker']['name'], '" width="20" style="padding: 6px 3px" />
						</div>
						<h5><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0')), '#com', $issueDetails['id'], '" rel="nofollow">', $context['current_issue']['name'], '</a></h5>
						<div class="smalltext">&#171; <strong>', !empty($issueDetails['counter']) ? $txt['reply'] . ' #' . $issueDetails['counter'] : '', ' ', $txt['on'], ':</strong> ', $issueDetails['time'], ' &#187;</div>
					</div>';
				
	if ($context['can_comment'] || $issueDetails['can_edit'] || $issueDetails['can_remove'])	
		echo '
					<ul class="reset smalltext postingbuttons">';

	if ($context['can_comment'])
		echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'reply', 'quote' => $issueDetails['id'], $context['session_var'] => $context['session_id'])), '">', $reply_button, '</a></li>';

	if ($issueDetails['can_edit'])
		echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'edit', 'com' => $issueDetails['id'], $context['session_var'] => $context['session_id'])), '">', $modify_button, '</a></li>';

	if ($issueDetails['can_remove'])
		echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'removeComment', 'com' => $issueDetails['id'], $context['session_var'] => $context['session_id'])), '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

	if ($context['can_comment'] || $issueDetails['can_edit'] || $issueDetails['can_remove'])
		echo '
					</ul>';
					
	echo '
					<div id="com_', $issueDetails['id'], '" class="post floatleft">
						<div class="inner">', $issueDetails['body'], '</div>
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
						<a href="' . $attachment['href'] . '"><img src="' . $settings['images_url'] . '/icons/clip.gif" align="middle" alt="*" border="0" />&nbsp;' . $attachment['name'] . '</a> ';

			echo '
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
	</div>';

	// Issue Info table
	echo '
	<div id="issueinfo" class="tborder floatright">
		<h3 class="catbg3 headerpadding clearfix">', $txt['issue_details'], '</h3>
		<div class="clearfix topborder windowbg smalltext">
			<ul class="details">
				<li>
					<dl class="clearfix">
						<dt>', $txt['issue_reported'], '</dt>
						<dd>', $context['current_issue']['created'], '</dd>
					</dl>
				</li>
				<li id="issue_updated">
					<dl class="clearfix">
						<dt>', $txt['issue_updated'], '</dt>
						<dd>', $context['current_issue']['updated'], '</dd>
					</dl>
				</li>
				<li id="issue_view_status" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_view_status'], '</dt>
						<dd>', $context['current_issue']['private'] ? $txt['issue_view_status_private'] : $txt['issue_view_status_public'], '</dd>
					</dl>
				</li>
				<li id="issue_tracker" class="clearfix">
					<dl class="clearfix">
						<dt>', $txt['issue_type'], '</dt>
						<dd>', $context['current_issue']['tracker']['name'], '</dd>
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
	
	// Javascript for Dropdowns
	if (!empty($context['can_issue_update']))
	{
		echo '
	<script language="JavaScript" type="text/javascript">
		var currentIssue = new PTIssue(', $context['current_issue']['id'], ', "', $context['issue_xml_url'], '")
		
		var updateLabel = currentIssue.addLabel("issue_updated", "updated");
		
		var ddIssueViewS = currentIssue.addDropdown("issue_view_status", "private", ', (int) $context['current_issue']['private'], ');
		ddIssueViewS.addOption(0, "', $txt['issue_view_status_public'], '");
		ddIssueViewS.addOption(1, "', $txt['issue_view_status_private'], '");';
		
		// Types
		echo '
		var ddIssueType = currentIssue.addDropdown("issue_tracker", "tracker", "', $context['current_issue']['tracker']['short'], '");';
		
		foreach ($context['project']['trackers'] as $id => $tracker)
			echo '
		ddIssueType.addOption(', $id, ', "', $tracker['tracker']['name'], '");';
		
		// Categories
		echo '
		var ddIssueCate = currentIssue.addDropdown("issue_category", "category", ', (int) $context['current_issue']['category']['id'], ');
		ddIssueCate.addOption(0, "', $txt['issue_none'], '");';

		foreach ($context['project']['category'] as $c)
			echo '
		ddIssueCate.addOption(', $c['id'], ', "', $c['name'], '");';
		
		// Affected Version
		echo '
		var ddIssueVers = currentIssue.addDropdown("issue_version", "version", ', (int) $context['current_issue']['version']['id'], ');
		ddIssueVers.addOption(0, "', $txt['issue_none'], '");';

		// Versions
		foreach ($context['versions'] as $v)
		{
			echo '
		ddIssueVers.addOption(', $v['id'], ', "', $v['name'], '", "group");';

			foreach ($v['sub_versions'] as $subv)
				echo '
		ddIssueVers.addOption(', $subv['id'], ', "', $subv['name'], '");';
		}

		if (!empty($context['can_issue_moderate']))
		{
			// Status
			echo '
		var ddIssueStat = currentIssue.addDropdown("issue_status", "status", ', (int) $context['current_issue']['status']['id'], ');';

			foreach ($context['issue_status'] as $status)
				echo '
		ddIssueStat.addOption(', $status['id'], ', "', $status['text'], '");';

			// Assigned to
			echo '
		var ddIssueAssi = currentIssue.addDropdown("issue_assign", "assign", ', (int) $context['current_issue']['assignee']['id'], ');
		ddIssueAssi.addOption(0, "', $txt['issue_none'], '");';
		
			foreach ($context['assign_members'] as $mem)
				echo '
		ddIssueAssi.addOption(', $mem['id'], ', "', $mem['name'], '");';
		
			// Fixed Version
			echo '
		var ddIssueFixv = currentIssue.addDropdown("issue_verfix", "version_fixed", ', (int) $context['current_issue']['version_fixed']['id'], ')
		ddIssueFixv.addOption(0, "', $txt['issue_none'], '");';

			// Versions
			foreach ($context['versions'] as $v)
			{
				echo '
		ddIssueFixv.addOption(', $v['id'], ', "', $v['name'], '", "group");';

				foreach ($v['sub_versions'] as $subv)
					echo '
		ddIssueFixv.addOption(', $subv['id'], ', "', $subv['name'], '");';
			}

			// Priority
			echo '
		var ddIssuePrio = currentIssue.addDropdown("issue_priority", "priority", ', (int) $context['current_issue']['priority_num'], ');';

			foreach ($context['issue']['priority'] as $id => $text)
				echo '
		ddIssuePrio.addOption(', $id, ', "', $txt[$text], '");';

		}

		echo '
	</script>';
	}
}

function template_issue_view_main()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	// Print out comments
	if ($context['num_events'] == 0)
		return;

	$alternate = true;

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

	echo '
	<div class="modbuttons clearfix margintop">
		<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
		', template_button_strip($buttons, 'bottom'), '
	</div>
	<div class="tborder comments">
		<h3 class="catbg3 headerpadding">
			', $txt['issue_comments'], '
		</h3>
		<div class="bordercolor">';

	while ($event = getEvent())
	{
		$id = isset($event['comment']) ? 'com' . $event['comment']['id'] : 'evt' . $event['id'];
		$id2 = isset($event['comment']) ? 'com_' . $event['comment']['id'] : 'evt_' . $event['id'];

		echo '
			<div class="clearfix topborder windowbg', $alternate ? '' : '2', ' largepadding" id="', $id, '">
				<div class="floatleft poster">
					<h4>', $event['member']['link'], '</h4>
					<ul class="reset smalltext">';

		// Show the member's custom title, if they have one.
		if (isset($event['member']['title']) && $event['member']['title'] != '')
			echo '
						<li>', $event['member']['title'], '</li>';

		// Show the member's primary group (like 'Administrator') if they have one.
		if (isset($event['member']['group']) && $event['member']['group'] != '')
			echo '
						<li>', $event['member']['group'], '</li>';

		// Don't show these things for guests.
		if (!$event['member']['is_guest'])
		{
			// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
			if ((empty($settings['hide_post_group']) || $event['member']['group'] == '') && $event['member']['post_group'] != '')
				echo '
						<li>', $event['member']['post_group'], '</li>';
			echo '
						<li>', $event['member']['group_stars'], '</li>';

			// Is karma display enabled?  Total or +/-?
			if ($modSettings['karmaMode'] == '1')
				echo '
						<li class="margintop">', $modSettings['karmaLabel'], ' ', $event['member']['karma']['good'] - $event['member']['karma']['bad'], '</li>';
			elseif ($modSettings['karmaMode'] == '2')
				echo '
						<li class="margintop">', $modSettings['karmaLabel'], ' +', $event['member']['karma']['good'], '/-', $event['member']['karma']['bad'], '</li>';

			// Is this user allowed to modify this member's karma?
			if ($event['member']['karma']['allow'])
				echo '
						<li>
								<a href="', $scripturl, '?action=modifykarma;sa=applaud;uid=', $event['member']['id'], ';issue=', $context['current_issue']['id'], '.' . $context['start'], ';com=', $event['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaApplaudLabel'], '</a>
								<a href="', $scripturl, '?action=modifykarma;sa=smite;uid=', $event['member']['id'], ';issue=', $context['current_issue']['id'], '.', $context['start'], ';com=', $event['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaSmiteLabel'], '</a>
						</li>';

			// Show online and offline buttons?
			if (!empty($modSettings['onlineEnable']))
				echo '
						<li>', $context['can_send_pm'] ? '<a href="' . $event['member']['online']['href'] . '" title="' . $event['member']['online']['label'] . '">' : '', $settings['use_image_buttons'] ? '<img src="' . $event['member']['online']['image_href'] . '" alt="' . $event['member']['online']['text'] . '" border="0" style="margin-top: 2px;" />' : $event['member']['online']['text'], $context['can_send_pm'] ? '</a>' : '', $settings['use_image_buttons'] ? '<span class="smalltext"> ' . $event['member']['online']['text'] . '</span>' : '', '</li>';

			// Show the member's gender icon?
			if (!empty($settings['show_gender']) && $event['member']['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
				echo '
						<li>', $txt['gender'], ': ', $event['member']['gender']['image'], '</li>';

			// Show how many posts they have made.
			if (!isset($context['disabled_fields']['posts']))
				echo '
						<li>', $txt['member_postcount'], ': ', $event['member']['posts'], '</li>';

			// Any custom fields?
			if (!empty($event['member']['custom_fields']))
			{
				foreach ($event['member']['custom_fields'] as $custom)
					echo '
						<li>', $custom['title'], ': ', $custom['value'], '</li>';
			}

			// Show avatars, images, etc.?
			if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($event['member']['avatar']['image']))
				echo '
						<li class="margintop" style="overflow: auto;">', $event['member']['avatar']['image'], '</li>';

			// Show their personal text?
			if (!empty($settings['show_blurb']) && $event['member']['blurb'] != '')
				echo '
						<li>', $event['member']['blurb'], '</li>';

			// This shows the popular messaging icons.
			if ($event['member']['has_messenger'] && $event['member']['can_view_profile'])
				echo '
						<li>
							<ul class="reset nolist">
								', !isset($context['disabled_fields']['icq']) && !empty($event['member']['icq']['link']) ? '<li>' . $event['member']['icq']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['msn']) && !empty($event['member']['msn']['link']) ? '<li>' . $event['member']['msn']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['aim']) && !empty($event['member']['aim']['link']) ? '<li>' . $event['member']['aim']['link'] . '</li>' : '', '
								', !isset($context['disabled_fields']['yim']) && !empty($event['member']['yim']['link']) ? '<li>' . $event['member']['yim']['link'] . '</li>' : '', '
							</ul>
						</li>';

			// Show the profile, website, email address, and personal message buttons.
			if ($settings['show_profile_buttons'])
			{
				echo '
						<li>
							<ul class="reset nolist">';
				// Don't show the profile button if you're not allowed to view the profile.
				if ($event['member']['can_view_profile'])
					echo '
								<li><a href="', $event['member']['href'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/icons/profile_sm.gif" alt="' . $txt['view_profile'] . '" title="' . $txt['view_profile'] . '" border="0" />' : $txt['view_profile']), '</a></li>';

				// Don't show an icon if they haven't specified a website.
				if ($event['member']['website']['url'] != '' && !isset($context['disabled_fields']['website']))
					echo '
								<li><a href="', $event['member']['website']['url'], '" title="' . $event['member']['website']['title'] . '" target="_blank" class="new_win">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/www_sm.gif" alt="' . $txt['www'] . '" border="0" />' : $txt['www']), '</a></li>';

				// Don't show the email address if they want it hidden.
				if (in_array($event['member']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
					echo '
								<li><a href="', $scripturl, '?action=emailuser;sa=email;com=', $event['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']), '</a></li>';

				// Since we know this person isn't a guest, you *can* message them.
				if ($context['can_send_pm'])
					echo '
								<li><a href="', $scripturl, '?action=pm;sa=send;u=', $event['member']['id'], '" title="', $event['member']['online']['label'], '">', $settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/im_' . ($event['member']['online']['is_online'] ? 'on' : 'off') . '.gif" alt="' . $event['member']['online']['label'] . '" border="0" />' : $event['member']['online']['label'], '</a></li>';

				echo '
							</ul>
						</li>';
			}

			// Are we showing the warning status?
			if (!isset($context['disabled_fields']['warning_status']) && $event['member']['warning_status'] && ($context['user']['can_mod'] || (!empty($modSettings['warning_show']) && ($modSettings['warning_show'] > 1 || $event['member']['id'] == $context['user']))))
				echo '
						<li>', $context['can_issue_warning'] ? '<a href="' . $scripturl . '?action=profile;u=' . $event['member']['id'] . ';sa=issueWarning">' : '', '<img src="', $settings['images_url'], '/warning_', $event['member']['warning_status'], '.gif" alt="', $txt['user_warn_' . $event['member']['warning_status']], '" />', $context['can_issue_warning'] ? '</a>' : '', '<span class="warn_', $event['member']['warning_status'], '">', $txt['warn_' . $event['member']['warning_status']], '</span></li>';
		}
		// Otherwise, show the guest's email.
		elseif (in_array($event['member']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
			echo '
						<li><a href="', $scripturl, '?action=emailuser;sa=email;com=', $event['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" border="0" />' : $txt['email']), '</a></li>';

		echo '
					</ul>
				</div>
				<div class="postarea">
					<div class="keyinfo">';

		if (!empty($event['title']))
			echo '
						<h5>', $event['title'], '</h5>';

		echo '
						<div class="smalltext">&#171; <strong>', !empty($event['counter']) ? $txt['reply'] . ' #' . $event['counter'] : '', ' ', $txt['on'], ':</strong> ', $event['time'], ' &#187;</div>
					</div>';
					
		if ($event['is_comment'] && ($context['can_comment'] || $event['comment']['can_edit'] || $event['comment']['can_remove']))
			echo '
					<ul class="reset smalltext postingbuttons">';

		if ($event['is_comment'] && $context['can_comment'])
			echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'reply', 'quote' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '">', $reply_button, '</a></li>';

		if ($event['is_comment'] && $event['comment']['can_edit'])
			echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'edit', 'com' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '">', $modify_button, '</a></li>';

		if ($event['is_comment'] && $event['comment']['can_remove'])
			echo '
						<li><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'removeComment', 'com' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

		if ($event['is_comment'] && ($context['can_comment'] || $event['comment']['can_edit'] || $event['comment']['can_remove']))
			echo '
					</ul>';
		
		// Message / Edit
		echo '
					<div id="', $id2, '" class="post">';

		if ($event['is_comment'])
			echo '
						<div class="inner">', $event['comment']['body'], '</div>';

		if (!empty($event['changes']))
		{
			echo '
						<ul class="smalltext normallist">';

			foreach ($event['changes'] as $change)
				echo '
							<li>', $change, '</li>';

			echo '
						</ul>';
		}

		echo '
					</div>
				</div>
				<div class="moderatorbar">
					<div class="smalltext floatleft">';

		// Show "« Last Edit: Time by Person »" if this post was edited.
		if ($settings['show_modify'] && !empty($event['comment']['modified']['name']))
			echo '
						&#171; <em>', $txt['last_edit'], ': ', $event['comment']['modified']['time'], ' ', $txt['by'], ' ', $event['comment']['modified']['name'], '</em> &#187;';

		echo '
					</div>
					<div class="smalltext floatright">';
		echo '
						<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
		if (allowedTo('moderate_forum') && !empty($event['ip']))
			echo '
						<a href="', $scripturl, '?action=trackip;searchip=', $event['ip'], '">', $event['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
		// Or, should we show it because this is you?
		elseif ($event['can_see_ip'])
			echo '
						<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $event['ip'], '</a>';
		// Okay, are you at least logged in?  Then we can show something about why IPs are logged...
		elseif (!$context['user']['is_guest'])
			echo '
						<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $txt['logged'], '</a>';
		// Otherwise, you see NOTHING!
		else
			echo '
						', $txt['logged'];

		echo '
					</div>';

		// Show the member's signature?
		if (!empty($event['member']['signature']) && empty($options['show_no_signatures']))
			echo '
					<div class="signature">', $event['member']['signature'], '</div>';

		echo '
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

function template_issue_view_below()
{
	global $context, $settings, $options, $txt, $modSettings, $settings;

	$mod_buttons = array(
		'delete' => array('test' => 'can_issue_moderate', 'text' => 'issue_delete', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['issue_delete_confirm'] . '\');"', 'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'delete', $context['session_var'] => $context['session_id']))),
		'subscribe' => array('test' => 'can_subscribe', 'text' => empty($context['is_subscribed']) ? 'project_subscribe' : 'project_unsubscribe', 'image' => empty($context['is_subscribed']) ? 'subscribe.gif' : 'unsubscribe.gif', 'lang' => true, 'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'subscribe', $context['session_var'] => $context['session_id']))),
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
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'reply2')), '" method="post">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['comment_issue'], '</div>
			<div class="smallpadding windowbg" style="text-align: center">
				<textarea id="comment" name="comment" rows="7" cols="75" tabindex="', $context['tabindex']++, '"></textarea>';

		echo '
				<div style="text-align: right">
					<input type="submit" name="post" value="', $txt['add_comment'], '" onclick="return submitThisOnce(this);" accesskey="s" tabindex="', $context['tabindex']++, '" />
					<input type="submit" name="preview" value="', $txt['preview'], '" onclick="return submitThisOnce(this);" accesskey="p" tabindex="', $context['tabindex']++, '" />
				</div>
			</div>
		</div><br />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form><br />';
	}

	echo '
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'sa' => 'tags')), '" method="post">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['issue_tags'], '</div>
			<div class="smallpadding windowbg">';

	if (!empty($context['current_tags']) || $context['can_add_tags'])
	{
		echo '
				<ul class="clearfix tags">';

		if (!empty($context['current_tags']))
		{
			foreach ($context['current_tags'] as $tag)
			{
				echo '
					<li>', $tag['link'];

				if ($context['can_remove_tags'])
					echo '
						<a href="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'sa' => 'tags', 'remove', 'tag' => $tag['id'], $context['session_var'] => $context['session_id'])), '"><img src="', $settings['images_url'], '/icons/quick_remove.gif" alt="', $txt['remove_tag'], '" /></a>';

					echo '
					</li>';
			}
		}

		if ($context['can_add_tags'])
			echo '
					<li class="tag_editor">
						<input type="text" name="tag" value="" tabindex="', $context['tabindex']++, '" />
						<input type="submit" name="add_tag" value="', $txt['add_tag'], '" tabindex="', $context['tabindex']++, '" />
					</li>';

		echo '
				</ul>';
	}

	echo '
			</div>
		</div><br />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form><br />';

	if ($context['can_issue_attach'])
	{
		echo '
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'sa' => 'upload')), '" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['issue_attach'], '</div>
			<div class="smallpadding windowbg">
				<input type="file" size="48" name="attachment[]" tabindex="', $context['tabindex']++, '" /><br />';

		if (!empty($modSettings['attachmentCheckExtensions']))
			echo '
					', $txt['allowed_types'], ': ', $context['allowed_extensions'], '<br />';
		echo '
					', $txt['max_size'], ': ', $modSettings['attachmentSizeLimit'], ' ' . $txt['kilobyte'], '<br />';

		echo '
				<div style="text-align: right">
					<input type="submit" name="add_comment" value="', $txt['add_attach'], '" tabindex="', $context['tabindex']++, '" />
				</div>
			</div>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';
	}
}

function template_issue_move()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	echo '
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'sa' => 'move')), '" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['move_issue'], '</div>
			<div class="smallpadding windowbg">
				', $txt['project_to'], ' <select id="project_to" name="project_to">';
	
	foreach ($context['projects'] as $project)
		echo '
					<option value="', $project['id'], '">', $project['name'], '</option>';
		
	echo '
				
				</select>
				<div style="text-align: right">
					<input type="submit" name="move_issue" value="', $txt['move_issue_btn'], '" tabindex="', $context['tabindex']++, '" />
				</div>
			</div>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';		
}

?>