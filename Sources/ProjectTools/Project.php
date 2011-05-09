<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 */

/**
 *
 * @todo Cache queries
 * @todo fix version load
 */
class ProjectTools_Project
{
	/**
	 * Contains project instances
	 */
	private static $_instances = array();
	
	/**
	 *
	 * @return ProjectTools_Project Project Instance
	 */
	static function getProject($id)
	{
		if (!isset(self::$_instances[$id]))
			self::$_instances[$id] = new self($id);
			
		if (self::$_instances[$id]->id === false)
			return false;
		
		return self::$_instances[$id];
	}
	
	/**
	 * Returns current project
	 * 
	 * @return ProjectTools_Project 
	 */
	static function getCurrent()
	{
		global $project;
			
		if (isset($project))
			return self::getProject($project);
		
		return false;
	}
		
	
	/**
	 * Project ID
	 * 
	 * @var boolean|int ID Of Project. False if not found
	 */
	public $id;
	
	/**
	 * @var ProjectTools_Permissions
	 */
	private $permissions;
	
	/**
	 * Name of project
	 */
	public $name;

	/**
	 *
	 */
	public $link;
	
	/**
	 *
	 */
	public $href;
	
	/**
	 *
	 */
	public $description;

	/**
	 *
	 */
	public $long_description;

	/**
	 *
	 */
	public $theme;
	
	/**
	 *
	 */
	public $override_theme = false;
	
	/**
	 *
	 */
	public $categories = array();

	/**
	 *
	 */
	public $groups = array();

	/**
	 *
	 */
	public $trackers = array();

	/**
	 *
	 */
	protected $extensions = array();

	/**
	 *
	 */
	protected $modules = array();
	
	/**
	 *
	 */
	public $developers = array();
	
	/**
	 *
	 */
	public $id_event_mod;
	
	/**
	 *
	 */
	protected $settings = array();

	/**
	 *
	 */
	protected $userSettings = array();
	
	/**
	 *
	 */
	public $versions = array();
	
	/**
	 *
	 */
	public $versions_id = array();
	
	/**
	 *
	 */
	private $queries = array();
	
	/**
	 *
	 */
	public function __construct($project)
	{
		global $smcFunc, $context, $modSettings, $user_info;
		
		$request = $smcFunc['db_query']('', '
			SELECT
				p.id_project, p.id_profile, p.name, p.description, p.long_description, p.trackers, p.modules, p.member_groups,
				p.id_event_mod, p.' . implode(', p.', $context['tracker_columns']) . ', p.project_theme
			FROM {db_prefix}projects AS p
			WHERE p.id_project = {int:project}
			LIMIT 1',
			array(
				'project' => $project,
			)
		);
		$row = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		
		if (!$row)
		{
			$this->id = false;
			
			return;
		}
		
		$this->id = $row['id_project'];
		$this->name = $row['name'];
		
		// TODO: Parsebbc?
		$this->description = /*parse_bbc*/($row['description']);
		$this->long_description = /*parse_bbc*/($row['long_description']);
		
		$this->link = ProjectTools::get_url(array('project' => $row['id_project']));
		$this->href = '<a href="' . $this->link . '">' . $row['name'] . '</a>';
		
		$this->theme = $row['project_theme'];
		$this->override_theme = !empty($row['override_theme']);
		
		$this->groups = explode(',', $row['member_groups']);
		
		foreach (explode(',', $row['modules']) as $module)
			$this->extensions[$module] = ProjectTools_Extensions::getExtension($module);
		
		$this->id_event_mod = $row['id_event_mod'];
		
		$this->permissions = ProjectTools_Permissions::getProfile($row['id_profile']);
		
		//
		$this->_loadCategories();
		$this->_loadDevelopers();
		$this->_loadTrackers($row, explode(',', $row['trackers']));
		$this->_loadSettings();
		$this->_setupQueries();
		
		//
		if (!empty($modSettings['cache_enable']))
		{
			$cache_groups = $user_info['groups'];
			asort($cache_groups);
			$cache_groups = implode(',', $cache_groups);
			// If it's a spider then cache it different.
			if ($user_info['possibly_robot'])
				$cache_groups .= '-spider';
				
			if (($temp = cache_get_data('project_versions:' . $this->id . ':' . $cache_groups, 240)) != null && time() - 240 > $modSettings['settings_updated'])
				list ($this->versions, $this->versions_id) = $temp;
		}
		
		// Load versions
		if (!empty($this->versions))
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_version, id_parent, version_name, release_date, status
				FROM {db_prefix}project_versions AS ver
				WHERE id_project = {int:project}
					AND {query_see_version}
				ORDER BY id_parent, version_name',
				array(
					'project' => $this->id,
				)
			);
	
			while ($row = $smcFunc['db_fetch_assoc']($request))
			{
				if ($row['id_parent'] == 0)
				{
					$this->versions[$row['id_version']] = array(
						'id' => $row['id_version'],
						'name' => $row['version_name'],
						'sub_versions' => array(),
					);
				}
				else
				{
					if (!isset($this->versions['versions'][$row['id_parent']]))
						continue;
	
					$this->versions[$row['id_parent']]['sub_versions'][$row['id_version']] = array(
						'id' => $row['id_version'],
						'name' => $row['version_name'],
						'status' => $row['status'],
						'release_date' => !empty($row['release_date']) ? unserialize($row['release_date']) : array(),
						'released' => $row['status'] >= 4,
					);
				}
	
				$this->versions_id[$row['id_version']] = $row['id_parent'];
			}
			$smcFunc['db_free_result']($request);
	
			cache_put_data('project_versions-' . $this->id . ':' . $cache_groups, array($this->versions, $this->versions_id), 240);
		}
	}
	
	/**
	 *
	 */
	private function _loadDevelopers()
	{
		global $smcFunc;
		
		// Developers
		$request = $smcFunc['db_query']('', '
			SELECT mem.id_member, mem.real_name
			FROM {db_prefix}project_developer AS dev
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = dev.id_member)
			WHERE id_project = {int:project}',
			array(
				'project' => $this->id,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$this->developers[$row['id_member']] = array(
				'id' => $row['id_member'],
				'name' => $row['real_name'],
			);
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	private function _loadTrackers($row, $trackers)
	{
		global $context;
		
		foreach ($trackers as $id)
		{
			$tracker = &$context['issue_trackers'][$id];
			$this->trackers[$id] = array(
				'id' => $id,
				'tracker' => &$context['issue_trackers'][$id],
				'short' => $tracker['short'],
				'open' => $row['open_' . $tracker['short']],
				'closed' => $row['closed_' . $tracker['short']],
				'total' => $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']],
				'progress' => round(($row['closed_' . $tracker['short']] / max(1, $row['open_' . $tracker['short']] + $row['closed_' . $tracker['short']])) * 100, 2),
				'link' => ProjectTools::get_url(array('project' => $row['id_project'], 'area' => 'issues', 'tracker' => $tracker['short'])),
			);
			unset($tracker);
		}
	}
	
	/**
	 * 
	 */
	private function _loadCategories()
	{
		global $smcFunc;
		
		// Category
		$request = $smcFunc['db_query']('', '
			SELECT id_category, category_name
			FROM {db_prefix}issue_category AS cat
			WHERE id_project = {int:project}',
			array(
				'project' => $this->id,
			)
		);

		while ($row = $smcFunc['db_fetch_assoc']($request))
			$this->categories[$row['id_category']] = array(
				'id' => $row['id_category'],
				'name' => $row['category_name']
			);
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	private function _loadSettings()
	{
		global $smcFunc, $user_info;
		
		// Load Project Settings
		$request = $smcFunc['db_query']('', '
			SELECT id_member, variable, value
			FROM {db_prefix}project_settings
			WHERE id_project = {int:project}
				AND (id_member = {int:no_member} OR id_member = {int:current_member})
			ORDER BY id_member',
			array(
				'project' => $this->id,
				'no_member' => 0,
				'current_member' => $user_info['id'],
			)
		);
	
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			if ($row['id_member'] === 0)
				$this->settings[$row['variable']] = $row['value'];
			else
				$this->userSettings[$row['variable']] = $row['value'];
		}
		$smcFunc['db_free_result']($request);
	}
	
	/**
	 *
	 */
	private function _setupQueries()
	{
		global $user_info;
		
		if ($this->isDeveloper() || allowedTo('project_admin') || $this->permissions->allowedTo('view_issue_private'))
		{
			$this->queries['see_issue_private'] = '1=1';
			$this->queries['see_issue'] = '1=1';
		}
		else
		{
			if ($user_info['is_guest'])
				$this->queries['see_issue_private'] = '0=1';
			else
				$this->queries['see_issue_private'] = 'i.id_reporter = ' . $user_info['id'];
				
			$this->queries['see_issue'] = '(FIND_IN_SET(' . implode(', i.versions) OR FIND_IN_SET(', $this->versions_id) . ', i.versions) AND ' . $this->queries['see_issue_private'] . ')';
		}
	}
	
	/**
	 *
	 */
	public function getModules()
	{
		// Load modules
		if (empty($this->modules))
		{
			// Load Modules
			foreach ($this->extensions as $id => $ext)
			{
				if (!$ext)
					continue;
				$module = $ext->getModule();
				$this->modules[$module] = new $module($this);
			}		
		}
		
		return $this->modules;
	}
	
	/**
	 *
	 */
	public function getSetting($setting, $allowUser = true)
	{
		if ($allowUser && isset($this->updateSettings[$setting]))
			return $this->updateSettings[$setting];
		elseif (isset($this->settings[$setting]))
			return $this->settings[$setting];
		else
			return false;
	}
	
	/**
	 *
	 */
	public function getQuery($query)
	{
		if (isset($this->queries[$query]))
			return $this->queries[$query];
		
		trigger_error('ProjectTools: Unknown query limiter (' . $query . ')', E_FATAL_ERROR);
	}
	
	/**
	 *
	 */
	function isDeveloper($id_member = null)
	{
		global $user_info;
		
		if ($id_member === null)
			$id_member = $user_info['id'];
		
		return isset($this->developers[$id_member]);
	}
	
	/**
	 * Checks whatever permission is allowed in this project
	 */
	function allowedTo($permission)
	{
		global $context, $user_info;

		// Admins and developers can do anything
		if (allowedTo('project_admin') || ProjectTools_Project::getCurrent()->isDeveloper())
			return true;
	
		return $this->permissions->allowedTo($permission);
	}
	
	/**
	 * Checks if permission is allowed in this project and shows error page if not
	 */
	function isAllowedTo($permission)
	{
		global $txt, $user_info;

		if (!$this->allowedTo($permission))
		{
			if ($user_info['is_guest'])
				is_not_guest($txt['cannot_project_' . $permission]);
	
			fatal_lang_error('cannot_project_' . $permission, false);
	
			// Getting this far is a really big problem, but let's try our best to prevent any cases...
			trigger_error('Hacking attempt...', E_USER_ERROR);
		}
	}
	
	/**
	 * Updates project settings
	 */
	function updateSettings($settings, $is_user_setting = false)
	{
		global $smcFunc, $user_info;
			
		$rows = array();
		
		foreach ($settings as $variable => $value)
		{
			$rows[] = array($this->id, $is_user_setting ? $user_info['id'] : 0, $variable, $value);
			if ($is_user_setting)
				$this->settings[$variable] = $value;
			else
				$this->userSettings[$variable] = $value;
		}
		
		$smcFunc['db_insert']('replace',
			'{db_prefix}project_settings',
			array(
				'id_project' => 'int',
				'id_member' => 'int',
				'variable' => 'varchar-255',
				'value' => 'string',
			),
			$rows,
			array('id_project', 'variable')
		);
	}
}

?>