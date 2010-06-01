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
$txt['project_maintenance_upgrade'] = 'Upgrade Project Tools database content for 0.5';
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

// Edit Project
$txt['edit_project'] = 'Edit Project';
$txt['project_name'] = 'Project Name';

$txt['project_description'] = 'Short description for project';
$txt['project_description_desc'] = 'Displayed on board index';
$txt['project_description_long'] = 'Description';
$txt['project_description_long_desc'] = 'Description displayed on Project Page';

$txt['project_profile'] = 'Permissions Profile';

$txt['project_theme'] = 'Project Theme';
$txt['project_theme_default'] = '(overall forum default)';
$txt['project_theme_override'] = 'Override member\'s theme';

$txt['project_board_index'] = 'Show Project in Board Index';
$txt['project_board_index_desc'] = '';
$txt['project_board_index_dont_show'] = 'Don\'t show';
$txt['project_board_index_before'] = 'Before Boards';
$txt['project_board_index_after'] = 'After Boards';

$txt['project_membergroups'] = 'Membergroups';
$txt['project_membergroups_desc'] = 'Select Membergroups allowed to see this project';
$txt['regular_members'] = 'Regular Members';
$txt['guests'] = 'Guests';
$txt['pgroups_post_group'] = 'This is post based group.';
$txt['check_all'] = 'Select All';

$txt['project_developers'] = 'Developers';
$txt['developer_add'] = 'Add';
$txt['developer_remove'] = 'Remove';

$txt['project_trackers'] = 'Issue Types';
$txt['project_trackers_desc'] = 'Select issue types you want to use in this project';

$txt['project_modules'] = 'Modules';
$txt['project_modules_desc'] = 'Select modules which are going to be available for this project';

$txt['project_submit'] = 'Submit';
$txt['delete_project'] = 'Delete Project';
$txt['pdelete_warning'] = 'Deleting project will delete permanently everything related to it, do you still to continue?';
$txt['confirm_project_delete'] = 'Confirm delete of project';
$txt['confirm_delete'] = 'Confirm';
$txt['cancel_delete'] = 'Cancel';

// Manage Categories
$txt['manage_project_category_description'] = 'Here you can create and edit issue categories';

// Category List
$txt['header_category'] = 'Category';
$txt['edit_category'] = 'Edit Category';

// Edit Category
$txt['new_category'] = 'New category';
$txt['category_name'] = 'Category name';
$txt['delete_category'] = 'Delete Category';
$txt['cdelete_warning'] = 'Are you sure?';

// Version List
$txt['header_version'] = 'Version';
$txt['new_version_group'] = 'New Version Group';
$txt['new_version'] = 'New Version';
$txt['edit_version'] = 'Edit Version';

// Edit Version
$txt['version_name'] = 'Name';
$txt['version_description'] = 'Description for version';
$txt['version_description_desc'] = 'Displayed on Project Pages';
$txt['version_release_date'] = 'Release date';
$txt['version_release_date_desc'] = 'Format is dd.mm.yy';
$txt['version_status'] = 'Status';
$txt['version_membergroups'] = 'Membergroups';
$txt['version_inherit_permission'] = 'Inherit membergroups permissions from project or parent version';
$txt['version_membergroups_desc'] = 'Select Membergroups allowed to see this version';
$txt['delete_version'] = 'Delete Version';
$txt['vdelete_warning'] = 'Deleting version will remove any releases and issues assigned to it, do you still to continue?';

// Manage Project Permissions
$txt['manage_project_permissions_description'] = 'Here you can create and edit projects';
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

?>