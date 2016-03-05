<?php
require 'EasySteamAPI.class.php';

$SteamAPI = new SteamAPI();

echo json_encode($SteamAPI->getJson("76561198063842089"));