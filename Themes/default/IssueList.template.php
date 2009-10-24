<?php
// Version: 0.4; IssueList

function template_issue_list()
{
	global $context, $settings, $options, $txt, $modSettings;
	
	echo '
	<h3 class="catbg">
		<span class="left"></span>
		<span class="right"></span>
		<span class="floatleft">', $txt['issue_search'], '</span>
		<img id="search_toggle" class="floatright" src="', $settings['images_url'], '/collapse.gif', '" alt="*" title="', $txt['upshrink_description'], '" align="bottom" style="margin: 0 1ex; display: none;" />
	</h3>
	<div id="search_panel" class="windowbg2"', empty($options['issue_search_collapse']) ? '' : ' style="display: none;"', '>
		<span class="topslice"><span></span></span>
		<div style="padding: 0.5em 0.7em">
			<form action="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues')), '" method="post">
				', $txt['issue_title'], ':
				<input type="text" name="title" value="', $context['issue_search']['title'], '" tabindex="', $context['tabindex']++, '" />
				<select name="status">
					<option value="all"', $context['issue_search']['status'] == 'all' ? ' selected="selected"' : '', '>', $txt['issue_search_all_issues'], '</option>
					<option value="open"', $context['issue_search']['status'] == 'open' ? ' selected="selected"' : '', '>', $txt['issue_search_open_issues'], '</option>
					<option value="closed"', $context['issue_search']['status'] == 'closed' ? ' selected="selected"' : '', '>', $txt['issue_search_closed_issues'], '</option>
					<option value="" disabled="disabled">--------</option>';

	foreach ($context['issue_status'] as $status)
		echo '
					<option value="', $status['id'], '"', $context['issue_search']['status'] == $status['id'] ? ' selected="selected"' : '', '>', $status['text'], '</option>';

	echo '
				</select>
				<select name="type">
					<option value="0"', empty($context['issue_search']['tracker']) ? ' selected="selected"' : '', '>', $txt['issue_search_all_types'], '</option>';

	foreach ($context['project']['trackers'] as $tracker)
		echo '
					<option value="', $tracker['tracker']['short'], '"', $context['issue_search']['tracker'] == $tracker['tracker']['short'] ? ' selected="selected"' : '', '>', $tracker['tracker']['name'], '</option>';

	echo '
				</select>
				<input class="button_submit" type="submit" name="search" value="', $txt['issue_search_button'], '" tabindex="', $context['tabindex']++, '" />
			</form>
		</div>
		<span class="botslice"><span></span></span>
	</div>
	<script type="text/javascript"><!-- // --><![CDATA[
		var oPSearchToggle = new smc_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', empty($options['issue_search_collapse']) ? 'false' : 'true', ',
			aSwappableContainers: [
				\'search_panel\'
			],
			aSwapImages: [
				{
					sId: \'search_toggle\',
					srcExpanded: smf_images_url + \'/collapse.gif\',
					altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ',
					srcCollapsed: smf_images_url + \'/expand.gif\',
					altCollapsed: ', JavaScriptEscape($txt['upshrink_description']), '
				}
			],
			oThemeOptions: {
				bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ',
				sOptionName: \'issue_search_collapse\',
				sSessionVar: ', JavaScriptEscape($context['session_var']), ',
				sSessionId: ', JavaScriptEscape($context['session_id']), '
			},
			oCookieOptions: {
				bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ',
				sCookieName: \'ptsearchtoggle\'
			}
		});
	// ]]></script>';
	
	$buttons = array(
		'reportIssue' => array(
			'text' => 'new_issue',
			'image' => 'new_issue.gif',
			'url' => project_get_url(array('project' => $context['project']['id'], 'sa' => 'reportIssue')),
			'lang' => true,
			'test' => 'can_report_issues',
		),
	);

	echo '
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'right'), '
		</div>
		<div class="issue_table">
			<table cellspacing="0" class="table_grid">
				<thead>
					<tr>';

		if (!empty($context['issues']))
			echo '
						<th scope="col" class="smalltext first_th"></th>
						<th scope="col" class="smalltext">', $txt['issue_title'], '</th>
						<th scope="col" class="smalltext">', $txt['issue_replies'], '</th>
						<th scope="col" class="smalltext">', $txt['issue_status'], '</th>
						<th scope="col" class="smalltext">', $txt['issue_version'], '</th>
						<th scope="col" class="smalltext">', $txt['issue_version_fixed'], '</th>
						<th scope="col" class="smalltext last_th">', $txt['issue_last_update'], '</th>';
		else
			echo '
						<th scope="col" class="smalltext first_th" width="8%">&nbsp;</th>
						<th class="smalltext" colspan="5"><strong>', $txt['issue_no_issues'], '</strong></th>
						<th scope="col" class="smalltext last_th" width="8%">&nbsp;</th>';

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
					<td class="windowbg icon">
						<a href="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'tracker' => $issue['tracker']['short'])), '">
							<img src="', $settings['images_url'], '/', $issue['tracker']['image'], '" alt="', $issue['tracker']['name'], '" />
						</a>
					</td>
					<td class="windowbg2 info">
						<h4>
							', !empty($issue['category']['link']) ? '[' . $issue['category']['link'] . '] ' : '', $issue['link'], ' ';
						// Is this topic new? (assuming they are logged in!)
			if ($issue['new'] && $context['user']['is_logged'])
					echo '
							<a href="', $issue['new_href'], '"><img src="', $settings['lang_images_url'], '/new.gif" alt="', $txt['new'], '" /></a>';

			echo '		</h4>
						<p class="floatright smalltext">', implode(' &nbsp;', $issue['tags']), '</p>
						<p class="smalltext">', $issue['reporter']['link'], '</p>
					</td>
					<td class="windowbg replies smalltext">
						', $issue['replies'], '
					</td>
					<td class="windowbg status smalltext center issue_', $issue['status']['name'], '">
						', $issue['status']['text'], $issue['is_assigned'] ? ' (' . $issue['assigned']['link'] . ')' : '', '
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
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'right'), '
		</div>';
}

?>