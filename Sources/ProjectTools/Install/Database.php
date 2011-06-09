<?php
/**
 *
 * @package ProjectTools
 * @subpackage Install
 */

/**
 *
 */
class ProjectTools_Install_Database extends Madjoki_Install_Database
{
	/**
	 *
	 */
	protected $tables = array(
		//
		'projects' => array(
			'name' => 'projects',
			// Rename old columns
			'rename' => array(
				'id_comment_mod' => 'id_event_mod',
			),
			// Columns
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'description',
					'type' => 'text',
				),
				array(
					'name' => 'long_description',
					'type' => 'text',
				),
				array(
					'name' => 'trackers',
					'type' => 'varchar',
					'size' => 255,
					'default' => '1,2',
				),
				array(
					'name' => 'member_groups',
					'type' => 'varchar',
					'size' => 255,
					'default' => '-1,0,2',
				),
				array(
					'name' => 'open_bug',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'closed_bug',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'open_feature',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'closed_feature',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'id_event_mod',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'id_profile',
					'type' => 'int',
					'unsigned' => true,
					'default' => 1,
				),
				// Project Theme
				array(
					'name' => 'project_theme',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'override_theme',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				// Show in board index
				array(
					'name' => 'id_category',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				// But where is cat?
				array(
					'name' => 'cat_position',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_project')
				),
			)
		),
		// Developers
		'project_developer' => array(
			'name' => 'project_developer',
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_member', 'id_project')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		),
		// Trackers
		'project_trackers' => array(
			'name' => 'project_trackers',
			'columns' => array(
				array(
					'name' => 'id_tracker',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'short_name',
					'type' => 'varchar',
					'size' => 10,
				),
				array(
					'name' => 'tracker_name',
					'type' => 'varchar',
					'size' => 255,
				),
				array(
					'name' => 'plural_name',
					'type' => 'varchar',
					'size' => 255,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_tracker')
				),
			),
		),
		// Project Permission Profiles
		'project_profiles' => array(
			'name' => 'project_profiles',
			'columns' => array(
				array(
					'name' => 'id_profile',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'profile_name',
					'type' => 'varchar',
					'size' => 255,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_profile')
				),
			),
		),
		// Permissions
		'project_permissions' => array(
			'name' => 'project_permissions',
			'columns' => array(
				array(
					'name' => 'id_profile',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_group',
					'type' => 'int',
				),
				array(
					'name' => 'permission',
					'type' => 'varchar',
					'size' => 30,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_profile', 'id_group', 'permission')
				),
				array(
					'name' => 'id_group',
					'type' => 'index',
					'columns' => array('id_profile', 'id_group')
				),
			),
		),
		// Versions
		'project_versions' => array(
			'name' => 'project_versions',
			'columns' => array(
				array(
					'name' => 'id_version',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_parent',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
				array(
					'name' => 'version_name',
					'type' => 'varchar',
					'default' => '',
					'size' => 255,
				),
				array(
					'name' => 'status',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'description',
					'type' => 'text',
				),
				array(
					'name' => 'release_date',
					'type' => 'varchar',
					'default' => '',
					'size' => 255,
				),
				array(
					'name' => 'member_groups',
					'type' => 'varchar',
					'default' => '-1,0,2',
					'size' => 255,
				),
				array(
					'name' => 'permission_inherit',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'open_bug',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'closed_bug',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'open_feature',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'closed_feature',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_version')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		),
		// Project Settings
		'project_settings' => array(
			'name' => 'project_settings',
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'variable',
					'type' => 'varchar',
					'size' => 255,
				),
				array(
					'name' => 'value',
					'type' => 'text',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_project', 'id_member', 'variable')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
				array(
					'name' => 'id_project_id_member',
					'type' => 'index',
					'columns' => array('id_project', 'id_member')
				),
			)
		),
		// Project Timeline
		'project_timeline' => array(
			'name' => 'project_timeline',
			'columns' => array(
				array(
					'name' => 'id_event',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'poster_name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'poster_email',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'poster_ip',
					'type' => 'varchar',
					'size' => 60,
					'default' => '',
				),
				array(
					'name' => 'versions',
					'type' => 'varchar',
					'size' => 255,
					'default' => '0',
				),
				array(
					'name' => 'event',
					'type' => 'varchar',
					'size' => 15,
					'default' => '',
				),
				array(
					'name' => 'event_time',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'event_data',
					'type' => 'text',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_event')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			),
		),
		// Categories/modules table
		'issue_category' => array(
			'name' => 'issue_category',
			'columns' => array(
				array(
					'name' => 'id_category',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'category_name',
					'type' => 'varchar',
					'size' => 30,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_category')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		),
		// Issues table
		'issues' => array(
			'name' => 'issues',
			// Rename old columns
			'rename' => array(
				'id_comment_mod' => 'id_event_mod',
			),
			// Columns
			'columns' => array(
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'auto' => true,
				),
				array(
					'name' => 'id_project',
					'type' => 'int',
				),
				array(
					'name' => 'subject',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'id_tracker',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'id_category',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_assigned',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_reporter',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_updater',
					'type' => 'int',
					'default' => 0,
				),			
				array(
					'name' => 'id_issue_event_first',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_issue_event_last',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_event_mod',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'versions',
					'type' => 'varchar',
					'size' => 255,
					'default' => '0',				
				),
				array(
					'name' => 'versions_fixed',
					'type' => 'varchar',
					'size' => 255,
					'default' => '0',
				),
				array(
					'name' => 'status',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'created',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'updated',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'priority',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'replies',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'private_issue',
					'type' => 'int',
					'default' => 0,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project'),
				),
				array(
					'name' => 'versions',
					'type' => 'index',
					'columns' => array('versions'),
				),
			),
		),
		// Data for custom fields
		'issue_custom_data' => array(
			'name' => 'issue_custom_data',
			// Columns
			'columns' => array(
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'auto' => true,
				),
				array(
					'name' => 'variable',
					'type' => 'varchar',
					'size' => 255,
				),
				array(
					'name' => 'value',
					'type' => 'text',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue', 'variable')
				),
				array(
					'name' => 'id_issue',
					'type' => 'index',
					'columns' => array('id_issue'),
				),
			),
		),
		// Issue events table
		'issue_events' => array(
			'name' => 'issue_events',
			// Columns
			'columns' => array(
				array(
					'name' => 'id_issue_event',
					'type' => 'int',
					'auto' => true,
				),
				array(
					'name' => 'id_issue',
					'type' => 'int',
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_comment',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_event',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'id_event_mod',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'event_time',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'poster_name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'poster_email',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'poster_ip',
					'type' => 'varchar',
					'size' => 60,
					'default' => '',
				),
				array(
					'name' => 'changes',
					'type' => 'text',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue_event')
				),
				array(
					'name' => 'id_issue',
					'type' => 'index',
					'columns' => array('id_issue')
				),
				array(
					'name' => 'id_event',
					'type' => 'index',
					'columns' => array('id_event')
				),
			),	
		),
		// Comments
		'issue_comments' => array(
			'name' => 'issue_comments',
			// Rename old columns
			'rename' => array(
				'id_comment_mod' => 'id_event_mod',
			),
			// Columns
			'columns' => array(
				array(
					'name' => 'id_comment',
					'type' => 'int',
					'auto' => true,
				),
				array(
					'name' => 'edit_time',
					'type' => 'int',
					'default' => 0,
				),
				array(
					'name' => 'edit_name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'body',
					'type' => 'text',
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_comment')
				),
			)
		),
		// Tags
		'issue_tags' => array(
			'name' => 'issue_tags',
			'columns' => array(
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'tag',
					'type' => 'varchar',
					'size' => 35,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue', 'tag')
				),
			),
		),
		// Log table for notify requests
		'log_notify_projects' => array(
			'name' => 'log_notify_projects',
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'sent',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_project', 'id_issue', 'id_member')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			),
		),
		// Log tables for read marks
		'log_projects' => array(
			'name' => 'log_projects',
			// Rename old columns
			'rename' => array(
				'id_comment' => 'id_event',
			),
			// Columns
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_event',
					'type' => 'int',
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_project', 'id_member')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		),
		// Log tables for read marks
		'log_project_mark_read' => array(
			'name' => 'log_project_mark_read',
			// Columns
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_event',
					'type' => 'int',
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_project', 'id_member')
				),
				array(
					'name' => 'id_project',
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		),
		// Log for issues
		'log_issues' => array(
			'name' => 'log_issues',
			// Rename old columns
			'rename' => array(
				'id_comment' => 'id_event',
			),
			// Columns
			'columns' => array(
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'id_event',
					'type' => 'int',
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue', 'id_member')
				),
				array(
					'name' => 'id_issue',
					'type' => 'index',
					'columns' => array('id_issue')
				),
			)
		),
		// SMF Attachements table
		'attachments' => array(
			'name' => 'attachments',
			'smf' => true,
			'columns' => array(
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'name' => 'id_issue',
					'type' => 'index',
					'columns' => array('id_issue')
				),
			)
		),
		// Issue Attachements
		'issue_attachments' => array(
			'name' => 'issue_attachments',
			'columns' => array(
				array(
					'name' => 'id_issue',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
				array(
					'name' => 'id_attach',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
				array(
					'name' => 'id_member',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
				array(
					'name' => 'id_event',
					'type' => 'int',
					'default' => 0,
					'unsigned' => true,
				),
			),
			'indexes' => array(
				array(
					'type' => 'primary',
					'columns' => array('id_issue', 'id_attach')
				),
				array(
					'name' => 'id_issue',
					'type' => 'index',
					'columns' => array('id_issue')
				),
			)
		),		
	);
}

?>