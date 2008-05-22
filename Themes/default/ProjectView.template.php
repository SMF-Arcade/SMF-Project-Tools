<?php
// Version: 0.1 Alpha; ProjectView

function template_project_view_above()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<div style="padding: 3px;">', theme_linktree(), '</div>';

	if (!empty($context['project_tabs']))
	{
		echo '
	<table class="tborder" width="100%" align="center" border="0" cellpadding="4" cellspacing="0">
		<tr class="titlebg">
			<td colspan="3">
				', $context['project_tabs']['title'], '
			</td>
		</tr>
		<tr class="windowbg2">
			<td colspan="3">', $context['project_tabs']['text'], '</td>
		</tr>
	</table>

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
	<div class="projectframe tborder">
		<h3 class="catbg headerpadding">', $context['project']['name'], '</h3>
		<div class="projectframe_section">
			<div class="windowbg2 middletext">
				<p class="section_full">
					', $context['project']['long_description'], '
				</p>
			</div>
		</div>
	</div><br />';

	// List of latest updated issues
	foreach ($context['issue_list'] as $issueList)
	{
		echo '
	<div class="issuecolumn">
		<div class="issuelistframe tborder columnmargin">
			<h3 class="catbg headerpadding">', $issueList['title'], '</h3>
			<table cellspacing="1" class="bordercolor issuetable">
				<tr>';

		if (!empty($context['recent_issues']))
			echo '
					<th class="catbg3 headerpadding"></th>
					<th class="catbg3 headerpadding">', $txt['issue_title'], '</th>
					<th class="catbg3 headerpadding"></th>
					<th class="catbg3 headerpadding"></th>';
		else
			echo '
					<th class="windowbg2" colspan="4"><strong>', $txt['issue_no_issues'], '</strong></th>';

		echo '
				</tr>';

		foreach ($issueList['issues'] as $issue)
		{
			echo '
				<tr>
					<td class="windowbg icon">
						<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;type=', $issue['type'], '">
							<img src="', $settings['images_url'], '/', $issue['type'], '.png" alt="" />
						</a>
					</td>
					<td class="windowbg2 info">
						<h4><a href="', $issue['link'], '">', $issue['name'], '</a></h4>
						<p class="smalltext">', $issue['reporter_link'], '</p>
					</td>
					<td class="windowbg stats smalltext">
					</td>
					<td class="windowbg lastissue">
					</td>
				</tr>';
		}

		echo '
			</table>
		</div>
	</div>';
	}

	// Statistics etc
	echo '
	<div class="projectframe tborder">
		<h3 class="catbg headerpadding">', $context['project']['name'], '</h3>
		<div class="projectframe_section">
			<div class="windowbg">
				<h4 class="headerpadding titlebg">Issue Trackers</h4>
				<p class="section"></p>
				<div class="windowbg2 sectionbody middletext">';

	foreach ($context['project']['issues'] as $type)
		echo '
					<h3><a href="', $type['link'], '" style="color: gray">', $type['info']['plural'], '</a></h3>
					<div class="smalltext" title="', sprintf($txt['project_open_closed'], $type['open'], $type['closed']), '"><span> <span>', $type['total'], '</span></div>';

	echo '
				</div>
			</div>
		</div>
		<div class="projectframe_section">
			<div class="windowbg">
				<h4 class="headerpadding titlebg">', $txt['project_timeline'], '</h4>
				<p class="section"></p>
				<div class="windowbg2 sectionbody middletext">';

	foreach ($context['events'] as $event)
	{
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