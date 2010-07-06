<?php
/**
 * Template for ProjecModule-Roadmap.php
 *
 * @package roadmap
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 * @see ProjectModule-Roadmap.php
 */

function template_project_roadmap()
{
	global $context, $settings, $options, $txt, $modSettings;

	foreach ($context['roadmap'] as $version)
	{
		echo '
	<h3 class="catbg"><span class="left"></span><span class="right"></span>
		<a href="', $version['href'], '">', $version['name'], '</a>
	</h3>
	<div class="windowbg">
		<span class="topslice"><span></span></span>
		<div style="padding: 0.5em">
			<div class="smalltext">', $version['release_date'], '</div>
			<div class="headerpadding">
				<div class="progressbar">
					<div style="width: ', $version['progress'], '%"></div>
				</div>
				<div class="smalltext">
					<a href="', project_get_url(array('project' => $context['project']['id'], 'area' => 'issues', 'status' => 'open', 'version' => $version['id'])), '">', sprintf($txt['open_issues'], $version['issues']['open']), '</a> /
					<a href="', project_get_url(array('project' => $context['project']['id'], 'area' => 'issues', 'status' => 'closed', 'version' => $version['id'])), '">', sprintf($txt['closed_issues'], $version['issues']['closed']), '</a>
				</div>
				<p>
					', $version['description'], '
				</p>
			</div>
		</div>
		<span class="botslice"><span></span></span>
	</div>
	<br />';
	}
}

function template_project_roadmap_version()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<h3 class="catbg"><span class="left"></span><span class="right"></span>
		', $context['version']['name'], '
	</h3>
	<div class="windowbg">
		<span class="topslice"><span></span></span>
		<div style="padding: 0.5em">
			<div class="smalltext">', $context['version']['release_date'], '</div>
			<p>
				', $context['version']['description'], '
			</p>
			<div class="progressbar">
				<div style="width: ', $context['version']['progress'], '%"></div>
			</div>
		</div>
		<span class="botslice"><span></span></span>
	</div>
	<br />';

	echo '
	<div class="issuelistframe tborder">
		<h3 class="titlebg"><span class="left"></span><span class="right"></span>
			', $txt['recent_issues'], '
		</h3>
		<table cellspacing="0" class="table_grid">
			<thead>
				<tr>';
	
		if (!empty($data))
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

		if (!empty($context['issues']))
		{
			foreach ($context['issues'] as $issue)
			{
				echo '
				<tr>
					<td class="windowbg icon">
						<a href="', project_get_url(array('project' => $context['project']['id'], 'area' => 'issues', 'tracker' => $issue['tracker']['short'])), '">
							<img src="', $settings['default_images_url'], '/', $issue['tracker']['image'], '" alt="', $issue['tracker']['name'], '" />
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
					<td align="right" class="smalltext" colspan="4">
						<a href="', $context['issues_href'], '">', $txt['issues_view_all'], '</a>
					</td>
				</tr>
			</tbody>';
	}
	
	echo '
		</table>
	</div>';
}

?>