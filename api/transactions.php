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
            if (!checkAvailability(array('token', 'destination_account_number', 'source', 'budget_category_id', 'amount', 'pin'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            //token
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $accountID = payloadClaim($token, 'account_id');
            $email = payloadClaim($token, 'email');
            $phone = payloadClaim($token, 'phone');
            $firstName = payloadClaim($token, 'first_name');
            $lastName = payloadClaim($token, 'last_name');
            $accountType = payloadClaim($token, 'account_type');
            $accountOfficerFirstName = payloadClaim($token, 'account_officer_first_name');
            $accountOfficerLastName = payloadClaim($token, 'account_officer_last_name');

            // Get and sanitize user input
            $destinationAccountNumber = $mysqli->real_escape_string($_POST['destination_account_number']);
            $source = $mysqli->real_escape_string($_POST['source']);
            $budgetCategoryID = $mysqli->real_escape_string($_POST['budget_category_id']);
            $amount = $mysqli->real_escape_string($_POST['amount']);

            //check otp
            $otp = isset($_POST['otp']) ? $mysqli->real_escape_string($_POST['otp']) : null; // OTP input (if provided)

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
            $stmt->close();

            //Check if transaction exceeds budget
            $stmt = $conn->prepare("CALL checkTransactionBudget(?, ?, ?, ?)");
            $stmt->bind_param("iids", $accountID, $budgetCategoryID, $amount, $source);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactionStatus = "Within Budget"; // Default
            if ($row = $result->fetch_assoc()) {
                $transactionStatus = $row['transaction_budget_status'];
            }
            $stmt->close();

            // If the transaction exceeds budget, handle OTP verification
            if ($transactionStatus === "Exceeds Budget") {
                if (!$otp) {
                    // Generate OTP
                    $generated_otp = rand(100000, 999999);

                    // Save OTP to the database (valid for 5 minutes)
                    $stmt = $mysqli->prepare("INSERT INTO otp (account_id, otp, expires_at) VALUES (?, ?, NOW() + INTERVAL 5 MINUTE)");
                    $stmt->bind_param("iis", $accountID, $generated_otp);
                    $stmt->execute();
                    $stmt->close();

                    // Send OTP via Email
                    $subject = "Your OTP for Fund Transfer";
                    $message = "Your OTP is: " . $generated_otp;
                    mail($email, $subject, $message);

                    echo json_encode(["status" => "OTP Required"]);
                    exit;
                }
            }

            $emailMeta = [
                "account_number"                => $accountID,
                "email"                         => $email,
                "phone"                         => $phone,
                "first_name"                    => $firstName,
                "last_name"                     => $lastName,
                "account_type"                  => $accountType,
                "account_officer_first_name"    => $accountOfficerFirstName,
                "account_officer_last_name"     => $accountOfficerLastName
            ];

            $message = supportHTML($emailMeta, $subject, $message);
            $successMessage = 'A support email has been sent to ' . $to;

            // Send support email
            if (!sendMail($to, $message, $subject)) {
                throw new Exception('An error occurred while sending the support email.');
            }

            //record this success message
            $stmt = $mysqli->prepare("CALL storeNotification($userID, 'Support Email Sent', '$successMessage')");
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
