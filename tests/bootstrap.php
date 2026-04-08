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

$skipWaiting = false;
$argv = $_SERVER['argv'] ?? [];
foreach ($argv as $key => $arg) {
    if ($arg === '--testsuite' && isset($argv[$key + 1]) && $argv[$key + 1] === 'unit') {
        $skipWaiting = true;
        break;
    }
}

if (!$skipWaiting) {
    $services = [];

    // Parse DATABASE_URL
    $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
    if ($databaseUrl && preg_match('/mysql:\/\/.*?@([^:\/?]+):(\d+)/i', $databaseUrl, $matches)) {
        $services[] = $matches[1] . ':' . $matches[2];
    }

    // Parse REDIS_URL
    $redisUrl = $_ENV['REDIS_URL'] ?? $_SERVER['REDIS_URL'] ?? '';
    if ($redisUrl && preg_match('/redis:\/\/([^:\/?]+):(\d+)/', $redisUrl, $matches)) {
        $services[] = $matches[1] . ':' . $matches[2];
    }

    // Parse Manticore
    $manticoreHost = $_ENV['MANTICORE_HOST'] ?? $_SERVER['MANTICORE_HOST'] ?? '';
    $manticorePort = $_ENV['MANTICORE_PORT'] ?? $_SERVER['MANTICORE_PORT'] ?? '9306';
    if ($manticoreHost) {
        $services[] = $manticoreHost . ':' . $manticorePort;
    }

    // Deduplicate
    $services = array_unique($services);

    foreach ($services as $host) {
        $parts = explode(':', $host);
        $hostname = $parts[0];
        $port = (int) $parts[1];
        $waitCount = 0;
        $maxWait = 120;

        fwrite(STDOUT, "Waiting for $host...");

        while ($waitCount < $maxWait) {
            $fp = @fsockopen($hostname, $port, $errno, $errstr, 1);

            if ($fp) {
                fclose($fp);
                fwrite(STDOUT, " OK\n");
                break;
            }

            $waitCount++;
            if ($waitCount % 10 === 0) {
                fwrite(STDOUT, ".");
            }
            sleep(1);

            if ($waitCount === $maxWait) {
                fwrite(STDERR, "\nTimeout waiting for $host after $maxWait seconds\n");
                exit(1);
            }
        }
    }
}
