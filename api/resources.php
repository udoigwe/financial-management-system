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
        case 'notifications':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');
            $notificationID = !isset($_GET['notification_id']) || empty($_GET['notification_id']) ? null : $mysqli->real_escape_string($_GET['notification_id']);

            //instantiate notifications array
            $notifications = array();

            // Initialize the query and filter array
            $query = "SELECT * FROM notifications WHERE user_id = $userID AND notification_status = 'Unread' AND 1=1";  // '1=1' acts as a base filter
            $params = [];
            $types = "";

            // Add conditions based on variable availability
            if (!is_null($notificationID)) {
                $query .= " AND notification_id = ?";
                $params[] = $notificationID;
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
                $notifications[] = $row;
            }

            $stmt->close();

            if ($notificationID) {
                //Update notification to read if clicked on
                $stmt = $mysqli->prepare("UPDATE notifications SET notification_status = 'Read' WHERE notification_id = ?");
                $stmt->bind_param('i', $notificationID);
                $stmt->execute();
                $stmt->close();
            }

            $meta = [
                'count'             =>             count($notifications),
                'notifications'     =>             $notifications
            ];

            $response['error'] = false;
            $response['data'] = $meta;

            break;

        case 'support':
            if (!checkAvailability(array('token', 'to', 'subject', 'message'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $to = $mysqli->real_escape_string($_POST['to']);
            $subject = $mysqli->real_escape_string($_POST['subject']);
            $message = $mysqli->real_escape_string($_POST['message']);
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
