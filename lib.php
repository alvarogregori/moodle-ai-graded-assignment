<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

function aigradedassign_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        default:
            return null;
    }
}

function aigradedassign_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->allowedfiletypes = $data->allowedfiletypes ?? '.txt,.doc,.docx';
    $data->maxfilesize = $data->maxfilesize ?? 0;
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;

    $id = $DB->insert_record('aigradedassign', $data);
    aigradedassign_save_examples($id, $data);

    return $id;
}

function aigradedassign_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->allowedfiletypes = $data->allowedfiletypes ?? '.txt,.doc,.docx';
    $data->maxfilesize = $data->maxfilesize ?? 0;
    $data->completionevaluated = !empty($data->completionevaluated) ? 1 : 0;

    $DB->update_record('aigradedassign', $data);
    aigradedassign_save_examples($data->id, $data);

    return true;
}

function aigradedassign_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('aigradedassign', ['id' => $id])) {
        return false;
    }

    $submissions = $DB->get_records('aigradedassign_submissions', ['aigradedassignid' => $id], '', 'id');
    if ($submissions) {
        [$insql, $params] = $DB->get_in_or_equal(array_keys($submissions), SQL_PARAMS_NAMED);
        $DB->delete_records_select('aigradedassign_evals', "submissionid $insql", $params);
    }

    $DB->delete_records('aigradedassign_submissions', ['aigradedassignid' => $id]);
    $DB->delete_records('aigradedassign_examples', ['aigradedassignid' => $id]);
    $DB->delete_records('aigradedassign', ['id' => $id]);

    return true;
}

function aigradedassign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!in_array($filearea, ['submission', 'rubric', 'example_submission'], true)) {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_aigradedassign', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    if ($filearea === 'submission' && !has_capability('mod/aigradedassign:viewallsubmissions', $context)) {
        global $DB, $USER;
        $submission = $DB->get_record('aigradedassign_submissions', ['id' => $itemid], '*', MUST_EXIST);
        if ((int)$submission->userid !== (int)$USER->id) {
            return false;
        }
    }

    if ($filearea !== 'submission' && !has_capability('mod/aigradedassign:view', $context)) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function aigradedassign_save_examples(int $aigradedassignid, stdClass $data): void {
    global $DB;

    $DB->delete_records('aigradedassign_examples', ['aigradedassignid' => $aigradedassignid]);

    $sample = trim((string)($data->example1sample ?? ''));
    $evaluation = trim((string)($data->example1evaluation ?? ''));
    if ($sample === '' && $evaluation === '') {
        return;
    }

    $DB->insert_record('aigradedassign_examples', (object)[
        'aigradedassignid' => $aigradedassignid,
        'sortorder' => 1,
        'sampletext' => $sample,
        'sampleformat' => FORMAT_PLAIN,
        'evaluationtext' => $evaluation,
        'evaluationformat' => FORMAT_PLAIN,
        'timecreated' => time(),
        'timemodified' => time(),
    ]);
}
