<?php

// Include PHPMailer classes
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//function to base64UrlEncode data
function base64UrlEncode($data)
{
    $urlSafeData = strtr(base64_encode($data), '+/', '-_');
    return rtrim($urlSafeData, '=');
}

//function to base64UrlDecode data
function base64UrlDecode($data)
{
    $urlUnsafeData = strtr($data, '-_', '+/');
    $paddedData = str_pad($urlUnsafeData, strlen($urlUnsafeData) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($paddedData);
}

//function to generate JWT
function generateJWT($algorithm, $header, $payload, $secret)
{
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    //delimit with period(.)
    $dataEncoded = $headerEncoded . "." . $payloadEncoded;
    $rawSignature = hash_hmac($algorithm, $dataEncoded, $secret, true);
    $signatureEncoded = base64UrlEncode($rawSignature);

    //delimit with second (.)
    $jwt = $dataEncoded . "." . $signatureEncoded;

    return $jwt;
}

//verify JWT
function verifyJWT($algorithm, $jwt, $secret)
{
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
    $dataEncoded = $headerEncoded . "." . $payloadEncoded;
    $signature = base64UrlDecode($signatureEncoded);
    $rawSignature = hash_hmac($algorithm, $dataEncoded, $secret, true);

    return hash_equals($rawSignature, $signature);
}

// return payload
function returnPayload($jwt)
{
    $dataArray = explode(".", $jwt);
    $payloadEncoded = $dataArray[1];
    $payloadDecoded = json_decode(base64_decode($payloadEncoded), true);

    return $payloadDecoded;
}

// return payload claim
function payloadClaim($jwt, $param)
{
    $dataArray = explode(".", $jwt);
    $payloadEncoded = $dataArray[1];
    $payloadDecoded = json_decode(base64_decode($payloadEncoded), true);
    $claim = $payloadDecoded[$param];

    return $claim;
}

//check availability of parameters
function checkAvailability($params)
{
    foreach ($params as $param) {
        if (!isset($_REQUEST[$param]) || $_REQUEST[$param] === null/* empty($_REQUEST[$param]) */) {
            return false;
        }
    }

    return true;
}

function sec_session_start()
{
    $session_name = 'sec_session_id';   // Set a custom session name
    /*Sets the session name. 
     *This must come before session_set_cookie_params due to an undocumented bug/feature in PHP. 
     */
    session_name($session_name);

    $secure = false;
    // This stops JavaScript being able to access the session id. SET TO true to disalow javascript access
    $httponly = false;
    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../pages/error.php");
        exit();
    }
    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);

    session_start();            // Start the PHP session 
    session_regenerate_id(true);    // regenerated the session, delete the old one. 
}

function checkbrute($user_id, $user_type, $mysqli)
{
    // Get timestamp of current time 
    $now = time();

    // All login attempts are counted from the past 2 hours. 
    $valid_attempts = $now - (2 * 60 * 60);

    if ($stmt = $mysqli->prepare("SELECT time_stamp FROM login_attempts WHERE user_id = ? AND time_stamp > '" . $valid_attempts . "' AND user_type = '" . $user_type . "'")) {
        $stmt->bind_param('i', $user_id);

        // Execute the prepared query. 
        $stmt->execute();
        $stmt->store_result();

        // If there have been more than 5 failed logins 
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    }
}

function esc_url($url)
{
    if ('' == $url) {
        return $url;
    }

    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;

    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }

    $url = str_replace(';//', '://', $url);

    $url = htmlentities($url);

    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);

    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function redirect_to($url)
{
    header("Location: {$url}");
}

//function to get client ip address
function get_client_ip_server()
{
    $ip = '';
    if ($_SERVER['HTTP_CLIENT_IP']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_CLIENT_IP']);
    } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_X_FORWARDED_FOR']);
    } elseif ($_SERVER['HTTP_X_FORWARDED']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_X_FORWARDED']);
    } elseif ($_SERVER['HTTP_FORWARDED']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_FORWARDED']);
    } elseif ($_SERVER['REMOTE_ADDR']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['REMOTE_ADDR']);
    } else {
        $ip = 'UNKNOWN';
    }

    return $ip;
}

function isValidDateTimeString($str_dt, $str_dateformat)
{
    $date = DateTime::createFromFormat($str_dateformat, $str_dt);
    return $date && DateTime::getLastErrors()["warning_count"] == 0 && DateTime::getLastErrors()["error_count"] == 0;
}
function sendMail($to, $message, $subject, $attachments = [])
{
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;  // Replace with your email
        $mail->Password   = GMAIL_PASSWORD;   // Use an App Password, NOT your actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // Use SSL
        $mail->Port       = 465;

        // Sender & Recipient
        $mail->setFrom('no-reply@noreply.com', 'No Reply');
        $mail->addAddress($to);

        // Attachments (if any)
        if (!empty($attachments)) {
            foreach ($attachments as $filePath) {
                $mail->addAttachment($filePath);
            }
        }

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Plain text fallback
        /* $mail->Debugoutput = 'html'; // Debug output in HTML
        $mail->SMTPDebug = 4; // Enable debugging (1 = errors, 2 = full output) */
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
