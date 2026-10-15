<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Uploaded institutional images use the Moodle File API.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    /** Describe screenshot storage. Tutorial text is shared site configuration. */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): \core_privacy\local\metadata\collection {
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        return $collection;
    }

    /** Contexts containing an image uploaded by the requested user. */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $list = new \core_privacy\local\request\contextlist();
        $list->add_from_sql("SELECT DISTINCT contextid FROM {files}
            WHERE component = :component AND filearea = :area AND userid = :userid AND filename <> :directory",
            ['component' => 'local_glpisupport', 'area' => 'tutorialimage', 'userid' => $userid, 'directory' => '.']);
        return $list;
    }

    /** List screenshot owners in the approved context. */
    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        $userlist->add_from_sql('userid', "SELECT DISTINCT userid FROM {files}
            WHERE component = :component AND filearea = :area AND contextid = :contextid AND filename <> :directory",
            ['component' => 'local_glpisupport', 'area' => 'tutorialimage',
                'contextid' => $userlist->get_context()->id, 'directory' => '.']);
    }

    /** Export only files owned by the requested user. */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            $writer = \core_privacy\local\request\writer::with_context($context);
            foreach (self::files($context->id) as $file) {
                if ((int)$file->get_userid() !== (int)$contextlist->get_user()->id) {
                    continue;
                }
                $path = ['GLPI', (string)$file->get_itemid()];
                $writer->export_custom_file($path, $file->get_filename(), $file->get_content());
                $writer->export_data($path, (object)[
                    'filename' => $file->get_filename(), 'author' => $file->get_author(),
                ]);
            }
        }
    }

    /** Delete all custom screenshots in an approved context. */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        get_file_storage()->delete_area_files($context->id, 'local_glpisupport', 'tutorialimage');
    }

    /** Delete screenshots owned by the requested user. */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_owned($context->id, [(int)$contextlist->get_user()->id]);
        }
    }

    /** Delete screenshots owned by an approved list of users. */
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        self::delete_owned($userlist->get_context()->id, $userlist->get_userids());
    }

    /** Fetch only plugin-managed screenshot files. */
    private static function files(int $contextid): array {
        return get_file_storage()->get_area_files($contextid, 'local_glpisupport', 'tutorialimage', false, 'id', false);
    }

    /** Scope deletion to file ownership, preserving other administrators' work. */
    private static function delete_owned(int $contextid, array $userids): void {
        foreach (self::files($contextid) as $file) {
            if (in_array((int)$file->get_userid(), array_map('intval', $userids), true)) {
                $file->delete();
            }
        }
    }
}
