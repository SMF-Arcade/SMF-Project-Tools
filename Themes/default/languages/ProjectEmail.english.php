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

?>