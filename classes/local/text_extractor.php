<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aigradedassign\local;

defined('MOODLE_INTERNAL') || die();

class text_extractor {
    public function extract(\stored_file $file): string {
        $filename = strtolower($file->get_filename());

        if (str_ends_with($filename, '.txt')) {
            return trim($file->get_content());
        }

        if (str_ends_with($filename, '.docx')) {
            return $this->extract_docx($file);
        }

        if (str_ends_with($filename, '.doc')) {
            return $this->extract_doc($file);
        }

        throw new \moodle_exception('unsupportedfiletype', 'aigradedassign');
    }

    private function extract_docx(\stored_file $file): string {
        if (!class_exists('\ZipArchive')) {
            throw new \moodle_exception('ziparchiverequired', 'aigradedassign');
        }

        $tmpdir = make_temp_directory('mod_aigradedassign');
        $tmpfile = $tmpdir . '/' . uniqid('submission_', true) . '.docx';
        $file->copy_content_to($tmpfile);

        $zip = new \ZipArchive();
        if ($zip->open($tmpfile) !== true) {
            @unlink($tmpfile);
            throw new \moodle_exception('cannotopendocx', 'aigradedassign');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmpfile);

        if ($xml === false) {
            throw new \moodle_exception('cannotreaddocx', 'aigradedassign');
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim(preg_replace('/[ \t]+/', ' ', $text));
    }

    private function extract_doc(\stored_file $file): string {
        $content = $file->get_content();
        $parts = [];

        if (preg_match_all('/[\x20-\x7E\x0A\x0D\x09]{5,}/', $content, $matches)) {
            $parts = array_merge($parts, $matches[0]);
        }

        $utf16 = @mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
        if (is_string($utf16) && preg_match_all('/[\p{L}\p{N}\p{P}\p{Zs}\r\n\t]{5,}/u', $utf16, $matches)) {
            $parts = array_merge($parts, $matches[0]);
        }

        $text = trim(implode("\n", array_unique(array_map('trim', $parts))));
        if ($text === '') {
            throw new \moodle_exception('cannotreaddoc', 'aigradedassign');
        }

        return $text;
    }
}
