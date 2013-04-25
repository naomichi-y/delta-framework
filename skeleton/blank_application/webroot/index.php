<?php
require '../config/delta_env.php';

Delta_BootLoader::run();

$controller = Delta_DIContainerFactory::create()->getComponent('controller');
$controller->dispatch();
