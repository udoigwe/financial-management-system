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
        case 'create':
            if (!checkAvailability(array('category_name', 'budget_limit', 'budget_limit_start_time', 'budget_limit_end_time', 'color_code', 'description'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $categoryName = $mysqli->real_escape_string($_POST['category_name']);
            $budgetLimit = $mysqli->real_escape_string($_POST['budget_limit']);
            $budgetLimitStartTime = $mysqli->real_escape_string($_POST['budget_limit_start_time']);
            $budgetLimitEndTime = $mysqli->real_escape_string($_POST['budget_limit_end_time']);
            $colorCode = $mysqli->real_escape_string($_POST['color_code']);
            $description = $mysqli->real_escape_string($_POST['description']);

            //logged in user details
            $token = $mysqli->real_escape_string($_GET['token']);
            $accountID = payloadClaim($token, 'account_id');
            $userID = payloadClaim($token, 'user_id');

            // Check if name already exists for the user
            $stmt = $mysqli->prepare("SELECT * FROM budget_categories WHERE account_id = ? AND category_name = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('is', $accountID, $categoryName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Category name already exists');
            }
            $stmt->close();

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // Insert new budget category
            $stmt = $mysqli->prepare("INSERT INTO budget_categories (`account_id`, `category_name`, `category_description`, `budget_limit`, `budget_limit_start_time`, `budget_limit_end_time`, `color_code`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ississs', $accountID, $categoryName, $description, $budgetLimit, $budgetLimitStartTime, $budgetLimitEndTime, $colorCode);
            $stmt->execute();
            $stmt->close();

            $successMessage = 'Budget Category created successfully';

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Budget Category Created', '$successMessage')");
            $stmt->execute();
            $stmt->close();
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

            break;

        case 'update':
            if (!checkAvailability(array('category_name', 'budget_limit', 'budget_limit_start_time', 'budget_limit_end_time', 'color_code', 'description', 'budget_category_status', 'category_id', 'token'))) {
                throw new Exception('Invalid request: All fields are required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $categoryName = $mysqli->real_escape_string($_POST['category_name']);
            $budgetLimit = $mysqli->real_escape_string($_POST['budget_limit']);
            $budgetLimitStartTime = $mysqli->real_escape_string($_POST['budget_limit_start_time']);
            $budgetLimitEndTime = $mysqli->real_escape_string($_POST['budget_limit_end_time']);
            $colorCode = $mysqli->real_escape_string($_POST['color_code']);
            $description = $mysqli->real_escape_string($_POST['description']);
            $categoryID = $mysqli->real_escape_string($_POST['category_id']);
            $budgetCategoryStatus = $mysqli->real_escape_string($_POST['budget_category_status']);

            //logged in user details
            $token = $mysqli->real_escape_string($_GET['token']);
            $accountID = payloadClaim($token, 'account_id');
            $userID = payloadClaim($token, 'user_id');

            // Check if name already exists for the user
            $stmt = $mysqli->prepare("SELECT * FROM budget_categories WHERE account_id = ? AND category_name = ? AND category_id != ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('isi', $accountID, $categoryName, $categoryID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                throw new Exception('Category name already exists');
            }
            $stmt->close();

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // update budget category
            $stmt = $mysqli->prepare("UPDATE budget_categories SET `category_name` = ?, `category_description` = ?, `budget_limit` = ?, `budget_limit_start_time` = ?, `budget_limit_end_time` = ?, `color_code` = ?, `budget_category_status` = ? WHERE category_id = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ssissssi', $categoryName, $description, $budgetLimit, $budgetLimitStartTime, $budgetLimitEndTime, $colorCode, $budgetCategoryStatus, $categoryID);
            $stmt->execute();
            $stmt->close();

            $successMessage = 'Budget Category updated successfully';

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Budget Category Updated', '$successMessage')");
            $stmt->execute();
            $stmt->close();
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $successMessage;

            break;

        case 'get':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $accountID = payloadClaim($token, 'account_id');
            $budgetCategoryStatus = !isset($_GET['budget_category_status']) || empty($_GET['budget_category_status']) ? null : $mysqli->real_escape_string($_GET['budget_category_status']);
            $categoryID = !isset($_GET['category_id']) || empty($_GET['category_id']) ? null : $mysqli->real_escape_string($_GET['category_id']);

            //instantiate categories array
            $categories = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM budget_categories WHERE account_id = $accountID AND 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($budgetCategoryStatus)) {
                $query .= " AND budget_category_status = ?";
                $params[] = $budgetCategoryStatus;
                $types .= "s"; // 'i' for integer type 's' for string type
            }
            if (!is_null($categoryID)) {
                $query .= " AND category_id = ?";
                $params[] = $categoryID;
                $types .= "i"; // 'i' for integer type 's' for string type
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
                $categories[] = $row;
            }

            $stmt->close();

            $response['error'] = false;
            $response['categories'] = $categories;

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

            //initialise customer details
            $customerDetails = null;

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
