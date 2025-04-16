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
            if (!checkAvailability(array('first_name', 'last_name', 'dob', 'address', 'phone', 'email', 'password', 'identification', 'identification_number', 'gender'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $firstName = $mysqli->real_escape_string($_POST['first_name']);
            $lastName = $mysqli->real_escape_string($_POST['last_name']);
            $dob = $mysqli->real_escape_string($_POST['dob']);
            $address = $mysqli->real_escape_string($_POST['address']);
            $phone = $mysqli->real_escape_string($_POST['phone']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $password = $mysqli->real_escape_string($_POST['password']);
            $identification = $mysqli->real_escape_string($_POST['identification']);
            $identificationNumber = $mysqli->real_escape_string($_POST['identification_number']);
            $gender = $mysqli->real_escape_string($_POST['gender']);

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
            //generate hashed password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // Insert new user record
            $stmt = $mysqli->prepare("INSERT INTO users (`first_name`, `last_name`, `dob`, `gender`, `address`, `phone`, `email`, `identification`, `identification_number`, `password`, `hash`, `hash_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('sssssssssss', $firstName, $lastName, $dob, $gender, $address, $phone, $email, $identification, $identificationNumber, $hashedPassword, $hash);
            $stmt->execute();
            $userID = $stmt->insert_id;
            $stmt->close();

            //get created account details
            $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE user_id = $userID LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();

            $account = $result->fetch_array();
            $stmt->close();
            $message = accountVerificationHTML($email, $hash, $account);
            $successMessage = 'An account verification email has been sent to ' . $email;

            // Send verification email
            if (!sendMail($email, $message, 'Account Verification')) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the verification email.');
            }

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Account Verification', '$successMessage')");
            $stmt->execute();
            $stmt->close();

            //inform customer to setup budget categories outside the savings category
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Budget Categories', 'Setup budget categories to track spending')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

            break;

        case 'account_update':
            if (!checkAvailability(array('token', 'first_name', 'last_name', 'dob', 'gender', 'address', 'phone', 'email', 'identification', 'identification_number', 'user_id'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $_GET['token'];
            $role = payloadClaim($token, 'role');

            // Get and sanitize user input
            $firstName = $mysqli->real_escape_string($_POST['first_name']);
            $lastName = $mysqli->real_escape_string($_POST['last_name']);
            $dob = $mysqli->real_escape_string($_POST['dob']);
            $gender = $mysqli->real_escape_string($_POST['gender']);
            $address = $mysqli->real_escape_string($_POST['address']);
            $phone = $mysqli->real_escape_string($_POST['phone']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $identification = $mysqli->real_escape_string($_POST['identification']);
            $identificationNumber = $mysqli->real_escape_string($_POST['identification_number']);
            $userID = $mysqli->real_escape_string($_POST['user_id']);

            // Check if email already exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? AND user_id != ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('si', $email, $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Email already exists');
            }
            $stmt->close();

            // Check if phone number exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE phone = ? AND user_id != ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('si', $phone, $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Phone number already exists');
            }
            $stmt->close();

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // update user record
            $stmt = $mysqli->prepare("UPDATE users SET `first_name` = ?, `last_name` = ?, `dob` = ?, `address` = ?, `phone` = ?, `email` = ?, `identification` = ?, `identification_number` = ?, `gender` = ? WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('sssssssssi', $firstName, $lastName, $dob, $address, $phone, $email, $identification, $identificationNumber, $gender, $userID);
            $stmt->execute();
            $stmt->close();

            $successMessage = 'Account updated successfully';

            $timestamp = time();
            $expirationTime = $timestamp + (60 * 60 * 24); // token expiration time

            //JWT Header
            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
            ];

            //JWT Payload
            if ($role === "Customer") {
                $payload = [
                    'iat'                           =>              $timestamp,
                    'exp'                           =>              $expirationTime,
                    'user_id'                       =>              $userID,
                    'first_name'                    =>              $firstName,
                    'last_name'                     =>              $lastName,
                    'gender'                        =>              $gender,
                    'dob'                           =>              $dob,
                    'address'                       =>              $address,
                    'phone'                         =>              $phone,
                    'email'                         =>              $email,
                    'identification'                =>              $identification,
                    'identification_number'         =>              $identificationNumber,
                    'role'                          =>              $role,
                    'account_status'                =>              payloadClaim($token, 'account_status'),
                    'joined_at'                     =>              payloadClaim($token, 'joined_at'),
                    'account_id'                    =>              payloadClaim($token, 'account_id'),
                    'account_officer_id'            =>              payloadClaim($token, 'account_officer_id'),
                    'account_type'                  =>              payloadClaim($token, 'account_type'),
                    'pin'                           =>              payloadClaim($token, 'pin'),
                    'account_officer_first_name'    =>              payloadClaim($token, 'account_officer_first_name'),
                    'account_officer_last_name'     =>              payloadClaim($token, 'account_officer_last_name'),
                    'account_officer_phone'         =>              payloadClaim($token, 'account_officer_phone'),
                    'account_officer_email'         =>              payloadClaim($token, 'account_officer_email'),
                ];
            } else {
                $payload = [
                    'iat'                           =>              $timestamp,
                    'exp'                           =>              $expirationTime,
                    'user_id'                       =>              $userID,
                    'first_name'                    =>              $firstName,
                    'last_name'                     =>              $lastName,
                    'gender'                        =>              $gender,
                    'dob'                           =>              $dob,
                    'address'                       =>              $address,
                    'phone'                         =>              $phone,
                    'email'                         =>              $email,
                    'identification'                =>              $identification,
                    'identification_number'         =>              $identificationNumber,
                    'role'                          =>              $role,
                    'account_status'                =>              payloadClaim($token, 'account_status'),
                ];
            }

            //secret key
            $secret = TOKEN_SECRET;

            //jwt
            $jwt = generateJWT('sha256', $header, $payload, $secret);

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Account Update', '$successMessage')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;
            $response['token'] = $jwt;

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
            $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT, ['cost' => 10]);

            // update password
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE email = ? AND hash = ?");
            $stmt->bind_param('sss', $hashedPassword, $email, $hash);

            if (!$stmt->execute()) {
                throw new Exception('An error occured while attempting to reset password: ' . $mysqli->error);
            }

            $response['error'] = false;
            $response['message'] = 'Password reset successful';

            break;

        case 'password_update':
            if (!checkAvailability(array('current_password', 'new_password', 'token'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $_GET['token'];
            $userID = payloadClaim($token, 'user_id');

            // Get and sanitize user input
            $currentPassword = $mysqli->real_escape_string($_POST['current_password']);
            $newPassword = $mysqli->real_escape_string($_POST['new_password']);

            // Check if current password exists
            $stmt = $mysqli->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
            $stmt->bind_param('i', $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('No stored password found is incorrect');
            }
            $storedPassword = $result->fetch_assoc()['password'];

            if (!password_verify($currentPassword, $storedPassword)) {
                throw new Exception('Current password does not match your stored password');
            }

            $stmt->close();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 10]);

            // update password
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param('si', $hashedPassword, $userID);

            if (!$stmt->execute()) {
                throw new Exception('An error occured while attempting to reset password: ' . $mysqli->error);
            }
            $stmt->close();

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Password Update', 'Password update was successful')");
            $stmt->execute();
            $stmt->close();

            $response['error'] = false;
            $response['message'] = 'Password update was successful';

            break;

        case 'pin_update':
            if (!checkAvailability(array('current_pin', 'new_pin', 'token'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $_GET['token'];
            $userID = payloadClaim($token, 'user_id');

            // Get and sanitize user input
            $currentPin = $mysqli->real_escape_string($_POST['current_pin']);
            $newPin = $mysqli->real_escape_string($_POST['new_pin']);

            // Check if current pin exists
            $stmt = $mysqli->prepare("SELECT pin FROM account_view WHERE user_id = ? LIMIT 1");
            $stmt->bind_param('i', $userID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('No pin found');
            }
            $pin = $result->fetch_assoc()['pin'];
            $stmt->close();

            if ($pin != $currentPin) {
                throw new Exception('Current pin does not match your stored pin');
            }

            // update pin
            $stmt = $mysqli->prepare("UPDATE account SET pin = ? WHERE user_id = ?");
            $stmt->bind_param('ii', $newPin, $userID);

            if (!$stmt->execute()) {
                throw new Exception('An error occured while attempting to reset password: ' . $mysqli->error);
            }
            $stmt->close();

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Pin Update', 'Transaction Pin update was successful')");
            $stmt->execute();
            $stmt->close();

            $response['error'] = false;
            $response['message'] = 'Transaction Pin update was successful';

            break;

        case 'sign_in':
            if (!checkAvailability(array('email', 'password'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            // Get and sanitize user input
            $email = $mysqli->real_escape_string($_POST['email']);
            $password = $mysqli->real_escape_string($_POST['password']);

            //initialise customer details
            $customerDetails = null;

            // Check if email and password combination exists
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Invalid credentials');
            }

            $user = $result->fetch_array();
            $stmt->close();

            //check password validity
            if (!password_verify($password, $user['password'])) {
                throw new Exception('Invalid password');
            }

            //check if user is inactive deny
            if ($user['account_status'] === "Inactive") {
                throw new Exception('Sorry!!! You have been denied access. Please contact support');
            }

            //check if user is a customer
            $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            $result->num_rows > 0 ? $customerDetails = $result->fetch_array() : $customerDetails = null;
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
            if ($user['role'] === "Customer") {
                $payload = [
                    'iat'                           =>              $timestamp,
                    'exp'                           =>              $expirationTime,
                    'user_id'                       =>              $user['user_id'],
                    'first_name'                    =>              $user['first_name'],
                    'last_name'                     =>              $user['last_name'],
                    'gender'                        =>              $user['gender'],
                    'dob'                           =>              $user['dob'],
                    'address'                       =>              $user['address'],
                    'phone'                         =>              $user['phone'],
                    'email'                         =>              $user['email'],
                    'identification'                =>              $user['identification'],
                    'identification_number'         =>              $user['identification_number'],
                    'role'                          =>              $user['role'],
                    'account_status'                =>              $user['account_status'],
                    'joined_at'                     =>              $user['created_at'],
                    'account_id'                    =>              $customerDetails['account_id'],
                    'account_officer_id'            =>              $customerDetails['account_officer_id'],
                    'account_type'                  =>              $customerDetails['account_type'],
                    'pin'                           =>              $customerDetails['pin'],
                    'account_officer_first_name'    =>              $customerDetails['account_officer_first_name'],
                    'account_officer_last_name'     =>              $customerDetails['account_officer_last_name'],
                    'account_officer_phone'         =>              $customerDetails['account_officer_phone'],
                    'account_officer_email'         =>              $customerDetails['account_officer_email'],
                ];
            } else {
                $payload = [
                    'iat'                   =>              $timestamp,
                    'exp'                   =>              $expirationTime,
                    'user_id'               =>              $user['user_id'],
                    'first_name'            =>              $user['first_name'],
                    'last_name'             =>              $user['last_name'],
                    'gender'                =>              $user['gender'],
                    'dob'                   =>              $user['dob'],
                    'address'               =>              $user['address'],
                    'phone'                 =>              $user['phone'],
                    'email'                 =>              $user['email'],
                    'identification'        =>              $user['identification'],
                    'identification_number' =>              $user['identification_number'],
                    'role'                  =>              $user['role'],
                    'account_status'        =>              $user['account_status'],
                ];
            }

            //secret key
            $secret = TOKEN_SECRET;

            //jwt
            $jwt = generateJWT('sha256', $header, $payload, $secret);
            $successMessage = 'Welcome onboard ' . $user['first_name'] . ' ' . $user['last_name'];

            //record the last seen of user as now
            $stmt = $mysqli->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ?");
            $stmt->bind_param('s', $user['user_id']);
            $stmt->execute();
            $stmt->close();

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification(?, 'Login Successful', ?)");
            $stmt->bind_param('is', $user['user_id'], $successMessage);
            $stmt->execute();
            $stmt->close();

            $response['error'] = false;
            $response['message'] = 'Welcome onboard ' . $user['first_name'];
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
