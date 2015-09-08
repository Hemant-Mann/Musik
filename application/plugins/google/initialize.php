<?php
$client_id = '<Client Id>';
$client_secret = '<Client Secret>';
$developer_key = '<Developer Key>';

$client = new Google_Client();
$client->setDeveloperKey($developer_key);

Framework\Registry::set("gClient", $client);
