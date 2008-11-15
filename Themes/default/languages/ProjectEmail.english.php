<?php
// Version: 0.2; ProjectEmail

if (!isset($txt['emails']))
	$txt['emails'] = array();

$txt['emails']['notification_project_new_issue'] = array(
		/*
			@additional_params: notification_project_new_issue
			@description:
		*/
		'subject' => 'New Issue: {ISSUENAME}',
		'body' => 'A new issue, \'{ISSUENAME}\' has been posted on a project you are watching.

You can see it at
{ISSUELINK}

Unsubscribe to new issues from this project by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

$txt['emails']['notification_project_new_issue_body'] = array(
		/*
			@additional_params: notification_project_new_issue_body
			@description:
		*/
		'subject' => 'New Issue: {ISSUENAME}',
		'body' => 'A new issue, \'{ISSUENAME}\' has been posted on a project you are watching.

You can see it at
{ISSUELINK}

{DETAILS}

Unsubscribe to new issues from this project by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

$txt['emails']['notification_project_new_comment'] = array(
		/*
			@additional_params: notification_project_new_comment
			@description:
		*/
		'subject' => 'New Comment: {ISSUENAME}',
		'body' => 'A new comment for \'{ISSUENAME}\' has been posted.

You can see it at
{ISSUELINK}

Unsubscribe to new changes from this issue by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

$txt['emails']['notification_project_new_comment_body'] = array(
		/*
			@additional_params: notification_project_new_comment_body
			@description:
		*/
		'subject' => 'New Comment: {ISSUENAME}',
		'body' => 'A new comment for \'{ISSUENAME}\' has been posted.

You can see it at
{ISSUELINK}

{BODY}

Unsubscribe to new changes from this issue by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

$txt['emails']['notification_project_update_issue'] = array(
		/*
			@additional_params: notification_project_update_issue
			@description:
		*/
		'subject' => 'Issue has been updated: {ISSUENAME}',
		'body' => 'Issue \'{ISSUENAME}\' has been updated.

You can see issue at
{ISSUELINK}

Unsubscribe to new changes from this issue by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

$txt['emails']['notification_project_update_issue_body'] = array(
		/*
			@additional_params: notification_project_update_issue
			@description:
		*/
		'subject' => 'Issue has been updated: {ISSUENAME}',
		'body' => 'Issue \'{ISSUENAME}\' has been updated.

You can see issue at
{ISSUELINK}

{BODY}

Unsubscribe to new changes from this issue by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

?>