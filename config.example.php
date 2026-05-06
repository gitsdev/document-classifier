<?php
// Copy this file to config.php and fill in your API key.
// config.php is gitignored so your key stays out of version control.
//
// To set via environment variable instead:
//   export ANTHROPIC_API_KEY="sk-ant-..."

$ANTHROPIC_API_KEY = getenv('ANTHROPIC_API_KEY');
if (!$ANTHROPIC_API_KEY) {
    $ANTHROPIC_API_KEY = ''; // <-- Paste your key here as a fallback
}

$ANTHROPIC_MODEL = 'claude-opus-4-7';
