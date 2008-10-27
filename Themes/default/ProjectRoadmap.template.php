<?php
// Version: 0.1 Alpha; ProjectRoadmap

function template_project_roadmap()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

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
			<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;status=open;version=', $version['id'], '">', sprintf($txt['open_issues'], $version['issues']['open']), '</a> /
			<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;status=closed;version=', $version['id'], '">', sprintf($txt['closed_issues'], $version['issues']['closed']), '</a>
		</div>
		<p>

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
}

?>