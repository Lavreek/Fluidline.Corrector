<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    ini_set('max_execution_time', 1200);
    ini_set('memory_limit', '10240M');

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
