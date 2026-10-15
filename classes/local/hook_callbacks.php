<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Initialise the Lambda header shortcut, once per page.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var bool Prevent duplicate AMD initialisation. */
    private static bool $initialised = false;

    /** Top-of-body hook. */
    public static function top(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        self::initialise();
    }

    /** Fallback for themes which omit the standard top-of-body output. */
    public static function footer(\core\hook\output\before_footer_html_generation $hook): void {
        self::initialise();
    }

    /** Queue the shortcut only for authenticated non-guest users. */
    private static function initialise(): void {
        global $CFG, $PAGE;
        if (self::$initialised) {
            return;
        }
        require_once($CFG->dirroot . '/local/glpisupport/lib.php');
        if (!local_glpisupport_available()) {
            return;
        }
        self::$initialised = true;
        $PAGE->requires->js_call_amd('local_glpisupport/navigation', 'init', [[
            'url' => (new \moodle_url('/local/glpisupport/index.php'))->out(false),
            'label' => local_glpisupport_label(),
            'active' => $PAGE->pagetype === 'local-glpisupport-index',
        ]]);
    }
}
