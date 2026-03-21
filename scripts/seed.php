<?php
/**
 * Inserts sample submissions + mock CV files for demos / evaluation.
 *
 * Run inside the backend container (see Makefile `make seed`).
 * Paths assume the standard Docker layout (/var/www/...).
 */

declare(strict_types=1);

require_once '/var/www/app/Services/Encryptor.php';

$config = require '/var/www/config/app.php';

$db = new PDO(
    sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['name'],
        $config['db']['charset']
    ),
    $config['db']['user'],
    $config['db']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$enc = new Encryptor($config['security']['encryption_key']);
$uploadDir = $config['uploads']['directory'];

$names = ['Carlos','María','Alejandro','Laura','Pablo','Sofía','Daniel','Lucía','Tomás','Elena',
          'Javier','Carmen','Miguel','Ana','Fernando','Isabel','Diego','Marta','Raúl','Paula',
          'Hugo','Irene','Adrián','Sara','Marcos','Claudia','Óscar','Nuria','Álvaro','Cristina'];
$lastNames = ['García López','Martínez Ruiz','Fernández Díaz','Rodríguez Gil','Sánchez Moreno',
              'López Torres','Díaz Navarro','Moreno Jiménez','Muñoz Romero','Álvarez Ortega',
              'Romero Delgado','Alonso Vega','Gutiérrez Molina','Navarro Castro','Torres Ramos',
              'Domínguez Iglesias','Vázquez Medina','Ramos Herrero','Gil Rubio','Ramírez Serrano',
              'Blanco León','Suárez Cano','Molina Ortiz','Morales Cortés','Ortega Garrido',
              'Delgado Moya','Castro Fuentes','Ruiz Campos','Jiménez Peña','Herrero Calvo'];
$relocations = ['si','no','depende'];
$educations = ['FP Grado Superior','Grado Universitario','Máster / Postgrado','Doctorado'];
$studyAreas = ['Ingeniería y Arquitectura','Informática y Tecnologías de la información','Economía y Empresa','Ciencias Sociales','Ciencias Naturales y Exactas'];
$levels = ['B1','B2','C1','C2'];
$situations = ['Empleado','Estudiando','En búsqueda de empleo','Desempleado'];
$roles = ['Analista de datos','Desarrollador web','Project Manager','Consultor IT','QA Tester','DevOps','Product Manager','UX Designer'];
$willingness = ['si','no','necesito_mas_info'];

$genders = ['hombre', 'mujer'];

$stmt = $db->prepare("INSERT INTO submissions (
    first_name, last_name, gender, email, phone_prefix, phone,
    country_of_residence, nationality, nationality_other, work_permit,
    relocation, date_of_birth, education, study_area, graduation_year,
    english_level, situation, job_role, tech_years_experience,
    linkedin_url, willing_to_train, cv_filename, utm_source, lead_id
) VALUES (
    :fn, :ln, :gn, :em, :pp, :ph,
    :cor, :nat, :no, :wp,
    :rel, :dob, :edu, :sa, :gy,
    :el, :sit, :jr, :tye,
    :li, :wt, :cv, :utm, :lid
)");

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

for ($i = 0; $i < 30; $i++) {
    $fn = $names[$i];
    $ln = $lastNames[$i];
    $gender = $genders[$i % 2];
    $email = strtolower(str_replace(['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ñ'],
                                    ['a','e','i','o','u','u','n','a','e','i','o','u','n'], $fn)) . '@example.com';
    $phone = '6' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    $dob = sprintf('%d-%02d-%02d', random_int(1985, 2002), random_int(1, 12), random_int(1, 28));
    $edu = $educations[array_rand($educations)];
    $sa = $studyAreas[array_rand($studyAreas)];
    $gy = (string) random_int(2010, 2025);
    $sit = $situations[array_rand($situations)];
    $jr = $sit === 'Empleado' ? $roles[array_rand($roles)] : null;
    $tye = random_int(0, 15);
    $wt = $willingness[array_rand($willingness)];

    $cvFile = bin2hex(random_bytes(16)) . '.pdf';
    file_put_contents($uploadDir . '/' . $cvFile, '%PDF-1.4 mock cv ' . $fn);

    $stmt->execute([
        ':fn' => $fn, ':ln' => $ln, ':gn' => $gender,
        ':em' => $enc->encrypt($email), ':pp' => '+34', ':ph' => $enc->encrypt($phone),
        ':cor' => 'España', ':nat' => 'española', ':no' => null, ':wp' => null,
        ':rel' => $relocations[array_rand($relocations)],
        ':dob' => $dob, ':edu' => $edu, ':sa' => $sa, ':gy' => $gy,
        ':el' => $levels[array_rand($levels)], ':sit' => $sit, ':jr' => $jr, ':tye' => $tye,
        ':li' => 'https://linkedin.com/in/' . strtolower(str_replace(['á','é','í','ó','ú','ñ'],['a','e','i','o','u','n'], $fn)),
        ':wt' => $wt, ':cv' => $cvFile, ':utm' => 'seed', ':lid' => 'mock-' . ($i + 1),
    ]);
    echo "Inserted $fn $ln\n";
}
echo "Done: 30 submissions inserted.\n";
