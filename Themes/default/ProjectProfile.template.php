<?php
// Version: 0.4; ProjectProfile

function template_project_profile_main()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
	<span class="clear upperframe"><span><!-- // --></span></span>
	<div class="roundframe"><div class="innerframe">
	<h3 class="titlebg"><span class="left"><span></span></span>
		<img class="icon" src="', $settings['images_url'], '/stats_info.gif" width="20" height="20" alt="" /> ', $txt['project_stats'], '
	</h3>
	<div class="windowbg2">
		<table border="0" cellpadding="1" cellspacing="0" width="100%">
			<tr>
				<td nowrap="nowrap">', $txt['profile_reported_issues'], ':</td>
				<td align="right"><a href="', $scripturl, '?action=profile;u=', $context['member']['id'], ';area=project;sa=reported">', $context['statistics']['reported_issues'], '</a></td>
			</tr><tr>
				<td nowrap="nowrap">', $txt['profile_assigned_issues'], ':</td>
				<td align="right"><a href="', $scripturl, '?action=profile;u=', $context['member']['id'], ';area=project;sa=assigned">', $context['statistics']['assigned_issues'], '</a></td>
			</tr>
		</table>
	</div>
	</div></div>
	<span class="lowerframe"><span><!-- // --></span></span>';
}

function template_issue_list_profile()
{
	global $context, $settings, $options, $txt, $modSettings;

	echo '
		<div class="middletext pagelinks">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
		<span class="clear upperframe"><span><!-- // --></span></span>
		<div class="roundframe"><div class="innerframe">
		<div class="tborder topic_table">
			<table class="table_grid" cellspacing="0">
				<thead>
				<tr class="catbg">';

		if (!empty($context['issues']))
			echo '
					<th scope="col" class="smalltext headerpadding"></th>
					<th scope="col" class="smalltext headerpadding">', $txt['issue_title'], '</th>
					<th scope="col" class="smalltext headerpadding">', $txt['issue_status'], '</th>
					<th scope="col" class="smalltext headerpadding">', $txt['issue_version'], '</th>
					<th scope="col" class="smalltext headerpadding">', $txt['issue_version_fixed'], '</th>
					<th scope="col" class="smalltext headerpadding">', $txt['issue_last_update'], '</th>';
		else
			echo '
					<th scope="col" class="smalltext headerpadding" colspan="5"><strong>', $txt['issue_no_issues'], '</strong></th>';

		echo '
				</tr>
				</thead>
				<tbody>';

	if (!empty($context['issues']))
	{
		foreach ($context['issues'] as $issue)
		{
			echo '
					<tr>
						<td class="windowbg">
							<img class="icon" src="', $settings['images_url'], '/', $issue['tracker']['image'], '" alt="', $issue['name'], '" />
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
					<td class="windowbg version smalltext">';

			if (empty($issue['versions']))
				echo $txt['issue_none'];
			else
			{
				$first = true;
				
				foreach ($issue['versions'] as $version)
				{
					if ($first)
						$first = false;
					else
						echo ', ';
						
					echo $version['name'];
				}
			}

			echo '
					</td>
					<td class="windowbg version smalltext">';

			if (empty($issue['versions_fixed']))
				echo $txt['issue_none'];
			else
			{
				$first = true;
				
				foreach ($issue['versions_fixed'] as $version)
				{
					if ($first)
						$first = false;
					else
						echo ', ';
						
					echo $version['name'];
				}
			}
			
			echo '
					</td>
					<td class="windowbg2 lastissue smalltext">
						', $issue['updater']['link'], '<br />
						', $issue['updated'], '
					</td>
				</tr>';
		}
	}

	echo '
			</tbody>
		</table>
	</div>
	</div></div>
	<span class="lowerframe"><span><!-- // --></span></span>
	<div class="middletext pagelinks">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
	<br class="clear" />';
}

?>