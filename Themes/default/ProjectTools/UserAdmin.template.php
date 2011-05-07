<?php
/**
 * Template for UserAdmin.php
 *
 * @package UserAdmin
 * @version 0.6
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.6
 * @see UserAdmin.php
 */

/**
 *
 */
function template_select_project()
{
	global $context, $settings, $options, $txt, $modSettings;
	
		echo '
	<div class="cat_bar">
		<h3 class="catbg">
			', $txt['project'], '
		</h3>
	</div>
	<div id="projects_table">
		<table class="table_list">
			<thead>
				<tr><th colspan="4"></th></tr>
			</thead>
			<tfoot>
				<tr><td colspan="4"></td></tr>
			</tfoot>
			<tbody class="content">';

		foreach ($context['admin_projects'] as $i => $project)
		{
			echo '
				<tr>
					<td class="windowbg2 info">
						<h4><a href="', $project['href'], '">', $project['name'], '</a></h4>
					</td>
				</tr>';
		}

		echo '
			</tbody>
		</table>
	</div>';
}

?>