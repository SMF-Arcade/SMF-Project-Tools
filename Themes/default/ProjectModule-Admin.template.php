<?php
// Version: 0.5; ProjectModule-Admin

function template_ProjectModuleAdmin_above()
{
	global $context, $settings, $options, $txt, $modSettings;

	if (!empty($context['project_admin_tabs']))
	{
		echo '
	<div class="buttonlist align_left"><ul>';

		// Print out all the items in this tab.
		$i = 1;
		$num_tabs = count($context['project_admin_tabs']['tabs']);
		foreach ($context['project_admin_tabs']['tabs'] as $tab)
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

function template_main()
{
	
}

function template_ProjectModuleAdmin_below()
{
	
}

?>