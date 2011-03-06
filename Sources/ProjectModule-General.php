<?php
/**
 * Contains base for modules to extend and main project page
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.5
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
	!!!
*/

global $extensionInformation;

$extensionInformation = array(
	'title' => 'General',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('general', 'ProjectModule_General');

/**
 * Project View
 *
 * @todo Move this to it's own file?
 */
class ProjectModule_General extends ProjectModule_Base
{
	function __construct()
	{
		$this->subActions = array(
			'main' => array(
				'file' => 'ProjectView.php',
				'callback' => 'ProjectView',
			),
			'subscribe' => array(
				'file' => 'ProjectView.php',
				'callback' => 'ProjectSubscribe',
			),
			'markasread' => array(
				'callback' => array($this, 'MarkRead')
			)
		);
	}
	
	
	function MarkRead()
	{
		global $project;
		
		markProjectsRead($project, isset($_REQUEST['unread']));
		
		redirectexit(project_get_url(array('project' => $project)));
	}
	
	function RegisterProjectArea()
	{
		return array(
			'area' => 'main', 'tab' => 'main',
		);
	}
}

?>