<?php

$env_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if ( ! is_readable($env_file))
{
    return;
}

$lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false)
{
    return;
}

foreach ($lines as $line)
{
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0)
    {
        continue;
    }
    if (strpos($line, '=') === false)
    {
        continue;
    }
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    if ($name === '')
    {
        continue;
    }
    if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'")))
    {
        $value = substr($value, 1, -1);
    }
    $_ENV[$name] = $value;
    putenv($name . '=' . $value);
}
