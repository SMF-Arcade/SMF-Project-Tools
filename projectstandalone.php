<?php

/*
	This file is for running Project Tools in standalone mode, where Project Tools is not located inside forum.
	Meant for having Project Tools inside site rather than inside forum

	Eexample settings

		INSERT INTO `smf_settings` (`variable` ,`value`)
		VALUES
			('projectStandalone', '2'),
			('projectStandaloneUrl', 'http://www.smfarcade.info/projects')

	projectStandalone:
		1 - No "SEO" urls
		2 - "SEO" urls (not supported yet)

	Example .htaccess rules for "SEO" urls
		(not supported yet)

	projectStandaloneUrl:

		Base url for SEO urls.

			eg. http://www.smfarcade.info/wiki

		url to projectstandalone.php for non SEO urls

			eg. http://www.smfarcade.info/projects/index.php

*/

// Here you can add SSI settings or any settings for your site
$section = 'project';

// Path to SSI or file which includes SSI.php
require_once(dirname(__FILE__) . '/SSI.php');
$ssi_on_error_method = true;

// DON'T modify anything below unless you are sure what your doing
require_once($sourcedir . '/Project.php');

Projects(true);

$context['page_title_html_safe'] = $smcFunc['htmlspecialchars'](un_htmlspecialchars($context['page_title']));

obExit(true);

?>