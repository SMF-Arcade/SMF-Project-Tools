<?php
// Version: 0.5; ProjectEmail

$txt['emails'] = !isset($txt['emails']) ? array() : $txt['emails'];

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

$txt['emails']['notification_project_delete_issue'] = array(
		/*
			@additional_params: notification_project_delete_issue
			@description:
		*/
		'subject' => 'Deleted Issue: {ISSUENAME}',
		'body' => 'Issue \'{ISSUENAME}\' has been deleteed from a project you are watching.

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

You can see comment at
{COMMENTLINK}

Unsubscribe to changes from this issue by using this link:
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

You can see comment at
{COMMENTLINK}

{BODY}

Unsubscribe to changes from this issue by using this link:
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

{UPDATES}

Unsubscribe to changes from this issue by using this link:
{UNSUBSCRIBELINK}

{REGARDS}'
);

?>