<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	if ($context['show_update'])
	{
		echo '
		<script language="JavaScript" type="text/javascript">
			function startEdit(param)
			{
				$j("#issueupdate").show();
				$j("#issueoptions").hide();

				$j("#issueinfo td.infocolumn.canedit div.edit").show();
				$j("#issueinfo td.infocolumn.canedit div.display").hide();
			}
		</script>';
	}

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');
	$modify_button = create_button('modify.gif', 'issue_edit', 'issue_edit');

	$reporter = &$context['current_issue']['reporter'];

	echo '
	<form action="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=updateIssue" method="post">
		<input type="hidden" name="sc" value="', $context['session_id'], '" />

		<div id="issueinfo" class="tborder">
			<h3 class="catbg3 clearfix">
				<div class="floatleft" style="width: 50%">
					<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
					<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
				</div>
				<div class="floatright">
					', $txt['project'], ': ', $context['project']['link'], '
				</div>
			</h3>
			<div class="windowbg2">
				<div class="infocolumn', $context['can_edit'] ? ' canedit' : '', '">
					<div class="display">
						<span class="dark">', $txt['issue_category'], '</span>
						<span class="value">', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '</span>
					</div>
				</div>
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_type'], '</span>
						<span class="value">', $context['current_issue']['type']['name'], '</span>
					</div>
				</div>
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_priority'], '</span>
						', $txt[$context['current_issue']['priority']], '
					</div>
				</div>
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_status'], '</span>
						', $context['current_issue']['status']['text'], '
					</div>
				</div>
				<div>
					<div class="display">
							<span class="dark">', $txt['issue_version'], '</span>
							', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
					</div>
				</div>
				<div>
					<div class="display">
						<span class="dark">', $txt['issue_version_fixed'], '</span>
						', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
					</div>
				</div>
			</div>
			<div>
				<div class="display">
					<span class="dark">', $txt['issue_assigned_to'], '</span>
					', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
				</div>
			</div>
		</div>

		<div class="tborder">
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
						<hr width="100%" size="1" class="hrcolor" />
						<div class="post">
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
		</div>

	</form>';
}

?>