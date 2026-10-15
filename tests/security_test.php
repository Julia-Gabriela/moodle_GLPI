<?php
// This file is part of Moodle - https://moodle.org/
namespace local_glpisupport;

defined('MOODLE_INTERNAL') || die();

use local_glpisupport\local\access;
use local_glpisupport\local\content;
use local_glpisupport\local\editor;
use local_glpisupport\local\image_policy;

/**
 * Integration checks for execution by the Moodle PHPUnit runner.
 * @package local_glpisupport
 * @copyright 2026 Júlia Gabriela Gomes da Silva
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_glpisupport\local\access
 * @covers \local_glpisupport\local\content
 * @covers \local_glpisupport\local\editor
 * @covers \local_glpisupport\local\image_policy
 */
final class security_test extends \advanced_testcase {
    /** Preserve superglobals outside these tests. */
    private array $requestbackup = [];

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->requestbackup = [$_POST, $_GET, $_SERVER['REQUEST_METHOD'] ?? null];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        global $PAGE;
        $PAGE->set_url('/local/glpisupport/manage.php');
        $PAGE->set_context(\context_system::instance());
        require_once(__DIR__ . '/../lib.php');
    }

    protected function tearDown(): void {
        [$_POST, $_GET, $method] = $this->requestbackup;
        if ($method === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $method;
        }
        parent::tearDown();
    }

    /** Form data representing one complete tutorial save. */
    private function form_data(): \stdClass {
        $data = new \stdClass();
        foreach (content::defaults() as $key => $value) {
            $data->$key = content::is_editor($key) ? ['text' => $value, 'format' => FORMAT_HTML] : $value;
        }
        for ($i = 1; $i <= 5; $i++) {
            $field = 'step' . $i . 'image';
            $data->$field = file_get_unused_draft_itemid();
        }
        return $data;
    }

    /** Assert that a denied request does not change the permanent configuration or images. */
    private function assert_save_blocked(\stdClass $data, string $errorcode): void {
        $before = get_config('local_glpisupport', 'contentjson');
        $files = get_file_storage()->get_area_files(\context_system::instance()->id,
            'local_glpisupport', 'tutorialimage', false, 'id', false);
        $beforeids = array_keys($files);
        try {
            editor::save($data);
            $this->fail('Unauthorised save was accepted');
        } catch (\moodle_exception $e) {
            $this->assertSame($errorcode, $e->errorcode);
        }
        $this->assertSame($before, get_config('local_glpisupport', 'contentjson'));
        $this->assertSame($beforeids, array_keys(get_file_storage()->get_area_files(\context_system::instance()->id,
            'local_glpisupport', 'tutorialimage', false, 'id', false)));
    }

    public function test_course_roles_do_not_grant_site_editing(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        foreach (['student', 'editingteacher'] as $role) {
            $user = $generator->create_user();
            $generator->enrol_user($user->id, $course->id, $role);
            $this->setUser($user);
            $_POST['sesskey'] = sesskey();
            access::require_reader();
            $this->assert_save_blocked($this->form_data(), 'nopermissions');
        }
    }

    public function test_csrf_and_get_saves_are_denied(): void {
        $this->setAdminUser();
        global $USER;
        unset($USER->ignoresesskey);
        $data = $this->form_data();
        foreach ([[], ['sesskey' => 'invalid'], ['sesskey' => ['invalid']]] as $post) {
            $_POST = $post;
            $this->assert_save_blocked($data, 'invalidsesskey');
        }
        $_GET = ['sesskey' => sesskey()];
        $_POST = [];
        $this->assert_save_blocked($data, 'invalidsesskey');
        $_GET = [];
        $_POST['sesskey'] = sesskey();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assert_save_blocked($data, 'invalidparameter');
    }

    public function test_html_is_cleaned_before_storage_and_output(): void {
        $this->setAdminUser();
        $data = $this->form_data();
        $data->step1body = ['text' => '<p><strong>Safe</strong></p><script>alert(1)</script>'
            . '<img src="x" onerror="alert(1)"><a href="javascript:alert(1)">Link</a>'];
        $saved = content::from_form((array)$data);
        $this->assertStringContainsString('<strong>Safe</strong>', $saved['step1body']);
        $this->assertDoesNotMatchRegularExpression('/<script|onerror|javascript:/i', $saved['step1body']);
        $this->assertDoesNotMatchRegularExpression('/<script|onerror|javascript:/i', content::html($data->step1body['text']));
    }

    public function test_admin_can_save_normalised_image_and_clean_text(): void {
        $this->setAdminUser();
        $_POST['sesskey'] = sesskey();
        global $USER;
        $data = $this->form_data();
        $storage = get_file_storage();
        $storage->create_file_from_string((object)[
            'contextid' => \context_user::instance($USER->id)->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $data->step1image, 'filepath' => '/', 'filename' => 'custom.png',
        ], file_get_contents(__DIR__ . '/../pix/step-1.png') . 'TRAILING_PAYLOAD');
        $data->step1body = ['text' => '<p>Updated</p><script>alert(1)</script>'];
        editor::save($data);
        $this->assertSame('<p>Updated</p>', content::get()['step1body']);
        $file = content::image(1);
        $this->assertSame('image/png', $file->get_mimetype());
        $this->assertSame('step-1.png', $file->get_filename());
        $this->assertStringNotContainsString('TRAILING_PAYLOAD', $file->get_content());
    }

    public function test_draft_from_another_user_is_not_read(): void {
        $this->setAdminUser();
        $owner = $this->getDataGenerator()->create_user();
        $draftid = file_get_unused_draft_itemid();
        get_file_storage()->create_file_from_string((object)[
            'contextid' => \context_user::instance($owner->id)->id, 'component' => 'user',
            'filearea' => 'draft', 'itemid' => $draftid, 'filepath' => '/', 'filename' => 'test.png',
        ], file_get_contents(__DIR__ . '/../pix/step-1.png'));
        $this->assertNull(image_policy::draft_image($draftid));
    }

    public function test_portal_url_requires_https_without_credentials(): void {
        foreach (['http://example.org', 'javascript:alert(1)', 'https://user:pass@example.org', '//example.org'] as $url) {
            $this->assertFalse(\local_glpisupport_valid_portal_url($url));
        }
        $this->assertTrue(\local_glpisupport_valid_portal_url('https://support.example.org/'));
    }
}
