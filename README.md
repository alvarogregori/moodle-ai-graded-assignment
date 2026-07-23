# AI Graded Assignment for Moodle 4.5+

Alpha Moodle activity module prototype.

> Status: experimental. This repository currently preserves the prototype state and should be rebuilt from a clean Moodle activity module template before production use.

Technical name: `mod_aigradedassign`

This is an alpha scaffold for a Moodle activity module that lets students submit a file and receive AI-generated evaluation feedback based on assignment instructions, a rubric, and 3 to 5 evaluated examples.

## Current scope

- Moodle activity module skeleton.
- Instance settings: name, HTML instructions, AI provider, model, temperature, token limit, rubric file, evaluated examples, file type settings.
- Global settings for Mistral, OpenAI and Anthropic API keys.
- Student submission form with one uploaded file.
- Adhoc task for asynchronous evaluation.
- TXT, DOCX and basic DOC text extraction.
- Mistral, OpenAI and Anthropic HTTP clients.
- Result storage and display.
- Custom completion when submission status becomes `evaluated`.
- Basic teacher report.

## Not yet included

- Gradebook integration.
- PDF extraction.
- Backup/restore.
- Full Privacy API export/delete implementation.
- Behat/PHPUnit tests.

## Installation

Copy the `aigradedassign` directory into:

```text
mod/aigradedassign
```

Then run Moodle upgrade:

```bash
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

Configure API keys in:

```text
Site administration > Plugins > Activity modules > AI Graded Assignment
```

## File notes

- Rubric files accept `.txt`, `.doc` and `.docx`.
- Evaluated example submitted-work files accept `.txt`, `.doc` and `.docx`.
- Student submissions accept `.txt`, `.doc` and `.docx` by default.
- PDF is intentionally excluded from this version.
- DOC extraction uses a simple best-effort text extraction fallback and may be less reliable than DOCX/TXT.

## Provider notes

- Mistral uses `/v1/chat/completions`.
- OpenAI uses `/v1/responses`.
- Anthropic uses `/v1/messages` with `anthropic-version: 2023-06-01`.

The API code is intentionally isolated under `classes/ai/` so endpoint changes can be handled without touching Moodle views or database logic.
