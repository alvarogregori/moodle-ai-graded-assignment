# Changelog

## 0.6.1-alpha (2026-09-16)

- Add Moodle Privacy API metadata, export, and deletion support.
- Add Moodle Backup and Restore API support for activities, submissions, and evaluations.
- Document external AI data transfers and gradebook storage.
- Add Marketplace-ready release notes and package metadata.

## 0.6.0-alpha (2026-09-01)

- Allow one to three private evaluated examples per activity.
- Require the first example and validate optional examples as complete pairs.
- Preserve existing examples during upgrade.

## 0.5.0-alpha (2026-07-28)

- Add optional tutor validation and editing of AI grades and assessments.
- Delay gradebook and completion updates until tutor approval when enabled.

## 0.4.2-alpha (2026-07-25)

- Add a site-wide default evaluation provider, initially Mistral.
- Make new activities inherit the site default provider.

## 0.4.1-alpha (2026-07-24)

- Add configurable Mistral, OpenAI, Anthropic, and OpenAI-compatible providers.
- Fix HTTP status handling for Moodle's cURL wrapper.

## 0.3.0-alpha (2026-07-23)

- Add gradebook integration with grades from 0 to 10.
- Add grading and assessment columns to the submissions report.

## 0.2.0-alpha (2026-07-23)

- Rebuild the activity module using the standard Moodle structure.
- Add plain-text submission, evaluation, and completion workflows.
