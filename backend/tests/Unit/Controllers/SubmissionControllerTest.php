<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for SubmissionController.
 * Uses SQLite in-memory DB for real SQL execution and a mock FileUploader.
 */
class SubmissionControllerTest extends TestCase
{
    private PDO $db;
    private Encryptor $encryptor;
    private FileUploader&MockObject $uploader;

    private const ENCRYPTION_KEY = 'test-encryption-key-for-unit-tests';

    protected function setUp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->exec('
            CREATE TABLE submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT,
                last_name TEXT,
                gender TEXT,
                email TEXT,
                phone_prefix TEXT,
                phone TEXT,
                country_of_residence TEXT,
                nationality TEXT,
                nationality_other TEXT,
                work_permit TEXT,
                relocation TEXT,
                date_of_birth TEXT,
                education TEXT,
                study_area TEXT,
                graduation_year TEXT,
                english_level TEXT,
                situation TEXT,
                job_role TEXT,
                tech_years_experience TEXT,
                linkedin_url TEXT,
                willing_to_train TEXT,
                cv_filename TEXT,
                utm_source TEXT,
                lead_id TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->encryptor = new Encryptor(self::ENCRYPTION_KEY);
        $this->uploader = $this->createMock(FileUploader::class);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
    }

    private function makeController(array $webhookConfig = ['enabled' => false, 'url' => '']): SubmissionController
    {
        return new SubmissionController($this->db, $this->uploader, $this->encryptor, $webhookConfig);
    }

    private function setValidPost(): void
    {
        $_POST = [
            'firstName'          => 'John',
            'lastName'           => 'Doe',
            'gender'             => 'male',
            'email'              => 'john@example.com',
            'phonePrefix'        => '+34',
            'phone'              => '612345678',
            'countryOfResidence' => 'Spain',
            'nationality'        => 'Spanish',
            'nationalityOther'   => '',
            'workPermit'         => '',
            'relocation'         => 'yes',
            'dateOfBirth'        => '1990-01-15',
            'education'          => 'university',
            'studyArea'          => 'CS',
            'graduationYear'     => '2012',
            'englishLevel'       => 'advanced',
            'situation'          => 'employed',
            'jobRole'            => 'Developer',
            'techYearsExperience' => '5',
            'linkedinUrl'        => '',
            'willingToTrain'     => 'yes',
            'utm_source'         => '',
            'lead_id'            => '',
        ];
    }

    /** Insert a row with pre-encrypted fields directly into the DB. */
    private function insertTestRow(string $email, string $phone, string $cvFilename = ''): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO submissions (
                first_name, last_name, gender, email, phone_prefix, phone,
                country_of_residence, nationality, relocation, date_of_birth,
                education, english_level, situation, willing_to_train, cv_filename
            ) VALUES (
                'John', 'Doe', 'male', :email, '+34', :phone,
                'Spain', 'Spanish', 'yes', '1990-01-15',
                'university', 'advanced', 'employed', 'yes', :cv
            )
        ");
        $stmt->execute([
            ':email' => $this->encryptor->encrypt($email),
            ':phone' => $this->encryptor->encrypt($phone),
            ':cv'    => $cvFilename,
        ]);
        return (int) $this->db->lastInsertId();
    }

    // ── list() ──────────────────────────────────────────────────────

    #[Test]
    public function list_returns_decrypted_submissions(): void
    {
        $this->insertTestRow('alice@test.com', '611111111');
        $this->insertTestRow('bob@test.com', '622222222');

        $controller = $this->makeController();

        try {
            $controller->list();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $submissions = $decoded['submissions'];

            $this->assertCount(2, $submissions);

            $emails = array_column($submissions, 'email');
            $phones = array_column($submissions, 'phone');
            $this->assertContains('alice@test.com', $emails);
            $this->assertContains('bob@test.com', $emails);
            $this->assertContains('611111111', $phones);
            $this->assertContains('622222222', $phones);
        }
    }

    #[Test]
    public function list_returns_empty_when_no_submissions(): void
    {
        $controller = $this->makeController();

        try {
            $controller->list();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertEmpty($decoded['submissions']);
        }
    }

    // ── downloadCv() ────────────────────────────────────────────────

    #[Test]
    public function downloadCv_returns_404_when_no_record(): void
    {
        $controller = $this->makeController();

        try {
            $controller->downloadCv(9999);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('CV not found', $decoded['error']);
        }
    }

    #[Test]
    public function downloadCv_returns_404_when_cv_filename_empty(): void
    {
        $id = $this->insertTestRow('a@b.com', '600000000', '');
        $controller = $this->makeController();

        try {
            $controller->downloadCv($id);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('CV not found', $decoded['error']);
        }
    }

    #[Test]
    public function downloadCv_returns_404_when_file_not_on_disk(): void
    {
        $id = $this->insertTestRow('a@b.com', '600000000', 'nonexistent_file.pdf');
        $controller = $this->makeController();

        try {
            $controller->downloadCv($id);
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(404, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('File not found on disk', $decoded['error']);
        }
    }

    #[Test]
    public function downloadCv_streams_file_when_found(): void
    {
        $uploadDir = '/var/www/uploads';
        if (!@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            $this->markTestSkipped('Cannot create /var/www/uploads for test');
        }

        $filename = 'test_cv_' . uniqid() . '.pdf';
        $filePath = $uploadDir . '/' . $filename;
        file_put_contents($filePath, '%PDF-test-content');

        $id = $this->insertTestRow('a@b.com', '600000000', $filename);
        $controller = $this->makeController();

        ob_start();
        try {
            @$controller->downloadCv($id);
            ob_end_clean();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $output = ob_get_clean();
            $this->assertSame(200, $e->statusCode);
            $this->assertStringContainsString('%PDF-test-content', $output);
        } finally {
            @unlink($filePath);
        }
    }

    // ── store() ─────────────────────────────────────────────────────

    #[Test]
    public function store_saves_submission_and_returns_success(): void
    {
        $this->setValidPost();
        $controller = $this->makeController();

        try {
            $controller->store();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Solicitud recibida correctamente', $decoded['message']);
            $this->assertIsInt($decoded['id']);
        }

        $row = $this->db->query('SELECT * FROM submissions')->fetch();
        $this->assertSame('John', $row['first_name']);
        $this->assertSame('Doe', $row['last_name']);
    }

    #[Test]
    public function store_returns_422_on_validation_failure(): void
    {
        $_POST = ['firstName' => '', 'lastName' => '', 'gender' => '', 'email' => '',
            'phonePrefix' => '', 'phone' => '', 'countryOfResidence' => '',
            'nationality' => '', 'nationalityOther' => '', 'workPermit' => '',
            'relocation' => '', 'dateOfBirth' => '', 'education' => '',
            'studyArea' => '', 'graduationYear' => '', 'englishLevel' => '',
            'situation' => '', 'jobRole' => '', 'techYearsExperience' => '',
            'linkedinUrl' => '', 'willingToTrain' => '', 'utm_source' => '',
            'lead_id' => ''];

        $controller = $this->makeController();

        try {
            $controller->store();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(422, $e->statusCode);
            $decoded = json_decode($e->body, true);
            $this->assertSame('Validation failed', $decoded['error']);
            $this->assertNotEmpty($decoded['fields']);
        }
    }

    #[Test]
    public function store_handles_file_upload(): void
    {
        $this->setValidPost();
        $_FILES['cv_file'] = [
            'name'     => 'resume.pdf',
            'type'     => 'application/pdf',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error'    => UPLOAD_ERR_OK,
            'size'     => 1024,
        ];

        $this->uploader->expects($this->once())
            ->method('upload')
            ->willReturn('random_hex_name.pdf');

        $controller = $this->makeController();

        try {
            $controller->store();
            $this->fail('HaltException was not thrown');
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
        }

        $row = $this->db->query('SELECT cv_filename FROM submissions')->fetch();
        $this->assertSame('random_hex_name.pdf', $row['cv_filename']);
    }

    #[Test]
    public function store_encrypts_email_and_phone(): void
    {
        $this->setValidPost();
        $controller = $this->makeController();

        try {
            $controller->store();
        } catch (HaltException) {
            // expected
        }

        $row = $this->db->query('SELECT email, phone FROM submissions')->fetch();

        $this->assertNotSame('john@example.com', $row['email']);
        $this->assertNotSame('612345678', $row['phone']);

        $this->assertSame('john@example.com', $this->encryptor->decrypt($row['email']));
        $this->assertSame('612345678', $this->encryptor->decrypt($row['phone']));
    }

    #[Test]
    public function store_strips_html_tags_from_input(): void
    {
        $this->setValidPost();
        $_POST['firstName'] = '<b>John</b>';
        $_POST['lastName'] = '<script>alert(1)</script>Doe';

        $controller = $this->makeController();

        try {
            $controller->store();
        } catch (HaltException) {
            // expected
        }

        $row = $this->db->query('SELECT first_name, last_name FROM submissions')->fetch();
        $this->assertSame('John', $row['first_name']);
        $this->assertSame('alert(1)Doe', $row['last_name']);
    }

    #[Test]
    public function store_saves_null_cv_when_no_file_uploaded(): void
    {
        $this->setValidPost();
        $controller = $this->makeController();

        try {
            $controller->store();
        } catch (HaltException) {
            // expected
        }

        $row = $this->db->query('SELECT cv_filename FROM submissions')->fetch();
        $this->assertNull($row['cv_filename']);
    }

    #[Test]
    public function store_parses_date_of_birth(): void
    {
        $this->setValidPost();
        $_POST['dateOfBirth'] = '1995-06-20';
        $controller = $this->makeController();

        try {
            $controller->store();
        } catch (HaltException) {
            // expected
        }

        $row = $this->db->query('SELECT date_of_birth FROM submissions')->fetch();
        $this->assertSame('1995-06-20', $row['date_of_birth']);
    }

    #[Test]
    public function store_sets_default_phone_prefix(): void
    {
        $this->setValidPost();
        $_POST['phonePrefix'] = '';
        $controller = $this->makeController();

        try {
            $controller->store();
        } catch (HaltException) {
            // expected
        }

        $row = $this->db->query('SELECT phone_prefix FROM submissions')->fetch();
        $this->assertSame('+34', $row['phone_prefix']);
    }

    #[Test]
    public function store_skips_webhook_when_disabled(): void
    {
        $this->setValidPost();
        $controller = $this->makeController(['enabled' => false, 'url' => 'http://hook.test']);

        try {
            $controller->store();
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
        }
    }

    #[Test]
    public function store_forwards_to_webhook_when_enabled(): void
    {
        $this->setValidPost();
        $controller = $this->makeController([
            'enabled' => true,
            'url'     => 'http://192.0.2.1/webhook',
        ]);

        try {
            $controller->store();
        } catch (HaltException $e) {
            $this->assertSame(200, $e->statusCode);
        }

        $count = $this->db->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
        $this->assertSame(1, (int) $count);
    }
}
