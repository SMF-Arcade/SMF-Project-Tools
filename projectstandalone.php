<?php
/**
 * This file is for running Project Tools in standalone mode,
 * where Project Tools is not located inside forum.
 *
 * Meant for having Project Tools inside site rather than inside forum
 *
 * Example settings:
 *
 * INSERT INTO `smf_settings` (`variable` ,`value`)
 * 	VALUES
 * ('projectStandalone', '2'),
 * ('projectStandaloneUrl', 'http://www.smfarcade.info/projects')
 *
 * projectStandalone:
 * 	1 - No "SEO" urls
 *	2 - "SEO" urls (not supported yet)
 *
 *	Example .htaccess rules for "SEO" urls
 *		(not supported yet)
 * 
 *	projectStandaloneUrl:
 *
 *		Base url for SEO urls.
 *
 *		eg. http://www.smfarcade.info/projects
 *
 *	url to renamed projectstandalone.php for non SEO urls
 *	eg. http://www.smfarcade.info/projects/index.php
 *
 * @package core
 * @version 0.5
 * @license http://download.smfproject.net/license.php New-BSD
 * @since 0.1
 */

/*
	
	

	Eexample settings

		

	

*/

// Here you can add SSI settings or any settings for your site
$project_tools = true;

// Path to SSI or file which includes SSI.php
require_once(dirname(dirname(__FILE__)) . '/SSI.php');

// Load Issue (if needed)
loadIssue();

if (isset($context['project_error']))
    fatal_lang_error($context['project_error'], false);

// Main thing
Projects(true);

obExit(null, null, true);

?>