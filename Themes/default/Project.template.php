<?php
// Version: 0.1 Alpha; Project

function template_project_above()
{
	global $scripturl, $txt, $context;
}

function template_project_list()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	echo '
	<div class="projectlistframe tborder">';

	if (!empty($context['projects']))
	{
		echo '
		<h3 class="catbg headerpadding">', $txt['project'], '</h3>
		<table cellspacing="1" class="bordercolor projectsframe">';

		foreach ($context['projects'] as $i => $project)
		{
			echo '
			<tr>
				<td class="windowbg icon">
					<a href="', $project['href'], '">';

				if ($project['new'])
					echo '
						<img src="', $settings['images_url'], '/on.gif" alt="', $txt['new_posts'], '" title="', $txt['new_issues'], '" />';
				else
					echo '
						<img src="', $settings['images_url'], '/off.gif" alt="', $txt['old_posts'], '" title="', $txt['old_issues'], '" />';

				echo '
					</a>
				</td>
				<td class="windowbg2 info">
					<h4><a href="', $project['href'], '">', $project['name'], '</a></h4>
					<p class="smalltext">', $project['description'], '</p>
				</td>
				<td class="windowbg stats smalltext">';

			/*foreach ($project['issues'] as $type)
				echo '
				<div class="smalltext" title="', sprintf($txt['project_open_closed'], $type['open'], $type['closed']), '"><span><a href="', $type['link'], '" style="color: gray">', $type['info']['plural'], '</a></span> <span><a href="', $type['link'], '">', $type['total'], '</a></span></div>';*/

			foreach ($project['issues'] as $type)
				echo '
					', $type['open'], ' / ', $type['total'], ' ', $type['info']['plural'], '<br />';

			echo '
				</td>
				<td class="windowbg lastissue">
				</td>
			</tr>';
		}

		echo '
		</table>';
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

	echo '
	</div><br />';

	// Statistics etc
	echo '
	<div class="tborder">
		<h3 class="catbg headerpadding">', $txt['project_timeline'], '</h3>
		<div class="projectframe_section">
			<div class="windowbg">
				<p class="section"></p>
				<div class="windowbg2 timeline middletext">';

	$first = true;

	foreach ($context['events'] as $date)
	{
		echo '
					<h5 class="windowbg', $first ? ' first' : '' ,'">', $date['date'], '</h5>
					<ul>';

		foreach ($date['events'] as $event)
			echo '
						<li>
							', $event['time'], ' - ', $event['link'], '<br />
							<span class="smalltext">', sprintf($txt['evt_' . $event['event']], $event['member_link']), '</span>
						</li>';

		echo '
					</ul>';

		$first = false;
	}

	echo '
				</div>
			</div>
		</div>
	</div>';
}

function template_project_below()
{
	global $scripturl, $txt, $context, $project_version;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project ', $project_version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2008
	</div>';
}


?>