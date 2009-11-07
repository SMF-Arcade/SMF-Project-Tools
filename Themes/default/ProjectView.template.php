<?php
// Version: 0.4; ProjectView

function template_project_view_above()
{
	global $context, $settings, $options, $txt, $modSettings;

	if (!empty($context['project_tabs']))
	{
		echo '
	<div class="buttonlist align_left"><ul>';

		// Print out all the items in this tab.
		$i = 1;
		$num_tabs = count($context['project_tabs']['tabs']);
		foreach ($context['project_tabs']['tabs'] as $tab)
		{
			echo '
		<li>
			<a href="', $tab['href'], '" class="', !empty($tab['is_selected']) ? 'active ' : '', 'firstlevel">
				<span class="firstlevel', $i == $num_tabs ? ' last' : '', '">', $tab['title'], '</span>
			</a>
		</li>';
			
			$i++;
		}
		
		echo '
	</ul></div>
	<br class="clear" />';
	}
}

function template_project_view()
{
	global $context, $settings, $options, $txt, $modSettings;

	$project_buttons = array(
		'subscribe' => array('test' => 'can_subscribe', 'text' => empty($context['is_subscribed']) ? 'project_subscribe' : 'project_unsubscribe', 'image' => empty($context['is_subscribed']) ? 'subscribe.gif' : 'unsubscribe.gif', 'lang' => true, 'url' => project_get_url(array('project' => $context['project']['id'], 'sa' => 'subscribe', $context['session_var'] => $context['session_id']))),
		'reportIssue' => array('test' => 'can_report_issues', 'text' => 'new_issue', 'image' => 'new_issue.gif', 'lang' => true, 'url' => project_get_url(array('project' => $context['project']['id'], 'sa' => 'reportIssue')),),
	);

	echo '
	<div id="modbuttons_top" class="modbuttons clearfix margintop">
		', template_button_strip($project_buttons, 'right'), '
	</div>
	<h3 class="catbg"><span class="left"></span><span class="right"></span>
		', $context['project']['name'], '
	</h3>
	<p class="description">
		', $context['project']['long_description'], '
	</p>';

	$side = true;

	// List of latest updated issues
	foreach ($context['issue_list'] as $issueList)
	{
		if ($side)
			echo '
	<div class="clearfix">';

		echo '
		<div class="issuecolumn">
			<div class="issue_table columnmargin_', $side ? 'right' : 'left', '">
				<h3 class="catbg"><span class="left"></span>
					', $issueList['title'], '
				</h3>
				<table cellspacing="0" class="table_grid">
					<thead>
						<tr>';

		if (!empty($issueList['issues']))
			echo '
							<th scope="col" class="smalltext first_th"></th>
							<th scope="col" class="smalltext">', $txt['issue_title'], '</th>
							<th scope="col" class="smalltext">', $txt['issue_replies'], '</th>
							<th scope="col" class="smalltext last_th">', $txt['issue_last_update'], '</th>';
		else
			echo '
							<th scope="col" class="smalltext first_th" width="8%">&nbsp;</th>
							<th class="smalltext" colspan="2"><strong>', $txt['issue_no_issues'], '</strong></th>
							<th scope="col" class="smalltext last_th" width="8%">&nbsp;</th>';

		echo '
						</tr>
					</thead>
					<tbody>';

		if (!empty($issueList['issues']))
		{
			foreach ($issueList['issues'] as $issue)
			{
				echo '
						<tr>
							<td class="windowbg icon">
								<a href="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'tracker' => $issue['tracker']['short'])), '">
									<img src="', $settings['images_url'], '/', $issue['tracker']['image'], '" alt="', $issue['tracker']['name'], '" />
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

			echo '
					</tbody>
					<tr class="catbg">
						<td colspan="4" align="right" class="headerpadding smalltext">
							<a href="', $issueList['href'], '">', $txt['issues_view_all'], '</a>
						</td>
					</tr>';
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

	$width = floor(100 / count($context['issue_status']));
	$tWidth = 100 - ($width * count($context['issue_status'])) + $width;

	echo '
	<div class="tborder clearfix">';

	foreach ($context['issue_status'] as $status)
	{
		echo '
		<div class="floatleft expl issue_', $status['name'], '" style="width:', $tWidth, '%"><span>', $status['text'], '</span></div>';
		$tWidth = $width;
	}
	echo '
	</div>
	<br class="clear" />';

	// Statistics etc
	echo '
	<span class="clear upperframe"><span><!-- // --></span></span>
	<div class="roundframe"><div class="innerframe">
		<h3 class="catbg"><span class="left"></span>
			', $context['project']['name'], '
		</h3>
		<div id="upshrinkHeaderIC">
			<h4 class="titlebg"><span class="left"></span><span class="right"></span>
				', $txt['project_statistics'], '
			</h4>
			<table width="100%">';

	foreach ($context['project']['trackers'] as $type)
		echo '
				<tr>
					<td width="10%">
						<a href="', $type['link'], '" style="color: gray">', $type['tracker']['plural'], '</a><br />
					</td>
					<td>
						<div class="progressbar"><div class="done" style="width: ', $type['progress'], '%"></div></div>
					</td>
				</tr>
				<tr>
					<td class="smalltext" colspan="2"><span>', $txt['project_open_issues'], ' ', $type['open'], '</span> / <span>', $txt['project_closed_issues'], ' ', $type['closed'], '</span></td>
				</tr>';

	echo '
			</table>
			<h3 class="catbg"><span class="left"></span>
				', $txt['project_timeline'], '
			</h3>';

	$first = true;

	foreach ($context['events'] as $date)
	{
		echo '
			<h5 class="windowbg', $first ? ' first' : '' ,'"><span class="left"></span><span class="right"></span>
				', $date['date'], '
			</h5>
			<ul class="reset">';

		foreach ($date['events'] as $event)
			echo '
				<li>
					', $event['time'], ' - ', $event['link'], '<br />
					<span class="smalltext">', sprintf($txt['evt_' . (!empty($event['extra']) ? 'extra_' : '') . $event['event']], $event['member_link'], $event['extra']), '</span>
				</li>';

		echo '
			</ul>';

		$first = false;
	}

	echo '
		</div>
	</div></div>
	<span class="lowerframe"><span></span></span>';
}

function template_project_view_below()
{
	global $context, $settings, $options, $txt, $modSettings;

}

?>