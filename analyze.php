<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('POST required', 405);
}
if (empty($ANTHROPIC_API_KEY)) {
    fail('ANTHROPIC_API_KEY is not configured. Set it in config.php or as an environment variable.', 500);
}
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    fail('No file uploaded or upload failed.');
}

$file       = $_FILES['file'];
$tmpPath    = $file['tmp_name'];
$origName   = $file['name'];
$fileSize   = $file['size'];

// Cap at 25MB to keep API requests reasonable
if ($fileSize > 25 * 1024 * 1024) {
    fail('File too large (max 25MB).');
}

// Sniff mime type — finfo is more reliable than the browser-supplied $file['type']
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($tmpPath);
$ext   = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

// Build the content block for the API based on file type
$userContent = [];

if ($mime === 'application/pdf' || $ext === 'pdf') {
    $data = base64_encode(file_get_contents($tmpPath));
    $userContent[] = [
        'type' => 'document',
        'source' => [
            'type' => 'base64',
            'media_type' => 'application/pdf',
            'data' => $data,
        ],
    ];
} elseif (strpos($mime, 'image/') === 0 || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
    $imgMime = $mime;
    if (strpos($imgMime, 'image/') !== 0) {
        $imgMime = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
    }
    $data = base64_encode(file_get_contents($tmpPath));
    $userContent[] = [
        'type' => 'image',
        'source' => [
            'type' => 'base64',
            'media_type' => $imgMime,
            'data' => $data,
        ],
    ];
} elseif (strpos($mime, 'text/') === 0 || in_array($ext, ['txt', 'csv', 'log', 'md'])) {
    $text = file_get_contents($tmpPath);
    // Truncate very long text files to fit the context window
    if (strlen($text) > 100000) {
        $text = substr($text, 0, 100000) . "\n\n[...truncated]";
    }
    $userContent[] = [
        'type' => 'text',
        'text' => "The following is the content of a text file named \"$origName\":\n\n$text",
    ];
} else {
    fail("Unsupported file type: $mime ($ext). Supported: PDF, images, text files.");
}

$userContent[] = [
    'type' => 'text',
    'text' => "Analyze this document and identify:\n"
            . "1. The document type (e.g., 'Bank Statement', 'Invoice', 'Tax Return', 'Pay Stub', 'Contract', 'ID Document', 'Utility Bill', 'Resume', 'Receipt', etc.)\n"
            . "2. The client/person/entity name the document is about (the primary subject — account holder, customer, taxpayer, etc.)\n"
            . "3. A one-sentence summary of what this document is.\n\n"
            . "Respond with ONLY a JSON object in this exact format, no other text:\n"
            . '{"document_type": "...", "client_name": "...", "summary": "..."}' . "\n\n"
            . 'If you cannot determine the client name, use "Unknown". If you cannot determine the document type, use "Unknown Document".',
];

$payload = [
    'model' => $ANTHROPIC_MODEL,
    'max_tokens' => 1024,
    'messages' => [
        ['role' => 'user', 'content' => $userContent],
    ],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    fail('Network error calling Claude API: ' . $curlErr, 500);
}

$decoded = json_decode($response, true);

if ($httpCode !== 200) {
    $apiMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'API returned status ' . $httpCode;
    fail('Claude API error: ' . $apiMsg, 502);
}

// Extract the first text block from the response
$text = '';
if (isset($decoded['content']) && is_array($decoded['content'])) {
    foreach ($decoded['content'] as $block) {
        if (isset($block['type']) && $block['type'] === 'text') {
            $text = $block['text'];
            break;
        }
    }
}

if ($text === '') {
    fail('Empty response from Claude.', 502);
}

// The model sometimes wraps JSON in ```json fences — strip them defensively
$cleaned = trim($text);
if (preg_match('/```(?:json)?\s*(.+?)\s*```/s', $cleaned, $m)) {
    $cleaned = $m[1];
}

$parsed = json_decode($cleaned, true);
if (!is_array($parsed)) {
    // Fall back to surfacing the raw text so the user sees what came back
    fail('Could not parse Claude response as JSON. Raw response: ' . substr($text, 0, 500), 502);
}

echo json_encode([
    'document_type' => isset($parsed['document_type']) ? $parsed['document_type'] : 'Unknown Document',
    'client_name'   => isset($parsed['client_name']) ? $parsed['client_name'] : 'Unknown',
    'summary'       => isset($parsed['summary']) ? $parsed['summary'] : '',
]);
