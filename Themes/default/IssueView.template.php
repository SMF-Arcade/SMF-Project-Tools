<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');

	$reporter = &$context['current_issue']['reporter'];

	echo '
	<div id="issueinfo" class="tborder">
		<h3 class="catbg3">
			<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
		</h3>';


	echo '
		<div class="bordercolor">
			<div class="clearfix topborder windowbg largepadding">
				<div class="floatleft poster">
					<h4>', $reporter['link'], '</h4>
				</div>
				<div class="postarea">
					<div class="keyinfo">
						<div class="messageicon floatleft"><img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" /></div>
						<h5>', $context['current_issue']['name'], '</h5>
						<div class="smalltext">&#171; <strong>', $txt['reported_on'], ':</strong> ', $context['current_issue']['created'], ' &#187;</div>
					</div>
					<div class="post">';


	// Info table
	echo '
						<div class="floatright infotable tborder">
							<div class="windowbg">
								<span class="dark">', $txt['issue_category'], '</span>
								<span class="value">', !empty($context['current_issue']['category']) ? $context['current_issue']['category'] : $txt['issue_none'], '</span>
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_type'], '</span>
								<span class="value">', $context['current_issue']['type']['name'], '</span>
							</div>
							<div class="windowbg">
								<span class="dark">', $txt['issue_priority'], '</span>
								', $txt[$context['current_issue']['priority']], '
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_status'], '</span>
								', $context['current_issue']['status']['text'], '
							</div>
							<div class="windowbg">
								<span class="dark">', $txt['issue_version'], '</span>
								', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
							</div>
							<div class="windowbg2">
								<span class="dark">', $txt['issue_version_fixed'], '</span>
								', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
							</div>
							<div class="windowbg last">
								<span class="dark">', $txt['issue_assigned_to'], '</span>
								', !empty($context['current_issue']['assignee']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
							</div>
						</div>';

	echo '
						', $context['current_issue']['body'], '
					</div>
				</div>
				<div class="moderatorbar">
					<div class="smalltext floatleft">

					</div>
					<div class="smalltext floatright">

					</div>
				</div>
			</div>
		</div>
	</div>';
}

?>