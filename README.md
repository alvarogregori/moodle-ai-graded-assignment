# AI Graded Assignment for Moodle 4.5+

`mod_aigradedassign` is a Moodle activity module for plain-text assignments with
automated feedback.

## Version 0.4.2 scope

- Standard Moodle activity creation and editing.
- Student-visible instructions.
- Private plain-text rubric, example submission, and example evaluation.
- One current plain-text submission per student, with attempt history recorded
  in evaluation rows.
- Evaluation through Mistral, OpenAI, Anthropic, a generic OpenAI-compatible
  endpoint, or a deterministic local mock.
- Site-level protected API key, model, and base URL settings for each remote
  provider.
- Gradebook integration using a numeric grade from 0 to 10.
- Automatic activity completion only after feedback is stored.
- No file uploads.
- Provider-neutral PHP interface and structured JSON grading response.

## Installation

Copy this repository to `mod/aigradedassign`, then run:

```text
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

Moodle 4.5 (build `2024100700`) or newer is required.

## Safety notes

The course-cache callback performs only one small database read and never calls
an AI provider. Private rubric and example fields are not included in course
module cache content and are not rendered on student pages.

Remote evaluation is the default behaviour. Mistral is the initial site-wide
default provider, and new activities inherit the site default unless a teacher
selects a different provider. The local mock remains available as an explicit
no-network option. When a remote provider is used, the assignment instructions,
private rubric, private evaluated example, and student submission are sent to
that provider for grading.

API keys are stored only in Moodle's site-level plugin configuration and are not
included in activity records, course caches, prompts, reports, or student pages.
Only site administrators can configure credentials and provider base URLs.
Custom base URLs should point only to services trusted by the site operator.

Remote requests run outside database transactions. If a provider is unavailable,
misconfigured, or returns an invalid response, the submission remains saved but
is not marked as evaluated, activity completion is not awarded, and no new
gradebook grade is written. Successful responses are validated as structured
JSON, constrained to a score from 0 to 10, stored as plain-text feedback, and
synchronised with the course gradebook.

The upgrade from the original prototype preserves activity and submission rows,
migrates the first evaluated example into the activity record, and renames the
prototype submission/evaluation fields. Upgrades do not send existing
submissions to an AI provider. Back up the plugin directory and database before
any upgrade.
