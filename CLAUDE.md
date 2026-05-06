# Project: Document Classifier

A PHP web app that uses the Claude API to classify uploaded documents (PDFs, images, text) and extract the client name.

## Stack

- **Runtime:** PHP 7.0+ via XAMPP (`/Applications/XAMPP/xamppfiles/htdocs/claudeCode`)
- **API:** Anthropic Messages API, raw cURL (no Composer dependencies)
- **Model:** `claude-opus-4-7` (configurable in `config.php`)

## File map

| File | Role |
| --- | --- |
| `index.html` | Drag-and-drop UI; processes files one at a time and updates each row as it finishes |
| `analyze.php` | Receives one file per request, builds the right content block (document/image/text), POSTs to `/v1/messages`, parses JSON from the response |
| `config.php` | Holds the API key — **gitignored**, never commit |
| `config.example.php` | Template committed to git |

## Conventions

- **Never commit `config.php`** — it contains the live API key. Only `config.example.php` is in git.
- **Sequential file processing** in the UI is intentional — each file gets its own API call so the user sees progressive results, and one failure doesn't block the rest.
- **Strict JSON return contract** — the prompt asks Claude for `{document_type, client_name, summary}` only. The backend strips ` ```json ``` ` fences defensively before `json_decode` since models occasionally wrap responses.
- **25MB upload cap per file** — enforced in `analyze.php`. Bump only if your PHP `upload_max_filesize` / `post_max_size` allow it.
- **Text files are truncated at 100KB** before being sent to the model.

## Running locally

1. Start XAMPP (Apache).
2. Visit `http://localhost/claudeCode/`.
3. API key must be in `config.php` or `ANTHROPIC_API_KEY` env var.

## Git / GitHub

- Repo: https://github.com/gitsdev/document-classifier (private)
- Default branch: `main`
- `.gitignore` excludes `config.php`, `.env`, `.claude/`, OS/editor cruft.
- Auth: GitHub CLI (`~/bin/gh`) authenticated as `gitsdev`. To push from a new shell, `~/bin/gh` needs to be on `PATH` — run `export PATH="$HOME/bin:$PATH"` or add it to `~/.zshrc`.
