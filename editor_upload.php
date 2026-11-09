<?php
declare(strict_types=1);

/**
 * Image upload for the HugeRTE editor (administrators only).
 *
 * The editor sends the picture here (POST, field "file", plus the CSRF token of the admin session).
 * The picture is checked, saved in the images/ folder and the editor gets its address back as JSON:
 *     {"location": "https://yoursite/images/photo.jpg"}
 * On a problem it answers with an HTTP error code and {"error": {"message": "..."}}.
 *
 * Second job: with the POST field action=list it returns the pictures that are already in the images/ folder
 * (newest first) for the "browse existing images" button of the editor:
 *     {"images": [{"name": "photo.jpg", "url": "https://yoursite/images/photo.jpg", "size": 12345, "time": 1760000000}]}
 */

// Folder (inside the site folder) where the pictures are stored, and the largest file accepted.
const UPLOAD_FOLDER   = 'images';
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;   // 5 MB

chdir(__DIR__);
ob_start();                                // swallow any stray output so the reply is clean JSON
require_once __DIR__ . '/snews.php';       // session, login check (_ADMIN), CSRF helpers, settings
ob_end_clean();

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function upload_fail(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['error' => ['message' => $message]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$nl = strtolower((string)s('language')) === 'nl';
$t = static fn(string $en, string $nl_text): string => $nl ? $nl_text : $en;

// 1. Only a logged-in administrator, only POST, only with the CSRF token of this session
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    upload_fail(405, $t('POST only.', 'Alleen POST is toegestaan.'));
}
if (!_ADMIN) {
    upload_fail(403, $t('Please log in again.', 'Log opnieuw in.'));
}
if (!csrf_check()) {
    upload_fail(403, $t('Security token is invalid. Reload the page and try again.', 'Beveiligingstoken ongeldig. Laad de pagina opnieuw en probeer het nog eens.'));
}

// 1b. "Browse existing images": list the pictures in the images/ folder (the top level only)
if (($_POST['action'] ?? '') === 'list') {
    $list_limit = 300;
    $items = [];
    $list_dir = path_inside_base(UPLOAD_FOLDER);
    if ($list_dir !== null && is_dir($list_dir)) {
        foreach (scandir($list_dir) ?: [] as $entry) {
            $path = $list_dir . DIRECTORY_SEPARATOR . $entry;
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if ($entry[0] === '.' || is_link($path) || !is_file($path) || !in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }
            $items[] = [
                'name' => $entry,
                'url'  => _SITE . UPLOAD_FOLDER . '/' . rawurlencode($entry),
                'size' => (int)filesize($path),
                'time' => (int)filemtime($path),
            ];
        }
    }
    usort($items, static fn(array $a, array $b): int => $b['time'] <=> $a['time']);
    $truncated = count($items) > $list_limit;
    echo json_encode(
        ['images' => array_slice($items, 0, $list_limit), 'truncated' => $truncated],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

// 2. The upload itself
$file = $_FILES['file'] ?? null;
if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
    upload_fail(400, $t('No file received.', 'Geen bestand ontvangen.'));
}
if ((int)$file['error'] !== UPLOAD_ERR_OK) {
    $too_big = in_array((int)$file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    upload_fail($too_big ? 413 : 400, $too_big
        ? $t('The file is too large for this server.', 'Het bestand is te groot voor deze server.')
        : $t('The upload failed.', 'Het uploaden is mislukt.'));
}
$tmp = (string)$file['tmp_name'];
if (!is_uploaded_file($tmp)) {
    upload_fail(400, $t('The upload failed.', 'Het uploaden is mislukt.'));
}
$size = (int)filesize($tmp);
if ($size <= 0 || $size > MAX_UPLOAD_SIZE) {
    upload_fail(413, $t('The picture may be at most 5 MB.', 'De afbeelding mag maximaal 5 MB zijn.'));
}

// 3. What is it really? The content decides, not the file name or the type the browser claims.
$info = @getimagesize($tmp);
$types = [
    IMAGETYPE_JPEG => ['ext' => 'jpg',  'allowed_by' => ['jpg', 'jpeg']],
    IMAGETYPE_PNG  => ['ext' => 'png',  'allowed_by' => ['png']],
    IMAGETYPE_GIF  => ['ext' => 'gif',  'allowed_by' => ['gif']],
    IMAGETYPE_WEBP => ['ext' => 'webp', 'allowed_by' => ['webp']],
];
if ($info === false || !isset($types[$info[2]])) {
    upload_fail(415, $t('This is not a supported picture (JPG, PNG, GIF or WEBP).', 'Dit is geen ondersteunde afbeelding (JPG, PNG, GIF of WEBP).'));
}
if ($info[0] < 1 || $info[1] < 1 || $info[0] > 12000 || $info[1] > 12000) {
    upload_fail(415, $t('The picture dimensions are not acceptable.', 'De afmetingen van de afbeelding zijn niet toegestaan.'));
}

// Respect the "allowed images" list of the site settings (Settings > allowed images)
$allowed = array_filter(array_map(static fn($e) => strtolower(trim($e)), explode(',', (string)s('allowed_images'))));
$type = $types[$info[2]];
if (!array_intersect($type['allowed_by'], $allowed)) {
    upload_fail(415, $t('This picture type is not enabled in the settings.', 'Dit afbeeldingstype staat niet aan in de instellingen.'));
}

// A picture must never carry PHP code (so-called polyglot files)
$head = (string)@file_get_contents($tmp, false, null, 0, 16384);
if (preg_match('/<\?(?:php|=)/i', $head)) {
    upload_fail(415, $t('This file is not accepted.', 'Dit bestand wordt niet geaccepteerd.'));
}

// 4. Target folder (inside the site folder only; created when it does not exist yet)
$target_dir = path_inside_base(UPLOAD_FOLDER);
if ($target_dir === null) {
    if (!@mkdir(UPLOAD_FOLDER, 0755) && !is_dir(UPLOAD_FOLDER)) {
        upload_fail(500, $t('The images folder could not be created.', 'De map images kon niet worden aangemaakt.'));
    }
    $target_dir = path_inside_base(UPLOAD_FOLDER);
}
if ($target_dir === null || !is_dir($target_dir) || !is_writable($target_dir)) {
    upload_fail(500, $t('The images folder is not writable.', 'De map images is niet beschrijfbaar.'));
}

// 5. Clean file name: letters, digits, "-" and "_" only; the extension comes from the real picture type.
//    An existing file is never overwritten.
$base = strtolower((string)pathinfo((string)$file['name'], PATHINFO_FILENAME));
$base = trim((string)preg_replace('/[^a-z0-9_-]+/', '-', $base), '-_');
$base = $base === '' ? 'image' : substr($base, 0, 60);

$name = $base . '.' . $type['ext'];
for ($i = 2; is_file($target_dir . DIRECTORY_SEPARATOR . $name) && $i < 1000; $i++) {
    $name = $base . '-' . $i . '.' . $type['ext'];
}
if (is_file($target_dir . DIRECTORY_SEPARATOR . $name) || upload_has_blocked_ext($name)) {
    upload_fail(500, $t('A free file name could not be found.', 'Er kon geen vrije bestandsnaam worden gevonden.'));
}

$destination = $target_dir . DIRECTORY_SEPARATOR . $name;
if (!move_uploaded_file($tmp, $destination)) {
    upload_fail(500, $t('The picture could not be saved.', 'De afbeelding kon niet worden opgeslagen.'));
}
@chmod($destination, 0644);

echo json_encode(['location' => _SITE . UPLOAD_FOLDER . '/' . rawurlencode($name)], JSON_UNESCAPED_SLASHES);
