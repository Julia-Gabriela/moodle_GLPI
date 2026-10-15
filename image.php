<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Serve tutorial screenshots through an authenticated controller.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/lib.php');
\local_glpisupport\local\access::require_reader();
if (!local_glpisupport_enabled()) {
    throw new moodle_exception('disabled', 'local_glpisupport');
}
$step = required_param('step', PARAM_INT);
if ($step < 1 || $step > 5) {
    throw new moodle_exception('invalidparameter');
}
$file = \local_glpisupport\local\content::image($step);
// Do not let a mislabeled legacy stored file be served as active content.
if ($file && !in_array($file->get_mimetype(), ['image/png', 'image/jpeg', 'image/webp'], true)) {
    throw new moodle_exception('invalidimage', 'local_glpisupport');
}
header('X-Content-Type-Options: nosniff');
if ($file) {
    send_stored_file($file, 0, 0, false, ['cacheability' => 'private']);
}
throw new moodle_exception('filenotfound', 'error');
