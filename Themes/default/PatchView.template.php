<?php
// Version: 0.1 Alpha; PatchView

function template_main()
{
	global $context, $settings, $options, $scripturl, $txt, $modSettings;

	foreach ($context['diff'] as $file)
	{
		echo '
	<div class="tborder patch">
		<h3 class="titlebg headerpadding">', $file['name_before'], '</h3>';

		$section = false;

		foreach ($file['actions'] as $action)
		{
			$style = '';

			if (trim($action[1]) == '')
				$action[1] = '&nbsp;';
			else
				$action[1] = htmlspecialchars($action[1]);

			if (empty($action[0]))
				$style = 'white-space: pre;';
			elseif ($action[0] == '@')
			{
				if ($section)
					echo '
			</dl>
		</div>';

				echo '
		<h4 class="catbg headerpadding">' . $action[1] . '</h4>
		<div class="windowbg2 smallpadding" style="font-family: monospace;">';

				$section = true;

				continue;
			}
			elseif ($action[0] == 'a')
				$style .= 'background-color: #DDFFDD';
			elseif ($action[0] == 'd')
				$style .= 'background-color: #FFDDDD';

			if (!$section)
				echo '
		<div class="windowbg2 smallpadding">';
			$section = true;

			echo '
			<dl class="clearfix">
				<dt>', $action[2], '</dt>
				<dd>', $action[1], '</dd>
			</dl>';
		}

		echo '
		</div>
	</div>
	<br />';

	}
}

?>