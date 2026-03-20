<?php

class SubmissionController
{
    private PDO $db;
    private FileUploader $uploader;
    private Encryptor $encryptor;
    private array $webhookConfig;

    public function __construct(PDO $db, FileUploader $uploader, Encryptor $encryptor, array $webhookConfig)
    {
        $this->db             = $db;
        $this->uploader       = $uploader;
        $this->encryptor      = $encryptor;
        $this->webhookConfig  = $webhookConfig;
    }

    public function store(): void
    {
        $data = self::sanitizeInput();

        $errors = SubmissionValidator::validate($data);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, ['fields' => $errors]);
        }

        $cvFilename = null;
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $cvFilename = $this->uploader->upload($_FILES['cv_file']);
        }

        $id = $this->save($data, $cvFilename);

        SecurityLogger::log('submission_saved', ['id' => $id]);

        if ($this->webhookConfig['enabled'] && !empty($this->webhookConfig['url'])) {
            WebhookForwarder::forward($data, $this->webhookConfig['url']);
        }

        Response::success([
            'id'      => $id,
            'message' => 'Solicitud recibida correctamente',
        ]);
    }

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

    private function save(array $data, ?string $cvFilename): int
    {
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
