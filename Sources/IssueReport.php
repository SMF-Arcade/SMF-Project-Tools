<?php
/**
 * Handles reporting new issues. And updating meta-data
 *
 * @package issuetracker
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @todo Move updating to its own file 
 * @since 0.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 * Displayis Issue Report form
 */
function ReportIssue()
{
	global $smcFunc, $context, $user_info, $txt, $modSettings, $sourcedir, $project, $options;

	projectIsAllowedTo('issue_report');
	require_once($sourcedir . '/Subs-Post.php');

	$context['can_subscribe'] = !$user_info['is_guest'];

	$context['issue'] = array(
		'title' => '',
		'private' => !empty($_REQUEST['private']),
		'tracker' => isset($_REQUEST['tracker']) && isset(ProjectTools_Project::getCurrent()->trackers[$_REQUEST['tracker']]) ? $_REQUEST['tracker'] : '',
		'version' => isset($_REQUEST['version']) ? (is_array($_REQUEST['version']) ? $_REQUEST['version'] : explode(',', $_REQUEST['version'])) : array(),
		'category' => isset($_REQUEST['category']) ? (int) $_REQUEST['category'] : 0,
	);

	$context['notify'] = isset($_POST['issue_subscribe']) ? !empty($_POST['issue_subscribe']) : !empty($options['auto_notify']);

	$form_title = '';
	$form_details = '';

	if (isset($_REQUEST['details']) || !empty($context['post_error']))
	{
		if (!isset($_REQUEST['title']))
			$_REQUEST['title'] = '';
		if (!isset($_REQUEST['details']))
			$_REQUEST['details'] = '';

		if (empty($context['post_error']))
		{
			// TODO CHECKS

			$previewing = true;
		}
		else
			$previewing = !empty($_POST['preview']);

		$form_title = strtr($smcFunc['htmlspecialchars']($_REQUEST['title']), array("\r" => '', "\n" => '', "\t" => ''));
		$form_details = $smcFunc['htmlspecialchars']($_REQUEST['details'], ENT_QUOTES);

		if ($previewing)
		{
			$context['preview_details'] = $form_details;
			preparsecode($form_details, true);
			preparsecode($context['preview_details']);

			$context['preview_details'] = parse_bbc($context['preview_details']);
			censorText($context['preview_details']);

			if ($form_title != '')
			{
				$context['preview_title'] = $form_title;
				censorText($context['preview_title']);
			}
			else
			{
				$context['preview_title'] = '<i>' . $txt['issue_no_title'] . '</i>';
			}
		}

		$context['issue']['title'] = $_REQUEST['title'];
		$context['details'] = $_REQUEST['details'];
	}

	$context['issue']['title'] = addcslashes($form_title, '"');
	$context['details'] = str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $form_details);

	// Editor
	require_once($sourcedir . '/Subs-Editor.php');

	$editorOptions = array(
		'form' => 'reportissue',
		'id' => 'details',
		'value' => $context['details'],
		'labels' => array(
			'post_button' => $txt['report_issue'],
		),
	);
	create_control_richedit($editorOptions);

	$context['post_box_name'] = $editorOptions['id'];
	$context['destination'] = 'report2';

	$context['show_version'] = !empty(ProjectTools_Project::getCurrent()->versions);
	$context['show_category'] = !empty(ProjectTools_Project::getCurrent()->categories);

	checkSubmitOnce('register');

	$context['linktree'][] = array(
		'name' => $txt['linktree_report_issue'],
		'url' => project_get_url(array('project' => $project, 'area' => 'issues', 'sa' => 'report')),
	);

	// Template
	loadTemplate('IssueReport');

	$context['sub_template'] = 'report_issue';
	$context['page_title'] = sprintf($txt['project_report_issue'], ProjectTools_Project::getCurrent()->name);
}

/**
 * Validates posted issue and add it to database if everything is ok.
 * Calls ReportIsssue if previewing or form had errors
 */
function ReportIssue2()
{
	global $smcFunc, $context, $user_info, $txt, $modSettings, $sourcedir, $project;

	projectIsAllowedTo('issue_report');

	$context['can_subscribe'] = !$user_info['is_guest'];

	if (!empty($_REQUEST['details_mode']) && isset($_REQUEST['details']))
	{
		require_once($sourcedir . '/Subs-Editor.php');

		$_REQUEST['details'] = html_to_bbc($_REQUEST['details']);
		$_REQUEST['details'] = un_htmlspecialchars($_REQUEST['details']);
		$_POST['details'] = $_REQUEST['details'];
	}

	if (isset($_REQUEST['preview']))
		return ReportIssue();

	require_once($sourcedir . '/Subs-Post.php');

	checkSubmitOnce('check');

	$post_errors = array();

	if (checkSession('post', '', false) != '')
		$post_errors[] = 'session_timeout';
	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['title'])) == '')
		$post_errors[] = 'no_tile';
	if (htmltrim__recursive(htmlspecialchars__recursive($_POST['details'])) == '')
		$post_errors[] = 'no_message';
	else
	{
		$_POST['details'] = $smcFunc['htmlspecialchars']($_POST['details'], ENT_QUOTES);

		preparsecode($_POST['details']);
		if ($smcFunc['htmltrim'](strip_tags(parse_bbc($_POST['details'], false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($_POST['details'], '[html]') === false))
			$post_errors[] = 'no_details';
	}

	if (count(ProjectTools_Project::getCurrent()->trackers) == 1)
		list ($_POST['tracker']) = array_keys(ProjectTools_Project::getCurrent()->trackers);

	if (empty($_POST['tracker']) || !isset(ProjectTools_Project::getCurrent()->trackers[$_POST['tracker']]))
		$post_errors[] = 'no_issue_type';
	
	if (!empty($_POST['version']))
	{
		$versions = getVersions(is_array($_POST['version']) ? $_POST['version'] : explode(',', $_POST['version']));
		
		$_POST['version'] = array();
		
		foreach ($versions as $ver)
			$_POST['version'][] = $ver['id'];
	}
	
	if (empty($_POST['version']))	
		$_POST['version'] = array(0);

	$_POST['guestname'] = $user_info['username'];
	$_POST['email'] = $user_info['email'];

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

		return ReportIssue();
	}

	$_POST['title'] = strtr($smcFunc['htmlspecialchars']($_POST['title']), array("\r" => '', "\n" => '', "\t" => ''));
	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	if ($smcFunc['strlen']($_POST['title']) > 100)
		$_POST['title'] = $smcFunc['substr']($_POST['title'], 0, 100);

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => $user_info['is_guest'] ? $_POST['guestname'] : $user_info['name'],
		'username' => $_POST['guestname'],
		'email' => $_POST['email'],
	);
	$issueOptions = array(
		'project' => $project,
		'subject' => $_POST['title'],
		'tracker' => $_POST['tracker'],
		'status' => 1,
		'priority' => 2,
		'category' => isset($_POST['category']) ? (int) $_POST['category'] : 0,
		'versions' => $_POST['version'],
		'assignee' => 0,
		'body' => $_POST['details'],
		'created' => time(),
		'updated' => time(),
		'private' => !empty($_REQUEST['private']),
		'mark_read' => true,
	);

	$issueOptions['id'] = createIssue($issueOptions, $posterOptions);

	// Send notifications
	sendProjectNotification($issueOptions, 'new_issue', $user_info['id']);

	if (!empty($_POST['issue_subscribe']) && $context['can_subscribe'])
	{
		$smcFunc['db_insert']('',
			'{db_prefix}log_notify_projects',
			array('id_project' => 'int', 'id_issue' => 'int', 'id_member' => 'int', 'sent' => 'int'),
			array(0, $issueOptions['id'], $user_info['id'], 0),
			array('id_project', 'id_issue', 'id_member')
		);
	}

	cache_put_data('project-' . $project, null, 120);

	redirectexit(project_get_url(array('project' => $project, 'area' => 'issues')));
}

/**
 * Handles ajax calls to update issue
 */
function IssueUpdate()
{
	global $context, $user_info, $smcFunc, $issue, $sourcedir;

	if (!ProjectTools_IssueTracker_Issue::getCurrent())
		fatal_lang_error('issue_not_found', false);

	is_not_guest();

	$type = ProjectTools_IssueTracker_Issue::getCurrent()->is_mine ? 'own' : 'any';

	checkSession('get');

	$_POST['guestname'] = $user_info['username'];
	$_POST['email'] = $user_info['email'];

	$_POST['guestname'] = htmlspecialchars($_POST['guestname']);
	$_POST['email'] = htmlspecialchars($_POST['email']);

	$posterOptions = array(
		'id' => $user_info['id'],
		'ip' => $user_info['ip'],
		'name' => $user_info['is_guest'] ? $_POST['guestname'] : $user_info['name'],
		'username' => $_POST['guestname'],
		'email' => $_POST['email'],
	);
	$issueOptions = array();

	$context['xml_data'] = array(
		'updates' => array(
			'identifier' => 'update',
			'children' => array(
			),
		),
	);

	if (projectAllowedTo('issue_update_' . $type) || projectAllowedTo('issue_moderate'))
		handleUpdate($posterOptions, $issueOptions, true);

	if (!empty($issueOptions))
	{
		$issueOptions['mark_read'] = true;
		$id_event = updateIssue($issue, $issueOptions, $posterOptions);
	}
	else
		$id_event = false;
		
	// Update time
	if ($id_event != false)
		$context['xml_data']['updates']['children'][] = array(
			'attributes' => array(
				'field' => 'updated',
				'id' => 0,
			),
			'value' => timeformat(time()),
		);
	
	$context['xml_data']['success'] = array(
		'identifier' => 'success',
		'children' => array(
			array('value' => $id_event !== false ? 1 : 0)
		),		
	);
	
	// Add new events
	if (isset($_REQUEST['last_event']))
	{
		require_once($sourcedir . '/IssueView.php');
		loadTemplate('IssueView');
		
		loadIssueView();
		
		$request = $smcFunc['db_query']('', '
			SELECT id_event, id_member
			FROM {db_prefix}project_timeline
			WHERE id_issue = {int:issue}
				AND id_event > {int:last_event} OR id_event = {int:current_event}',
			array(
				'issue' => $issue,
				'last_event' => (int) $_REQUEST['last_event'],
				'current_event' => is_int($id_event) ? $id_event : 0,
			)
		);
		
		$events = array();
		$posters = array();
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if (!empty($row['id_member']))
				$posters[$row['id_member']] = $row['id_member'];
			$events[] = $row['id_event'];
		}
		$smcFunc['db_free_result']($request);
	
		if (!empty($posters))
			loadMemberData($posters);
	
		$context['num_events'] = count($events);
	
		// Load events
		if (!empty($events))
		{
			$context['comment_request'] = $smcFunc['db_query']('', '
				SELECT
					tl.id_event, tl.id_member, tl.event, tl.event_time , tl.event_data, tl.poster_name, tl.poster_email, tl.poster_ip,
					IFNULL(c.id_comment, 0) AS is_comment, c.id_comment, c.post_time, c.edit_time, c.body, c.edit_name, c.edit_time, tl.event_data,
					IFNULL(c.id_event_mod, {int:new_from}) < {int:new_from} AS is_read
				FROM {db_prefix}project_timeline AS tl
					LEFT JOIN {db_prefix}issue_comments AS c ON (c.id_event = tl.id_event)
				WHERE tl.id_event IN ({array_int:events})',
				array(
					'events' => $events,
					'new_from' => ProjectTools_IssueTracker_Issue::getCurrent()->new_from,
				)
			);
		}
		else
			$context['comment_request'] = false;
	
		$context['counter_start'] = $_REQUEST['start'];
		
		// Get html using buffers (todo: use better method?)
		ob_start();
		
		$alternate = true;
	
		while ($event = getEvent())
		{
			if ($event['type'] == 'comment')
				template_event_full($event, $alternate);
			else
				template_event_compact($event, $alternate);
		}
			
		$comments_html = ob_get_contents();
		ob_end_clean();
		
		$context['xml_data']['events'] = array(
			'identifier' => 'event',
			'children' => array(
				array('value' => $comments_html)
			),		
		);		
	}

	// Template
	loadTemplate('Xml');
	$context['sub_template'] = 'generic_xml';
}

/**
 * Helper function for getting parameters for update function
 */
function handleUpdate(&$posterOptions, &$issueOptions, $xml_data = false)
{
	global $context, $user_info, $smcFunc, $sourcedir, $txt;

	$type = ProjectTools_IssueTracker_Issue::getCurrent()->is_mine ? 'own' : 'any';

	// Assigning
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['assign']))
	{
		if (!isset(ProjectTools_Project::getCurrent()->developers[(int) $_REQUEST['assign']]))
			$_REQUEST['assign'] = 0;

		$issueOptions['assignee'] = (int) $_REQUEST['assign'];
		
		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'assign',
					'id' => $issueOptions['assignee'],
				),
				'value' => $issueOptions['assignee'],
			);
	}

	// Title
	if (!empty($_REQUEST['title']) && trim($_REQUEST['title']) != '')
	{
		$_REQUEST['title'] = strtr($smcFunc['htmlspecialchars']($_REQUEST['title']), array("\r" => '', "\n" => '', "\t" => ''));
		$issueOptions['subject'] = $_REQUEST['title'];
		
		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'subject',
					'id' => 0,
				),
				'value' => $issueOptions['subject'],
			);
	}

	// Private
	if (isset($_REQUEST['private']))
	{
		$issueOptions['private'] = !empty($_REQUEST['private']);

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'private',
					'id' => $issueOptions['private'] ? 1 : 0,
				),
				'value' => $issueOptions['private'] ? 1 : 0,
			);		
	}

	// Priority
	if (isset($_REQUEST['priority']) && isset($context['issue']['priority'][(int) $_REQUEST['priority']]))
	{
		$issueOptions['priority'] = (int) $_REQUEST['priority'];
		
		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'priority',
					'id' => $issueOptions['priority'],
				),
				'value' => $issueOptions['priority'],
			);
	}

	// Version
	if (isset($_REQUEST['version']))
	{
		$issueOptions['versions'] = is_array($_REQUEST['version']) ? $_REQUEST['version'] : explode(',', $_REQUEST['version']);

		foreach ($issueOptions['versions'] as $k => $v)
		{
			$v = (int) $v;
			
			if (!isset($context['versions_id'][$v]))
				unset($issueOptions['versions'][$k]);
				
			$issueOptions['versions'][$k] = $v;
		}

		if ($xml_data)
		{
			$version_text = '';
			
			foreach (getVersions($issueOptions['versions']) as $version)
			{
				if (!empty($version_text))
					$version_text .= ', ';
				
				$version_text .= $version['name'];
			}
			
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'version',
					'id' => $issueOptions['versions'],
				),
				'value' => !empty($version_text) ? $version_text : $txt['issue_none'],
			);
			
			unset($version_text);
		}
	}

	// Version fixed
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['version_fixed']))
	{
		$issueOptions['versions_fixed'] = is_array($_REQUEST['version_fixed']) ? $_REQUEST['version_fixed'] : explode(',', $_REQUEST['version_fixed']);

		foreach ($issueOptions['versions_fixed'] as $k => $v)
		{
			$v = (int) $v;
			
			if (!isset($context['versions_id'][$v]))
				unset($issueOptions['versions_fixed'][$k]);
				
			$issueOptions['versions_fixed'][$k] = $v;
		}

		if ($xml_data)
		{
			$version_text = '';
			
			foreach (getVersions($issueOptions['versions_fixed']) as $version)
			{
				if (!empty($version_text))
					$version_text .= ', ';
				
				$version_text .= $version['name'];
			}
			
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'version_fixed',
					'id' => $issueOptions['versions_fixed'],
				),
				'value' => !empty($version_text) ? $version_text : $txt['issue_none'],
			);
			
			unset($version_text);
		}
	}

	// Category
	if (isset($_REQUEST['category']))
	{
		if (!isset(ProjectTools_Project::getCurrent()->category[(int) $_REQUEST['category']]))
			$_REQUEST['category'] = 0;

		$issueOptions['category'] = (int) $_REQUEST['category'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'category',
					'id' => $issueOptions['category'],
				),
				'value' => $issueOptions['category'],
			);
	}

	// Status
	if (projectAllowedTo('issue_moderate') && isset($_REQUEST['status']))
	{
		if (isset($context['issue_status'][(int) $_REQUEST['status']]))
			$issueOptions['status'] = (int) $_REQUEST['status'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'status',
					'id' => $issueOptions['status'],
				),
				'value' => $issueOptions['status'],
			);
	}

	if (isset($_REQUEST['tracker']) && isset(ProjectTools_Project::getCurrent()->trackers[$_REQUEST['tracker']]))
	{
		$issueOptions['tracker'] = $_REQUEST['tracker'];

		if ($xml_data)
			$context['xml_data']['updates']['children'][] = array(
				'attributes' => array(
					'field' => 'tracker',
					'id' => $issueOptions['tracker']
				),
				'value' => $issueOptions['tracker'],
			);
	}
}

/**
 * Takes file uploads
 *
 * @todo Move Issue Attachments to module
 */
function IssueUpload()
{
	global $context, $smcFunc, $sourcedir, $user_info, $txt, $modSettings;

	require_once($sourcedir . '/Subs-Post.php');

	// Not possible
	if (empty($modSettings['projectAttachments']))
		redirectexit(project_get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));

	projectIsAllowedTo('issue_attach');

	$total_size = 0;

	$attachIDs = array();

	foreach ($_FILES['attachment']['tmp_name'] as $n => $dummy)
	{
		if ($_FILES['attachment']['name'][$n] == '')
			continue;

		$total_size += $_FILES['attachment']['size'][$n];
		if (!empty($modSettings['attachmentPostLimit']) && $total_size > $modSettings['attachmentPostLimit'] * 1024)
			fatal_lang_error('file_too_big', false, array($modSettings['attachmentPostLimit']));

		$attachmentOptions = array(
			'poster' => $user_info['id'],
			'name' => $_FILES['attachment']['name'][$n],
			'tmp_name' => $_FILES['attachment']['tmp_name'][$n],
			'size' => $_FILES['attachment']['size'][$n],
			'approved' => true,
		);

		if (createAttachment($attachmentOptions))
		{
			$attachIDs[] = $attachmentOptions['id'];
			if (!empty($attachmentOptions['thumb']))
				$attachIDs[] = $attachmentOptions['thumb'];
		}
		else
		{
			if (in_array('could_not_upload', $attachmentOptions['errors']))
				fatal_lang_error('attach_timeout', 'critical');
			if (in_array('too_large', $attachmentOptions['errors']))
				fatal_lang_error('file_too_big', false, array($modSettings['attachmentSizeLimit']));
			if (in_array('bad_extension', $attachmentOptions['errors']))
				fatal_error($attachmentOptions['name'] . '.<br />' . $txt['cant_upload_type'] . ' ' . $modSettings['attachmentExtensions'] . '.', false);
			if (in_array('directory_full', $attachmentOptions['errors']))
				fatal_lang_error('ran_out_of_space', 'critical');
			if (in_array('bad_filename', $attachmentOptions['errors']))
				fatal_error(basename($attachmentOptions['name']) . '.<br />' . $txt['restricted_filename'] . '.', 'critical');
			if (in_array('taken_filename', $attachmentOptions['errors']))
				fatal_lang_error('filename_exisits');
		}
	}

	if (empty($attachIDs))
		fatal_lang_error('no_files_selected');

	$smcFunc['db_query']('', '
		UPDATE {db_prefix}attachments
		SET id_issue = {int:issue}
		WHERE id_attach IN({array_int:attach})',
		array(
			'issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id,
			'attach' => $attachIDs,
		)
	);

	$posterOptions = array(
		'id' => $user_info['id'],
		'name' => $user_info['name'],
		'username' => $user_info['username'],
		'email' => $user_info['email'],
		'ip' => $user_info['ip'],
	);
	$eventOptions = array(
		'time' => time(),
	);

	$id_event = createTimelineEvent(ProjectTools_IssueTracker_Issue::getCurrent()->id, ProjectTools_Project::getCurrent()->id, 'new_attachment', array('attachments' => $attachIDs), $posterOptions, $eventOptions);

	$rows = array();

	foreach ($attachIDs as $id)
		$rows[] = array(ProjectTools_IssueTracker_Issue::getCurrent()->id, $id, $user_info['id'], $id_event);

	$smcFunc['db_insert']('insert',
		'{db_prefix}issue_attachments',
		array('id_issue' => 'int', 'id_attach' => 'int', 'id_member' => 'int', 'id_event' => 'int',),
		$rows,
		array('id_issue', 'id_attach')
	);

	redirectexit(project_get_url(array('issue' => ProjectTools_IssueTracker_Issue::getCurrent()->id . '.0')));
}

?>