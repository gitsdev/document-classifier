<?php
// Copy this file to config.php and fill in your API key.
// config.php is gitignored so your key stays out of version control.
//
// Preferred: create a .env file in this directory with:
//   ANTHROPIC_API_KEY=sk-ant-...
// Or set via environment variable:
//   export ANTHROPIC_API_KEY="sk-ant-..."

$envFile = __DIR__ . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key = trim($parts[0]);
        $val = trim($parts[1], " \t\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
        }
    }
}

$ANTHROPIC_API_KEY = getenv('ANTHROPIC_API_KEY');
if (!$ANTHROPIC_API_KEY) {
    $ANTHROPIC_API_KEY = ''; // <-- Paste your key here as a fallback
}

$ANTHROPIC_MODEL = 'claude-opus-4-7';
