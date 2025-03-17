<?php
//database connect
require_once './DB_CONNECT.php';

// php mailer
require_once('PHPMailer/phpmailer/class.phpmailer.php');

//api functions
require_once './functions.php';

//api functions
require_once './email-templates.php';

//resize class
//require_once 'resize-class.php';

//resize class
//require_once 'time-ago.php';

//response array
$response = array();

//check api call
if (isset($_GET['call']) && !empty($_GET['call'])) {
    //sanitize call
    $call = $mysqli->real_escape_string($_GET['call']);

    switch ($call) {
        case 'sign_up':
            if (checkAvailability((array('name', 'dob', 'address', 'phone', 'email', 'password')))) {
                //description of params
                $name = $mysqli->real_escape_string($_POST['name']);
                $dob = $mysqli->real_escape_string($_POST['dob']);
                $address = $mysqli->real_escape_string($_POST['address']);
                $phone = $mysqli->real_escape_string($_POST['phone']);
                $email = $mysqli->real_escape_string($_POST['email']);
                $password = $mysqli->real_escape_string($_POST['password']);

                //check if email already exists
                $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
                $stmt->execute();    // Execute the prepared query.
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $stmt->close();

                    //check if phone number exists
                    $stmt = $mysqli->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
                    $stmt->bind_param('s', $phone);  // Bind "$phone" to parameter.
                    $stmt->execute();    // Execute the prepared query.
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        $stmt->close();
                        $hash = md5(rand(0, 1000));
                        $message = accountVerificationHTML($email, $hash);

                        // Turn off auto-commit mode
                        $mysqli->autocommit(false);
                        $mysqli->begin_transaction();

                        //insert new user record
                        $stmt = $mysqli->prepare("INSERT INTO users (`name`, `dob`, `address`, `phone`, `email`, `password`, `hash`, `hash_time`) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                        $stmt->bind_param('sssssss', $name, $dob, $address, $phone, $email, $password, $hash);
                        $stmt->execute();

                        if (sendMail($email, $message, 'Account Verification')) {
                            $stmt->close();
                            $mysqli->commit();

                            $response['error'] = false;
                            $response['message'] = 'An account verification email has been sent to ' . $email;
                        } else {
                            $stmt->close();
                            $mysqli->rollback();

                            $response['error'] = true;
                            $response['message'] = 'An error occured while sending a verification email to ' . $email;
                        }
                    } else {
                        //email already exists
                        $response['error'] = true;
                        $response['message'] = 'Phone number aready exists';
                    }
                } else {
                    //email already exists
                    $response['error'] = true;
                    $response['message'] = 'Email aready exists';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Invalid request: All fields are required';
            }

        default:

            $response['error'] = true;
            $response['message'] = 'Invalid api call';

            break;
    }
} else {
    $response['error'] = true;
    $response['message'] = 'No api call';
}

//return json encoded response
echo json_encode($response);
