<?php
//database connect
require_once './DB_CONNECT.php';

//api functions
require_once './functions.php';

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
        case 'customer_dashboard':
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

            //instantiate transactions summary array
            $transactions = array();
            $year = date('Y');
            $month = date("m");
            $days = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30');
            $debitArray = array();
            $creditArray = array();
            $dayLabelsArray = array();

            //monthly confirmed transaction summary
            foreach ($days as $day) {
                $period = $year . "-" . $month . "-" . $day;

                //select daily Credits
                $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit' AND DATE(created_at) = '$period' AND account_id = $accountID");
                $stmt->execute();
                $result = $stmt->get_result();
                $totalCredits = intval($result->fetch_assoc()['total_credits']);
                $stmt->close();

                //select daily debits
                $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit' AND DATE(created_at) = '$period' AND account_id = $accountID");
                $stmt->execute();
                $result = $stmt->get_result();
                $totalDebits = intval($result->fetch_assoc()['total_debits']);
                $stmt->close();

                $dayLabelsArray[] = $period;
                $debitArray[] = $totalDebits;
                $creditArray[] = $totalCredits;
            }

            //morris chart data
            $transactionChartData = [
                'creditdataset'             =>            $creditArray,
                'debitdataset'              =>            $debitArray,
                'daysdataset'               =>            $dayLabelsArray
            ];

            $meta = [
                'transaction_chart_data'             =>             $transactionChartData
            ];

            $response['error'] = false;
            $response['dashboard'] = $meta;

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
