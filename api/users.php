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
