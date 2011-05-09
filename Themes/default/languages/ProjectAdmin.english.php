<?php
// Version: 0.5; ProjectAdmin

// Important! Before editing these language files please read the text at the topic of index.english.php.

// General
$txt['project_tools_admin'] = 'Project Tools';
$txt['project_tools_admin_desc'] = 'Here you can edit some settings of SMF Project Tools';
$txt['project_latest_news'] = 'Latest News';
$txt['project_news_unable_to_connect'] = 'Unable to connect to <a href="http://www.smfproject.net">SMF Project.net</a> for latest news...';
$txt['project_version_info'] = 'Version Information';
$txt['project_installed_version'] = 'Installed version';
$txt['project_latest_version'] = 'Latest version';

// Settings
$txt['project_settings'] = 'Settings';
$txt['project_settings_title'] = 'Project Tools Settings';

$txt['projectEnabled'] = 'Enable Project Tools';
$txt['projectAttachments'] = 'Enable Attachments';
$txt['issuesPerPage'] = 'Issues Per Page';
$txt['commentsPerPage'] = 'Comments Per Page';

$txt['setting_project_access'] = 'Groups allowed to access Project Tools';
$txt['setting_project_access_subtext'] = 'You need to set permission for individual projects to make users able to see them.';
$txt['setting_project_admin'] = 'Groups allowed to administrate Project Tools';

// Maintenance
$txt['project_maintain_done'] = 'The maintenance task \'%1$s\' was executed successfully.';
$txt['project_maintenance_repair'] = 'Repair';
$txt['project_maintenance_repair_info'] = 'Find and repair errors';
$txt['project_maintenance_upgrade'] = 'Upgrade Project Tools database content for 0.6';
$txt['project_maintain_run_now'] = 'Run';

// Repair maintenance action
$txt['repair_no_errors'] = 'Congratulations, no errors found!  Thanks for checking.';
$txt['errors_list'] = 'Errors found';
$txt['fix_errors'] = 'Do you want to try to fix errors?';

$txt['repair_step_general_maintenance'] = 'General maintenance';
$txt['repair_step_comments_not_linked'] = 'Comments not linked to events';
$txt['repair_step_events_without_poster'] = 'Events without poster info';
$txt['repair_step_not_needed_events'] = 'Events no longer needed';

$txt['error_comment_not_linked'] = 'Comment #%1$d not linked with event';
$txt['error_issue_info_event'] = 'Event #%1$d is linked to non-existing issue';
$txt['error_missing_poster_info_event'] = 'Event #%1$d is missing poster info';
$txt['error_unnecessary_event'] = 'Unnecessary Event #%1$d';

// Extensions
$txt['extension_enable'] = 'Enable';
$txt['extension_name'] = 'Name';
$txt['extension_version'] = 'Version';
$txt['extension_api_version'] = 'Api Version';

$txt['extension_modules'] = 'Provides Following Modules';

// Manage Projects
$txt['manage_projects_description'] = 'Here you can create and edit projects';

// Project List
$txt['header_project'] = 'Project';

//
$txt['delete_project'] = 'Delete Project';
$txt['pdelete_warning'] = 'Deleting project will delete permanently everything related to it, do you still to continue?';
$txt['confirm_project_delete'] = 'Confirm delete of project';
$txt['confirm_delete'] = 'Confirm';
$txt['cancel_delete'] = 'Cancel';

// Manage Project Permissions
$txt['manage_project_permissions_description'] = 'Here you can create and edit project permission profiles';
$txt['membergroups_name'] = 'Name';
$txt['membergroups_members_top'] = 'Members';

// Profile List
$txt['header_profile'] = 'Profile';
$txt['header_used_by'] = 'Used By';
$txt['header_delete'] = 'Delete';
$txt['used_by_projects'] = '%s projects';
$txt['not_in_use'] = 'Not in use';
$txt['profiles_delete_selected'] = 'Remove selected';

// New Profile
$txt['title_new_profile'] = 'New Project Profile';
$txt['new_profile'] = 'New Profile';
$txt['profile_name'] = 'Profile Name';
$txt['profile_copy_from'] = 'Copy Permissions From';
$txt['profile_create'] = 'Create';

// Edit Profile
$txt['title_edit_profile'] = 'Edit Project Profile: %s';
$txt['edit_profile'] = 'Project Profile "%s"';
$txt['header_group_name'] = 'Group';

// Edit Profile Permissions
$txt['title_edit_profile_group'] = 'Edit Permissions for Group "%2$s" in Profile "%1$s"';
$txt['edit_profile_group'] = 'Permissions for group "%s"';

// Project Permission names
$txt['permissionname_project_issue_view'] = 'View Issues';
$txt['permissionname_project_issue_view_private'] = 'View All Private Issues';
$txt['permissionname_project_issue_report'] = 'Report Issues';
$txt['permissionname_project_issue_comment'] = 'Comment Issues';
$txt['permissionname_project_issue_update_own'] = 'Update their own Issues';
$txt['permissionname_project_issue_update_any'] = 'Update any Issue';
$txt['permissionname_project_issue_attach'] = 'Add Attachements to Issues';
$txt['permissionname_project_issue_moderate'] = 'Edit Issues';
$txt['permissionname_project_issue_move'] = 'Move Issue to another Project';

$txt['permissionname_project_delete_comment_own'] = 'Delete their own comments';
$txt['permissionname_project_delete_comment_any'] = 'Delete any comment';
$txt['permissionname_project_edit_comment_own'] = 'Modify their own comments';
$txt['permissionname_project_edit_comment_any'] = 'Modify any comment';

$txt['permission_save'] = 'Save Changes';

// Upgrade
$txt['upgrade_no_tracker'] = 'There is no tracker &quot;%s&quot;, please create it and re-run upgrade';

// Errors
$txt['admin_no_projects'] = 'No Projects Created';
$txt['no_issue_types'] = 'No issue types selected';
$txt['profile_not_found'] = 'Profile not found';
$txt['profile_in_use'] = 'Profile is in use and can\'t be removed!';

// User side admin
// These things to be moved to another file in 0.6?



?>