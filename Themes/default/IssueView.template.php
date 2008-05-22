<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	if ($context['show_update'])
	{
		echo '
		<script language="JavaScript" type="text/javascript">
			$j(document).bind("ready", function()
			{
				$j("#issueinfo td.infocolumn").bind("dblclick", function ()
				{
					$j(this).children("div.edit").show();
					$j(this).children("div.display").hide();
				});
			});
		</script>';
	}

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');
	$modify_button = create_button('modify.gif', 'issue_edit', 'issue_edit');

	$reporter = &$context['current_issue']['reporter'];

	echo '
	<div id="issueinfo" class="tborder">
		<h3 class="catbg3">
			<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
			<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
			<div class="floatright">
				', $txt['project'], ': ', $context['current_issue']['project']['link'], '
			</div>
		</h3>';

	echo '
		<table cellspacing="1" class="bordercolor issueinfoframe">
			<tr class="windowbg smalltext">
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_category'], '</span>
						<span class="value">', !empty($context['current_issue']['category']) ? $context['current_issue']['category'] : $txt['issue_none'], '</span>
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
							<a class="cancel" href="#">x</a>
						</div>
					</div>
				</td>
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_type'], '</span>
						<span class="value">', $context['current_issue']['type']['name'], '</span>
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
							<a class="cancel" href="#">x</a>
						</div>
					</div>
				</td>
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_priority'], '</span>
						', $txt[$context['current_issue']['priority']], '
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
							<a class="cancel" href="#">x</a>
						</div>
					</div>
				</td>
			</tr>
			<tr class="windowbg2 smalltext">
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_status'], '</span>
						', $context['current_issue']['status']['text'], '
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
						</div>
					</div>
				</td>
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_version'], '</span>
						', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
						</div>
					</div>
				</td>
				<td class="infocolumn">
					<div class="display">
						<span class="dark">', $txt['issue_version_fixed'], '</span>
						', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
						</div>
					</div>
				</td>
			</tr>
			<tr class="windowbg smalltext">
				<td class="infocolumn" colspan="3" width="100%">
					<div class="display">
						<span class="dark">', $txt['issue_assigned_to'], '</span>
						', !empty($context['current_issue']['assignee']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
					</div>
					<div class="edit">
						<div class="floatleft">
						</div>
						<div class="floatright">
							<a href="#">', $modify_button, '</a>
						</div>
					</div>
				</td>
			</tr>
		</table>
	</div>

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
						<div id="infotable" class="floatright infotable tborder">
							<div  itrow">

							</div>
	';

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