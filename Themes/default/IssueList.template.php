<?php
// Version: 0.1 Alpha; IssueList

function template_issue_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	$buttons = array(
		'post_issue' => array(
			'text' => 'new_issue',
			'image' => 'new_issue.gif',
			'url' => $scripturl . '?project=' . $context['project']['id'] . ';sa=reportIssue',
			'lang' => true
		),
	);

	echo '
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="middletext" style="padding-bottom: 4px;" valign="bottom">', $txt['pages'], ': ', $context['page_index'], '&nbsp;&nbsp;<a href="#issue_bottom"><b>', $txt['go_down'], '</b></a></td>
				<td style="padding-right: 1ex;" align="right">
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td>', template_button_strip($buttons, 'bottom'), '</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<div class="tborder">
			<table class="bordercolor" border="0" cellpadding="4" cellspacing="1" width="100%">';

	if (!empty($context['issues']))
	{
		echo '
				<tr>
					<td class="catbg3" width="16"></td>
					<td class="catbg3">', $txt['issue_title'], '</td>
					<td class="catbg3">', $txt['issue_status'], '</td>
					<td class="catbg3">', $txt['issue_reported_by'], '</td>
					<td class="catbg3"></td>
					<td class="catbg3">', $txt['issue_category'], '</td>
					<td class="catbg3">', $txt['issue_assigned_to'], '</td>
				</tr>';

		foreach ($context['issues'] as $i => $issue)
		{
			echo '
				<tr class="windowbg">
					<td style="text-align: center"><a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;type=', $issue['type'], '"><img src="', $settings['images_url'], '/', $issue['type'], '.png" alt="" /></a></td>
					<td class="windowbg2"><a href="', $issue['link'], '">', $issue['name'], '</a></td>
					<td>', $issue['status'], '</td>
					<td class="windowbg2">', $issue['reporter'], '</td>
					<td>', $issue['created'], '<br />', $issue['updated'], '</td>
					<td>-----</td>
					<td>-----</td>
				</tr>';
		}

	}
	else
	{
		echo '
				<tr>
					<td class="catbg3"><b>', $txt['issue_no_issues'], '</b></td>
				</tr>';
	}

	echo '
			</table>
		</div>
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td class="middletext">', $txt['pages'], ': ', $context['page_index'], '&nbsp;&nbsp;<a href="#issue_top"><b>', $txt['go_up'], '</b></a></td>
				<td style="padding-right: 1ex;" align="right">
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td>', template_button_strip($buttons, 'top'), '</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>';
}

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');

	$reporter = &$context['current_issue']['reporter'];

	echo '
	<div id="issueinfo" class="tborder">
		<h3 class="catbg3">
			<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
		</h3>';


	echo '
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
					<div class="post">';


	// Info table
	echo '
						<div class="floatright infotable tborder">
							<div class="windowbg">
								<span class="dark">', $txt['issue_category'], '</span>
								<span class="value">', !empty($context['current_issue']['category']) ? $context['current_issue']['category'] : $txt['issue_none'], '</span>
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_type'], '</span>
								<span class="value">', $context['current_issue']['type']['name'], '</span>
							</div>
							<div class="windowbg">
								<span class="dark">', $txt['issue_priority'], '</span>
								', $txt[$context['current_issue']['priority']], '
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_status'], '</span>
								', $context['current_issue']['status']['text'], '
							</div>
							<div class="windowbg">
								<span class="dark">', $txt['issue_version'], '</span>
								', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_version_fixed'], '</span>
								', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
							</div>
							<div class="windowbg last">
								<span class="dark">', $txt['issue_assigned_to'], '</span>
								', !empty($context['current_issue']['assignee']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
							</div>
						</div>';

	echo '
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
	</div>';

/*


	// Description part
	echo '
				<tr>
					<td valign="top" width="16%" rowspan="2" style="overflow: hidden;">
						<b>', $reporter['link'], '</b>
						<div class="smalltext" id="reporter_extra">';

	// Show title
	if (isset($reporter['title']) && $reporter['title'] != '')
		echo '
							', $reporter['title'], '<br />';

	// ... and group
	if (isset($reporter['group']) && $reporter['group'] != '')
		echo '
							', $reporter['group'], '<br />';

	if (!$reporter['is_guest'])
	{
		if ((empty($settings['hide_post_group']) || $reporter['group'] == '') && $reporter['post_group'] != '')
			echo '
							', $reporter['post_group'], '<br />';
		echo '
							', $reporter['group_stars'], '<br />';

		if ($modSettings['karmaMode'] == '1')
			echo '
							<br />
							', $modSettings['karmaLabel'], ' ', $reporter['karma']['good'] - $reporter['karma']['bad'], '<br />';
		elseif ($modSettings['karmaMode'] == '2')
			echo '
							<br />
							', $modSettings['karmaLabel'], ' +', $reporter['karma']['good'], '/-', $reporter['karma']['bad'], '<br />';

		if ($reporter['karma']['allow'])
			echo '
							<a href="', $scripturl, '?action=modifykarma;sa=applaud;uid=', $reporter['id'], ';issue=', $context['current_issue']['id'], ';sesc=', $context['session_id'], '">', $modSettings['karmaApplaudLabel'], '</a>
							<a href="', $scripturl, '?action=modifykarma;sa=smite;uid=', $reporter['id'], ';issue=', $context['current_issue']['id'], ';sesc=', $context['session_id'], '">', $modSettings['karmaSmiteLabel'], '</a><br />';

		if (!empty($modSettings['onlineEnable']))
			echo '
							', $context['can_send_pm'] ? '<a href="' . $reporter['online']['href'] . '" title="' . $reporter['online']['label'] . '">' : '', $settings['use_image_buttons'] ? '<img src="' . $reporter['online']['image_href'] . '" alt="' . $reporter['online']['text'] . '" border="0" style="margin-top: 2px;" />' : $reporter['online']['text'], $context['can_send_pm'] ? '</a>' : '', $settings['use_image_buttons'] ? '<span class="smalltext"> ' . $reporter['online']['text'] . '</span>' : '', '<br /><br />';

		if (!empty($settings['show_gender']) && $reporter['gender']['image'] != '' && !isset($context['disabled_fields']['gender']))
			echo '
							', $txt['gender'], ': ', $reporter['gender']['image'], '<br />';

		if (!isset($context['disabled_fields']['posts']))
			echo '
							', $txt['member_postcount'], ': ', $reporter['posts'], '<br />';

		// Custom fields?
		echo '<br />';

		// Show avatars, images, etc.?
		if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($reporter['avatar']['image']))
			echo '
							<div style="overflow: auto; width: 100%;">', $reporter['avatar']['image'], '</div><br />';

		// Show their personal text?
		if (!empty($settings['show_blurb']) && $reporter['blurb'] != '')
			echo '
							', $reporter['blurb'], '<br />
							<br />';

		// This shows the popular messaging icons.
		echo '
							', !isset($context['disabled_fields']['icq']) ? $reporter['icq']['link'] : '', '
							', !isset($context['disabled_fields']['msn']) ? $reporter['msn']['link'] : '', '
							', !isset($context['disabled_fields']['aim']) ? $reporter['aim']['link'] : '', '
							', !isset($context['disabled_fields']['yim']) ? $reporter['yim']['link'] : '', '
							<br />';

		// Show the profile, website, email address, and personal message buttons.
		if ($settings['show_profile_buttons'])
		{
			// Don't show the profile button if you're not allowed to view the profile.
			if ($reporter['can_view_profile'])
				echo '
							<a href="', $reporter['href'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/icons/profile_sm.gif" alt="' . $txt['view_profile'] . '" title="' . $txt['view_profile'] . '" border="0" />' : $txt['view_profile']), '</a>';

			// Don't show an icon if they haven't specified a website.
			if ($reporter['website']['url'] != '' && !isset($context['disabled_fields']['website']))
				echo '
							<a href="', $reporter['website']['url'], '" title="' . $reporter['website']['title'] . '" target="_blank">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/www_sm.gif" alt="' . $txt['www'] . '" border="0" />' : $txt['www']), '</a>';

			// Don't show the email address if they want it hidden.
			if (empty($reporter['hide_email']))
				echo '
							<a href="', !empty($modSettings['make_email_viewable']) ? 'mailto:' . $reporter['email'] : $scripturl . '?action=emailuser;sa=email;issue=' . $context['current_issue']['id'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']), '</a>';

			// Since we know this person isn't a guest, you *can* message them.
			if ($context['can_send_pm'])
				echo '
							<a href="', $scripturl, '?action=pm;sa=send;u=', $reporter['id'], '" title="', $reporter['online']['label'], '">', $settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/im_' . ($reporter['online']['is_online'] ? 'on' : 'off') . '.gif" alt="' . $reporter['online']['label'] . '" border="0" />' : $reporter['online']['label'], '</a>';
		}

		// Are we showing the warning status?
		if (!isset($context['disabled_fields']['warning_status']) && $reporter['warning_status'] && ($context['user']['can_mod'] || !empty($modSettings['warning_show'])))
			echo '
							<br />
							', $context['can_issue_warning'] ? '<a href="' . $scripturl . '?action=profile;u=' . $reporter['id'] . ';sa=issueWarning">' : '', '<img src="', $settings['images_url'], '/warning_', $reporter['warning_status'], '.gif" alt="', $txt['user_warn_' . $reporter['warning_status']], '"/>', $context['can_issue_warning'] ? '</a>' : '', '<span class="warn_', $reporter['warning_status'], '">', $txt['warn_' . $reporter['warning_status']], '</span>';
	}
	// Otherwise, show the guest's email.
	elseif (empty($reporter['hide_email']))
		echo '
							<br />
							<br />
							<a href="mailto:', $reporter['email'], '">', ($settings['use_image_buttons'] ? '<img src="' . $settings['images_url'] . '/email_sm.gif" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" border="0" />' : $txt['email']), '</a>';

	/*
	 				<tr class="windowbg">
					<td', empty($context['current_issue']['updated']) ? ' colspan="2" width="66%"' : ' width="33%"', '><span style="color: grey">', $txt['issue_created'], '</span> ', $context['current_issue']['created'], '</td>',
					!empty($context['current_issue']['updated']) ? '<td><span style="color: grey">' . $txt['issue_updated'] . '</span> ' . $context['current_issue']['updated']. '</td>' : '', '
					<td width="33%"><span style="color: grey">', $txt['issue_reported_by'], '</span> ???</td>
				</tr>
	*/
/*
	echo '
						</div>
					</td>
					<td valign="top" width="85%" height="100%">
						<table width="100%" border="0"><tr>
							<td valign="middle" width="10%">viesti kuva</td>
							<td valign="middle">
								<div style="font-weight: bold;" id="subject_report">
									<a href="#">', $context['current_issue']['name'], '</a>
								</div>
								<div class="smalltext">&#171; <b>', $txt['reported_on'], ':</b> ', $context['current_issue']['created'], ' &#187; &#187;</div>
							</td>
							<td align="', !$context['right_to_left'] ? 'right' : 'left', '" valign="bottom" height="20" style="font-size: smaller;"><div id="report_quick_mod">';

	// Report... finally
	echo '
							</div></td>
						</tr></table>';

	echo '
						<hr width="100%" size="1" class="hrcolor" />
						<div class="post" id="reportdesc"', '>
							', $context['current_issue']['body'], '
						</div>
					</td>
				</tr>';

	// And footer
	echo '
				<tr id="issue_footer">
					<td valign="bottom" class="smalltext" width="85%">
						<table width="100%" border="0" style="table-layout: fixed;"><tr>
							<td colspan="2" class="smalltext" width="100%">';

	echo '
							</td>
						</tr><tr>
							<td valign="bottom" class="smalltext" id="updatetime">';

	echo '
							</td>
							<td align="', !$context['right_to_left'] ? 'right' : 'left', '" valign="bottom" class="smalltext">';

	echo '
							</td>
						</tr></table>';

	echo '
					</td>
					</tr>
				</table>
			</td></tr>
		</table>
	</td></tr>
	<tr><td style="padding: 0 0 1px 0;"></td></tr>
</table>';*/


/*		<tr class="windowbg"><td>
			<table width="100%" cellpadding="5" cellspacing="0" style="table-layout: fixed;">
				<td valign="top" width="16%" rowspan="2" style="overflow: hidden;">
					***
				</td>
				<td valign="top" width="85%" height="100%">
					<td valign="middle">igon</td>
					<td valign="middle">
					<div style="font-weight: bold;">
						', $context['current_issue']['name'], '
					</div>
					<div class="smalltext">&#171; blah &#187;</div></td>
					<td align="', !$context['right_to_left'] ? 'right' : 'left', '" valign="bottom" height="20" style="font-size: smaller;">
					buttons?
				</div></td>
			</table>
			<hr width="100%" size="1" class="hrcolor" />
			<div class="post" id="issue_main"', '>
				', $context['current_issue']['body'], '
			</div>
		</td></tr>';

	if (!empty($context['current_issue']['updated']))
		echo '
			<div>&#171; <i>', $txt['issue_updated'], ': ', $context['current_issue']['updated'], ' </i> &#187;</div>';
	echo '
	</table></td></tr>
</table>';*/

/*	echo '
<table cellpadding="0" cellspacing="0" border="0" width="100%" class="bordercolor">';

	while ($comment = $context['get_comment']())
	{
		echo '
	<tr><td style="padding: 1px 1px 0 1px;">
		<table width="100%" cellpadding="3" cellspacing="0" border="0">
			<tr><td class="', $comment['alternate'] == 0 ? 'windowbg' : 'windowbg2', '
				<table width="100%" cellpadding="5" cellspacing="0" style="table-layout: fixed;">
					<tr>
						<td valign="top" width="16%" rowspan="2" style="overflow: hidden;">
							<b>', $comment['member']['link'], '</b>
							<div class="smalltext" id="msg_', $comment['id'], '_extra_info">member info here';

		echo '
							</div>
						</td>
						<td valign="top" width="85%" height="100%">
							<table width="100%" border="0"><tr>
								<td valign="middle">igon</td>
								<td valign="middle">
									<div style="font-weight: bold;">
										', $comment['subject'], '</a>
									</div>
									<div class="smalltext">&#171; <b>', !empty($comment['counter']) ? $txt['reply'] . ' #' . $comment['counter'] : '', ' ', $txt['on'], ':</b> ', $comment['time'], ' &#187;</div></td>
								<td align="', !$context['right_to_left'] ? 'right' : 'left', '" valign="bottom" height="20" style="font-size: smaller;">
									buttons?
								</div></td>
							</tr></table>
							<hr width="100%" size="1" class="hrcolor" />
							<div class="post" id="comment_', $comment['id'], '"', '>
								', $comment['body'], '
							</div>
						</td>
					</tr>
					<tr>
						<td valign="bottom" class="smalltext" width="85%">
							<table width="100%" border="0" style="table-layout: fixed;"><tr>
								<td colspan="2" class="smalltext" width="100%">attachements</td>
							</tr><tr><td valign="bottom" class="smalltext">';

		if ($settings['show_modify'] && !empty($comment['modified']['name']))
			echo '
								&#171; <i>', $txt['last_edit'], ': ', $comment['modified']['time'], ' ', $txt['by'], ' ', $comment['modified']['name'], '</i> &#187;';

		echo '
							</td><td align="', !$context['right_to_left'] ? 'right' : 'left', '" valign="bottom" class="smalltext">
								ip heer';

		echo '
							</td></tr>
						</table>';

		if (!empty($comment['member']['signature']) && empty($options['show_no_signatures']) && $context['signature_enabled'])
			echo '
							<hr width="100%" size="1" class="hrcolor" />
							<div class="signature">', $comment['member']['signature'], '</div>';

		echo '
						</td>
					</tr>
				</table>
			</td></tr>
		</table>
	</td></tr>';
	}

	echo '
	<tr><td style="padding: 0 0 1px 0;"></td></tr>
</table>
<a name="isssueEnd"></a>';*/

}
?>