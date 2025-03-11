<?php
header('Content-Type: application/json');
$config = file_get_contents('data.json');
echo $config;
