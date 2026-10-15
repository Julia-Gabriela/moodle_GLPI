<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Authenticated support tutorial.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
\local_glpisupport\local\access::require_reader();
if (!local_glpisupport_enabled()) {
    throw new moodle_exception('disabled', 'local_glpisupport');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/glpisupport/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_pagetype('local-glpisupport-index');
$PAGE->set_title(local_glpisupport_label());
$PAGE->set_heading(local_glpisupport_label());
$PAGE->navbar->add(local_glpisupport_label(), $PAGE->url);
$PAGE->requires->css(new moodle_url('/local/glpisupport/styles.css'));
$floating = get_config('local_glpisupport', 'floatingbutton');
$PAGE->requires->js_call_amd('local_glpisupport/tutorial', 'init', [[
    'floating' => $floating === false || (bool)$floating,
]]);

$content = \local_glpisupport\local\content::get();
$steps = [];
for ($i = 1; $i <= 5; $i++) {
    $steps[] = [
        'number' => $i,
        'title' => $content['step' . $i . 'title'],
        'body' => \local_glpisupport\local\content::html($content['step' . $i . 'body']),
        'image' => \local_glpisupport\local\content::image_url($i),
        'alt' => $content['step' . $i . 'alt'],
        'showimage' => (bool)$content['step' . $i . 'showimage'] && (bool)\local_glpisupport\local\content::image($i),
        'imagecaption' => $content['imagecaption'],
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_glpisupport/tutorial', [
    'portalurl' => local_glpisupport_portal_url(),
    'mainbuttonlabel' => local_glpisupport_button_label('mainbuttonlabel'),
    'floatingbuttonlabel' => local_glpisupport_button_label('floatingbuttonlabel'),
    'buttonstyle' => local_glpisupport_button_style(),
    'settingsurl' => has_capability('moodle/site:config', context_system::instance())
        ? (new moodle_url('/local/glpisupport/manage.php'))->out(false) : '',
    'pagetitle' => $content['pagetitle'],
    'intro' => \local_glpisupport\local\content::html($content['intro']),
    'audiencetitle' => $content['audiencetitle'],
    'audiencetext' => \local_glpisupport\local\content::html($content['audiencetext']),
    'summarytitle' => $content['summarytitle'],
    'trackingtitle' => $content['trackingtitle'],
    'trackingbody' => \local_glpisupport\local\content::html($content['trackingbody']),
    'steps' => $steps,
]);
echo $OUTPUT->footer();
