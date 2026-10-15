<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Reject insecure destinations instead of silently saving an unusable portal setting.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class portal_url_setting extends \admin_setting_configtext {
    /** Retain native validation, with a strict HTTPS policy. */
    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        return $data === '' || (is_string($data) && \local_glpisupport_valid_portal_url($data))
            ? true : get_string('invalidportalurl', 'local_glpisupport');
    }
}
