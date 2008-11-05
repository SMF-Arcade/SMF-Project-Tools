<?php
// Version: 0.2; PatchView

function template_main()
{
	global $context, $settings, $options, $txt, $modSettings;

	foreach ($context['diff'] as $file)
	{
		echo '
	<div class="tborder patch">
		<h3 class="titlebg headerpadding">', $file['name_before'], '</h3>
			<div class="windowbg">';

		$section = false;

		foreach ($file['actions'] as $action)
		{
			$style = '';

			if (trim($action[1]) == '')
				$action[1] = '&nbsp;';
			else
				$action[1] = htmlspecialchars($action[1]);

			if (isset($action[2]) && trim($action[2]) == '')
				$action[2] = '&nbsp;';
			if (isset($action[2]) && trim($action[3]) == '')
				$action[3] = '&nbsp;';

			if (empty($action[0]))
				$style = '';
			elseif ($action[0] == '@')
			{
				if (!$section)
				{
					$section = true;
					continue;
				}
				echo '
			<dl class="clearfix">
				<dt>...</dt>
				<dd class="windowbg2" style="', $style, '"> </dd>
			</dl>';

				continue;
			}
			elseif ($action[0] == 'a')
				$style .= 'background-color: #DDFFDD';
			elseif ($action[0] == 'd')
				$style .= 'background-color: #FFDDDD';

			echo '
			<dl class="clearfix">
				<dt>', $action[2], '</dt>
				<dt>', $action[3], '</dt>
				<dd class="windowbg2" style="', $style, '">', $action[1], '</dd>
			</dl>';
		}

		echo '
		</div>
	</div>
	<br />';

	}
}

?>