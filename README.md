# AI Graded Assignment for Moodle 4.5+

`mod_aigradedassign` is a Moodle activity module for plain-text assignments with
automated feedback.

## Version 0.2.2 scope

- Standard Moodle activity creation and editing.
- Student-visible instructions.
- Private plain-text rubric, example submission, and example evaluation.
- One current plain-text submission per student, with attempt history recorded
  in evaluation rows.
- Deterministic local mock evaluation (no external network request).
- Automatic activity completion only after feedback is stored.
- No gradebook writes and no file uploads.
- Provider-neutral PHP interface ready for later Mistral, OpenAI, and Anthropic
  adapters.

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

The upgrade from the original prototype preserves activity and submission rows,
migrates the first evaluated example into the activity record, and renames the
prototype submission/evaluation fields. Back up the plugin directory and
database before any upgrade.
