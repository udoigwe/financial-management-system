<?php
//database connect
require_once './DB_CONNECT.php';

//api functions
require_once './functions.php';

//api functions
require_once './email-templates.php';

//response array
$response = array();

try {
    //check api call
    if (!isset($_GET['call']) || empty($_GET['call'])) {
        throw new Exception('No API call specified.');
    }

    //sanitize call
    $call = $mysqli->real_escape_string($_GET['call']);

    switch ($call) {
        case 'sign_up':
            if (!checkAvailability(array('name', 'dob', 'address', 'phone', 'email', 'password'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $name = $mysqli->real_escape_string($_POST['name']);
            $dob = $mysqli->real_escape_string($_POST['dob']);
            $address = $mysqli->real_escape_string($_POST['address']);
            $phone = $mysqli->real_escape_string($_POST['phone']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $password = $mysqli->real_escape_string($_POST['password']);

            // Check if email already exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Email already exists');
            }
            $stmt->close();

            // Check if phone number exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Phone number already exists');
            }
            $stmt->close();

            // Generate account verification hash
            $hash = md5(rand(0, 1000));
            $message = accountVerificationHTML($email, $hash);

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // Insert new user record
            $stmt = $mysqli->prepare("INSERT INTO users (`name`, `dob`, `address`, `phone`, `email`, `password`, `hash`, `hash_time`) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('sssssss', $name, $dob, $address, $phone, $email, $password, $hash);
            $stmt->execute();

            // Send verification email
            if (!sendMail($email, $message, 'Account Verification')) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the verification email.');
            }

            // Commit transaction if everything is successful
            $mysqli->commit();
            $stmt->close();

            $response['error'] = false;
            $response['message'] = 'An account verification email has been sent to ' . $email;

            break;

        case 'account_verification':
            if (!checkAvailability(array('email', 'salt'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $email = $mysqli->real_escape_string($_POST['email']);
            $hash = $mysqli->real_escape_string($_POST['salt']);

            // Check if email and hash
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? AND hash = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ss', $email, $hash);
            $stmt->execute();

            if (!$stmt->num_rows === 0) {
                throw new Exception('Email & hash combination does not exist');
            }
            $stmt->close();

            // verify account
            $stmt = $mysqli->prepare("UPDATE users SET account_status = 'Active' WHERE email = ?");
            $stmt->bind_param('s', $email);

            if (!$stmt->execute()) {
                throw new Exception('An error occured while attempting to activate account: ' . $mysqli->error);
            }

            $response['error'] = false;
            $response['message'] = 'Account verified successfully';

            break;

        case 'send_recovery_email':
            if (!checkAvailability(array('email'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $email = $mysqli->real_escape_string($_POST['email']);

            // Check if email already exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Email does not exist');
            }
            $stmt->close();

            // Generate account verification hash
            $hash = md5(rand(0, 1000));
            $message = resetPasswordLink($email, $hash);

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // Insert new user record
            $stmt = $mysqli->prepare("UPDATE users SET hash = ?, hash_time = CURRENT_TIMESTAMP WHERE email = ?");
            $stmt->bind_param('ss', $hash, $email);
            $stmt->execute();

            // Send recovery email
            if (!sendMail($email, $message, 'Password Recovery')) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the recovery email.');
            }

            // Commit transaction if everything is successful
            $mysqli->commit();
            $stmt->close();

            $response['error'] = false;
            $response['message'] = 'A password recovery email has been sent to ' . $email;

            break;

        case 'password_recovery':
            if (!checkAvailability(array('email', 'salt', 'new_pass'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $email = $mysqli->real_escape_string($_POST['email']);
            $hash = $mysqli->real_escape_string($_POST['salt']);
            $newPass = $mysqli->real_escape_string($_POST['new_pass']);

            // Check if email and hash combination exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? AND hash = ? LIMIT 1");
            $stmt->bind_param('ss', $email, $hash);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Email & hash combination does not exist');
            }
            $stmt->close();

            // update password
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE email = ? AND hash = ?");
            $stmt->bind_param('sss', $newPass, $email, $hash);

            if (!$stmt->execute()) {
                throw new Exception('An error occured while attempting to reset password: ' . $mysqli->error);
            }

            $response['error'] = false;
            $response['message'] = 'Password reset successful';

            break;

        case 'sign_in':
            if (!checkAvailability(array('email', 'password'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $email = $mysqli->real_escape_string($_POST['email']);
            $password = $mysqli->real_escape_string($_POST['password']);

            // Check if email and password combination exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? AND password = ? LIMIT 1");
            $stmt->bind_param('ss', $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Invalid credentials');
            }

            $user = $result->fetch_array();
            $stmt->close();

            $timestamp = time();
            $expirationTime = $timestamp + (60 * 60 * 24); // token expiration time

            // Login successful.
            //JWT Header
            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
            ];

            //JWT Payload
            $payload = [
                'iat'                   =>              $timestamp,
                'exp'                   =>              $expirationTime,
                'user_id'               =>              $user['user_id'],
                'name'                  =>              $user['name'],
                'dob'                   =>              $user['dob'],
                'address'               =>              $user['address'],
                'phone'                 =>              $user['phone'],
                'email'                 =>              $user['email'],
                'role'                  =>              $user['role'],
                'account_status'        =>              $user['account_status'],
            ];

            //secret key
            $secret = TOKEN_SECRET;

            //jwt
            $jwt = generateJWT('sha256', $header, $payload, $secret);

            $response['error'] = false;
            $response['message'] = 'Welcome onboard ' . $user['name'];
            $response['token'] = $jwt;

            break;

        default:
            throw new Exception('Invalid API call');
    }
} catch (Exception $e) {
    // Catch and handle exceptions
    $response['error'] = true;
    $response['message'] = $e->getMessage();
}

// Return JSON encoded response
echo json_encode($response);
