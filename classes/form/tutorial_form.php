<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Administrator-only tutorial editor, with one image manager per step.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tutorial_form extends \moodleform {
    /** Image upload policy shared by preparation, validation and saving. */
    public static function file_options(): array {
        return [
            'maxfiles' => 1, 'subdirs' => false,
            'maxbytes' => \local_glpisupport\local\image_policy::max_bytes(),
            'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp'],
            'return_types' => FILE_INTERNAL,
        ];
    }

    /** Define editable areas without exposing code or raw configuration JSON. */
    public function definition(): void {
        $mform = $this->_form;
        $str = static fn(string $key): string => get_string($key, 'local_glpisupport');
        $editor = ['maxfiles' => 0, 'context' => $this->_customdata['context']];
        $text = static function(string $name, string $label) use ($mform): void {
            $mform->addElement('text', $name, $label, ['size' => 70]);
            $mform->setType($name, PARAM_TEXT);
            $mform->addRule($name, get_string('required'), 'required', null, 'client');
        };
        $body = static function(string $name, string $label) use ($mform, $editor): void {
            $mform->addElement('editor', $name, $label, ['rows' => 6], $editor);
            $mform->setType($name, PARAM_RAW);
        };
        $mform->addElement('header', 'introsection', $str('edit_intro'));
        $mform->setExpanded('introsection', true);
        $text('pagetitle', $str('edit_pagetitle'));
        $body('intro', $str('edit_introbody'));
        $text('audiencetitle', $str('edit_audiencetitle'));
        $body('audiencetext', $str('edit_audiencebody'));
        $text('summarytitle', $str('edit_summarytitle'));
        $text('imagecaption', $str('edit_imagecaption'));

        for ($i = 1; $i <= 5; $i++) {
            $prefix = 'step' . $i;
            $mform->addElement('header', $prefix . 'section', get_string('edit_step', 'local_glpisupport', $i));
            $mform->setExpanded($prefix . 'section', false);
            $text($prefix . 'title', $str('edit_steptitle'));
            $body($prefix . 'body', $str('edit_stepbody'));
            $mform->addElement('static', $prefix . 'currentimage', $str('edit_currentimage'),
                \html_writer::empty_tag('img', [
                    'src' => \local_glpisupport\local\content::image_url($i),
                    'alt' => $this->_customdata['content'][$prefix . 'alt'],
                    'style' => 'max-width:100%;width:360px;height:auto;border:1px solid #dce5ef;',
                ]));
            $mform->addElement('filemanager', $prefix . 'image', $str('edit_image'), null, self::file_options());
            $mform->addElement('static', $prefix . 'imagehelp', '', $str('edit_imagehelp'));
            $text($prefix . 'alt', $str('edit_imagealt'));
            $mform->addElement('advcheckbox', $prefix . 'showimage', $str('edit_showimage'));
        }
        $mform->addElement('header', 'trackingsection', $str('edit_tracking'));
        $mform->setExpanded('trackingsection', false);
        $text('trackingtitle', $str('edit_trackingtitle'));
        $body('trackingbody', $str('edit_trackingbody'));
        $this->add_action_buttons(true, $str('savecontent'));
    }

    /** Reject non-image payloads regardless of their filename extension. */
    public function validation($data, $files): array {
        $errors = array_merge(parent::validation($data, $files), \local_glpisupport\local\content::validate_form($data));
        for ($i = 1; $i <= 5; $i++) {
            $key = 'step' . $i . 'image';
            $draftid = $data[$key] ?? null;
            if (!is_scalar($draftid) || !ctype_digit((string)$draftid) || (int)$draftid < 1) {
                $errors[$key] = get_string('invalidimage', 'local_glpisupport');
                continue;
            }
            try {
                \local_glpisupport\local\image_policy::draft_image((int)$draftid);
            } catch (\moodle_exception $e) {
                $errors[$key] = $e->getMessage();
            }
        }
        return $errors;
    }
}
