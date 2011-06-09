<?php
/**
 * Main handler for Project Tools
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
class ProjectTools_ChangesParser
{
	/**
	 *
	 */
	public static function Parse($project, $changes, $short = false)
	{
		global $txt, $context, $memberContext;
		
		if (!$project instanceof ProjectTools_Project)
			$project = ProjectTools_Project::getProject($project);
		
		$return = array();

		foreach ($changes as $key => $field)
		{
			list ($field, $old_value, $new_value) = $field;

			// Change values to something meaningful
			if ($field == 'status')
			{
				$old_value = $context['issue_status'][$old_value]['text'];
				$new_value = $context['issue_status'][$new_value]['text'];
			}
			elseif ($field == 'type')
			{
				foreach ($context['issue_trackers'] as $tracker)
					if ($tracker['short'] == $old_value)
					{
						$old_value = $tracker['name'];
						break;
					}
				foreach ($context['issue_trackers'] as $tracker)
					if ($tracker['short'] == $new_value)
					{
						$new_value = $tracker['name'];
						break;
					}
			}
			elseif ($field == 'tracker')
			{
				$old_value = $context['issue_trackers'][$old_value]['name'];
				$new_value = $context['issue_trackers'][$new_value]['name'];
			}
			elseif ($field == 'view_status')
			{
				if (empty($old_value))
					$old_value = $txt['issue_view_status_public'];
				else
					$old_value = $txt['issue_view_status_private'];

				if (empty($new_value))
					$new_value = $txt['issue_view_status_public'];
				else
					$new_value = $txt['issue_view_status_private'];
			}
			elseif ($field == 'version' || $field == 'target_version')
			{
				if (empty($old_value))
					$old_value = $txt['issue_none'];
				else
					$old_value = getVersions(explode(',', $old_value), $project->id, true);

				if (empty($new_value))
					$new_value = $txt['issue_none'];
				else
					$new_value = getVersions(explode(',', $new_value), $project->id, true);
			}
			elseif ($field == 'category')
			{
				if (empty($old_value))
					$old_value = $txt['issue_none'];
				elseif (isset($project->category[$old_value]))
					$old_value = $project->category[$old_value]['name'];

				if (empty($new_value))
					$new_value = $txt['issue_none'];
				elseif (isset($project->category[$new_value]))
					$new_value = $project->category[$new_value]['name'];
			}
			elseif ($field == 'assign')
			{
				loadMemberData(array((int)$old_value, (int)$new_value));

				if (empty($old_value))
					$old_value = $txt['issue_none'];
				elseif (loadMemberContext($old_value))
					$old_value = $memberContext[$old_value]['link'];

				if (empty($new_value))
					$new_value = $txt['issue_none'];
				elseif (loadMemberContext($new_value))
					$new_value = $memberContext[$new_value]['link'];
			}
			elseif ($field == 'tags')
			{
				if (!empty($new_value))
					$changes[] = sprintf($txt['change_add_tag'], implode(', ', $new_value));
				if (!empty($old_value))
					$changes[] = sprintf($txt['change_remove_tag'], implode(', ', $old_value));
					
				continue;
			}
			
			$return[] = sprintf($txt['change_' . ($short ? 'timeline_' : '') . $field], $old_value, $new_value);
		}
		
		return $return;
	}
}

?>