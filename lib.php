<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Shared configuration and navigation.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** Whether the portal is enabled. Defaults to enabled before settings are saved. */
function local_glpisupport_enabled(): bool {
    $value = get_config('local_glpisupport', 'enabled');
    return $value === false || (bool)$value;
}

/** Authenticated non-guest users can access the portal. */
function local_glpisupport_available(): bool {
    global $CFG;
    return !during_initial_install() && empty($CFG->upgraderunning)
        && isloggedin() && !isguestuser() && local_glpisupport_enabled();
}

/** Configurable navigation label. */
function local_glpisupport_label(): string {
    $label = trim((string)get_config('local_glpisupport', 'menulabel'));
    return $label !== '' ? $label : get_string('menulabel', 'local_glpisupport');
}

/** Configurable plain-text button label with a usable fallback. */
function local_glpisupport_button_label(string $name): string {
    $label = trim((string)get_config('local_glpisupport', $name));
    return $label !== '' ? $label : get_string('access', 'local_glpisupport');
}

/** Read an integer configuration while preserving zero and bounding the value. */
function local_glpisupport_pixels(string $name, int $default, int $max): int {
    $value = get_config('local_glpisupport', $name);
    if ($value === false || !preg_match('/^\d+$/', (string)$value)) {
        return $default;
    }
    return max(0, min($max, (int)$value));
}

/** Build safe CSS variables from the button settings, without accepting arbitrary CSS. */
function local_glpisupport_button_style(): string {
    $style = [];
    foreach (['desktop' => [90, 24], 'mobile' => [90, 16]] as $device => $defaults) {
        $bottom = local_glpisupport_pixels($device . 'bottom', $defaults[0], 400);
        $edge = local_glpisupport_pixels($device . 'edge', $defaults[1], 120);
        $side = get_config('local_glpisupport', $device . 'side') === 'left' ? 'left' : 'right';
        $prefix = '--glpisupport-' . $device;
        $style[] = $prefix . '-bottom:' . $bottom . 'px';
        $style[] = $prefix . '-edge:' . $edge . 'px';
        $style[] = $prefix . '-left:' . ($side === 'left' ? 'max(' . $edge . 'px, env(safe-area-inset-left, 0px))' : 'auto');
        $style[] = $prefix . '-right:' . ($side === 'right' ? 'max(' . $edge . 'px, env(safe-area-inset-right, 0px))' : 'auto');
        $sizes = ['compact' => [14, 8, 16, 44], 'normal' => [16, 10, 20, 46], 'large' => [18, 12, 24, 52]];
        $size = (string)get_config('local_glpisupport', $device . 'size');
        $values = $sizes[$size] ?? $sizes[$device === 'mobile' ? 'compact' : 'normal'];
        foreach (['font', 'py', 'px', 'height'] as $i => $key) {
            $style[] = $prefix . '-' . $key . ':' . $values[$i] . 'px';
        }
    }
    foreach (['buttonbackground' => ['bg', '#004587'], 'buttontextcolour' => ['text', '#ffffff']] as $key => $values) {
        $colour = (string)get_config('local_glpisupport', $key);
        if (!preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/i', $colour)) {
            $colour = $values[1];
        }
        $style[] = '--glpisupport-button-' . $values[0] . ':' . $colour;
    }
    $style[] = '--glpisupport-button-radius:' . local_glpisupport_pixels('buttonradius', 6, 30) . 'px';
    $shadow = get_config('local_glpisupport', 'buttonshadow');
    $style[] = '--glpisupport-button-shadow:' . ($shadow === false || (bool)$shadow
        ? '0 3px 12px rgba(0,35,70,.2)' : 'none');
    return implode(';', $style) . ';';
}

/** A portal link must use HTTPS without embedded credentials, whitespace or backslashes. */
function local_glpisupport_valid_portal_url(string $url): bool {
    if ($url === '' || strlen($url) > 2048 || str_contains($url, '\\')
            || preg_match('/[\x00-\x20\x7f]/', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $parts = parse_url($url);
    return $parts && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
        && strtolower($parts['scheme'] ?? '') === 'https';
}

/** Fail closed to the known institutional HTTPS endpoint when configuration is invalid. */
function local_glpisupport_portal_url(): string {
    $url = trim((string)get_config('local_glpisupport', 'portalurl'));
    return local_glpisupport_valid_portal_url($url) ? $url : '';
}

/** Native navigation fallback for supported Moodle layouts. */
function local_glpisupport_extend_navigation(global_navigation $navigation): void {
    if (!local_glpisupport_available()) {
        return;
    }
    if (!$navigation->find('local_glpisupport', navigation_node::TYPE_CUSTOM)) {
        $navigation->add(
            local_glpisupport_label(),
            new moodle_url('/local/glpisupport/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_glpisupport'
        );
    }
}
