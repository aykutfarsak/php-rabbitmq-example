<?php

require 'vendor/autoload.php';

if (empty($argv[1])) exit("Need task data. Exit\n");

$data = unserialize(base64_decode($argv[1]));

// do a task..
file_put_contents('log.txt', $data);