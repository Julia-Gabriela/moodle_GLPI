<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Moodle output hook registration.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => [\local_glpisupport\local\hook_callbacks::class, 'top'],
        'priority' => 400,
    ],
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => [\local_glpisupport\local\hook_callbacks::class, 'footer'],
        'priority' => 400,
    ],
];

