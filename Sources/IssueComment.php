<?php
/**
 * Handles commenting issue and editing comments
 *
 * @package issuetracker
 * @version 0.5.2
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

/**
 * Display Reply or Edit Reply page
 */
function IssueReply()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $issue, $modSettings, $options, $project;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found', false);

	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_subscribe'] = !$user_info['is_guest'];
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate');
	$context['can_issue_attach'] = projectAllowedTo('issue_attach');

	$context['allowed_extensions'] = strtr($modSettings['attachmentExtensions'], array(',' => ', '));

	if ($context['can_issue_update'])
	{
		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = &$context['project']['developers'];
	}

	$context['destination'] = 'reply2';
	
	// Check if user has subscribed to issue
	if (!$user_info['is_guest'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT sent
			FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}
			LIMIT 1',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
		$context['is_subscribed'] = $smcFunc['db_num_rows']($request) != 0;
		$smcFunc['db_free_result']($request);
	}
	else
		$context['is_subscribed'] = false;
	
	$context['notify'] = isset($_POST['issue_subscribe']) ? !empty($_POST['issue_subscribe']) : ($context['is_subscribed'] || !empty($options['auto_notify']));

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');
	require_once($sourcedir . '/Subs-Post.php');

	$editing = false;

	$form_comment = '';

	// Editing
	if ($_REQUEST['sa'] == 'edit' || $_REQUEST['sa'] == 'edit2')
	{
		projectIsAllowedTo('edit_comment_own');
		require_once($sourcedir . '/Subs-Post.php');

		if (empty($_REQUEST['com']) || !is_numeric($_REQUEST['com']))
			fatal_lang_error('comment_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
				AND c.id_member = {int:current_user}' : '') . '
			ORDER BY id_comment',
			array(
				'current_user' => $user_info['id'],
				'issue' => $issue,
				'comment' => (int) $_REQUEST['com'],
			)
		);

		$context['destination'] = 'edit2;com=' . (int) $_REQUEST['com'];

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('comment_not_found', false);

		$form_comment = un_preparsecode($row['body']);

		$editing = true;
	}
	elseif (isset($_REQUEST['quote']) && is_numeric($_REQUEST['quote']))
	{
		checkSession('get');

		require_once($sourcedir . '/Subs-Post.php');

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				INNER JOIN {db_prefix}issues AS i ON (i.id_issue = c.id_issue)
				INNER JOIN {db_prefix}projects AS p ON (p.id_project = i.id_project)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
				LEFT JOIN {db_prefix}project_developer AS dev ON (dev.id_project = p.id_project
					AND dev.id_member = {int:current_member})
			WHERE {query_see_issue}
				AND id_comment = {int:comment}
			ORDER BY id_comment',
			array(
				'issue' => $issue,
				'comment' => $_REQUEST['quote'],
				'current_member' => $user_info['id'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('comment_not_found', false);

		$form_comment = $row['body'];
		censorText($form_comment);

		// fix html tags
		if (strpos($form_comment, '[html]') !== false)
		{
			$parts = preg_split('~(\[/code\]|\[code(?:=[^\]]+)?\])~i', $form_comment, -1, PREG_SPLIT_DELIM_CAPTURE);
			for ($i = 0, $n = count($parts); $i < $n; $i++)
			{
				// It goes 0 = outside, 1 = begin tag, 2 = inside, 3 = close tag, repeat.
				if ($i % 4 == 0)
					$parts[$i] = preg_replace('~\[html\](.+?)\[/html\]~ise', '\'[html]\' . preg_replace(\'~<br\s?/?>~i\', \'&lt;br /&gt;<br />\', \'$1\') . \'[/html]\'', $parts[$i]);
			}
			$form_comment = implode('', $parts);
		}

		$form_comment = preg_replace('~<br(?: /)?' . '>~i', "\n", $form_comment);
		$form_comment = '[quote author=' . $row['real_name'] . ' link=' . 'issue=' . $issue . '.com' . $_REQUEST['quote'] . '#com' . $_REQUEST['quote'] . ' date=' . $row['post_time'] . "]\n" . rtrim($form_comment) . "\n[/quote]";
	}

	if (isset($_REQUEST['comment']) || !empty($context['post_error']))
	{
		if (!isset($_REQUEST['details']))
			$_REQUEST['details'] = '';

		if (empty($context['post_error']))
		{
			// TODO CHECKS

			$previewing = true;
		}
		else
			$previewing = !empty($_POST['preview']);

		$form_comment = $smcFunc['htmlspecialchars']($_REQUEST['comment'], ENT_QUOTES);

		if ($previewing)
		{
			$context['preview_comment'] = $form_comment;
			preparsecode($form_comment, true);
			preparsecode($context['preview_comment']);

			$context['preview_comment'] = parse_bbc($context['preview_comment']);
			censorText($context['preview_comment']);
		}

		$context['comment'] = $_REQUEST['comment'];
	}

	$context['comment'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $form_comment);

	$editorOptions = array(
		'form' => 'reportissue',
		'id' => 'comment',
		'width' => '550px',
		'value' => $context['comment'],
		'labels' => array(
			'post_button' => $editing ? $txt['issue_save'] : $txt['issue_reply'],
		),
	);
	create_control_richedit($editorOptions);

	checkSubmitOnce('register');

	$context['post_box_name'] = 'comment';

	// Template
	$context['sub_template'] = 'issue_reply';
	$context['page_title'] = sprintf($txt['project_view_issue'], $context['project']['name'], $context['current_issue']['id'], $context['current_issue']['name']);

	loadTemplate('IssueReport');
}

/**
 * Add new reply or save edit
 */
function IssueReply2()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $issue, $modSettings, $project;

	if (!isset($context['current_issue']) || !projectAllowedTo('issue_comment'))
		fatal_lang_error('issue_not_found', false);

	$type = $context['current_issue']['is_mine'] ? 'own' : 'any';

	$context['show_update'] = false;
	$context['can_comment'] = projectAllowedTo('issue_comment');
	$context['can_subscribe'] = !$user_info['is_guest'];
	$context['can_issue_moderate'] = projectAllowedTo('issue_moderate');
	$context['can_issue_update'] = projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate');
	$context['can_issue_attach'] = projectAllowedTo('issue_attach');

	$context['allowed_extensions'] = strtr($modSettings['attachmentExtensions'], array(',' => ', '));

	if ($context['can_issue_update'])
	{
		$context['can_edit'] = true;
		$context['show_update'] = true;
	}

	if (projectAllowedTo('issue_moderate'))
	{
		$context['can_assign'] = true;
		$context['assign_members'] = &$context['project']['developers'];
	}

	if (!empty($_REQUEST['comment_mode']) && isset($_REQUEST['comment']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['comment'] = html_to_bbc($_REQUEST['comment']);
		$_REQUEST['comment'] = un_htmlspecialchars($_REQUEST['comment']);
		$_POST['comment'] = $_REQUEST['comment'];
	}

	if (isset($_REQUEST['preview']))
		return IssueReply();

	require_once($sourcedir . '/Subs-Post.php');
	require_once($sourcedir . '/IssueReport.php');

	checkSubmitOnce('check');

	$post_errors = array();

	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['comment'])) == '')
		$post_errors[] = 'no_message';
	else
	{
		$_POST['comment'] = $smcFunc['htmlspecialchars']($_POST['comment'], ENT_QUOTES);

		preparsecode($_POST['comment']);
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['comment'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['message'], '[html]') === false))
			$post_errors[] = 'no_message';
	}

	if (!empty($post_errors))
	{
		loadLanguage('Errors');
		$_REQUEST['preview'] = true;

		$context['post_error'] = array('messages' => array());
		foreach ($post_errors as $post_error)
		{
			$context['post_error'][$post_error] = true;
			$context['post_error']['messages'][] = $txt['error_' . $post_error];
		}

		return IssueReply();
	}
	
	// Check if user has subscribed to issue
	if (!$user_info['is_guest'])
	{
		$request = $smcFunc['db_query']('', '
			SELECT sent
			FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}
			LIMIT 1',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
		$context['is_subscribed'] = $smcFunc['db_num_rows']($request) != 0;
		$smcFunc['db_free_result']($request);
	}
	else
		$context['is_subscribed'] = false;	

	$_POST['guestname'] = $user_info['username'];
	$_POST['email'] = $user_info['email'];

	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => $user_info['is_guest'] ? $_POST['guestname'] : $user_info['name'],
		'username' => $user_info['username'],
		'email' => $_POST['email'],
	);
	$issueOptions = array(
		'mark_read' => true,
	);

	if (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions);

	if (count($issueOptions) > 1)
		$event_data = updateIssue($issue, $issueOptions, $posterOptions, empty($_REQUEST['com']));
	else
		$event_data = array();

	if (empty($_REQUEST['com']))
	{
		// Event data might be boolean in certain cases
		if (!is_array($event_data))
			$event_data = array();
			
		$commentOptions = array(
			'body' => $_POST['comment'],
		);
		$id_comment = createComment($context['project']['id'], $issue, $commentOptions, $posterOptions, $event_data);

		$commentOptions['id'] = $id_comment;

		sendIssueNotification(array('id' => $issue, 'project' => $context['project']['id']), $commentOptions, $event_data, 'new_comment', $user_info['id']);
	}
	else
	{
		projectIsAllowedTo('edit_comment_own');
		require_once($sourcedir . '/Subs-Post.php');

		if (empty($_REQUEST['com']) || !is_numeric($_REQUEST['com']))
			fatal_lang_error('comment_not_found', false);

		$request = $smcFunc['db_query']('', '
			SELECT c.id_comment, c.post_time, c.edit_time, c.body,
				IFNULL(mem.real_name, c.poster_name) AS real_name, c.poster_email, c.poster_ip, c.id_member
			FROM {db_prefix}issue_comments AS c
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
				AND c.id_member = {int:current_user}' : '') . '
			ORDER BY id_comment',
			array(
				'current_user' => $user_info['id'],
				'issue' => $issue,
				'comment' => (int) $_REQUEST['com'],
			)
		);

		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		if (!$row)
			fatal_lang_error('comment_not_found', false);

		$commentOptions = array(
			'body' => $_POST['comment'],
		);

		modifyComment($_REQUEST['com'], $issue, $commentOptions, $posterOptions);

		$id_comment = $_REQUEST['com'];
	}

	if (!empty($_POST['issue_subscribe']) && $context['can_subscribe'] && !$context['is_subscribed'])
	{
		$smcFunc['db_insert']('',
			'{db_prefix}log_notify_projects',
			array(
				'id_project' => 'int',
				'id_issue' => 'int',
				'id_member' => 'int',
				'sent' => 'int',
			),
			array(
				0,
				$issue,
				$user_info['id'],
				0,
			),
			array('id_project', 'id_issue', 'id_member')
		);
	}
	// Unsubscribe
	elseif (empty($_POST['issue_subscribe']) && $context['is_subscribed'])
	{
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}log_notify_projects
			WHERE id_issue = {int:issue}
				AND id_member = {int:current_member}',
			array(
				'issue' => $issue,
				'current_member' => $user_info['id'],
			)
		);
	}

	$request = $smcFunc['db_query']('', '
		SELECT id_event
		FROM {db_prefix}issue_comments
		WHERE id_comment = {int:comment}',
		array(
			'current_user' => $user_info['id'],
			'issue' => $issue,
			'comment' => $id_comment,
		)
	);
	list ($id_event) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	redirectexit(project_get_url(array('issue' => $issue . '.com' . $id_event)) . '#com' . $id_comment);
}

/**
 * Delete Comment
 */
function IssueDeleteComment()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	if (!isset($context['current_issue']) || empty($_REQUEST['com']))
		fatal_lang_error('issue_not_found', false);

	projectIsAllowedTo('edit_comment_own');
	require_once($sourcedir . '/Subs-Post.php');

	$request = $smcFunc['db_query']('', '
		SELECT c.id_comment, c.id_event, c.poster_name, c.id_member
		FROM {db_prefix}issue_comments AS c
		WHERE id_comment = {int:comment}' . (!projectAllowedTo('edit_comment_any') ? '
			AND c.id_member = {int:current_user}' : '') . '
			AND c.id_issue = {int:issue}
		ORDER BY id_comment',
		array(
			'current_user' => $user_info['id'],
			'comment' => (int) $_REQUEST['com'],
			'issue' => $context['current_issue']['id'],
		)
	);

	$row = $smcFunc['db_fetch_assoc']($request);
	if (!$row)
		fatal_lang_error('comment_not_found', false);
	$smcFunc['db_free_result']($request);

	if ($row['id_comment'] == $context['current_issue']['details']['id'])
		fatal_lang_error('comment_cant_remove_first', false);

	// Delete comment
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}issue_comments
		WHERE id_comment = {int:comment}',
		array(
			'comment' => $row['id_comment'],
		)
	);

	// Check event_data, there might be changes that we should keep
	$request = $smcFunc['db_query']('', '
		SELECT event_data
		FROM {db_prefix}project_timeline
		WHERE id_event = {int:event}',
		array(
			'event' => $row['id_event'],
		)
	);

	list ($event_data) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// By default remove event too
	$removeEvent = true;

	if ($event_data && $event_data = unserialize($event_data))
	{
		if (isset($event_data['changes']) && is_array($event_data['changes']) && !empty($event_data['changes']))
			$removeEvent = false;
	}

	if ($removeEvent)
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}project_timeline
			WHERE id_event = {int:event}',
			array(
				'event' => $row['id_event'],
			)
		);
	else
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}project_timeline
			SET event = {string:update_issue}
			WHERE id_event = {int:event}',
			array(
				'update_issue' => 'update_issue',
				'event' => $row['id_event'],
			)
		);

	// Adjust reply count on issue
	$request = $smcFunc['db_query']('', '
		SELECT count(*) as total
		FROM {db_prefix}project_timeline
		WHERE id_issue = {int:issue} AND event = {string:event}',
		array(
			'issue' => $context['current_issue']['id'],
			'event' => 'new_comment',
		)
	);
	list ($num_replies) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);
	
	$smcFunc['db_query']('', '
		UPDATE {db_prefix}issues
		SET replies = {int:replies}
		WHERE id_issue = {int:issue}',
		array(
			'issue' => $context['current_issue']['id'],
			'replies' => $num_replies,
		)
	);

	logAction('project_remove_comment', array('comment' => $row['id_comment']));

	redirectexit(project_get_url(array('issue' => $context['current_issue']['id'] . '.0')));
}

?>
