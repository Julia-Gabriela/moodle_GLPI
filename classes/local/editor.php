<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Persist tutorial content only after checking permission, method and session key.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor {
    /** Validate everything before starting a transaction or modifying permanent files. */
    public static function save(\stdClass $data): void {
        global $DB, $USER;
        access::require_write();
        $saved = content::from_form((array)$data);
        $images = [];
        for ($i = 1; $i <= 5; $i++) {
            $field = 'step' . $i . 'image';
            $draftid = $data->$field ?? null;
            if (!is_scalar($draftid) || !ctype_digit((string)$draftid) || (int)$draftid < 1) {
                throw new \moodle_exception('invalidimage', 'local_glpisupport');
            }
            $images[$i] = image_policy::draft_image((int)$draftid);
        }
        $context = \context_system::instance();
        $storage = get_file_storage();
        $transaction = $DB->start_delegated_transaction();
        for ($i = 1; $i <= 5; $i++) {
            $storage->delete_area_files($context->id, 'local_glpisupport', 'tutorialimage', $i);
            if ($images[$i]) {
                $storage->create_file_from_string((object)[
                    'contextid' => $context->id, 'component' => 'local_glpisupport',
                    'filearea' => 'tutorialimage', 'itemid' => $i, 'filepath' => '/',
                    'filename' => 'step-' . $i . '.' . $images[$i]['extension'],
                    'mimetype' => $images[$i]['mimetype'], 'userid' => $USER->id,
                ], $images[$i]['content']);
            }
        }
        set_config('contentjson', json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'local_glpisupport');
        $transaction->allow_commit();
    }
}
