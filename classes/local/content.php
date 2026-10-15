<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Editable support tutorial content and screenshots.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content {
    /** Default content used until administrators save their own version. */
    public static function defaults(): array {
        $str = static fn(string $key): string => get_string($key, 'local_glpisupport');
        $para = static fn(string $text): string => \html_writer::tag('p', s($text));
        $data = [
            'pagetitle' => $str('page_title'),
            'intro' => $para($str('intro')),
            'audiencetitle' => $str('audience_title'),
            'audiencetext' => $para($str('audience_text')),
            'summarytitle' => $str('summary'),
            'imagecaption' => $str('enlarge_hint'),
            'trackingtitle' => $str('tracking_title'),
            'trackingbody' => $para($str('tracking_text')),
        ];
        for ($i = 1; $i <= 5; $i++) {
            $body = $para($str('step' . $i . '_text'));
            if ($i === 5) {
                $body .= '<ul>';
                foreach (['data', 'problem', 'description'] as $field) {
                    $body .= '<li><strong>' . s($str('fields_' . $field)) . '</strong> '
                        . s($str('fields_' . $field . '_text')) . '</li>';
                }
                $body .= '</ul>' . $para($str('send_text'));
            }
            $data['step' . $i . 'title'] = $str('step' . $i . '_title');
            $data['step' . $i . 'body'] = $body;
            $data['step' . $i . 'alt'] = $str('step' . $i . '_alt');
            $data['step' . $i . 'showimage'] = 0;
        }
        return $data;
    }

    /** Merge saved values with defaults for backwards-compatible upgrades. */
    public static function get(): array {
        $defaults = self::defaults();
        $saved = json_decode((string)get_config('local_glpisupport', 'contentjson'), true);
        if (!is_array($saved)) {
            return $defaults;
        }
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $saved) && (is_string($saved[$key]) || is_int($saved[$key]))) {
                $defaults[$key] = str_ends_with($key, 'showimage') ? (int)(bool)$saved[$key] : (string)$saved[$key];
            }
        }
        return $defaults;
    }

    /** Validate expected scalar/editor shapes and limit work done by the HTML cleaner. */
    public static function validate_form(array $data): array {
        $errors = [];
        foreach (self::defaults() as $key => $default) {
            if (str_ends_with($key, 'showimage')) {
                if (!in_array($data[$key] ?? 0, [0, 1, '0', '1'], true)) {
                    $errors[$key] = get_string('invalidcontent', 'local_glpisupport');
                }
            } else if (self::is_editor($key)) {
                $editor = $data[$key] ?? null;
                $value = is_array($editor) ? ($editor['text'] ?? null) : null;
                if (!is_string($value) || strlen($value) > 65536) {
                    $errors[$key] = get_string('invalidcontent', 'local_glpisupport');
                }
            } else {
                $value = $data[$key] ?? null;
                if (!is_string($value) || trim($value) === '' || strlen($value) > 2000) {
                    $errors[$key] = get_string('invalidcontent', 'local_glpisupport');
                }
            }
        }
        return $errors;
    }

    /** Whether a field is rich text. */
    public static function is_editor(string $key): bool {
        return in_array($key, ['intro', 'audiencetext', 'trackingbody'], true) || str_ends_with($key, 'body');
    }

    /** Keep only known fields and clean HTML before it reaches stored configuration. */
    public static function from_form(array $data): array {
        if (self::validate_form($data)) {
            throw new \moodle_exception('invalidcontent', 'local_glpisupport');
        }
        $saved = [];
        foreach (self::defaults() as $key => $default) {
            if (str_ends_with($key, 'showimage')) {
                $saved[$key] = (int)($data[$key] ?? 0);
            } else if (self::is_editor($key)) {
                $saved[$key] = clean_text($data[$key]['text'], FORMAT_HTML);
            } else {
                $saved[$key] = trim(clean_param($data[$key], PARAM_TEXT));
            }
        }
        return $saved;
    }

    /** Single screenshot file, or false when no image has been uploaded. */
    public static function image(int $step) {
        $files = get_file_storage()->get_area_files(\context_system::instance()->id,
            'local_glpisupport', 'tutorialimage', $step, 'id', false);
        return $files ? reset($files) : false;
    }

    /** Cache key changes whenever an administrator replaces a screenshot. */
    public static function image_url(int $step): string {
        $file = self::image($step);
        return (new \moodle_url('/local/glpisupport/image.php', [
            'step' => $step,
            'v' => $file ? $file->get_contenthash() : 'original',
        ]))->out(false);
    }

    /** Filter and clean editor content through the Moodle text pipeline. */
    public static function html(string $text): string {
        return clean_text(format_text(clean_text($text, FORMAT_HTML), FORMAT_HTML, [
            'context' => \context_system::instance(), 'noclean' => false, 'trusted' => false,
            'filter' => false, 'para' => false,
        ]), FORMAT_HTML);
    }
}
