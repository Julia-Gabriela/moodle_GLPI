<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Access checks shared by the controllers and the save service.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access {
    /** Require a real login. Guests cannot read the tutorial or its image controller. */
    public static function require_reader(): void {
        require_login(null, false);
        if (isguestuser()) {
            throw new \moodle_exception('noguest', 'local_glpisupport');
        }
    }

    /** Editing is a site-wide permission, never a course teacher permission. */
    public static function require_editor(): void {
        self::require_reader();
        require_capability('moodle/site:config', \context_system::instance());
    }

    /** Reject writes before reading drafts, including empty or forged POST requests. */
    public static function require_write(): void {
        self::require_editor();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new \moodle_exception('invalidparameter');
        }
        // Do not accept a token from the URL or a malformed array parameter.
        if (!isset($_POST['sesskey']) || !is_string($_POST['sesskey']) || $_POST['sesskey'] === '') {
            throw new \moodle_exception('invalidsesskey');
        }
        require_sesskey();
    }
}
