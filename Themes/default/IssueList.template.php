<?php
// Version: 0.1 Alpha; IssueList

function template_issue_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

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
				<form action="', $scripturl, '?project=', $context['project']['id'], ';sa=issues" method="post">
					', $txt['issue_title'], ':
					<input type="text" name="title" value="', $context['issue_search']['title'], '" />
					<select name="status">
						<option value="all"', $context['issue_search']['status'] == 'all' ? ' selected="selected"' : '', '>', $txt['issue_search_all_issues'], '</option>
						<option value="open"', $context['issue_search']['status'] == 'open' ? ' selected="selected"' : '', '>', $txt['issue_search_open_issues'], '</option>
						<option value="closed"', $context['issue_search']['status'] == 'closed' ? ' selected="selected"' : '', '>', $txt['issue_search_closed_issues'], '</option>
						<option value="" disabled="disabled">--------</option>';

	foreach ($context['issue']['status'] as $status)
		echo '
						<option value="', $status['id'], '"', $context['issue_search']['status'] == $status['id'] ? ' selected="selected"' : '', '>', $status['text'], '</option>';

	echo '
					</select>
					<select name="type">
						<option value="0"', empty($context['issue_search']['type']) ? ' selected="selected"' : '', '>', $txt['issue_search_all_types'], '</option>';

	foreach ($context['possible_types'] as $type)
		echo '
						<option value="', $type['id'], '"', $context['issue_search']['type'] == $type['id'] ? ' selected="selected"' : '', '>', $type['name'], '</option>';

	echo '
					</select>
					<input type="submit" name="search" value="', $txt['issue_search_button'], '" />
				</form>
			</div>
		</div>
	</div>';

	$buttons = array(
		'post_issue' => array(
			'text' => 'new_issue',
			'image' => 'new_issue.gif',
			'url' => $scripturl . '?project=' . $context['project']['id'] . ';sa=reportIssue',
			'lang' => true,
			'test' => 'can_report_issues',
		),
	);

	echo '
		<div class="modbuttons clearfix margintop">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'bottom'), '
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
						<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;type=', $issue['type'], '">
							<img src="', $settings['images_url'], '/', $issue['type'], '.png" alt="" />
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
						', $issue['status']['text'], '<br />
					</td>
					<td class="windowbg version smalltext">
						', $issue['version']['link'], '
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
			', template_button_strip($buttons, 'top'), '
		</div>';
}

?>