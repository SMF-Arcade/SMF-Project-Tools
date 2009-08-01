<?php
// Version: 0.4; IssueList

function template_issue_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	echo '
	<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
		var issueSearch = new smfToggle("issue_search", ', empty($options['issue_search_collapse']) ? 'false' : 'true', ');
		issueSearch.setOptions("issue_search_collapse", "', $context['session_id'], '");
		issueSearch.addToggleImage("search_toggle", "/upshrink.gif", "/upshrink2.gif");
		issueSearch.addTogglePanel("search_panel");
	// ]]></script>
	<div class="tborder">
		<div class="titlebg headerpadding clearfix">
			<span class="floatleft">', $txt['issue_search'], '</span>
			<div class="floatright">
				<a href="#" onclick="issueSearch.toggle(); return false;"><img id="search_toggle" src="', $settings['images_url'], '/', empty($options['issue_search_collapse']) ? 'upshrink.gif' : 'upshrink2.gif', '" alt="*" align="bottom" style="margin: 0 1ex;" /></a>
			</div>
		</div>
		<div id="search_panel" class="bordercolor"', empty($options['issue_search_collapse']) ? '' : ' style="display: none;"', '>
			<div class="windowbg2" style="padding: 0.5em 0.7em">
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
					<input type="submit" name="search" value="', $txt['issue_search_button'], '" tabindex="', $context['tabindex']++, '" />
				</form>
			</div>
		</div>
	</div>';

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
		<div class="issuelistframe tborder">
			<table cellspacing="1" class="bordercolor issuetable">
				<tr>';

		if (!empty($context['issues']))
			echo '
					<th class="catbg3 headerpadding"></th>
					<th class="catbg3 headerpadding">', $txt['issue_title'], '</th>
					<th class="catbg3 headerpadding">', $txt['issue_replies'], '</th>
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
			</table>
		</div>
		<div class="pagesection">
			<div class="align_left">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'right'), '
		</div>';
}

?>