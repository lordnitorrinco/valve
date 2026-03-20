<?php

class SubmissionValidator
{
    private const REQUIRED_FIELDS = [
        'firstName'          => 'El nombre es obligatorio',
        'lastName'           => 'Los apellidos son obligatorios',
        'gender'             => 'El género es obligatorio',
        'email'              => 'El email es obligatorio',
        'phone'              => 'El teléfono es obligatorio',
        'countryOfResidence' => 'El país es obligatorio',
        'nationality'        => 'La nacionalidad es obligatoria',
        'relocation'         => 'Campo obligatorio',
        'dateOfBirth'        => 'La fecha es obligatoria',
        'education'          => 'El nivel de estudios es obligatorio',
        'englishLevel'       => 'El nivel de inglés es obligatorio',
        'situation'          => 'La situación es obligatoria',
        'willingToTrain'     => 'Campo obligatorio',
    ];

    private const MAX_LENGTHS = [
        'firstName'          => 100,
        'lastName'           => 150,
        'email'              => 255,
        'phone'              => 30,
        'phonePrefix'        => 10,
        'countryOfResidence' => 100,
        'nationality'        => 100,
        'nationalityOther'   => 100,
        'jobRole'            => 150,
        'linkedinUrl'        => 500,
        'studyArea'          => 100,
        'situation'          => 50,
        'education'          => 50,
    ];

    public static function validate(array $data): array
    {
        $v = new Validator();

        foreach (self::REQUIRED_FIELDS as $field => $message) {
            $v->required($field, $data[$field], $message);
        }

        foreach (self::MAX_LENGTHS as $field => $max) {
            $v->maxLength($field, $data[$field], $max, "Máximo $max caracteres");
        }

        $v->email('email', $data['email'], 'Email no válido');
        $v->pattern('phone', $data['phone'], '/^[\d\s\-+()]{7,30}$/', 'Formato de teléfono no válido');

        if (!empty($data['linkedinUrl'])) {
            $v->url('linkedinUrl', $data['linkedinUrl'], 'URL de LinkedIn no válida');
        }

        if (!empty($data['dateOfBirth'])) {
            $v->pattern('dateOfBirth', $data['dateOfBirth'], '/^\d{4}-\d{2}-\d{2}$/', 'Formato de fecha no válido');
        }

        return $v->errors();
    }
}
