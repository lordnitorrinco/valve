<?php

/**
 * Handles admission form submissions.
 *
 * Orchestrates the full submission pipeline:
 *  1. Sanitize input (strip HTML tags)
 *  2. Validate against form rules
 *  3. Upload CV file (if provided)
 *  4. Encrypt PII fields (email, phone)
 *  5. Save to database
 *  6. Forward to external webhook (if enabled)
 *  7. Return success response
 */
class SubmissionController
{
    private PDO $db;
    private FileUploader $uploader;
    private Encryptor $encryptor;

    /** @var array{enabled: bool, url: string} Webhook configuration */
    private array $webhookConfig;

    /**
     * @param PDO          $db             Database connection
     * @param FileUploader $uploader       File upload handler
     * @param Encryptor    $encryptor      PII encryption service
     * @param array        $webhookConfig  Keys: enabled (bool), url (string)
     */
    public function __construct(PDO $db, FileUploader $uploader, Encryptor $encryptor, array $webhookConfig)
    {
        $this->db             = $db;
        $this->uploader       = $uploader;
        $this->encryptor      = $encryptor;
        $this->webhookConfig  = $webhookConfig;
    }

    /**
     * List all submissions with decrypted PII fields.
     *
     * Returns a JSON array of all submissions ordered by newest first.
     * Email and phone are decrypted before sending to the client.
     */
    public function list(): void
    {
        $stmt = $this->db->query('SELECT * FROM submissions ORDER BY created_at DESC');
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['email'] = $this->encryptor->decrypt($row['email']);
            $row['phone'] = $this->encryptor->decrypt($row['phone']);
        }

        Response::success(['submissions' => $rows]);
    }

    /**
     * Download a CV file by submission ID.
     *
     * Looks up the submission, resolves the file path, verifies it exists,
     * and streams it to the client with the correct Content-Type.
     *
     * @param int $id  Submission ID
     */
    public function downloadCv(int $id): void
    {
        $stmt = $this->db->prepare('SELECT cv_filename FROM submissions WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row || empty($row['cv_filename'])) {
            Response::error('CV not found', 404);
        }

        $filePath = '/var/www/uploads/' . $row['cv_filename'];
        if (!file_exists($filePath)) {
            Response::error('File not found on disk', 404);
        }

        $ext = strtolower(pathinfo($row['cv_filename'], PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        // Override the default application/json Content-Type set by CORS
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'), true);
        header('Content-Disposition: attachment; filename="cv_' . $id . '.' . $ext . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Process a new admission submission.
     *
     * Called by the POST /api/submit route after all security checks
     * (CSRF, content-type, origin, honeypot, rate limit) have passed.
     */
    public function store(): void
    {
        $data = self::sanitizeInput();

        // Validate all fields
        $errors = SubmissionValidator::validate($data);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, ['fields' => $errors]);
        }

        // Handle optional CV file upload
        $cvFilename = null;
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $cvFilename = $this->uploader->upload($_FILES['cv_file']);
        }

        // Encrypt PII and persist
        $id = $this->save($data, $cvFilename);

        SecurityLogger::log('submission_saved', ['id' => $id]);

        // Forward to external webhook if enabled (non-blocking)
        if ($this->webhookConfig['enabled'] && !empty($this->webhookConfig['url'])) {
            WebhookForwarder::forward($data, $this->webhookConfig['url']);
        }

        Response::success([
            'id'      => $id,
            'message' => 'Solicitud recibida correctamente',
        ]);
    }

    /**
     * Extract and sanitize all form fields from $_POST.
     *
     * Applies strip_tags() to prevent stored XSS.
     *
     * @return array<string, string> Sanitized field values
     */
    private static function sanitizeInput(): array
    {
        $fields = [
            'firstName', 'lastName', 'gender', 'email', 'phonePrefix', 'phone',
            'countryOfResidence', 'nationality', 'nationalityOther', 'workPermit',
            'relocation', 'dateOfBirth', 'education', 'studyArea', 'graduationYear',
            'englishLevel', 'situation', 'jobRole', 'techYearsExperience',
            'linkedinUrl', 'willingToTrain', 'utm_source', 'lead_id',
        ];

        $data = [];
        foreach ($fields as $field) {
            $raw = trim($_POST[$field] ?? '');
            $data[$field] = strip_tags($raw);
        }
        return $data;
    }

    /**
     * Insert the submission into the database.
     *
     * Encrypts email and phone before storage. Uses prepared statements
     * to prevent SQL injection.
     *
     * @param  array       $data        Sanitized form data
     * @param  string|null $cvFilename  Uploaded CV filename (or null)
     * @return int                      Auto-generated submission ID
     */
    private function save(array $data, ?string $cvFilename): int
    {
        // Parse and validate date format
        $dateOfBirth = null;
        if (!empty($data['dateOfBirth'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['dateOfBirth']);
            $dateOfBirth = $d ? $d->format('Y-m-d') : null;
        }

        $stmt = $this->db->prepare("
            INSERT INTO submissions (
                first_name, last_name, gender, email, phone_prefix, phone,
                country_of_residence, nationality, nationality_other, work_permit,
                relocation, date_of_birth, education, study_area, graduation_year,
                english_level, situation, job_role, tech_years_experience,
                linkedin_url, willing_to_train, cv_filename,
                utm_source, lead_id
            ) VALUES (
                :first_name, :last_name, :gender, :email, :phone_prefix, :phone,
                :country_of_residence, :nationality, :nationality_other, :work_permit,
                :relocation, :date_of_birth, :education, :study_area, :graduation_year,
                :english_level, :situation, :job_role, :tech_years_experience,
                :linkedin_url, :willing_to_train, :cv_filename,
                :utm_source, :lead_id
            )
        ");

        $stmt->execute([
            ':first_name'            => $data['firstName'],
            ':last_name'             => $data['lastName'],
            ':gender'                => $data['gender'],
            ':email'                 => $this->encryptor->encrypt($data['email']),
            ':phone_prefix'          => $data['phonePrefix'] ?: '+34',
            ':phone'                 => $this->encryptor->encrypt($data['phone']),
            ':country_of_residence'  => $data['countryOfResidence'],
            ':nationality'           => $data['nationality'],
            ':nationality_other'     => $data['nationalityOther'] ?: null,
            ':work_permit'           => $data['workPermit'] ?: null,
            ':relocation'            => $data['relocation'],
            ':date_of_birth'         => $dateOfBirth,
            ':education'             => $data['education'],
            ':study_area'            => $data['studyArea'] ?: null,
            ':graduation_year'       => $data['graduationYear'] ?: null,
            ':english_level'         => $data['englishLevel'],
            ':situation'             => $data['situation'],
            ':job_role'              => $data['jobRole'] ?: null,
            ':tech_years_experience' => $data['techYearsExperience'] ?: null,
            ':linkedin_url'          => $data['linkedinUrl'] ?: null,
            ':willing_to_train'      => $data['willingToTrain'],
            ':cv_filename'           => $cvFilename,
            ':utm_source'            => $data['utm_source'] ?: null,
            ':lead_id'               => $data['lead_id'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
