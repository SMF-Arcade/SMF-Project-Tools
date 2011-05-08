<?php
/**
 * Template for ProjectList.php
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.5
 * @see Project.php
 */

function template_project_list()
{
	global $context, $settings, $options, $txt, $modSettings;

	if (!empty($context['projects']))
	{
		echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['project'], '
		</h3>
	</div>
	<div id="projects_table">
		<table class="table_list">
			<thead>
				<tr><th colspan="4"></th></tr>
			</thead>
			<tfoot>
				<tr><td colspan="4"></td></tr>
			</tfoot>
			<tbody class="content">';

		foreach ($context['projects'] as $i => $project)
		{
			echo '
				<tr>
					<td class="windowbg icon">
						<a href="', $project['href'], '">';

				if ($project['new'])
					echo '
							<img src="', $settings['images_url'], '/on.png" alt="', $txt['new_issues'], '" title="', $txt['new_issues'], '" />';
				else
					echo '
							<img src="', $settings['images_url'], '/off.png" alt="', $txt['old_issues'], '" title="', $txt['old_issues'], '" />';

				echo '
						</a>
					</td>
					<td class="windowbg2 info">
						<h4><a href="', $project['href'], '">', $project['name'], '</a></h4>
						<p>', $project['description'], '</p>';

				if (!empty($project['developers']))
					echo '
						<p class="developers"><span class="smalltext">', count($project['developers']) == 1 ? $txt['developer'] : $txt['developers'], ': ', implode(', ', $project['developers']), '</span></p>';

				echo '
					</td>
					<td class="windowbg stats smalltext">';

			foreach ($project['trackers'] as $tracker)
				echo '
						', $tracker['open'], ' / ', $tracker['total'], ' ', $tracker['tracker']['plural'], '<br />';
					
			echo '
					</td>
					<td class="windowbg2 lastissue">
					</td>
				</tr>';
		}

		echo '
			</tbody>
		</table>
	</div>';
	}
	else
	{
		echo '
		<table cellspacing="1" class="bordercolor projectsframe">
			<tr>
				<td class="catbg3 headerpadding"><b>', $txt['no_projects'], '</b></td>
			</tr>
		</table>';
	}

	// Statistics etc
	echo '
	<br /><br />
	<span class="upperframe"><span></span></span>
	<div class="roundframe"><div class="innerframe">
		<div class="cat_bar">
			<h3 class="catbg">
				', $txt['project_timeline'], '
			</h3>
		</div>
		<div id="upshrinkHeaderProjectTL">';

	$first = true;

	foreach ($context['events'] as $date)
	{
		echo '
					<div class="title_bar">
						<h4 class="titlebg">', $date['date'], '</h4>
					</div>
					<ul class="reset">';

		foreach ($date['events'] as $event)
			echo '
						<li>
							', $event['time'], ' - ', $event['link'], '<br />
							<span class="smalltext">', $event['project_link'], ' - ', sprintf($txt['evt_' . (!empty($event['extra']) ? 'extra_' : '') . $event['event']], $event['member_link'], $event['extra']), '</span>
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

?>