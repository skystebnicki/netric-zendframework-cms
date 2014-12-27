<?php
/**
 * Synchronize objects between netric api and local database
 */
namespace NetricScripts;
use Netric;

require_once("Bootstrap.php");

$sm = \NetricScripts\Bootstrap::getServiceManager();

$dataMapperApi = $sm->get("DataMapperApi");
$dataMapperLocal = $sm->get("DataMapper");
$config = $sm->get("Config");

$options = getopt("t::");

// Get objects to pull from command line
if (isset($options['t']) && $options['t']!==false)
{
	$objects = array(
		$options['t'],
	);
}
else
{
	$objects = array(
		"cms_site",
		"cms_snippet",
		"cms_page",
		"cms_page_template",
		"content_feed",
		"content_feed_post",
	);
}


foreach ($objects as $objname)
{
    $dataMapperApi->syncCollection($objname, $dataMapperLocal, true);
}
