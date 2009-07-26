<?php
// Version: 0.4; ProjectRoadmap

function template_project_roadmap()
{
	global $context, $settings, $options, $txt, $modSettings;

	foreach ($context['roadmap'] as $version)
	{
		echo '
	<h3><a href="', $version['href'], '">', $version['name'], '</a></h3>
	<div class="smalltext">', $version['release_date'], '</div>
	<div class="headerpadding">
		<div class="progressbar">
			<div style="width: ', $version['progress'], '%"></div>
		</div>
		<div class="smalltext">
			<a href="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'status' => 'open', 'version_fixed' => $version['id'])), '">', sprintf($txt['open_issues'], $version['issues']['open']), '</a> /
			<a href="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'status' => 'closed', 'version_fixed' => $version['id'])), '">', sprintf($txt['closed_issues'], $version['issues']['closed']), '</a>
		</div>
		<p>
			', $version['description'], '
		</p>
	</div>';
	}
}

function template_project_roadmap_version()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<h2>', $context['version']['name'], '</h2>
	<div class="smalltext">', $context['version']['release_date'], '</div>
	<p>
		', $context['version']['description'], '
	</p>
	<div class="progressbar">
		<div style="width: ', $context['version']['progress'], '%"></div>
	</div>';

	echo '
	<div class="issuelistframe tborder">
		<h3 class="catbg headerpadding">', $txt['recent_issues'], '</h3>
		<table cellspacing="1" class="bordercolor issuetable">
			<tr>';

	if (!empty($context['issues'] ))
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

	if (!empty($context['issues']))
	{
		foreach ($context['issues'] as $issue)
		{
			echo '
			<tr>
				<td class="windowbg icon">
					<a href="', project_get_url(array('project' => $context['project']['id'], 'sa' => 'issues', 'tracker' => $issue['tracker']['short'])), '">
						<img src="', $settings['images_url'], '/', $issue['tracker']['image'], '" alt="', $issue['name'], '" />
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
			<tr class="catbg">
				<td align="right" class="headerpadding smalltext" colspan="4">
					<a href="', $context['issues_href'], '">', $txt['issues_view_all'], '</a>
				</td>
			</tr>';
	}

	echo '
		</table>
	</div>';
}

?>