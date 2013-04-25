<?php
require dirname(__DIR__) . '/config/delta_env.php';

Delta_BootLoader::run(Delta_BootLoader::BOOT_TYPE_CONSOLE);

$console = Delta_DIContainerFactory::create()->getComponent('console');
$console->start();
