# Document Classifier

A simple PHP web app that uses the Claude API to identify the type and client name of uploaded documents (bank statements, invoices, tax returns, ID documents, etc.).

## How it works

Drop one or more files into the upload zone, click **Analyze Documents**, and Claude returns:

- **Document type** — e.g. "Bank Statement", "Invoice", "Tax Return"
- **Client name** — the primary subject of the document (account holder, customer, taxpayer)
- **One-sentence summary**

PDFs are sent as base64 document blocks, images use vision, text files are read inline.

## Setup

1. **XAMPP** (or any PHP 7+ environment with cURL) serving this directory.
2. **Anthropic API key** — copy `config.example.php` to `config.php` and paste your key, or set the `ANTHROPIC_API_KEY` environment variable.
3. Open `http://localhost/claudeCode/` in your browser.

## Files

| File | Purpose |
| --- | --- |
| `index.html` | Drag-and-drop upload UI |
| `analyze.php` | Backend that calls the Claude API per file |
| `config.example.php` | Template for the API key (copy to `config.php`) |
| `.gitignore` | Excludes `config.php` and other secrets |

## Supported file types

PDF, PNG, JPG/JPEG, GIF, WEBP, TXT (25MB cap per file).

## Model

Uses `claude-opus-4-7`. Change the model in `config.php` if you want a faster/cheaper one (e.g. `claude-sonnet-4-6` or `claude-haiku-4-5`).
