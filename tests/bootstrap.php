<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$services = ['db:3306', 'redis:6379', 'manticore:9306'];

foreach ($services as $host) {
    $parts = explode(':', $host);
    $hostname = $parts[0];
    $port = (int) $parts[1];
    $waitCount = 0;

    while ($waitCount < 60) {
        $fp = @fsockopen($hostname, $port, $errno, $errstr, 1);

        if ($fp) {
            fclose($fp);
            break;
        }

        $waitCount++;
        sleep(1);

        if ($waitCount === 60) {
            fwrite(STDERR, "Timeout waiting for $host\n");
            exit(1);
        }
    }
}
