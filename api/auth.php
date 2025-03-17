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
    
            //echo "<script>console.log('PHP Log: " . addslashes($_POST) . "');</script>";
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
?>
