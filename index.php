<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$postData = file_get_contents('php://input');
$_POST = json_decode($postData, true);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception("No vendor found, please run composer install --no-dev");
}
require_once __DIR__.'/vendor/autoload.php';
if (!file_exists(__DIR__.'/config.inc.php')) {
    throw new Exception("config.inc.php file not found, please create if and copy config.inc.example content.");
}
require_once __DIR__.'/config.inc.php';

$base_uri = trim(GLPI_URL, '/').'/apirest.php';

// define api client
$api_client = new \GuzzleHttp\Client();

$session_token = $_POST['session-token'];
$filename  = $_POST['name'];
$filepath  = './files/'.$_POST['name'];

// we may need more file control here, but for the example purpose, i skip
file_put_contents($filepath, base64_decode($_POST['file']));
// construct file keys
$inputname = 'filename';

// let's proceed a document addition
try {
    $response = $api_client->request('POST', $base_uri . '/Document/', [
        'headers' => [
            'Session-Token' => $session_token,
            'App-Token' => $_POST['app-token'],
        ],
        'multipart' => [
            // the document part
            [
                'name' => 'uploadManifest',
                'contents' => json_encode([
                    'input' => [
                        'name'       => $filename,
                        '_filename'  => [$filename],
                    ]
                ])
            ],
            // the FILE part
            [
                'name' => $inputname . '[]',
                'contents' => file_get_contents($filepath),
                'filename' => $filename
            ]
        ]]);
    $document_return = json_decode((string)$response->getBody(), true);
    $json = $response->getBody();
} catch (Exception $exception) {
    $json = json_encode(
        [
            'error' => true,
            "message" => $exception->getMessage(),
            "trace" => $exception->getTraceAsString()
        ]);
} finally {
    unlink($filepath);
}
// display return
//if ($response->getStatusCode() != 201) {
//    throw new Exception("Error when sending file/document to api");
//}

header("Content-Type: application/json");
echo $json;

