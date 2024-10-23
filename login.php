<?php
/**
 * LRsoft Corp.
 * https://lrsoft.id
 *
 * Author : Zaf
 */

session_start();

global $_envs;

const sso_staf = 'S';
const sso_dosen = 'D';
const sso_mahasiswa = 'M';

$_envs = load_env();
$_app_uri = get_env('APP_URI', $_envs);
$_sso_secret = get_env('SSO_SECRET', $_envs);

$_usso = $_POST['usso'] ?? [];
$_info = $_usso['info'] ?? [];
$_login = $_usso['login'] ?? [];
$_level = $_usso['level'] ?? [];

$_signature = $_usso['signature'] ?? time();
$__akses = $_level['kode_akses'] ?? time();
$__view = $_level['kode_view'] ?? time();

if (_signature_verify($_signature, array($_login, $__akses), $_sso_secret)) {

    switch ($__view) {

        case sso_staf:
            // do something with staf data from $_info array

        case sso_dosen:
            // do something with dosen data from $_info array

        case sso_mahasiswa:
            // do something with mahasiswa data from $_info array

            $_SESSION['info'] = $_info;

            _e([
                'status' => true,
                'redirect' => sprintf('%s/print.php', $_app_uri)
            ]);

            break;

        default:
            echo json_encode(array(
                'status' => false,
                'data' => 'Unknown level.'
            ));
    }

} else echo json_encode(array(
    'status' => false,
    'data' => 'Invalid signature.',
));

function _e($data)
{
    global $_envs;
    header('Content-Type: application/json');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
    header('Access-Control-Allow-Origin: ' . get_env('SSO_URI', $_envs));

    echo json_encode($data);
}


function _signature($data, $key)
{
    return hash_hmac('sha256', json_encode($data), $key);
}

function _signature_verify($token, $data, $key)
{
    return $token === _signature($data, $key);
}

function load_env($_file = '.env'): array
{
    $_out = [];
    if (file_exists($_path = sprintf('%s/%s', dirname(__FILE__), $_file))) {
        $_lines = file($_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($_lines as $_line) {
            if (strpos(trim($_line), '#') === 0)
                continue;
            list($__name, $__value) = explode('=', $_line, 2);
            $_out[trim($__name)] = trim($__value);
        }
    }
    return $_out;
}

function get_env($_key, $_envs, $_die = true)
{
    $_tmp = getenv($_key) ?: ($_envs[$_key] ?? false);
    if (!$_tmp && $_die)
        die(sprintf('%s required!', $_key));
    return $_tmp;
}