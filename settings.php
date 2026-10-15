<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Administrator settings.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    require_once(__DIR__ . '/lib.php');
    $settings = new admin_settingpage('local_glpisupport', get_string('pluginname', 'local_glpisupport'));
    $ADMIN->add('localplugins', $settings);
    $ADMIN->add('localplugins', new admin_externalpage('local_glpisupport_content',
        get_string('editcontent', 'local_glpisupport'), new moodle_url('/local/glpisupport/manage.php'), 'moodle/site:config'));
    $settings->add(new admin_setting_heading('local_glpisupport/contentlink',
        get_string('editcontent', 'local_glpisupport'), html_writer::link(new moodle_url('/local/glpisupport/manage.php'),
            get_string('editcontent_link', 'local_glpisupport'))));
    $settings->add(new admin_setting_heading('local_glpisupport/general',
        get_string('general_heading', 'local_glpisupport'), get_string('general_desc', 'local_glpisupport')));
    $settings->add(new admin_setting_configcheckbox('local_glpisupport/enabled',
        get_string('enabled', 'local_glpisupport'), get_string('enabled_desc', 'local_glpisupport'), 1));
    $settings->add(new admin_setting_configtext('local_glpisupport/menulabel',
        get_string('menulabel_setting', 'local_glpisupport'), get_string('menulabel_desc', 'local_glpisupport'),
        get_string('menulabel', 'local_glpisupport'), PARAM_TEXT));
    $settings->add(new \local_glpisupport\local\portal_url_setting('local_glpisupport/portalurl',
        get_string('portalurl', 'local_glpisupport'), get_string('portalurl_desc', 'local_glpisupport'),
        '', PARAM_URL));
    $settings->add(new admin_setting_heading('local_glpisupport/buttons',
        get_string('buttons_heading', 'local_glpisupport'), get_string('buttons_desc', 'local_glpisupport')));
    $settings->add(new admin_setting_configtext('local_glpisupport/mainbuttonlabel',
        get_string('mainbuttonlabel', 'local_glpisupport'), get_string('mainbuttonlabel_desc', 'local_glpisupport'),
        get_string('access', 'local_glpisupport'), PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_glpisupport/floatingbuttonlabel',
        get_string('floatingbuttonlabel', 'local_glpisupport'), get_string('floatingbuttonlabel_desc', 'local_glpisupport'),
        get_string('access', 'local_glpisupport'), PARAM_TEXT));
    $settings->add(new admin_setting_configcheckbox('local_glpisupport/floatingbutton',
        get_string('floatingbutton', 'local_glpisupport'), get_string('floatingbutton_desc', 'local_glpisupport'), 1));

    $sides = ['right' => get_string('right', 'local_glpisupport'), 'left' => get_string('left', 'local_glpisupport')];
    $sizes = [
        'compact' => get_string('compact', 'local_glpisupport'),
        'normal' => get_string('normal', 'local_glpisupport'),
        'large' => get_string('large', 'local_glpisupport'),
    ];
    // Regex param types are validated by Moodle before settings are saved.
    $bottomformat = '/^(?:0|[1-9][0-9]?|[1-3][0-9]{2}|400)$/';
    $edgeformat = '/^(?:0|[1-9][0-9]?|1[01][0-9]|120)$/';
    foreach (['desktop' => [90, 24, 'normal'], 'mobile' => [90, 16, 'compact']] as $device => $devicedefaults) {
        $settings->add(new admin_setting_heading('local_glpisupport/' . $device,
            get_string($device . '_heading', 'local_glpisupport'), get_string($device . '_desc', 'local_glpisupport')));
        $settings->add(new admin_setting_configselect('local_glpisupport/' . $device . 'side',
            get_string('side', 'local_glpisupport'), get_string('side_desc', 'local_glpisupport'), 'right', $sides));
        $settings->add(new admin_setting_configtext('local_glpisupport/' . $device . 'bottom',
            get_string('bottom', 'local_glpisupport'), get_string('bottom_desc', 'local_glpisupport'),
            $devicedefaults[0], $bottomformat, 6));
        $settings->add(new admin_setting_configtext('local_glpisupport/' . $device . 'edge',
            get_string('edge', 'local_glpisupport'), get_string('edge_desc', 'local_glpisupport'),
            $devicedefaults[1], $edgeformat, 6));
        $settings->add(new admin_setting_configselect('local_glpisupport/' . $device . 'size',
            get_string('buttonsize', 'local_glpisupport'), get_string('buttonsize_desc', 'local_glpisupport'), $devicedefaults[2], $sizes));
    }
    $settings->add(new admin_setting_heading('local_glpisupport/appearance',
        get_string('appearance_heading', 'local_glpisupport'), get_string('appearance_desc', 'local_glpisupport')));
    $settings->add(new admin_setting_configcolourpicker('local_glpisupport/buttonbackground',
        get_string('buttonbackground', 'local_glpisupport'), get_string('buttonbackground_desc', 'local_glpisupport'), '#004587'));
    $settings->add(new admin_setting_configcolourpicker('local_glpisupport/buttontextcolour',
        get_string('buttontextcolour', 'local_glpisupport'), get_string('buttontextcolour_desc', 'local_glpisupport'), '#ffffff'));
    $settings->add(new admin_setting_configtext('local_glpisupport/buttonradius',
        get_string('buttonradius', 'local_glpisupport'), get_string('buttonradius_desc', 'local_glpisupport'),
        6, '/^(?:0|[1-2]?[0-9]|30)$/', 6));
    $settings->add(new admin_setting_configcheckbox('local_glpisupport/buttonshadow',
        get_string('buttonshadow', 'local_glpisupport'), get_string('buttonshadow_desc', 'local_glpisupport'), 1));

    $sample = html_writer::tag('span', s(local_glpisupport_button_label('floatingbuttonlabel')), [
        'style' => 'display:inline-block;background:var(--glpisupport-button-bg);color:var(--glpisupport-button-text);'
            . 'border-radius:var(--glpisupport-button-radius);box-shadow:var(--glpisupport-button-shadow);'
            . 'font-size:var(--glpisupport-desktop-font);padding:var(--glpisupport-desktop-py) var(--glpisupport-desktop-px);'
            . 'min-height:var(--glpisupport-desktop-height);font-weight:bold;overflow-wrap:anywhere;max-width:100%;',
    ]);
    $sample = html_writer::div($sample, '', ['style' => local_glpisupport_button_style() . 'padding:12px 0;']);
    $settings->add(new admin_setting_heading('local_glpisupport/preview',
        get_string('preview_heading', 'local_glpisupport'), $sample . get_string('preview_desc', 'local_glpisupport')));
}
