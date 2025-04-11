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
        case 'get_accounts':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $accountID = !isset($_GET['account_id']) || empty($_GET['account_id']) ? null : $mysqli->real_escape_string($_GET['account_id']);
            $user_id = !isset($_GET['user_id']) || empty($_GET['user_id']) ? null : $mysqli->real_escape_string($_GET['user_id']);
            $accountOfficerID = !isset($_GET['account_officer_id']) || empty($_GET['account_officer_id']) ? null : $mysqli->real_escape_string($_GET['account_officer_id']);
            $gender = !isset($_GET['gender']) || empty($_GET['gender']) ? null : $mysqli->real_escape_string($_GET['gender']);
            $identification = !isset($_GET['identification']) || empty($_GET['identification']) ? null : $mysqli->real_escape_string($_GET['identification']);

            //instantiate notifications array
            $accounts = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM account_view WHERE 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($accountID)) {
                $query .= " AND account_id = ?";
                $params[] = $accountID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($user_id)) {
                $query .= " AND user_id = ?";
                $params[] = $user_id;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($accountOfficerID)) {
                $query .= " AND account_officer_id = ?";
                $params[] = $accountOfficerID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($gender)) {
                $query .= " AND gender = ?";
                $params[] = $gender;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($identification)) {
                $query .= " AND identification = ?";
                $params[] = $identification;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            $query .= " ORDER BY created_at DESC";

            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Error preparing statement: " . $mysqli->error);
            }

            // Only bind parameters if there are any
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }

            $stmt->close();

            $response['error'] = false;
            $response['accounts'] = $accounts;

            break;

        case 'create_user':
            if (!checkAvailability(array('token', 'first_name', 'last_name', 'dob', 'address', 'phone', 'email', 'password', 'identification', 'identification_number', 'gender', 'role'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $mysqli->real_escape_string($_GET['token']);
            $userId = payloadClaim($token, 'user_id');

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
            $role = $mysqli->real_escape_string($_POST['role']);

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

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // Insert new user record
            $stmt = $mysqli->prepare("INSERT INTO users (`first_name`, `last_name`, `dob`, `gender`, `address`, `phone`, `email`, `identification`, `identification_number`, `password`, `role`, `account_status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('sssssssssss', $firstName, $lastName, $dob, $gender, $address, $phone, $email, $identification, $identificationNumber, $password, $role);
            $stmt->execute();
            $userID = $stmt->insert_id;
            $stmt->close();

            $emailMeta = [
                'first_name'        =>      $firstName,
                'last_name'         =>      $lastName,
                'password'          =>      $password,
                'email'             =>      $email,
                'role'              =>      $role
            ];

            $message = accountCreationNotificationHTML($emailMeta);
            $successMessage = 'An account creation notification email has been sent to ' . $email;

            // Send notification email
            if (!sendMail($email, $message, 'Account Creation Notification')) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the verification email.');
            }

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Account Creation Notification', '$successMessage')");
            $stmt->execute();
            $stmt->close();

            $stmt = $mysqli->prepare("CALL storeNotification($userId, 'Account Creation Notification', '$successMessage')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

            break;

        case 'get_all_users':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $user_id = !isset($_GET['user_id']) || empty($_GET['user_id']) ? null : $mysqli->real_escape_string($_GET['user_id']);
            $account_status = !isset($_GET['account_status']) || empty($_GET['account_status']) ? null : $mysqli->real_escape_string($_GET['account_status']);
            $role = !isset($_GET['role']) || empty($_GET['role']) ? null : $mysqli->real_escape_string($_GET['role']);

            //instantiate users array
            $users = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM users WHERE 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($user_id)) {
                $query .= " AND user_id = ?";
                $params[] = $user_id;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($account_status)) {
                $query .= " AND account_status = ?";
                $params[] = $account_status;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($role)) {
                $query .= " AND role = ?";
                $params[] = $role;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            $query .= " ORDER BY created_at DESC";

            $stmt = $mysqli->prepare($query);
            if ($stmt === false) {
                throw new Exception("Error preparing statement: " . $mysqli->error);
            }

            // Only bind parameters if there are any
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            $stmt->close();

            $response['error'] = false;
            $response['users'] = $users;

            break;

        case 'user_update':
            if (!checkAvailability(array('token', 'first_name', 'last_name', 'dob', 'gender', 'address', 'phone', 'email', 'identification', 'identification_number', 'account_status', 'user_id'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $_GET['token'];
            $userId = payloadClaim($token, 'user_id');

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
            $accountStatus = $mysqli->real_escape_string($_POST['account_status']);

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
            $stmt = $mysqli->prepare("UPDATE users SET `first_name` = ?, `last_name` = ?, `dob` = ?, `address` = ?, `phone` = ?, `email` = ?, `identification` = ?, `identification_number` = ?, `gender` = ?, `account_status` = ? WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ssssssssssi', $firstName, $lastName, $dob, $address, $phone, $email, $identification, $identificationNumber, $gender, $accountStatus, $userID);
            $stmt->execute();
            $stmt->close();

            $successMessage = 'User updated successfully';
            $successMessage2 = 'Your account was updated successfully';

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Account Update', '$successMessage2')");
            $stmt->execute();
            $stmt->close();

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userId, 'Account Update', '$successMessage')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

            break;
        
        case 'send_support_response_email':
            if (!checkAvailability(array('to', 'subject', 'message', 'user_id', 'token'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            $token = $_GET['token'];
            $userId = payloadClaim($token, 'user_id');

            // Get and sanitize user input
            $to = $mysqli->real_escape_string($_POST['to']);
            $subject = $mysqli->real_escape_string($_POST['subject']);
            $msg = $mysqli->real_escape_string($_POST['message']);
            $user_id = $mysqli->real_escape_string($_POST['user_id']);

            $message = supportResponseHTML($subject, $msg);
            $successMessage = 'An email has been sent to ' . $to;
            $successMessage2 = 'An email has been sent to ' . $to;

            // Send verification email
            if (!sendMail($to, $message, $subject)) {
                throw new Exception('An error occurred while sending the email.');
            }

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userId, 'Support Email Sent', '$successMessage')");
            $stmt->execute();
            $stmt->close();
            
            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($user_id, 'Support Email Received', '$successMessage2')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

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
