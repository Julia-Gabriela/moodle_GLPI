<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Central content editor. Moodle forms enforce sesskey validation on submission.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/lib.php');
\local_glpisupport\local\access::require_editor();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    \local_glpisupport\local\access::require_write();
} else if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    throw new moodle_exception('invalidparameter');
}
admin_externalpage_setup('local_glpisupport_content');

$context = context_system::instance();
$content = \local_glpisupport\local\content::get();
$form = new \local_glpisupport\form\tutorial_form(null, ['context' => $context, 'content' => $content]);
$defaults = new stdClass();
foreach ($content as $key => $value) {
    $iseditor = \local_glpisupport\local\content::is_editor($key);
    $defaults->$key = $iseditor ? ['text' => $value, 'format' => FORMAT_HTML] : $value;
}
for ($i = 1; $i <= 5; $i++) {
    $field = 'step' . $i . 'image';
    $draftid = file_get_submitted_draft_itemid($field);
    file_prepare_draft_area($draftid, $context->id, 'local_glpisupport', 'tutorialimage', $i,
        \local_glpisupport\form\tutorial_form::file_options());
    $defaults->$field = $draftid;
}
$form->set_data($defaults);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/glpisupport/index.php'));
}
if ($data = $form->get_data()) {
    \local_glpisupport\local\editor::save($data);
    redirect(new moodle_url('/local/glpisupport/manage.php'), get_string('contentsaved', 'local_glpisupport'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editcontent', 'local_glpisupport'));
echo html_writer::div(
    html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'local_glpisupport']),
        get_string('buttons_and_access', 'local_glpisupport'), ['class' => 'btn btn-primary mr-2']) . ' '
    . html_writer::link(new moodle_url('/local/glpisupport/index.php'), get_string('viewtutorial', 'local_glpisupport'),
        ['class' => 'btn btn-secondary']), 'mb-4');
echo html_writer::tag('p', get_string('editcontent_desc', 'local_glpisupport'));
$form->display();
echo $OUTPUT->footer();
