<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Validate and re-encode screenshots, discarding embedded metadata and trailing data.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class image_policy {
    /** Maximum decoded image size, checked before GD allocates a bitmap. */
    public const MAX_PIXELS = 16000000;
    /** Maximum length of either side. */
    public const MAX_DIMENSION = 8000;

    /** Respect both the site limit and a five MiB ceiling. */
    public static function max_bytes(): int {
        global $CFG;
        return get_max_upload_file_size($CFG->maxbytes, 0, 5 * 1024 * 1024);
    }

    /** Turn a real raster image into an image-only file with a verified MIME type. */
    public static function normalise(\stored_file $file): array {
        if ($file->get_filesize() < 1 || $file->get_filesize() > self::max_bytes()
                || $file->get_filepath() !== '/') {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        $bytes = $file->get_content();
        $info = @getimagesizefromstring($bytes);
        $formats = [
            'image/png' => ['extensions' => ['png'], 'encoder' => 'imagepng', 'extension' => 'png'],
            'image/jpeg' => ['extensions' => ['jpg', 'jpeg'], 'encoder' => 'imagejpeg', 'extension' => 'jpg'],
            'image/webp' => ['extensions' => ['webp'], 'encoder' => 'imagewebp', 'extension' => 'webp'],
        ];
        $mime = $info['mime'] ?? '';
        $format = $formats[$mime] ?? null;
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!$info || !$format || !in_array($extension, $format['extensions'], true)) {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION
                || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw new \moodle_exception('image_dimensions', 'local_glpisupport');
        }
        $encoder = $format['encoder'];
        if (!function_exists('imagecreatefromstring') || !function_exists($encoder)) {
            throw new \moodle_exception('image_decoder_missing', 'local_glpisupport');
        }
        // A recognised header is not proof that the complete file is a decodable image.
        $image = @imagecreatefromstring($bytes);
        if (!$image) {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        try {
            imagesavealpha($image, true);
            ob_start();
            try {
                $ok = $mime === 'image/png' ? @$encoder($image, null, 6) : @$encoder($image, null, 95);
                $normalised = ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($image);
        }
        if (!$ok || !$normalised || strlen($normalised) > self::max_bytes()) {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        return ['content' => $normalised, 'mimetype' => $mime, 'extension' => $format['extension']];
    }

    /** Only drafts belonging to the current user's context may be read. */
    public static function draft_image(int $draftid): ?array {
        global $USER;
        if ($draftid < 1) {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        $files = get_file_storage()->get_area_files(\context_user::instance($USER->id)->id,
            'user', 'draft', $draftid, 'id', false);
        if (count($files) > 1) {
            throw new \moodle_exception('invalidimage', 'local_glpisupport');
        }
        return $files ? self::normalise(reset($files)) : null;
    }
}
