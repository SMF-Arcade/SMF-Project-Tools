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
		<div class="modbuttons clearfix margintop">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#bot"><b>' . $txt['go_down'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'bottom'), '
		</div>
		<div class="issuelistframe tborder columnmargin">
			<table cellspacing="1" class="bordercolor issuetable">
				<tr>';

		if (!empty($context['issues']))
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
						<h4><a href="', $issue['link'], '">', $issue['name'], '</a></h4>
						<p class="smalltext">', $issue['reporter_link'], '</p>
					</td>
					<td class="windowbg stats smalltext">
					</td>
					<td class="windowbg lastissue">
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