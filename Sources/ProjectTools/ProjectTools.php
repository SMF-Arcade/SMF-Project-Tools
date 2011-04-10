<?php
/**
 * 
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *
 */
class ProjectTools
{
	/**
	* Generates url for project tools pages
	* @param array $params Array of GET parametrs
	* @param int $project 
	*/
	public static function get_url($params = array(), $project = null, $is_admin = false)
	{
		global $scripturl, $modSettings;
		
		$action = !$is_admin ? 'projects' : 'projectadmin';
		
		// Detect project
		if ($project === null && !empty($params))
		{
			if (isset($params['project']))
				$project = $params['project'];
			elseif (!empty($GLOBALS['project']))
				$project = $GLOBALS['project'];
			// Should never happen, log in case it happens
			else
			{
				log_error('Unable to detect project! Please include this in bug report: ' . print_r(debug_backtrace(), true));
				trigger_error('Unable to detect project! See error_log for details');
			}
		}
				
		// Running in "standalone" mode WITH rewrite
		if (!empty($modSettings['projectStandalone']) && $modSettings['projectStandalone'] == 2)
		{
			// Main Page? Too easy
			if (empty($params))
				return $modSettings['projectStandaloneUrl'] . '/';
				
			if (isset($params['project']))
				unset($params['project']);
			
			if (count($params) === 0)
				return $modSettings['projectStandaloneUrl'] . '/' . $project . '/';
		
			$query = '';
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($query))
					$query .= ';';
				else
					$query .= '?';
		
				if (is_int($p))
					$query .= $value;
				else
					$query .= $p . '=' . $value;
			}
		
			return $modSettings['projectStandaloneUrl'] . '/' . $project . '/' . $query;
		}
		// Running in "standalone" mode without rewrite
		elseif (!empty($modSettings['projectStandalone']))
		{
			$return = '';
			
			// Which url shall be base for this?
			$base = !empty($modSettings['projectStandaloneUrl_project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]) ? $modSettings['projectStandaloneUrl_project_' . $project] : (!empty($modSettings['projectStandaloneUrl']) ? $modSettings['projectStandaloneUrl'] : '{SCRIPTURL}');
			
			if (isset($params['project']) && !empty($modSettings['projectStandaloneUrl_project_' . $project]))
				unset($params['project']);
				
			if (count($params) === 0)
			{
				if ($base == '{SCRIPTURL}')
					return $scripturl . '?action=' . $action;
				
				return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl']));
			}
		
			if ($is_admin)
				$params['action'] = $action;
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($return))
					$return .= ';';
				else
					$return .= '?';
		
				if (is_int($p))
					$return .= $value;
				else
					$return .= $p . '=' . $value;
			}
		
			return strtr($base, array('{SCRIPTURL}' => $scripturl, '{BOARDURL}' => $GLOBALS['boardurl'])) . $return;		
		}
		// Running in standard mode
		else
		{
			$return = '';
		
			if (empty($params) || $is_admin)
				$params['action'] = $action;
		
			foreach ($params as $p => $value)
			{
				if ($value === null)
					continue;
		
				if (!empty($return))
					$return .= ';';
				else
					$return .= '?';
		
				if (is_int($p))
					$return .= $value;
				else
					$return .= $p . '=' . $value;
			}
		
			return $scripturl . $return;
		}
	}
	
	/**
	*
	*/
	public static function get_admin_url($params = array(), $project = null)
	{
		return self::get_url($params, $project, true);
	}
}

?>