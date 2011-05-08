<?php
/**
 * Template for Project.php
 *
 * @package core
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 * @see Project.php
 */

function template_project_above()
{
	global $txt, $context;
	
	echo '
	<a name="top"></a>';
	
	if (!empty($context['project_tabs']))
	{
		echo '
	<div id="adm_container">
		<ul class="admin_menu project_menu">';

		// Print out all the items in this tab.
		$s = 0;
		$num_tabs = count($context['project_tabs']['tabs']);
		
		foreach ($context['project_tabs']['tabs'] as $button)
		{
			$s++;
			$is_last = $s == $num_tabs;
			
			if ($button['is_selected'])
				echo '
				<li class="', $s == 1 ? 'first ': '', 'chosen', $is_last ? ' last last_chosen' : '', '"><h4><a href="', $button['href'], '">', $button['title'] , '</a></h4>';
			else
				echo '
				<li', $s == 1 ? ' class="first"': '', $is_last ? ' class="last"' : '', '><h4><a href="', $button['href'], '">', $button['title'] , '</a></h4>';
			
			if (!empty($button['sub_buttons']))
			{
				echo '
			<ul>';
			
				foreach ($button['sub_buttons'] as $childbutton)
					echo '
				<li>
					<a href="', $childbutton['href'], '"', !empty($childbutton['is_selected']) ? ' class="chosen"' : '', isset($childbutton['target']) ? ' target="' . $childbutton['target'] . '"' : '', '>
						<span', isset($childbutton['is_last']) ? ' class="last"' : '', '>', $childbutton['title'], !empty($childbutton['sub_buttons']) ? '...' : '', '</span>
					</a>
				</li>';
					
				echo '
			</ul>';
			}
			
			echo '
		</li>';
			
			$i++;
		}
		
		echo '
	</ul></div>
	<br class="clear" />
	<div class="cat_bar">
		<h3 class="catbg">
		', $context['project_tabs']['title'], '
		</h3>
	</div>
	<p class="description">
		', $context['project_tabs']['description'], '
	</p>';
	
		if (isset($context['project_sub_tabs']))
		{
			echo '
	<div class="dropmenu"><ul>';
	
			// Print out all the items in this tab.
			$i = 1;
			$num_tabs = count($context['project_sub_tabs']);
			foreach ($context['project_sub_tabs'] as $tab)
			{
				echo '
		<li>
			<a href="', $tab['href'], '" class="', !empty($tab['is_selected']) ? 'active ' : '', 'firstlevel">
				<span class="firstlevel', $i == $num_tabs ? ' last' : '', '">', $tab['title'], '</span>
			</a>
		</li>';
				
				$i++;
			}
			
			echo '
	</ul></div>
	<br class="clear" />';
		}	
	}
}

function template_project_below()
{
	global $txt, $context;

	// Print out copyright and version. Removing copyright is not allowed by license
	echo '
	<a name="bot"></a>
	<div id="project_bottom" class="smalltext" style="text-align: center;">
		Powered by: <a href="http://www.smfproject.net/" target="_blank">SMF Project Tools ', ProjectTools::$version, '</a> &copy; <a href="http://www.madjoki.com/" target="_blank">Niko Pahajoki</a> 2007-2010
	</div>';
}

?>