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

/*
	!!!
*/

global $extensionInformation;

$extensionInformation = array(
	'title' => 'General',
	'version' => '0.5',
	'api_version' => 1,
);

register_project_feature('general', 'ProjectModule_General');

class ProjectModule_Base
{
	// Define subactions this handles by default
	public $subActions = array();
	
	// Default constructor
	function __construct()
	{
		$this->subActions = array('main' => array('callback' => array($this, 'mainView')));
	}
	
	public function registersubAction($sa, $data)
	{
		$this->subActions[$sa] = $data;
	}
	
	function beforeSubaction(&$subaction)
	{
		global $sourcedir;
		
		// Check that subaction exists, if not use "main"
		if (!isset($this->subActions[$subaction]))
			$subaction = 'main';
		
		// No main subaction? Use first then
		if (!isset($this->subActions[$subaction]) && count($this->subActions) > 0)
			list ($subaction, ) = array_keys($this->subActions);
		elseif (!isset($this->subActions[$subaction]))
			trigger_error(__CLASS__ . ' doesn\'t contain any subaction', E_USER_ERROR);
			
		// Check permission to subaction?
		if (isset($this->subActions[$subaction]['permission']))
			isAllowedTo($this->subActions[$subaction]['permission']);
		if (isset($this->subActions[$subaction]['project_permission']))
			projectIsAllowedTo($this->subActions[$subaction]['project_permission']);
		
		// File required?
		if (isset($this->subActions[$subaction]['file']))
			require_once($sourcedir . '/' . $this->subActions[$subaction]['file']);
	}
	
	final function main($subaction)
	{
		call_user_func($this->subActions[$subaction]['callback']);
	}
}

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
		
		redirectexit(project_get_url('project' => $project));
	}
	
	function RegisterProjectArea()
	{
		return array(
			'area' => 'main', 'tab' => 'main',
		);
	}
}

?>