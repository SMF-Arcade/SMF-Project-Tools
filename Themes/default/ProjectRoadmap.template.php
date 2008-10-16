<?php
// Version: 0.1 Alpha; ProjectRoadmap

function template_project_roadmap()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	foreach ($context['roadmap'] as $version)
	{
		echo '
	<h3 class="headerpadding catbg">', $version['name'], '</h3>
	<div class="progressbar">
		<div style="width: ', $version['progress'], '%"></div>
	</div>
	<div class="smalltext">
		<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;status=open;version=', $version['id'], '">', sprintf($txt['open_issues'], $version['issues']['open']), '</a> /
		<a href="', $scripturl, '?project=', $context['project']['id'], ';sa=issues;status=closed;version=', $version['id'], '">', sprintf($txt['closed_issues'], $version['issues']['closed']), '</a>
	</div>
	<p>
		', $version['description'], '
	</p>';
	}
}

?>