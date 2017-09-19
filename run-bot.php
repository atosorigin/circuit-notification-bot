#!/usr/bin/env php
<?php
if(count($argv) != 2)
{
    fwrite(STDERR, "ERROR: This script takes exactly one argument. For more execute \"{$argv[0]} help\".\n");
    exit(2);
}

if($argv[1] == "help")
{
    fwrite(STDERR, "Circuit Bot runner.\nUsage: {$argv[0]} bot-dir");
    exit(0);
}

$bot_dir=$argv[1];

if(is_dir($bot_dir))
{

    chdir($bot_dir);

    require_once('./vendor/autoload.php');

    $config_file = './config.php';
    $config;

    if(is_file($config_file))
    {
        require_once('./config.php'); // . is PWD not __DIR__ !
    }
    else
    {
        $config = [];
    }

    circuit_bot($config);
}
else
{
    fwrite(STDERR, "ERROR: Bot directory \"{$bot_dir}\" does not exists or is no directory!");
    exit(3);
}
