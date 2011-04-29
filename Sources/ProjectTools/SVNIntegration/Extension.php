<?php
/**
 * 
 *
 * @package SVNIntegration
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

/**
 *
 */
class ProjectTools_SVNIntegration_Extension extends ProjectTools_ExtensionBase
{
	/**
	 *
	 */
	public function getExtensionInfo()
	{
		return array(
			'title' => 'SVN Integration',
			'version' => '0.6',
			'api_version' => 2,
		);
	}
	
	/**
	 *
	 */
	public function getModule()
	{
		return 'ProjectTools_SVNIntegration_Module';
	}
	
	/**
	 *
	 */
	public function onActivate()
	{
		global $smcFunc;
		
		db_extend('packages');
		
		$smcFunc['db_create_table'](
			'{db_prefix}projects_svn_repository',
			array(
				array(
					'name' => 'id_repository',
					'type' => 'int',
					'auto' => true,
					'unsigned' => true,
				),
				array(
					'name' => 'id_project',
					'type' => 'int',
					'unsigned' => true,
					'default' => 0,
				),
				array(
					'name' => 'repo_name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'name',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_read',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_write',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_deny',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
			),
			array(
				array(
					'type' => 'primary',
					'columns' => array('id_repository')
				),
				array(
					'type' => 'index',
					'columns' => array('id_project')
				),
			)
		);
		
		$smcFunc['db_create_table'](
			'{db_prefix}projects_svn_branches',
			array(
				array(
					'name' => 'id_repository',
					'type' => 'int',
					'unsigned' => true,
				),
				array(
					'name' => 'path',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'branch',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_read',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_write',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
				array(
					'name' => 'permission_deny',
					'type' => 'varchar',
					'size' => 255,
					'default' => '',
				),
			),
			array(
				array(
					'type' => 'index',
					'columns' => array('id_repository')
				),
				array(
					'type' => 'unique',
					'columns' => array('id_repository', 'path')
				),	
			)
		);
	}
	
	/**
	 *
	 */
	public function onDisable()
	{
		log_error('testing [onDisable]');
	}
}

?>