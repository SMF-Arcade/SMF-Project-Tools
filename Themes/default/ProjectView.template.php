<?php
// Version: 0.1 Alpha; ProjectView

function template_project_view_above()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	if (!empty($context['project_tabs']))
	{
		echo '
	<div class="tborder">
		<div class="titlebg headerpadding clearfix">
			<span class="floatleft">', $context['project_tabs']['title'], '</span>
		</div>
	</div>
	<table cellpadding="0" cellspacing="0" border="0" style="margin-left: 10px;">
		<tr>
			<td class="maintab_first">&nbsp;</td>';

			// Print out all the items in this tab.
			foreach ($context['project_tabs']['tabs'] as $tab)
			{
				if (!empty($tab['is_selected']))
				{
					echo '
			<td class="maintab_active_first">&nbsp;</td>
			<td valign="top" class="maintab_active_back">
				<a href="', $tab['href'], '">', $tab['title'], '</a>
			</td>
			<td class="maintab_active_last">&nbsp;</td>';

					$selected_tab = $tab;
				}
				else
					echo '
			<td valign="top" class="maintab_back">
				<a href="', $tab['href'], '">', $tab['title'], '</a>
			</td>';
			}

			// the end of tabs
			echo '
			<td class="maintab_last">&nbsp;</td>
		</tr>
	</table><br />';
	}
}

function template_project_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<div class="tborder">
		<h3 class="catbg headerpadding">', $context['project']['name'], '</h3>
		<div class="projectframe_section">
			<div class="windowbg2 middletext">
				<p class="section_full">
					', $context['project']['long_description'], '
				</p>
			</div>
		</div>
	</div><br />';

	$side = true;

	// List of latest updated issues
	foreach ($context['issue_list'] as $issueList)
	{
		if ($side)
			echo '
	<div class="clearfix">';

		echo '
		<div class="issuecolumn">
			<div class="issuelistframe tborder columnmargin_', $side ? 'right' : 'left', '">
				<h3 class="catbg headerpadding">', $issueList['title'], '</h3>
				<table cellspacing="1" class="bordercolor issuetable">
					<tr>';

		if (!empty($issueList['issues']))
			echo '
						<th class="catbg3 headerpadding"></th>
						<th class="catbg3 headerpadding">', $txt['issue_title'], '</th>
						<th class="catbg3 headerpadding">', $txt['issue_replies'], '</th>
						<th class="catbg3 headerpadding">', $txt['issue_last_update'], '</th>';
		else
			echo '
						<th class="windowbg2 headerpadding" colspan="4"><strong>', $txt['issue_no_issues'], '</strong></th>';

		echo '
					</tr>';

		if (!empty($issueList['issues']))
		{
			foreach ($issueList['issues'] as $issue)
			{
				echo '
					<tr>
						<td class="windowbg icon">
							<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;type=', $issue['type'], '">
								<img src="', $settings['images_url'], '/', $issue['type'], '.png" alt="" />
							</a>
						</td>
						<td class="windowbg2 info issue_', $issue['status']['name'], '">
							<h4>
								', !empty($issue['category']['link']) ? '[' . $issue['category']['link'] . '] ' : '', $issue['link'], ' ';
						// Is this topic new? (assuming they are logged in!)
				if ($issue['new'] && $context['user']['is_logged'])
					echo '
								<a href="', $issue['new_href'], '"><img src="', $settings['lang_images_url'], '/new.gif" alt="', $txt['new'], '" /></a>';
				echo '
							</h4>
							<p class="smalltext">', !empty($issue['version']['link']) ? '[' . $issue['version']['link'] . '] ' : '', $issue['reporter']['link'], '</p>
						</td>
						<td class="windowbg replies smalltext">
							', $issue['replies'], '
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
		</div>';

		if (!$side)
			echo '
	</div>';

		$side = !$side;
	}

	if (!$side)
		echo '
	</div>';

	//
	$width = 100 / count($context['issue']['status']);

	echo '
	<div class="tborder clearfix">';

	foreach ($context['issue']['status'] as $status)
		echo '
		<div class="floatleft expl issue_', $status['name'], '" style="width:', $width, '%"><span>', $status['text'], '</span></div>';

	echo '
	</div><br />';

	// Statistics etc
	echo '
	<div class="tborder">
		<h3 class="catbg headerpadding">', $context['project']['name'], '</h3>
		<div class="projectframe_section">
			<div class="windowbg">
				<h4 class="headerpadding titlebg">', $txt['project_statistics'], '</h4>
				<p class="section"></p>
				<div class="windowbg2 sectionbody middletext">
					<table width="100%">';

	// TODO: Move calculations to ProjectView.php
	foreach ($context['project']['trackers'] as $type)
		echo '
					<tr>
						<td width="10%">
							<a href="', $type['link'], '" style="color: gray">', $type['info']['plural'], '</a><br />
						</td>
						<td>
							<div class="progressbar"><div class="done" style="width: ', round(($type['closed'] / max(1, $type['total'])) * 100, 2), '%"></div></div>
						</td>
					</tr>
					<tr>
						<td class="smalltext" colspan="2"><span>', $txt['project_open_issues'], ' ', $type['open'], '</span> / <span>', $txt['project_closed_issues'], ' ', $type['closed'], '</span></td>
					</tr>';

	echo '
					</table>
				</div>
			</div>
		</div>
		<div class="projectframe_section">
			<div class="windowbg">
				<h4 class="headerpadding titlebg">', $txt['project_timeline'], '</h4>
				<p class="section"></p>
				<div class="windowbg2 sectionbody middletext">';

	foreach ($context['events'] as $date)
	{
		echo '
				<div class="windowbg"><h5>', $date['date'], '</h5></div>';

		foreach ($date['events'] as $event)
			echo '
					<div>', $event['time'], ' - ', $event['link'], '<br /><span class="smalltext">', sprintf($txt['evt_' . $event['event']], $event['member_link']), '</span></div>';
	}

	echo '
				</div>
			</div>
		</div>
	</div>';
}

function template_project_view_below()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

}

?>