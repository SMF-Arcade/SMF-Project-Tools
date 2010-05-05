<?php
// Version: 0.5; IssueView

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
			'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0' , 'area' => 'issues', 'sa' => 'reply')),
			'lang' => true
		),
	);

	if ($context['current_issue']['details']['first_new'])
		echo '
	<a name="new"></a>';

	// Issue Details
	echo '
	<a name="com', $context['current_issue']['comment_first'], '"></a>
	<div id="issue_comments" class="floatleft">
		<h3 class="catbg"><span class="left"></span><span class="right"></span>
			<img src="', $settings['default_images_url'], '/', $context['current_issue']['tracker']['image'], '" alt="', $context['current_issue']['tracker']['name'], '" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
		</h3>';

}

function template_issue_view_main()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;
	
	echo '
	<div id="forumposts">';

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
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'right'), '
		</div>';
	
	$alternate = true;

	while ($event = getEvent())
	{
		if ($event['type'] == 'comment')
			template_event_full($event, $alternate);
		else
			template_event_compact($event, $alternate);
	}
	
	echo '
		<div id="loaded_events"></div>';

	echo '
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'right'), '
		</div><br />
	</div>';
}

function template_issue_view_below()
{
	global $context, $settings, $options, $txt, $modSettings, $settings;

	$mod_buttons = array(
		'delete' => array('test' => 'can_issue_moderate', 'text' => 'issue_delete', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['issue_delete_confirm'] . '\');"', 'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'delete', $context['session_var'] => $context['session_id']))),
		'move' => array('test' => 'can_issue_move', 'text' => 'issue_move', 'lang' => true, 'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'move', $context['session_var'] => $context['session_id']))),
		'subscribe' => array('test' => 'can_subscribe', 'text' => empty($context['is_subscribed']) ? 'project_subscribe' : 'project_unsubscribe', 'image' => empty($context['is_subscribed']) ? 'subscribe.gif' : 'unsubscribe.gif', 'lang' => true, 'url' => project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'sa' => 'subscribe', $context['session_var'] => $context['session_id']))),
	);

	echo '
	</div>';
	
	
	// Issue Info table
	echo '
	<div id="issueinfo" class="floatright">
		<h3 class="catbg"><span class="left"></span>
			', $txt['issue_details'], '
		</h3>
		<div class="windowbg">
			<span class="topslice"><span></span></span>
			<div class="smalltext">
				<ul class="details">
					<li>
						<dl>
							<dt>', $txt['issue_reported'], '</dt>
							<dd>', $context['current_issue']['created'], '</dd>
						</dl>
					</li>
					<li id="issue_updated">
						<dl>
							<dt>', $txt['issue_updated'], '</dt>
							<dd>', $context['current_issue']['updated'], '</dd>
						</dl>
					</li>
					<li id="issue_view_status">
						<dl>
							<dt>', $txt['issue_view_status'], '</dt>
							<dd>', $context['current_issue']['private'] ? $txt['issue_view_status_private'] : $txt['issue_view_status_public'], '</dd>
						</dl>
					</li>
					<li id="issue_tracker">
						<dl>
							<dt>', $txt['issue_type'], '</dt>
							<dd>', $context['current_issue']['tracker']['name'], '</dd>
						</dl>
					</li>
					<li id="issue_status">
						<dl>
							<dt>', $txt['issue_status'], '</dt>
							<dd>', $context['current_issue']['status']['text'], '</dd>
						</dl>
					</li>
					<li id="issue_priority">
						<dl>
							<dt>', $txt['issue_priority'], '</dt>
							<dd>', $txt[$context['current_issue']['priority']], '</dd>
						</dl>
					</li>
					<li id="issue_version">
						<dl>
							<dt>', $txt['issue_version'], '</dt>
							<dd>';
	
	if (empty($context['current_issue']['versions']))
		echo $txt['issue_none'];
	else
	{
		$first = true;
		
		foreach ($context['current_issue']['versions'] as $version)
		{
			if ($first)
				$first = false;
			else
				echo ', ';
				
			echo $version['name'];
		}
	}
						
	echo '
							</dd>
						</dl>
					</li>
					<li id="issue_verfix">
						<dl>
							<dt>', $txt['issue_version_fixed'], '</dt>
							<dd>';
						
	if (empty($context['current_issue']['versions_fixed']))
		echo $txt['issue_none'];
	else
	{
		$first = true;
		
		foreach ($context['current_issue']['versions_fixed'] as $version)
		{
			if ($first)
				$first = false;
			else
				echo ', ';
				
			echo $version['name'];
		}
	}
						
	echo '
							</dd>
						</dl>
					</li>
					<li id="issue_assign">
						<dl>
							<dt>', $txt['issue_assigned_to'], '</dt>
							<dd>', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '</dd>
						</dl>
					</li>
					<li id="issue_category">
						<dl>
							<dt>', $txt['issue_category'], '</dt>
							<dd>', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '</dd>
						</dl>
					</li>
				</ul>
			</div>
			<span class="botslice"><span></span></span>
		</div>
		<br class="clear" />';

	// Tags		
	echo '
		<h3 class="catbg"><span class="left"><!-- // --></span>', $txt['issue_tags'], '</h3>
		<form action="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'area' => 'issues', 'sa' => 'tags')), '" method="post">
			<div class="windowbg">
				<span class="topslice"><span><!-- // --></span></span>';

	if (!empty($context['current_tags']) || $context['can_add_tags'])
	{
		echo '
				<ul class="reset clearfix tags">';

		if (!empty($context['current_tags']))
		{
			foreach ($context['current_tags'] as $tag)
			{
				echo '
					<li>', $tag['link'];

				if ($context['can_remove_tags'])
					echo '
						<a href="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'area' => 'issues', 'sa' => 'tags', 'remove', 'tag' => $tag['id'], $context['session_var'] => $context['session_id'])), '"><img src="', $settings['images_url'], '/icons/quick_remove.gif" alt="', $txt['remove_tag'], '" /></a>';

					echo '
					</li>';
			}
		}

		if ($context['can_add_tags'])
			echo '
					<li class="tag_editor">
						<input type="text" name="tag" value="" tabindex="', $context['tabindex']++, '" />
						<input class="button_submit" type="submit" name="add_tag" value="', $txt['add_tag'], '" tabindex="', $context['tabindex']++, '" />
					</li>';

		echo '
				</ul>
				<br class="clear" />';
	}

	echo '
				<span class="botslice"><span><!-- // --></span></span>
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
		</form>
		<br class="clear" />';

	// Attachments
	if (!empty($context['attachments']) || $context['can_issue_attach'])
	{
		echo '
		<h3 class="catbg"><span class="left"><!-- // --></span>', $txt['issue_attachments'], '</h3>
		<div class="windowbg">
			<span class="topslice"><span><!-- // --></span></span>
			<div class="content">';
			
		if (!empty($context['attachments']))
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
		
		
		if ($context['can_issue_attach'])
		{
			if (!empty($context['attachments']))
				echo '
				<hr />';
				
			echo '
				<form action="', project_get_url(array('issue' => $context['current_issue']['id'], '.0', 'area' => 'issues', 'sa' => 'upload')), '" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">		
					<input type="file" size="32" name="attachment[]" tabindex="', $context['tabindex']++, '" /><br />';

			if (!empty($modSettings['attachmentCheckExtensions']))
				echo '
						', $txt['allowed_types'], ': ', $context['allowed_extensions'], '<br />';
			echo '
						', $txt['max_size'], ': ', $modSettings['attachmentSizeLimit'], ' ' . $txt['kilobyte'], '<br />';

			echo '
					<input class="button_submit" type="submit" name="add_comment" value="', $txt['add_attach'], '" tabindex="', $context['tabindex']++, '" />
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				</form>';
		}
		
		echo '
			</div>
			<span class="botslice"><span><!-- // --></span></span>
		</div>';
	}
	
	echo '
	</div>
	<br class="clear" />
	<div id="moderationbuttons">
		', template_button_strip($mod_buttons, 'bottom'), '
	</div>
	<div class="tborder">
		<div class="titlebg2" style="padding: 4px;" align="', !$context['right_to_left'] ? 'right' : 'left', '">&nbsp;</div>
	</div><br />';

	if ($context['can_comment'])
	{
		echo '
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues',  'sa' => 'reply2')), '" method="post">
		<div class="tborder">
			<h3 class="catbg"><span class="left"><!-- // --></span>', $txt['comment_issue'], '</h3>
			<div class="smallpadding windowbg" style="text-align: center">
				<span class="topslice"><span><!-- // --></span></span>
				<textarea id="comment" name="comment" rows="7" cols="75" tabindex="', $context['tabindex']++, '"></textarea>';

		echo '
				<div style="text-align: right; padding-right: 5px;">
					<input class="button_submit" type="submit" name="post" value="', $txt['add_comment'], '" onclick="return submitThisOnce(this);" accesskey="s" tabindex="', $context['tabindex']++, '" />
					<input class="button_submit" type="submit" name="preview" value="', $txt['preview'], '" onclick="return submitThisOnce(this);" accesskey="p" tabindex="', $context['tabindex']++, '" />
				</div>
				<span class="botslice"><span><!-- // --></span></span>
			</div>
		</div><br />
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form><br />';
	}
	
	echo '
	<script language="JavaScript" type="text/javascript">
		var currentIssue = new PTIssue(', $context['current_issue']['id'], ', "', $context['issue_xml_url'], '", ', $context['current_issue']['id_event_mod'], ', "loaded_events");
		currentIssue.addLabel("issue_updated", "updated");';
		
	// If no permission to update, add as labels, so they get updated on ajax events
	if (empty($context['can_issue_update']))
	{
		echo '
		currentIssue.addLabel("issue_view_status", "private");
		currentIssue.addLabel("issue_tracker", "tracker");
		currentIssue.addLabel("issue_category", "category");
		currentIssue.addLabel("issue_version", "version");
		currentIssue.addLabel("issue_status", "status");
		currentIssue.addLabel("issue_assign", "assign");
		currentIssue.addLabel("issue_verfix", "version_fixed");
		currentIssue.addLabel("issue_priority", "priority");';
	}
	else
	{
		echo '
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
		
		if (!empty($context['versions']))
		{
			// Affected Version
			echo '
			var ddIssueVers = currentIssue.addMultiDropdown("issue_version", "version");';

			// Versions
			foreach ($context['versions'] as $v)
			{
				echo '
			ddIssueVers.addOption(', $v['id'], ', "', $v['name'], '", ', isset($context['current_issue']['versions'][$v['id']]) ? 1 : 0 ,', "group");';
	
				foreach ($v['sub_versions'] as $subv)
					echo '
			ddIssueVers.addOption(', $subv['id'], ', "', $subv['name'], '", ', isset($context['current_issue']['versions'][$subv['id']]) ? 1 : 0 ,');';
			}
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
			
			if (!empty($context['versions']))
			{
				// Fixed Version
				echo '
			var ddIssueFixv = currentIssue.addMultiDropdown("issue_verfix", "version_fixed")';
	
				// Versions
				foreach ($context['versions'] as $v)
				{
					echo '
			ddIssueFixv.addOption(', $v['id'], ', "', $v['name'], '", ', isset($context['current_issue']['versions_fixed'][$v['id']]) ? 1 : 0 ,', "group");';
	
				foreach ($v['sub_versions'] as $subv)
					echo '
			ddIssueFixv.addOption(', $subv['id'], ', "', $subv['name'], '", ', isset($context['current_issue']['versions_fixed'][$subv['id']]) ? 1 : 0 ,');';
				}
			}

			// Priority
			echo '
			var ddIssuePrio = currentIssue.addDropdown("issue_priority", "priority", ', (int) $context['current_issue']['priority_num'], ');';

			foreach ($context['issue']['priority'] as $id => $text)
				echo '
			ddIssuePrio.addOption(', $id, ', "', $txt[$text], '");';
		}
		// If no permission, add as labels, so they get updated
		else
		{
			echo '
		currentIssue.addLabel("issue_status", "status");
		currentIssue.addLabel("issue_assign", "assign");
		currentIssue.addLabel("issue_verfix", "version_fixed");
		currentIssue.addLabel("issue_priority", "priority");';
		}
	}
	

	echo '
	</script>';
}

function template_event_full(&$event, &$alternate)
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$reply_button = create_button('quote.gif', 'reply_quote', 'quote', 'align="middle"');
	$modify_button = create_button('modify.gif', 'modify_msg', 'modify', 'align="middle"');
	$remove_button = create_button('delete.gif', 'remove_comment_alt', 'remove_comment', 'align="middle"');
	
	$id = isset($event['comment']) ? 'com' . $event['comment']['id'] : 'evt' . $event['id'];
	$id2 = isset($event['comment']) ? 'com_' . $event['comment']['id'] : 'evt_' . $event['id'];
		
	echo '
		<div class="windowbg', $alternate ? '' : '2', '">
			<span class="topslice"><span></span></span>';

	// Show information about the poster of this message.
	echo '
			<div class="poster">
				<h4>';
	// Show online and offline buttons?
	if (!empty($modSettings['onlineEnable']) && !$event['member']['is_guest'])
		echo  $context['can_send_pm'] ? '<a href="' . $event['member']['online']['href'] . '" title="' . $event['member']['online']['label'] . '">' : '', '<img src="', $event['member']['online']['image_href'], '" alt="', $event['member']['online']['text'], '" />', $context['can_send_pm'] ? '</a>' : '', '&nbsp;';

	echo $event['member']['link'], '</h4>
				<ul class="reset smalltext" id="', $id, '_extra_info">';

	// Show the member's custom title, if they have one.
	if (isset($event['member']['title']) && $event['member']['title'] != '')
		echo '
					<li class="title">', $event['member']['title'], '</li>';

	// Show the member's primary group (like 'Administrator') if they have one.
	if (isset($event['member']['group']) && $event['member']['group'] != '')
		echo '
					<li class="membergroup">', $event['member']['group'], '</li>';

	// Don't show these things for guests.
	if (!$event['member']['is_guest'])
	{
		// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
		if ((empty($settings['hide_post_group']) || $event['member']['group'] == '') && $event['member']['post_group'] != '')
			echo '
					<li class="postgroup">', $event['member']['post_group'], '</li>';
		echo '
					<li class="stars">', $event['member']['group_stars'], '</li>';

		// Show avatars, images, etc.?
		if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($event['member']['avatar']['image']))
			echo '
					<li class="avatar" style="overflow: auto;">', $event['member']['avatar']['image'], '</li>';

		// Show how many posts they have made.
		if (!isset($context['disabled_fields']['posts']))
				echo '
					<li class="postcount">', $txt['member_postcount'], ': ', $event['member']['posts'], '</li>';

		// Is karma display enabled?  Total or +/-?
		if ($modSettings['karmaMode'] == '1')
			echo '
					<li class="karma">', $modSettings['karmaLabel'], ' ', $event['member']['karma']['good'] - $event['member']['karma']['bad'], '</li>';
		elseif ($modSettings['karmaMode'] == '2')
			echo '
					<li class="karma">', $modSettings['karmaLabel'], ' +', $event['member']['karma']['good'], '/-', $event['member']['karma']['bad'], '</li>';

		// Is this user allowed to modify this member's karma?
		if ($event['member']['karma']['allow'])
			echo '
					<li class="karma_allow">
						<a href="', $scripturl, '?action=modifykarma;sa=applaud;uid=', $event['member']['id'], ';issue=', $context['current_issue']['id'], '.' . $context['start'], ';e=', $event['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaApplaudLabel'], '</a>
						<a href="', $scripturl, '?action=modifykarma;sa=smite;uid=', $event['member']['id'], ';issue=', $context['current_issue']['id'], '.', $context['start'], ';e=', $event['id'], ';', $context['session_var'], '=', $context['session_id'], '">', $modSettings['karmaSmiteLabel'], '</a>
					</li>';

		// Show the member's gender icon?
		if (!empty($settings['show_gender']) && $event['member']['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
			echo '
					<li class="gender">', $txt['gender'], ': ', $event['member']['gender']['image'], '</li>';

		// Show their personal text?
		if (!empty($settings['show_blurb']) && $event['member']['blurb'] != '')
			echo '
					<li class="blurb">', $event['member']['blurb'], '</li>';

		// Any custom fields to show as icons?
		if (!empty($event['member']['custom_fields']))
		{
			$shown = false;
			foreach ($event['member']['custom_fields'] as $custom)
			{
				if ($custom['placement'] != 1 || empty($custom['value']))
					continue;
				if (empty($shown))
				{
					$shown = true;
					echo '
					<li class="im_icons">
						<ul>';
				}
				echo '
							<li>', $custom['value'], '</li>';
			}
			if ($shown)
				echo '
						</ul>
					</li>';
		}

		// This shows the popular messaging icons.
		if ($event['member']['has_messenger'] && $event['member']['can_view_profile'])
			echo '
					<li class="im_icons">
						<ul>
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
					<li class="profile">
						<ul>';
			// Don't show the profile button if you're not allowed to view the profile.
			if ($event['member']['can_view_profile'])
				echo '
							<li><a href="', $event['member']['href'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/icons/profile_sm.gif" alt="' . $txt['view_profile'] . '" title="' . $txt['view_profile'] . '" border="0" />' : $txt['view_profile']), '</a></li>';

			// Don't show an icon if they haven't specified a website.
			if ($event['member']['website']['url'] != '' && !isset($context['disabled_fields']['website']))
				echo '
							<li><a href="', $event['member']['website']['url'], '" title="' . $event['member']['website']['title'] . '" target="_blank" class="new_win">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/www_sm.gif" alt="' . $event['member']['website']['title'] . '" border="0" />' : $txt['www']), '</a></li>';

			// Don't show the email address if they want it hidden.
			if (in_array($event['member']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
				echo '
							<li><a href="', $scripturl, '?action=emailuser;sa=email;uid=', $event['member']['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']), '</a></li>';

			// Since we know this person isn't a guest, you *can* message them.
			if ($context['can_send_pm'])
				echo '
							<li><a href="', $scripturl, '?action=pm;sa=send;u=', $event['member']['id'], '" title="', $event['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline'], '">', $settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/im_' . ($event['member']['online']['is_online'] ? 'on' : 'off') . '.gif" alt="' . ($event['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']) . '" border="0" />' : ($event['member']['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']), '</a></li>';
				
			echo '
						</ul>
					</li>';
		}

		// Any custom fields for standard placement?
		if (!empty($event['member']['custom_fields']))
		{
			foreach ($event['member']['custom_fields'] as $custom)
				if (empty($custom['placement']) || empty($custom['value']))
					echo '
					<li class="custom">', $custom['title'], ': ', $custom['value'], '</li>';
		}

		// Are we showing the warning status?
		if (!isset($context['disabled_fields']['warning_status']) && $event['member']['warning_status'] && ($context['user']['can_mod'] || !empty($modSettings['warning_show'])))
			echo '
					<li class="warning">', $context['can_issue_warning'] ? '<a href="' . $scripturl . '?action=profile;area=issuewarning;u=' . $event['member']['id'] . '">' : '', '<img src="', $settings['images_url'], '/warning_', $event['member']['warning_status'], '.gif" alt="', $txt['user_warn_' . $event['member']['warning_status']], '" />', $context['can_issue_warning'] ? '</a>' : '', '<span class="warn_', $event['member']['warning_status'], '">', $txt['warn_' . $event['member']['warning_status']], '</span></li>';
	}
	// Otherwise, show the guest's email.
	elseif (in_array($event['member']['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
		echo '
					<li class="email"><a href="', $scripturl, '?action=emailuser;sa=email;uid=', $event['member']['id'], '" rel="nofollow">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" border="0" />' : $txt['email']), '</a></li>';

	// Done with the information about the poster... on to the post itself.
	echo '
				</ul>
			</div>
			<div class="postarea">
				<div class="flow_hidden">
					<div class="keyinfo">';
					
	if (!empty($event['title']))
		echo '
						<h5>', $event['title'], '</h5>';
						
	echo '
						<div class="smalltext">&#171; <strong>', !empty($event['counter']) ? $txt['reply_noun'] . ' #' . $event['counter'] : '', ' ', $txt['on'], ':</strong> ', $event['time'], ' &#187;</div>
						<div id="', $id, '_quick_mod"></div>
					</div>';

	// If this is the first post, (#0) just say when it was posted - otherwise give the reply #.
	if ($event['is_comment'] && ($context['can_comment'] || $event['comment']['can_edit'] || $event['comment']['can_remove']))
		echo '
					<ul class="reset smalltext quickbuttons">';

	// Can they reply? Have they turned on quick reply?
	if ($event['is_comment'] && $context['can_comment'])
		echo '
						<li class="quote_button" ><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'reply', 'quote' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '">', $txt['quote'], '</a></li>';

	// Can the user modify the contents of this post?
	if ($event['is_comment'] && $event['comment']['can_edit'])
		echo '
						<li class="modify_button"><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'edit', 'com' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '">', $txt['modify'], '</a></li>';

	// How about... even... remove it entirely?!
	if ($event['is_comment'] && $event['comment']['can_remove'])
		echo '
						<li class="remove_button"><a href="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'removeComment', 'com' => $event['comment']['id'], $context['session_var'] => $context['session_id'])), '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $txt['remove'], '</a></li>';

	if ($event['is_comment'] && ($context['can_comment'] || $event['comment']['can_edit'] || $event['comment']['can_remove']))
		echo '
					</ul>';

	echo '
				</div>
				<div class="post">
					<div class="inner" id="', $id2, '">
						', $event['comment']['body'];

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
				</div>';

	// Now for the signature, ip logged, etc...
	echo '
				<div class="moderatorbar">
					<div class="smalltext modified" id="modified_', $event['id'], '">';

	// Show "« Last Edit: Time by Person »" if this post was edited.
	if ($settings['show_modify'] && !empty($event['comment']['modified']['name']))
		echo '
							&#171; <em>', $txt['last_edit'], ': ', $event['comment']['modified']['time'], ' ', $txt['by'], ' ', $event['comment']['modified']['name'], '</em> &#187;';

	echo '
					</div>
					<div class="smalltext reportlinks">
						<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
	if ($context['can_moderate_forum'] && !empty($event['member']['ip']))
		echo '
						<a href="', $scripturl, '?action=', !empty($event['member']['is_guest']) ? 'trackip' : 'profile;area=tracking;sa=ip;u='. $event['member']['id'], ';searchip=', $event['member']['ip'], '">', $event['member']['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
	// Or, should we show it because this is you?
	elseif ($event['can_see_ip'])
		echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $event['member']['ip'], '</a>';
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

	// Are there any custom profile fields for above the signature?
	if (!empty($event['member']['custom_fields']))
	{
		$shown = false;
		foreach ($event['member']['custom_fields'] as $custom)
		{
			if ($custom['placement'] != 2 || empty($custom['value']))
				continue;
			if (empty($shown))
			{
				$shown = true;
				echo '
					<div class="custom_fields_above_signature">
						<ul class="reset nolist">';
			}
			echo '
							<li>', $custom['value'], '</li>';
		}
		if ($shown)
			echo '
						</ul>
					</div>';
	}

	// Show the member's signature?
	if (!empty($event['member']['signature']) && empty($options['show_no_signatures']) && $context['signature_enabled'])
		echo '
					<div class="signature">', $event['member']['signature'], '</div>';

	echo '
				</div>
				<span class="botslice"><span></span></span>
			</div>
			<hr class="post_separator" />';
		
	$alternate = !$alternate;	
}

function template_event_compact(&$event, &$alternate)
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;
	
	$id = isset($event['comment']) ? 'com' . $event['comment']['id'] : 'evt' . $event['id'];
	$id2 = isset($event['comment']) ? 'com_' . $event['comment']['id'] : 'evt_' . $event['id'];

	echo '
		<div class="windowbg', $alternate ? '' : '2', '">
			<span class="topslice"><span></span></span>';
		
	if (!empty($event['title']))
		echo '
			<h5>', $event['title'], '</h5>
			<div class="smalltext">&#171; <strong>', !empty($event['counter']) ? $txt['reply_noun'] . ' #' . $event['counter'] : '', ' ', $txt['on'], ':</strong> ', $event['time'], ' &#187;</div>
			<div class="post">
				<div class="inner" id="', $id2, '">';

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
			</div>';

		// Now for the signature, ip logged, etc...
		echo '
			<div class="moderatorbar">
				<div class="smalltext reportlinks">
					<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
		if ($context['can_moderate_forum'] && !empty($event['member']['ip']))
			echo '
						<a href="', $scripturl, '?action=', !empty($event['member']['is_guest']) ? 'trackip' : 'profile;area=tracking;sa=ip;u='. $event['member']['id'], ';searchip=', $event['member']['ip'], '">', $event['member']['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
		// Or, should we show it because this is you?
		elseif ($event['can_see_ip'])
			echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $event['member']['ip'], '</a>';
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
			<span class="botslice"><span></span></span>
		</div>
		<hr class="post_separator" />';
		
	$alternate = !$alternate;	
}

function template_issue_move()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	echo '
	<form action="', project_get_url(array('issue' => $context['current_issue']['id'] . '.0', 'area' => 'issues', 'sa' => 'move')), '" method="post" accept-charset="', $context['character_set'], '">
		<div class="tborder">
			<h3 class="catbg"><span class="left"><!-- // --></span>', $txt['move_issue'], '</h3>
			<div class="smallpadding windowbg">
				', $txt['project_to'], ' <select id="project_to" name="project_to">';
	
	foreach ($context['projects'] as $project)
		echo '
					<option value="', $project['id'], '">', $project['name'], '</option>';
		
	echo '
				
				</select>
				<div style="text-align: right">
					<input class="button_submit" type="submit" name="move_issue" value="', $txt['move_issue_btn'], '" tabindex="', $context['tabindex']++, '" />
				</div>
			</div>
		</div>
		<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
	</form>';		
}

?>