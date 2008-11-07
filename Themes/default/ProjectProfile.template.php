<?php
// Version: 0.2; ProjectProfile

function template_project_profile_main()
{
	global $context, $settings, $options, $scripturl, $txt;

	echo '
	<div class="tborder clearfix projectstats">
		<h3 class="titlebg headerpadding">', $txt['project_stats'], '</h3>
		<div class="projectstats_section">
			<div class="windowbg">
				<p class="section"><img src="', $settings['images_url'], '/stats_info.gif" width="20" height="20" alt="" /></p>
				<div class="windowbg2 sectionbody middletext">
					<table border="0" cellpadding="1" cellspacing="0" width="100%">
						<tr>
							<td nowrap="nowrap">', $txt['profile_reported_issues'], ':</td>
							<td align="right"><a href="">', $context['statistics']['reported_issues'], '</a></td>
						</tr><tr>
							<td nowrap="nowrap">', $txt['profile_assigned_issues'], ':</td>
							<td align="right"><a href="">', $context['statistics']['assigned_issues'], '</a></td>
						</tr>
					</table>
				</div>
			</div>
		</div>';

	echo '
	</div>';
}

?>