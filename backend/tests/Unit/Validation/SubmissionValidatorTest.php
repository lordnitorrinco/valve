<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for SubmissionValidator (job application form).
 * Verifies happy path, required fields, formats, lengths, optional fields, and bulk empty input.
 */
class SubmissionValidatorTest extends TestCase
{
    private function validData(): array
    {
        return [
            'firstName'          => 'Pablo',
            'lastName'           => 'García López',
            'gender'             => 'hombre',
            'email'              => 'pablo@example.com',
            'phonePrefix'        => '+34',
            'phone'              => '600123456',
            'countryOfResidence' => 'España',
            'nationality'        => 'española',
            'nationalityOther'   => '',
            'workPermit'         => '',
            'relocation'         => 'si',
            'dateOfBirth'        => '1995-06-15',
            'education'          => 'Grado Universitario',
            'studyArea'          => 'Informática',
            'graduationYear'     => '2020',
            'englishLevel'       => 'B2',
            'situation'          => 'Empleado',
            'jobRole'            => 'Developer',
            'techYearsExperience' => '3',
            'linkedinUrl'        => 'https://linkedin.com/in/pablo',
            'willingToTrain'     => 'si',
            'utm_source'         => '',
            'lead_id'            => '',
        ];
    }

    /** Tests that a fully valid payload produces no validation errors. */
    #[Test]
    public function valid_data_returns_no_errors(): void
    {
        $errors = SubmissionValidator::validate($this->validData());
        $this->assertEmpty($errors);
    }

    /** Tests that clearing required fields yields errors for those keys. */
    #[Test]
    public function missing_required_fields_return_errors(): void
    {
        $data = $this->validData();
        $data['firstName'] = '';
        $data['email'] = '';
        $data['phone'] = '';

        $errors = SubmissionValidator::validate($data);

        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
    }

    /** Tests that a malformed email string triggers an email error. */
    #[Test]
    public function invalid_email_format_returns_error(): void
    {
        $data = $this->validData();
        $data['email'] = 'not-valid';

        $errors = SubmissionValidator::validate($data);
        $this->assertArrayHasKey('email', $errors);
    }

    /** Tests that a non-numeric phone value fails phone validation. */
    #[Test]
    public function invalid_phone_format_returns_error(): void
    {
        $data = $this->validData();
        $data['phone'] = 'abc';

        $errors = SubmissionValidator::validate($data);
        $this->assertArrayHasKey('phone', $errors);
    }

    /** Tests that fields longer than configured max length surface errors. */
    #[Test]
    public function fields_exceeding_max_length_return_errors(): void
    {
        $data = $this->validData();
        $data['firstName'] = str_repeat('a', 101);

        $errors = SubmissionValidator::validate($data);
        $this->assertArrayHasKey('firstName', $errors);
    }

    /** Tests that LinkedIn URL must be a valid URL when provided. */
    #[Test]
    public function invalid_linkedin_url_returns_error(): void
    {
        $data = $this->validData();
        $data['linkedinUrl'] = 'not-a-url';

        $errors = SubmissionValidator::validate($data);
        $this->assertArrayHasKey('linkedinUrl', $errors);
    }

    /** Tests that date of birth must match the expected format (not e.g. DD/MM/YYYY). */
    #[Test]
    public function invalid_date_format_returns_error(): void
    {
        $data = $this->validData();
        $data['dateOfBirth'] = '15/06/1995';

        $errors = SubmissionValidator::validate($data);
        $this->assertArrayHasKey('dateOfBirth', $errors);
    }

    /** Tests that optional URL and text fields may be empty without errors. */
    #[Test]
    public function empty_optional_fields_do_not_cause_errors(): void
    {
        $data = $this->validData();
        $data['linkedinUrl'] = '';
        $data['jobRole'] = '';
        $data['nationalityOther'] = '';

        $errors = SubmissionValidator::validate($data);
        $this->assertEmpty($errors);
    }

    /** Tests that blanking all fields reports every required field as missing. */
    #[Test]
    public function all_required_fields_missing_returns_all_errors(): void
    {
        $data = array_map(fn() => '', $this->validData());
        $errors = SubmissionValidator::validate($data);

        $requiredFields = [
            'firstName', 'lastName', 'gender', 'email', 'phone',
            'countryOfResidence', 'nationality', 'relocation', 'dateOfBirth',
            'education', 'englishLevel', 'situation', 'willingToTrain',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $errors, "Missing error for: $field");
        }
    }
}
