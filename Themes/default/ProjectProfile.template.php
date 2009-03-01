<?php
// Version: 0.3; ProjectProfile

function template_project_profile_main()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
	<div class="tborder clearfix projectstats">
		<h3 class="titlebg headerpadding">', $txt['project_stats'], '</h3>
		<div class="projectstats_section">
			<div class="windowbg">
				<p class="section"><img src="', $settings['images_url'], '/stats_info.gif" width="20" height="20" alt="" /></p>
				<div class="windowbg2 sectionbody middletext">
					<table border="0" cellpadding="1" cellspacing="0" width="100%">
						<tr>
							<td nowrap="nowrap">', $txt['profile_reported_issues'], ':</td>
							<td align="right"><a href="', $scripturl, '?action=profile;u=', $context['member'], ';area=project;sa=reported">', $context['statistics']['reported_issues'], '</a></td>
						</tr><tr>
							<td nowrap="nowrap">', $txt['profile_assigned_issues'], ':</td>
							<td align="right"><a href="', $scripturl, '?action=profile;u=', $context['member'], ';area=project;sa=assigned">', $context['statistics']['assigned_issues'], '</a></td>
						</tr>
					</table>
				</div>
			</div>
		</div>';

	echo '
	</div>';
}

function template_issue_list_profile()
{
	global $context, $settings, $options, $txt, $modSettings;

	echo '
		<div class="modbuttons clearfix margintop">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
		</div>
		<div class="issuelistframe tborder">
			<table cellspacing="1" class="bordercolor issuetable">
				<tr>';

		if (!empty($context['issues']))
			echo '
					<th class="catbg3 headerpadding"></th>
					<th class="catbg3 headerpadding">', $txt['issue_title'], '</th>
					<th class="catbg3 headerpadding">', $txt['issue_status'], '</th>
					<th class="catbg3 headerpadding">', $txt['issue_version'], '</th>
					<th class="catbg3 headerpadding">', $txt['issue_version_fixed'], '</th>
					<th class="catbg3 headerpadding">', $txt['issue_last_update'], '</th>';
		else
			echo '
					<th class="catbg3 headerpadding" colspan="4"><strong>', $txt['issue_no_issues'], '</strong></th>';

		echo '
				</tr>';

	if (!empty($context['issues']))
	{
		foreach ($context['issues'] as $issue)
		{
			echo '
				<tr>
					<td class="windowbg icon">
						<img src="', $settings['images_url'], '/', $issue['tracker']['image'], '" alt="" />
					</td>
					<td class="windowbg2 info">
						<h4>
							', $issue['link'], ' ';
						// Is this topic new? (assuming they are logged in!)
			if ($issue['new'] && $context['user']['is_logged'])
					echo '
							<a href="', $issue['new_href'], '"><img src="', $settings['lang_images_url'], '/new.gif" alt="', $txt['new'], '" /></a>';

			echo '		</h4>
						<p class="floatright smalltext">', implode(' &nbsp;', $issue['tags']), '</p>
						<p class="smalltext">[', $issue['project']['link'], '', !empty($issue['category']['link']) ? ' / ' . $issue['category']['link'] . '' : '', '] ', $issue['reporter']['link'], '</p>
					</td>
					<td class="windowbg status smalltext center issue_', $issue['status']['name'], '">
						', $issue['status']['text'], '<br />
					</td>
					<td class="windowbg version smalltext">
						', $issue['version']['link'], '
					</td>
					<td class="windowbg version smalltext">
						', $issue['version_fixed']['link'], '
					</td>
					<td class="windowbg2 lastissue smalltext">
						', $issue['updater']['link'], '<br />
						', $issue['updated'], '
					</td>
				</tr>';
		}
	}

	echo '
			</table>
		</div>
		<div class="modbuttons clearfix marginbottom">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
		</div>';
}

?>