<?php
// Version: 0.1 Alpha; PatchView

function template_main()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	foreach ($context['diff'] as $file)
	{
		echo '
	<div class="tborder">
		<h3 class="titlebg headerpadding">', $file['name_before'], '</h3>';

		$section = false;

		foreach ($file['actions'] as $action)
		{
			$style = '';

			if (trim($action[1]) == '')
				$action[1] = '&nbsp;';

			if (empty($action[0]))
				$style = '';
			elseif ($action[0] == '@')
			{
				if ($section)
					$data .= '</div>';

				echo '
		<h4 class="catbg headerpadding">' . $action[1] . '</h4>
		<div class="windowbg2 smallpadding" style="font-family: monospace; white-space: pre;">';

				$section = true;

				continue;
			}
			elseif ($action[0] == 'a')
				$style .= ' background-color: #DDFFDD';
			elseif ($action[0] == 'd')
				$style .= ' background-color: #FFDDDD';

			if (!$section)
				echo '
		<div class="windowbg2 smallpadding" style="font-family: monospace">';
			$section = true;

			echo '
			<div style="' . $style . '">' . $action[1] . '</div>';
		}

		echo '
		</div>
	</div>
	<br />';

	}
}

?>