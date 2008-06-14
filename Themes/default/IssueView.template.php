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
			<h3 class="catbg3">
				<div class="floatleft" style="width: 50%">
					<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
					<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
				</div>
				<div class="floatright">
					', $txt['project'], ': ', $context['project']['link'], '
				</div>
				<div style="clear: both"></div>
			</h3>
			<table cellspacing="1" class="bordercolor issueinfoframe">
				<tr class="windowbg smalltext">
					<td class="infocolumn', $context['can_edit'] ? ' canedit' : '', '">
						<div class="display">
							<span class="dark">', $txt['issue_category'], '</span>
							<span class="value">', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '</span>
						</div>';

	if ($context['can_edit'])
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_category'], '</span>
							<span class="value">
								<select name="category">
									<option></option>';

		foreach ($context['project']['category'] as $c)
			echo '
									<option value="', $c['id'], '" ', $context['current_issue']['category']['id'] == $c['id'] ? ' selected="selected"' : '', '>', $c['name'], '</option>';
		echo '
								</select>
							</span>
						</div>';
	}

	echo '
					</td>
					<td class="infocolumn', $context['can_edit'] ? ' canedit' : '', '">
						<div class="display">
							<span class="dark">', $txt['issue_type'], '</span>
							<span class="value">', $context['current_issue']['type']['name'], '</span>
						</div>';

	if ($context['can_edit'])
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_type'], '</span>
							<span class="value">
								<select name="type">';

		foreach ($context['possible_types'] as $id => $type)
			echo '
									<option value="', $id, '" ', $type['selected'] ? ' selected="selected"' : '', '/>', $type['name'], '</option>';

		echo '
								</select>
							</span>
						</div>';
	}

	echo '
					</td>
					<td class="infocolumn">
						<div class="display">
							<span class="dark">', $txt['issue_priority'], '</span>
							', $txt[$context['current_issue']['priority']], '
						</div>
						<div class="edit">
						</div>
					</td>
				</tr>
				<tr class="windowbg2 smalltext">
					<td class="issue_',  $context['current_issue']['status']['name'], ' infocolumn', !empty($context['change_status']) ? ' canedit' : '', '">
						<div class="display">
							<span class="dark">', $txt['issue_status'], '</span>
							', $context['current_issue']['status']['text'], '
						</div>';

	if (!empty($context['change_status']))
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_status'], '</span>
							<span class="value">
								<select name="status">';


		foreach ($context['issue']['status'] as $status)

			echo '
									<option value="', $status['id'], '"', $context['current_issue']['status']['id'] == $status['id'] ? ' selected="selected"' : '', '>', $status['text'], '</option>';

		echo '
								</select>
							</span>
						</div>';
	}

	echo '
					</td>
					<td class="infocolumn', $context['can_edit'] ? ' canedit' : '', '">
						<div class="display">
							<span class="dark">', $txt['issue_version'], '</span>
							', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '
						</div>';

	if ($context['can_edit'])
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_version'], '</span>
							<span class="value">
								<select name="version">
									<option></option>';


		foreach ($context['versions'] as $v)
		{
			echo '
									<option value="', $v['id'], '" style="font-weight: bold"', $context['current_issue']['version']['id'] == $v['id'] ? ' selected="selected"' : '', '>', $v['name'], '</option>';

			foreach ($v['sub_versions'] as $subv)
				echo '
									<option value="', $subv['id'], '"', $context['current_issue']['version']['id'] == $subv['id'] ? ' selected="selected"' : '', '>', $subv['name'], '</option>';
		}

		echo '
								</select>
							</span>
						</div>';
	}

	echo '
					</td>
					<td class="infocolumn', !empty($context['set_target']) ? ' canedit' : '', '">
						<div class="display">
							<span class="dark">', $txt['issue_version_fixed'], '</span>
							', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '
						</div>';

	if (!empty($context['set_target']))
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_version_fixed'], '</span>
							<span class="value">
								<select name="version_fixed">
									<option></option>';


		foreach ($context['versions'] as $v)
		{
			echo '
									<option value="', $v['id'], '" style="font-weight: bold"', $context['current_issue']['version_fixed']['id'] == $v['id'] ? ' selected="selected"' : '', '>', $v['name'], '</option>';

			foreach ($v['sub_versions'] as $subv)
				echo '
									<option value="', $subv['id'], '"', $context['current_issue']['version_fixed']['id'] == $subv['id'] ? ' selected="selected"' : '', '>', $subv['name'], '</option>';
		}

		echo '
								</select>
							</span>
						</div>';
	}

	echo '
					</td>
				</tr>
				<tr class="windowbg smalltext">
					<td class="infocolumn', $context['can_assign'] ? ' canedit' : '', '" colspan="3" width="100%">
						<div class="display">
							<span class="dark">', $txt['issue_assigned_to'], '</span>
							', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '
						</div>';

	if ($context['can_assign'])
	{
		echo '
						<div class="edit">
							<span class="dark">', $txt['issue_assigned_to'], '</span>
							<select name="assign">
								<option></option>';

		foreach ($context['assign_members'] as $mem)
			echo '
								<option value="', $mem['id'], '"',$context['current_issue']['assignee']['id'] == $mem['id'] ? ' selected="selected"' : '', '>', $mem['name'], '</option>';

		echo '
							</select>
						</div>';
	}
	echo '
					</td>
				</tr>
				<tr id="issueupdate" class="catbg" style="display: none">
					<td align="right" colspan="3" width="100%">
						<input type="submit" />
					</td>
				</tr>
				<tr id="issueoptions" class="catbg">
					<td align="right" colspan="3" width="100%">
						<a href="#" onclick="startEdit(); return false;">', $txt['issue_edit'], '</a>
					</td>
				</tr>
			</table>
		</div>
	</form>

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
	</div>';
}

?>