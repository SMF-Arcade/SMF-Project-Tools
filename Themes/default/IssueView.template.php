<?php
// Version: 0.1 Alpha; IssueView

function template_issue_view()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings, $settings;

	$delete_button = create_button('delete.gif', 'issue_delete', 'issue_delete');
	$modify_button = create_button('modify.gif', 'issue_edit', 'issue_edit');

	$reporter = &$context['current_issue']['reporter'];

	$buttons = array(
		'reply' => array(
			'text' => 'reply',
			'image' => 'reply_issue.gif',
			'url' => $scripturl . '?issue=' . $context['current_issue']['id'] . '.0;sa=reply',
			'lang' => true
		),
	);

	if ($context['can_issue_update'])
	{
		echo '
		<script language="JavaScript" type="text/javascript">
			var editing = false;
			var editItem = 0;
			var editTimer;

			$j(document).bind("ready", function()
			{
				$j("#issueinfot li.canedit dl").bind("click", dropDownEvent);
				$j("#issueinfot li.canedit").bind("mouseout", editMouseLeave).bind("mouseover", editMouseOver);
			});

			function dropDownEvent()
			{
				if (editing)
				{
					if (editItem == this)
						return editEnd2(editItem);

					editEnd2(editItem)
				}

				editStart(this);
			}

			function editStart(item)
			{
				if (editing)
					editEnd2(editItem);

				editing = true;
				editItem = item;

				$j(item).parent("li").addClass("selected");
				$j(item).parent("li").children("ul").width($j(item).width());
			}

			function editMouseLeave()
			{
				editTimer = setTimeout(editEndBody, 500)
			}
			function editMouseOver()
			{
				clearTimeout(editTimer);
			}

			function editEndBody()
			{
				editEnd2(editItem);
			}

			function editEnd()
			{
				editEnd2(this);
			}

			function editEnd2(item)
			{
				$j(item).parent("li").removeClass("selected");
				editing = false;
			}';

		echo '
		</script>';
	}

	echo '
	<form action="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update" method="post">
		<a name="com', $context['current_issue']['comment_first'], '"></a>
		<div id="issueinfo" class="floatright tborder">
			<h3 class="catbg3 headerpadding clearfix">', $txt['issue_details'], '</h3>
			<div id="issueinfot" class="clearfix topborder windowbg smalltext">
				<ul class="details">
					<li class="clearfix', !empty($context['can_issue_update']) ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_type'], '</dt>
							<dd>
								', $context['current_issue']['type']['name'], '
							</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_update']))
	{
		echo '
						<ul class="options">';

		foreach ($context['possible_types'] as $id => $type)
			echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';type=', $id, '">', $type['name'], '</a></li>';

		echo '
						</ul>';
	}

	echo '
					</li>
					<li class="clearfix', !empty($context['can_issue_update']) ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_category'], '</dt>
							<dd>', !empty($context['current_issue']['category']['id']) ? $context['current_issue']['category']['link'] : $txt['issue_none'], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_update']))
	{
		echo '
						<ul class="options">
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';category=0">', $txt['issue_none'],'</a></li>';

		foreach ($context['project']['category'] as $c)
			echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';category=',$c['id'], '">', $c['name'],'</a></li>';

		echo '
						</ul>';
	}

	echo '
					</li>
					<li class="clearfix', $context['can_issue_moderate'] ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_status'], '</dt>
							<dd>', $context['current_issue']['status']['text'], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_moderate']))
	{
		echo '
						<ul class="options">';

			foreach ($context['issue']['status'] as $status)
				echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';status=', $status['id'], '">', $status['text'], '</a></li>';

		echo '
						</ul>';
	}

	echo '
					</li>
					<li class="clearfix', $context['can_issue_moderate'] ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_priority'], '</dt>
							<dd>', $txt[$context['current_issue']['priority']], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_moderate']))
	{
		echo '
						<ul class="options">';

			foreach ($context['issue']['priority'] as $id => $text)
				echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';priority=', $id, '">', $txt[$text], '</a></li>';

		echo '
						</ul>';
	}

	echo '
					</li>
					<li>
						<dl class="clearfix">
							<dt>', $txt['issue_reported'], '</dt>
							<dd>	', $context['current_issue']['created'], '</dd>
						</dl>
					</li>
					<li>
						<dl class="clearfix">
							<dt>', $txt['issue_updated'], '</dt>
							<dd>', $context['current_issue']['updated'], '</dd>
						</dl>
					</li>
					<li class="clearfix ', !empty($context['can_issue_update']) ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_version'], '</dt>
							<dd>', !empty($context['current_issue']['version']['id']) ? $context['current_issue']['version']['name'] : $txt['issue_none'], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_update']))
	{
		echo '
						<ul class="options">
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version=0">', $txt['issue_none'], '</a></li>';
		foreach ($context['versions'] as $v)
		{
			echo '
							<li style="font-weight: bold"><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version=', $v['id'], '">', $v['name'], '</a></li>';

			foreach ($v['sub_versions'] as $subv)
				echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version=', $subv['id'], '">', $subv['name'], '</a></li>';
		}

		echo '
						</ul>';
	}

	echo '
					</li>
					<li class="clearfix', !empty($context['can_issue_moderate']) ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_version_fixed'], '</dt>
							<dd>', !empty($context['current_issue']['version_fixed']['id']) ? $context['current_issue']['version_fixed']['name'] : $txt['issue_none'], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_moderate']))
	{
		echo '
						<ul class="options">
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version_fixed=0">', $txt['issue_none'], '</a></li>';
		foreach ($context['versions'] as $v)
		{
			echo '
							<li style="font-weight: bold"><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version_fixed=', $v['id'], '">', $v['name'], '</a></li>';

			foreach ($v['sub_versions'] as $subv)
				echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';version_fixed=', $subv['id'], '">', $subv['name'], '</a></li>';
		}

		echo '
						</ul>';
	}

	echo '
					</li>
					<li class="clearfix', !empty($context['can_issue_moderate']) ? ' canedit' : '', '">
						<dl class="clearfix">
							<dt>', $txt['issue_assigned_to'], '</dt>
							<dd>', !empty($context['current_issue']['assignee']['id']) ? $context['current_issue']['assignee']['link'] : $txt['issue_none'], '</dd>
							<dd class="button"></dd>
						</dl>';

	if (!empty($context['can_issue_moderate']))
	{
		echo '
						<ul class="options">
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';assign=0">', $txt['issue_none'], '</a></li>';

			foreach ($context['assign_members'] as $mem)
				echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], ';sa=update;sesc=', $context['session_id'], ';assign=', $mem['id'], '">', $mem['name'], '</a></li>';

		echo '
						</ul>';
	}

	echo '
					</li>
				</ul>
			</div>
		</div>';

	$alternate = false;

	echo '';

	$reply_button = create_button('quote.gif', 'reply_quote', 'quote', 'align="middle"');
	$remove_button = create_button('delete.gif', 'remove_comment_alt', 'remove_comment', 'align="middle"');

	while ($comment = getComment())
	{
		if ($comment['first_new'])
		{
			echo '
		<a name="new"></a>';
		}
		if ($comment['first'])
		{
			echo '
		<div id="firstcomment" class="tborder">
			<h3 class="catbg3 headerpadding">
				<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" />
				<span>', $txt['issue'], ': ', $context['current_issue']['name'], '</span>
			</h3>
			<div class="bordercolor">';
		}

		echo '
				<div class="clearfix topborder windowbg', $alternate ? '2' : '', ' largepadding"', !$comment['first'] ? ' id="com' . $comment['id'] . '"' : '', '>
					<div class="floatleft poster">
						<h4>', $comment['member']['link'], '</h4>
						<ul class="smalltext">';

		// Show the member's custom title, if they have one.
		if (isset($comment['member']['title']) && $comment['member']['title'] != '')
			echo '
							<li>', $comment['member']['title'], '</li>';

		// Show the member's primary group (like 'Administrator') if they have one.
		if (isset($comment['member']['group']) && $comment['member']['group'] != '')
			echo '
							<li>', $comment['member']['group'], '</li>';

		echo '
						</ul>
					</div>
					<div class="postarea">
						<div class="keyinfo">';

		if ($comment['first'])
		{
			echo '
							<div class="messageicon floatleft">
								<img src="', $settings['images_url'], '/', $context['current_issue']['type']['image'], '" align="bottom" alt="" width="20" style="padding: 6px 3px" />
							</div>
							<h5><a href="', $scripturl , '?issue=', $context['current_issue']['id'], '.0#com', $comment['id'], '" rel="nofollow">', $context['current_issue']['name'], '</a></h5>';
		}
		echo '
							<div class="smalltext">&#171; <strong>', !empty($comment['counter']) ? $txt['reply'] . ' #' . $comment['counter'] : '', ' ', $txt['on'], ':</strong> ', $comment['time'], ' &#187;</div>
						</div>
						<ul class="smalltext postingbuttons">';

		if ($context['can_comment'])
			echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=reply;quote=', $comment['id'], ';sesc=', $context['session_id'], '">', $reply_button, '</a></li>';

		if ($comment['can_remove'])
			echo '
							<li><a href="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=removeComment;comment=', $comment['id'], ';sesc=', $context['session_id'], '" onclick="return confirm(\'', $txt['remove_comment_sure'], '?\');">', $remove_button, '</a></li>';

		echo '
						</ul>
						<hr width="100%" size="1" class="hrcolor" />
						<div id="com_', $comment['id'], '" class="post">
							', $comment['body'], '
						</div>
					</div>
					<div class="moderatorbar">
						<div class="smalltext floatleft">';

		// Show attachments
		if ($comment['first'] && !empty($context['attachments']))
		{
			echo '
						<hr width="100%" size="1" class="hrcolor" />
						<div style="overflow: auto; width: 100%;">';
			foreach ($context['attachments'] as $attachment)
			{
				if ($attachment['is_image'])
				{
					if ($attachment['thumbnail']['has_thumb'])
						echo '
								<a href="', $attachment['href'], ';image" id="link_', $attachment['id'], '" onclick="', $attachment['thumbnail']['javascript'], '"><img src="', $attachment['thumbnail']['href'], '" alt="" id="thumb_', $attachment['id'], '" border="0" /></a><br />';
					else
						echo '
								<img src="' . $attachment['href'] . ';image" alt="" width="' . $attachment['width'] . '" height="' . $attachment['height'] . '" border="0" /><br />';
				}
				echo '
								<a href="' . $attachment['href'] . '"><img src="' . $settings['images_url'] . '/icons/clip.gif" align="middle" alt="*" border="0" />&nbsp;' . $attachment['name'] . '</a>
										(', $attachment['size'], ($attachment['is_image'] ? ', ' . $attachment['real_width'] . 'x' . $attachment['real_height'] . ' - ' . $txt['attach_viewed'] : ' - ' . $txt['attach_downloaded']) . ' ' . $attachment['downloads'] . ' ' . $txt['attach_times'] . '.)<br />';
			}

			echo '
						</div>';
		}

		echo '
						</div>
						<div class="smalltext floatright">';
		echo '
							<img src="', $settings['images_url'], '/ip.gif" alt="" border="0" />';

		// Show the IP to this user for this post - because you can moderate?
		if (allowedTo('moderate_forum') && !empty($comment['ip']))
			echo '
							<a href="', $scripturl, '?action=trackip;searchip=', $comment['ip'], '">', $comment['ip'], '</a> <a href="', $scripturl, '?action=helpadmin;help=see_admin_ip" onclick="return reqWin(this.href);" class="help">(?)</a>';
		// Or, should we show it because this is you?
		elseif ($comment['can_see_ip'])
			echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $comment['ip'], '</a>';
		// Okay, are you at least logged in?  Then we can show something about why IPs are logged...
		elseif (!$context['user']['is_guest'])
			echo '
							<a href="', $scripturl, '?action=helpadmin;help=see_member_ip" onclick="return reqWin(this.href);" class="help">', $txt['logged'], '</a>';
		// Otherwise, you see NOTHING!
		else
			echo '
							', $txt['logged'];

		echo '
						</div>
					</div>
				</div>';

			$alternate = !$alternate;

		if ($comment['first'])
		{
			echo '
			</div>
		</div><br />
	</form>
	<form action="', $scripturl, '?issue=', $context['current_issue']['id'], '.0;sa=update" method="post">
		<div class="modbuttons clearfix margintop">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'bottom'), '
		</div>
		<div class="tborder">
			<h3 class="catbg3 headerpadding">
				', $txt['issue_comments'], '
			</h3>
			<div class="bordercolor">';
		}
	}

	echo '
			</div>
		</div>
		<div class="modbuttons clearfix marginbottom">
			<div class="floatleft middletext">', $txt['pages'], ': ', $context['page_index'], !empty($modSettings['topbottomEnable']) ? $context['menu_separator'] . '&nbsp;&nbsp;<a href="#top"><b>' . $txt['go_up'] . '</b></a>' : '', '</div>
			', template_button_strip($buttons, 'top'), '
		</div><br />';

	$mod_buttons = array(
		'delete' => array('test' => 'can_issue_moderate', 'text' => 'issue_delete', 'lang' => true, 'custom' => 'onclick="return confirm(\'' . $txt['issue_delete_confirm'] . '\');"', 'url' => $scripturl . '?issue=' . $context['current_issue']['id'] . '.0;sa=delete;sesc=' . $context['session_id']),
	);

	echo '
	<div id="moderationbuttons">', 	template_button_strip($mod_buttons, 'bottom'), '</div>
		<input type="hidden" name="sc" value="', $context['session_id'], '" />';


	echo '
		<div class="tborder">
			<div class="titlebg2" style="padding: 4px;" align="', !$context['right_to_left'] ? 'right' : 'left', '">&nbsp;</div>
	</div><br />';

	if ($context['can_comment'])
	{
		echo '
		<div class="tborder">
			<div class="catbg headerpadding">', $txt['comment_issue'], '</div>
			<div class="smallpadding windowbg" style="text-align: center">
				<textarea id="comment" name="comment" rows="7" cols="75"></textarea>';

		echo '
				<div style="text-align: right">
					<input type="submit" name="add_comment" value="', $txt['add_comment'], '" />
				</div>
			</div>
		</div><br />';
	}

	echo '
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
	</form><br />';

	if ($context['can_issue_attach'])
	{
		echo '
		<form action="', $scripturl , '?issue=', $context['current_issue']['id'], '.0;sa=upload" method="post" accept-charset="', $context['character_set'], '" enctype="multipart/form-data">
			<div class="tborder">
				<div class="catbg headerpadding">', $txt['issue_attach'], '</div>
				<div class="smallpadding windowbg">
					<input type="file" size="48" name="attachment[]" /><br />';

		if (!empty($modSettings['attachmentCheckExtensions']))
			echo '
					', $txt['allowed_types'], ': ', $context['allowed_extensions'], '<br />';
		echo '
					', $txt['max_size'], ': ', $modSettings['attachmentSizeLimit'], ' ' . $txt['kilobyte'], '<br />';

		echo '
					<div style="text-align: right">
						<input type="submit" name="add_comment" value="', $txt['add_attach'], '" />
					</div>
				</div>
			</div>
			<input type="hidden" name="sc" value="', $context['session_id'], '" />
		</form>';
	}
}

function template_issue_reply()
{
	global $context, $settings, $options, $txt, $scripturl, $modSettings;

	echo '
	<form action="', $scripturl, '?sa=', $context['destination'], '" method="post" accept-charset="', $context['character_set'], '" name="reportissue" id="reportissue" onsubmit="submitonce(this);saveEntities();" enctype="multipart/form-data">
		<div class="tborder" id="reportform">
			<h4 class="headerpadding titlebg">', $txt['issue_reply'], '</h4>
			<div class="windowbg">
				<dl>
					<dd>
						', template_control_richedit($context['post_box_name'], 'bbc'), '
					</dd>
					<dd>
						', template_control_richedit($context['post_box_name'], 'message'), '
					</dd>
					<dd class="full center">
						<span class="smalltext"><br />', $txt['shortcuts'], '</span><br />
						', template_control_richedit($context['post_box_name'], 'buttons'), '
					</dd>
					<dd class="clear"></dd>
				</dl>
			</div>
		</div>

		<input type="hidden" name="issue" value="', $context['current_issue']['id'], '" />
		<input type="hidden" name="sc" value="', $context['session_id'], '" />
		<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
	</form>';
}

?>