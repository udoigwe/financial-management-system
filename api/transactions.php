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
        case 'funds_transfer':
            //check for empty or undefined parameters
            if (!checkAvailability(array('token', 'destination_account_number', 'source', 'budget_category_id', 'amount', 'pin'))) {
                throw new Exception('Invalid request: Token is required');
            }

            //verify jwt token
            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            //logged user details from token
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $accountID = payloadClaim($token, 'account_id');
            $email = payloadClaim($token, 'email');
            $phone = payloadClaim($token, 'phone');
            $firstName = payloadClaim($token, 'first_name');
            $lastName = payloadClaim($token, 'last_name');

            // Get and sanitize user input
            $destinationAccountNumber = $mysqli->real_escape_string($_POST['destination_account_number']);
            $source = $mysqli->real_escape_string($_POST['source']);
            $budgetCategoryID = $mysqli->real_escape_string($_POST['budget_category_id']);
            $amount = $mysqli->real_escape_string($_POST['amount']);
            $pin = $mysqli->real_escape_string($_POST['pin']);

            //check otp
            $otp = isset($_POST['otp']) ? $mysqli->real_escape_string($_POST['otp']) : null; // OTP input (if provided)

            //check if transaction pin exists
            $stmt = $mysqli->prepare("CALL validateTransactionPIN(?, ?)");
            $stmt->bind_param("ii", $userID, $pin);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['result'] === "USER_NOT_FOUND") {
                throw new Exception('User not found');
            }

            if ($row['result'] === "INVALID_PIN") {
                throw new Exception('Invalid transaction PIN');
            }
            $stmt->close();

            //get budget category details
            $stmt = $mysqli->prepare("SELECT * FROM budget_categories_view WHERE account_id = ? LIMIT 1");
            $stmt->bind_param('i', $accountID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Budget category does not exist');
            }

            $budgetCategory = $result->fetch_assoc();
            $stmt->close();

            //get destination account details
            $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE account_id = ? LIMIT 1");
            $stmt->bind_param('i', $destinationAccountNumber);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Destination account does not exist');
            }

            $destinationAccount = $result->fetch_assoc();
            $destinationUserID = $destinationAccount['user_id'];
            $stmt->close();

            //Check if transaction exceeds budget
            $stmt = $mysqli->prepare("CALL checkTransactionBudget(?, ?, ?, ?)");
            $stmt->bind_param("iids", $accountID, $budgetCategoryID, $amount, $source);
            $stmt->execute();
            $result = $stmt->get_result();

            //$transactionStatus = "Within Budget"; // Default
            if ($row = $result->fetch_assoc()) {
                $transactionStatus = $row['transaction_budget_status'];
            }
            $stmt->close();

            // Start database transaction
            $mysqli->autocommit(false);
            $mysqli->begin_transaction();

            // If the transaction exceeds budget, handle OTP verification
            if ($transactionStatus === "Exceeds Budget") {
                if (!$otp) {
                    // Generate OTP
                    $generated_otp = rand(100000, 999999);

                    // Save OTP to the database (valid for 5 minutes)
                    $stmt = $mysqli->prepare("INSERT INTO otp (account_id, otp, expires_at) VALUES (?, ?, NOW() + INTERVAL 5 MINUTE)");
                    $stmt->bind_param("ii", $accountID, $generated_otp);
                    $stmt->execute();
                    $stmt->close();

                    $emailMeta = [
                        "first_name"                    => $firstName,
                        "amount"                        => $amount,
                        "destination_account_number"    => $destinationAccountNumber,
                        "otp"                           => $generated_otp
                    ];

                    // Send OTP via Email
                    $subject = "Your OTP for Fund Transfer";
                    $message = otpHTML($emailMeta, $subject);
                    $successMessage = 'An OTP has been sent to ' . $email . '. Please check your inbox.';

                    if (!sendMail($email, $message, $subject)) {
                        $mysqli->rollback(); // Rollback transaction if email fails
                        throw new Exception('An error occurred while sending the recovery email.');
                    }

                    //record this success message
                    $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Your OTP for Fund Transfer', '$successMessage')");
                    $stmt->execute();
                    $stmt->close();

                    // Commit transaction if everything is successful
                    $mysqli->commit();

                    //throw the success message in an exception
                    throw new Exception("OTP_REQUIRED");
                } else {
                    //verify otp
                    $stmt = $mysqli->prepare("CALL verifyOTP(?, ?)");
                    $stmt->bind_param("ii", $accountID, $otp);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    if ($row['status'] === "OTP_NOT_FOUND") {
                        throw new Exception('OTP not found');
                    }

                    if ($row['status'] === "OTP_EXPIRED") {
                        throw new Exception('OTP expired');
                    }

                    $stmt->close();
                }
            }

            //Process the transaction
            $stmt = $mysqli->prepare("CALL fundsTransfer(?, ?, ?, ?, ?)");
            $stmt->bind_param("iidis", $accountID, $destinationAccountNumber, $amount, $budgetCategoryID, $source);
            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            // Get the results (updated balances)
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            $debitEmailMeta = [
                "first_name"                    => $firstName,
                "amount"                        => $amount,
                "originating_balance"           => $data['originating_balance'],
                "source"                        => $source,
            ];

            $creditEmailMeta = [
                "first_name"                    => $destinationAccount['first_name'],
                "amount"                        => $amount,
                "destination_balance"           => $data['destination_balance'],
                "destination"                   => $budgetCategory['category_name'] === "Savings" ? 'Safe Lock' : "Main Account"
            ];

            $debitSubject = 'Debit Alert';
            $debitMessage = debitHTML($debitEmailMeta, $debitSubject);
            $creditSubject = 'Credit Alert';
            $creditMessage = creditHTML($creditEmailMeta, $creditSubject);
            $debitSuccessMessage = 'Your account has been debited with $' . $amount;
            $creditSuccessMessage = 'Your account has been credited with $' . $amount;

            // Send debit email notification
            if (!sendMail($email, $debitMessage, $debitSubject)) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the support email.');
            }

            // Send credit email notification
            if (!sendMail($destinationAccount['email'], $creditMessage, $creditSubject)) {
                $mysqli->rollback(); // Rollback transaction if email fails
                throw new Exception('An error occurred while sending the support email.');
            }

            //send in-app notifications to debited account holder
            $stmt = $mysqli->prepare("CALL storeNotification($userID, '$debitSubject', '$debitSuccessMessage')");
            $stmt->execute();
            $stmt->close();

            //send in-app notifications to credited account holder
            $stmt = $mysqli->prepare("CALL storeNotification($destinationUserID, '$creditSubject', '$creditSuccessMessage')");
            $stmt->execute();
            $stmt->close();

            // Commit transaction if everything is successful
            $mysqli->commit();

            $response['error'] = false;
            $response['message'] = $debitSuccessMessage;

            break;

        case 'check_safe_lock_period':
            //check for empty or undefined parameters
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            //verify jwt token
            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            //logged user details from token
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $accountID = payloadClaim($token, 'account_id');

            //check if safe lock period valid
            $stmt = $mysqli->prepare("CALL checkSafeLockPeriod(?)");
            $stmt->bind_param("i", $accountID);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row['status'] === "LOCK_ACTIVE") {
                throw new Exception('LOCK_ACTIVE');
            }
            $stmt->close();

            $response['error'] = false;
            $response['message'] = $row['status'];

            break;
        case 'get_transactions':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $account_id = payloadClaim($token, "account_id");
            $firstName = payloadClaim($token, "first_name");
            $lastName = payloadClaim($token, "last_name");
            $email = payloadClaim($token, "email");
            $phone = payloadClaim($token, "phone");

            $transactionID = !isset($_GET['transaction_id']) || empty($_GET['transaction_id']) ? null : $mysqli->real_escape_string($_GET['transaction_id']);
            $accountID = !isset($_GET['account_id']) || empty($_GET['account_id']) ? null : $mysqli->real_escape_string($_GET['account_id']);
            $budgetCategoryID = !isset($_GET['budget_category_id']) || empty($_GET['budget_category_id']) ? null : $mysqli->real_escape_string($_GET['budget_category_id']);
            $transactionType = !isset($_GET['transaction_type']) || empty($_GET['transaction_type']) ? null : $mysqli->real_escape_string($_GET['transaction_type']);
            $transactionBudgetStatus = !isset($_GET['transaction_budget_status']) || empty($_GET['transaction_budget_status']) ? null : $mysqli->real_escape_string($_GET['transaction_budget_status']);
            $transactionSource = !isset($_GET['transaction_source']) || empty($_GET['transaction_source']) ? null : $mysqli->real_escape_string($_GET['transaction_source']);
            $transactionDestination = !isset($_GET['transaction_destination']) || empty($_GET['transaction_destination']) ? null : $mysqli->real_escape_string($_GET['transaction_destination']);
            $fromCreatedAt = !isset($_GET['from_created_at']) || empty($_GET['from_created_at']) ? null : $mysqli->real_escape_string($_GET['from_created_at']);
            $toCreatedAt = !isset($_GET['to_created_at']) || empty($_GET['to_created_at']) ? null : $mysqli->real_escape_string($_GET['to_created_at']);

            //instantiate transactions array
            $transactions = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM transactions_view WHERE 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($transactionID)) {
                $query .= " AND transaction_id = ?";
                $params[] = $transactionID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($accountID)) {
                $query .= " AND account_id = ?";
                $params[] = $accountID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($budgetCategoryID)) {
                $query .= " AND budget_category_id = ?";
                $params[] = $budgetCategoryID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionType)) {
                $query .= " AND transaction_type = ?";
                $params[] = $transactionType;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionBudgetStatus)) {
                $query .= " AND transaction_budget_status = ?";
                $params[] = $transactionBudgetStatus;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionSource)) {
                $query .= " AND transaction_source = ?";
                $params[] = $transactionSource;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionDestination)) {
                $query .= " AND transaction_destination = ?";
                $params[] = $transactionDestination;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($fromCreatedAt)) {
                $query .= " AND DATE(created_at) >= ?";
                $params[] = $fromCreatedAt;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($toCreatedAt)) {
                $query .= " AND DATE(created_at) <= ?";
                $params[] = $toCreatedAt;
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
                $transactions[] = $row;
            }

            $stmt->close();

            //get statement summary
            $stmt = $mysqli->prepare("CALL GenerateAccountStatementSummary(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('iiissssss', $transactionID, $accountID, $budgetCategoryID, $transactionType, $transactionBudgetStatus, $transactionSource, $transactionDestination, $fromCreatedAt, $toCreatedAt);

            if (!$stmt->execute()) {
                throw new Exception('An error occured: ' . $mysqli->error);
            }
            $result = $stmt->get_result();
            $accountSummary = $result->fetch_assoc();
            $stmt->close();

            $meta = [
                'transactions'  =>      $transactions,
                'summary'       =>      $accountSummary,
                "first_name"    =>      $firstName,
                "last_name"     =>      $lastName,
                "email"         =>      $email,
                "phone"         =>      $phone,
                "account_id"    =>      $account_id
            ];

            $response['error'] = false;
            $response['transactions'] = $meta;

            break;
        case 'get_transactions_2':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');

            $firstName = null;
            $lastName = null;
            $email = null;
            $phone = null;

            $transactionID = !isset($_GET['transaction_id']) || empty($_GET['transaction_id']) ? null : $mysqli->real_escape_string($_GET['transaction_id']);
            $accountID = !isset($_GET['account_id']) || empty($_GET['account_id']) ? null : $mysqli->real_escape_string($_GET['account_id']);
            $budgetCategoryID = !isset($_GET['budget_category_id']) || empty($_GET['budget_category_id']) ? null : $mysqli->real_escape_string($_GET['budget_category_id']);
            $transactionType = !isset($_GET['transaction_type']) || empty($_GET['transaction_type']) ? null : $mysqli->real_escape_string($_GET['transaction_type']);
            $transactionBudgetStatus = !isset($_GET['transaction_budget_status']) || empty($_GET['transaction_budget_status']) ? null : $mysqli->real_escape_string($_GET['transaction_budget_status']);
            $transactionSource = !isset($_GET['transaction_source']) || empty($_GET['transaction_source']) ? null : $mysqli->real_escape_string($_GET['transaction_source']);
            $transactionDestination = !isset($_GET['transaction_destination']) || empty($_GET['transaction_destination']) ? null : $mysqli->real_escape_string($_GET['transaction_destination']);
            $fromCreatedAt = !isset($_GET['from_created_at']) || empty($_GET['from_created_at']) ? null : $mysqli->real_escape_string($_GET['from_created_at']);
            $toCreatedAt = !isset($_GET['to_created_at']) || empty($_GET['to_created_at']) ? null : $mysqli->real_escape_string($_GET['to_created_at']);

            //instantiate transactions array
            $transactions = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM transactions_view WHERE 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($transactionID)) {
                $query .= " AND transaction_id = ?";
                $params[] = $transactionID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($accountID)) {
                $query .= " AND account_id = ?";
                $params[] = $accountID;
                $types .= "i"; // 'i' for integer type 's' for string type

                //get account details
                $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE account_id = ? LIMIT 1");
                $stmt->bind_param("i", $accountID);
                $stmt->execute();
                $res = $stmt->get_result();
                $holder = $res->fetch_assoc();
                $firstName = $holder['first_name'];
                $lastName = $holder['last_name'];
                $email = $holder['email'];
                $phone = $holder['phone'];
                $stmt->close();
            }

            if (!is_null($budgetCategoryID)) {
                $query .= " AND budget_category_id = ?";
                $params[] = $budgetCategoryID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionType)) {
                $query .= " AND transaction_type = ?";
                $params[] = $transactionType;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionBudgetStatus)) {
                $query .= " AND transaction_budget_status = ?";
                $params[] = $transactionBudgetStatus;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionSource)) {
                $query .= " AND transaction_source = ?";
                $params[] = $transactionSource;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionDestination)) {
                $query .= " AND transaction_destination = ?";
                $params[] = $transactionDestination;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($fromCreatedAt)) {
                $query .= " AND DATE(created_at) >= ?";
                $params[] = $fromCreatedAt;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($toCreatedAt)) {
                $query .= " AND DATE(created_at) <= ?";
                $params[] = $toCreatedAt;
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
                $transactions[] = $row;
            }

            $stmt->close();

            //get statement summary
            $stmt = $mysqli->prepare("CALL GenerateAccountStatementSummary(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('iiissssss', $transactionID, $accountID, $budgetCategoryID, $transactionType, $transactionBudgetStatus, $transactionSource, $transactionDestination, $fromCreatedAt, $toCreatedAt);

            if (!$stmt->execute()) {
                throw new Exception('An error occured: ' . $mysqli->error);
            }
            $result = $stmt->get_result();
            $accountSummary = $result->fetch_assoc();
            $stmt->close();

            $meta = [
                'transactions'  =>      $transactions,
                'summary'       =>      $accountSummary,
                "first_name"    =>      $firstName,
                "last_name"     =>      $lastName,
                "email"         =>      $email,
                "phone"         =>      $phone,
                "account_id"    =>      $accountID
            ];

            $response['error'] = false;
            $response['transactions'] = $meta;

            break;

        case 'get_transactions_3':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');

            $firstName = null;
            $lastName = null;
            $email = null;
            $phone = null;

            $transactionID = !isset($_GET['transaction_id']) || empty($_GET['transaction_id']) ? null : $mysqli->real_escape_string($_GET['transaction_id']);
            $accountID = !isset($_GET['account_id']) || empty($_GET['account_id']) ? null : $mysqli->real_escape_string($_GET['account_id']);
            $budgetCategoryID = !isset($_GET['budget_category_id']) || empty($_GET['budget_category_id']) ? null : $mysqli->real_escape_string($_GET['budget_category_id']);
            $transactionType = !isset($_GET['transaction_type']) || empty($_GET['transaction_type']) ? null : $mysqli->real_escape_string($_GET['transaction_type']);
            $transactionBudgetStatus = !isset($_GET['transaction_budget_status']) || empty($_GET['transaction_budget_status']) ? null : $mysqli->real_escape_string($_GET['transaction_budget_status']);
            $transactionSource = !isset($_GET['transaction_source']) || empty($_GET['transaction_source']) ? null : $mysqli->real_escape_string($_GET['transaction_source']);
            $transactionDestination = !isset($_GET['transaction_destination']) || empty($_GET['transaction_destination']) ? null : $mysqli->real_escape_string($_GET['transaction_destination']);
            $fromCreatedAt = !isset($_GET['from_created_at']) || empty($_GET['from_created_at']) ? null : $mysqli->real_escape_string($_GET['from_created_at']);
            $toCreatedAt = !isset($_GET['to_created_at']) || empty($_GET['to_created_at']) ? null : $mysqli->real_escape_string($_GET['to_created_at']);

            //instantiate transactions array
            $transactions = array();

            //instantiate my account IDs array
            $myAccountIDs = array();

            //get all account holders under me
            $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE account_officer_id = $userID");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $myAccountIDs[] = $row['account_id'];
            }
            $implodedAccountIDs = count($myAccountIDs) > 0 ? implode(', ', $myAccountIDs) : null;
            $stmt->close();

            // Initialize the query and filter array
            $query = !$implodedAccountIDs ? "SELECT * FROM transactions_view WHERE 1 = 0" : "SELECT * FROM transactions_view WHERE account_id IN ($implodedAccountIDs)";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($transactionID)) {
                $query .= " AND transaction_id = ?";
                $params[] = $transactionID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($accountID)) {
                $query .= " AND account_id = ?";
                $params[] = $accountID;
                $types .= "i"; // 'i' for integer type 's' for string type

                //get account details
                $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE account_id = ? LIMIT 1");
                $stmt->bind_param("i", $accountID);
                $stmt->execute();
                $res = $stmt->get_result();
                $holder = $res->fetch_assoc();
                $firstName = $holder['first_name'];
                $lastName = $holder['last_name'];
                $email = $holder['email'];
                $phone = $holder['phone'];
                $stmt->close();
            }

            if (!is_null($budgetCategoryID)) {
                $query .= " AND budget_category_id = ?";
                $params[] = $budgetCategoryID;
                $types .= "i"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionType)) {
                $query .= " AND transaction_type = ?";
                $params[] = $transactionType;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionBudgetStatus)) {
                $query .= " AND transaction_budget_status = ?";
                $params[] = $transactionBudgetStatus;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionSource)) {
                $query .= " AND transaction_source = ?";
                $params[] = $transactionSource;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($transactionDestination)) {
                $query .= " AND transaction_destination = ?";
                $params[] = $transactionDestination;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($fromCreatedAt)) {
                $query .= " AND DATE(created_at) >= ?";
                $params[] = $fromCreatedAt;
                $types .= "s"; // 'i' for integer type 's' for string type
            }

            if (!is_null($toCreatedAt)) {
                $query .= " AND DATE(created_at) <= ?";
                $params[] = $toCreatedAt;
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
                $transactions[] = $row;
            }

            $stmt->close();

            //get statement summary
            $stmt = $mysqli->prepare("CALL GenerateAccountStatementSummary(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('iiissssss', $transactionID, $accountID, $budgetCategoryID, $transactionType, $transactionBudgetStatus, $transactionSource, $transactionDestination, $fromCreatedAt, $toCreatedAt);

            if (!$stmt->execute()) {
                throw new Exception('An error occured: ' . $mysqli->error);
            }
            $result = $stmt->get_result();
            $accountSummary = $result->fetch_assoc();
            $stmt->close();

            $meta = [
                'transactions'  =>      $transactions,
                'summary'       =>      $accountSummary,
                "first_name"    =>      $firstName,
                "last_name"     =>      $lastName,
                "email"         =>      $email,
                "phone"         =>      $phone,
                "account_id"    =>      $accountID
            ];

            $response['error'] = false;
            $response['transactions'] = $meta;

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
