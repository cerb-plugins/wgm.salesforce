<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
$tables = $db->metaTables();

$consumer_key = $settings->get('wgm.salesforce', 'consumer_key', null);
$consumer_secret = $settings->get('wgm.salesforce', 'consumer_secret', null);

if(!is_null($consumer_key) || !is_null($consumer_secret)) {
	$credentials = [
		'consumer_key' => $consumer_key,
		'consumer_secret' => $consumer_secret,
	];
	
	$settings->set('wgm.salesforce', 'credentials', $credentials, true, true);
	$settings->delete('wgm.salesforce', ['consumer_key','consumer_secret']);
}

return TRUE;