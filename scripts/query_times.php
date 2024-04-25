<?php

// Reads log from stdin and outputs slow queries and QPS

$threshold = isset($argv[1]) ? $argv[1] : 1000;
$queries = 0;
$queryTimes = 0;
$maxQTime = 0;
$startTime = microtime(true);

while (false !== ($line = fgets(STDIN))) {
    if (false === strstr($line, 'o.a.s.c.S.Request') || false === strstr($line, 'path=/select')) {
        continue;
    }
    if (!preg_match('/QTime=(\d+)/', $line, $matches)) {
        continue;
    }
    $qtime = $matches[1];
    if (!preg_match('/params=\{(.+)\}/', $line, $matches)) {
        continue;
    }
    $queryParams = [];
    // parse_str doesn't handle repeated params, so parse manually:
    foreach (explode('&', $matches[1]) as $param) {
        $parts = explode('=', $param, 2);
        $key = urldecode($parts[0]);
        $value = isset($parts[1]) ? urldecode($parts[1]) : '';
        $value = preg_replace_callback(
            '/[\x00-\x1f]/',
            function ($matches) {
                return '\x' . ord($matches[0]);
            },
            $value
        );
        $queryParams[] = "$key = $value";
    }
    ++$queries;
    $queryTimes += $qtime;
    $maxQTime = max($maxQTime, $qtime);
    if (microtime(true) - $startTime > 1.0) {
        echo "$queries per second, avg qtime " . floor($queryTimes / $queries) . ", max qtime $maxQTime" . PHP_EOL;
        $startTime = microtime(true);
        $queries = 0;
        $queryTimes = 0;
        $maxQTime = 0;
    }
    if ($qtime < $threshold) {
        continue;
    }
    echo substr($line, 0, 23) . " QTime=$qtime" . PHP_EOL;
    echo implode(PHP_EOL, $queryParams);
    echo PHP_EOL;
}
