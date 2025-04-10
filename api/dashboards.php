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

            //get balance of safe lock
            $stmt = $mysqli->prepare("SELECT balance FROM safe_lock WHERE account_id = $accountID");
            $stmt->execute();
            $result = $stmt->get_result();
            $safeLockBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get main account balance
            $stmt = $mysqli->prepare("SELECT balance FROM account WHERE account_id = $accountID");
            $stmt->execute();
            $result = $stmt->get_result();
            $mainAccountBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get unread messages count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS unread_notifications FROM notifications WHERE user_id = $userID AND notification_status = 'Unread'");
            $stmt->execute();
            $result = $stmt->get_result();
            $unreadMessagesCount = intval($result->fetch_assoc()['unread_notifications']);
            $stmt->close();

            //get active budget categories count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS active_budget_categories_count FROM budget_categories WHERE account_id = $accountID AND budget_category_status = 'Active'");
            $stmt->execute();
            $result = $stmt->get_result();
            $activeBudgetCategoriesCount = intval($result->fetch_assoc()['active_budget_categories_count']);
            $stmt->close();

            //get successful transactions count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS transactions_count FROM transactions WHERE account_id = $accountID");
            $stmt->execute();
            $result = $stmt->get_result();
            $transactionsCount = intval($result->fetch_assoc()['transactions_count']);
            $stmt->close();

            //get successful transactions count that exceeded budget
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS exceeds_budget_count FROM transactions WHERE account_id = $accountID AND transaction_budget_status = 'Exceeds Budget'");
            $stmt->execute();
            $result = $stmt->get_result();
            $exceedsBudgetCount = intval($result->fetch_assoc()['exceeds_budget_count']);
            $stmt->close();

            //get successful transactions count that within budget
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS within_budget_count FROM transactions WHERE account_id = $accountID AND transaction_budget_status = 'Within Budget'");
            $stmt->execute();
            $result = $stmt->get_result();
            $withinBudgetCount = intval($result->fetch_assoc()['within_budget_count']);
            $stmt->close();

            //select total Credits
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit' AND account_id = $accountID");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalCredits = intval($result->fetch_assoc()['total_credits']);
            $stmt->close();

            //select total debits
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit' AND account_id = $accountID");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalDebits = intval($result->fetch_assoc()['total_debits']);
            $stmt->close();

            $meta = [
                'transaction_chart_data'                =>              $transactionChartData,
                'safe_lock_balance'                     =>              $safeLockBalance,
                'main_account_balance'                  =>              $mainAccountBalance,
                'unread_messages_count'                 =>              $unreadMessagesCount,
                'active_budget_categories_count'        =>              $activeBudgetCategoriesCount,
                'transactions_count'                    =>              $transactionsCount,
                'exceeds_budget_count'                  =>              $exceedsBudgetCount,
                'within_budget_count'                   =>              $withinBudgetCount,
                'total_credits'                         =>              $totalCredits,
                'total_debits'                          =>              $totalDebits,
            ];

            $response['error'] = false;
            $response['dashboard'] = $meta;

            break;

        case 'admin_dashboard':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');

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
                $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit' AND DATE(created_at) = '$period'");
                $stmt->execute();
                $result = $stmt->get_result();
                $totalCredits = intval($result->fetch_assoc()['total_credits']);
                $stmt->close();

                //select daily debits
                $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit' AND DATE(created_at) = '$period'");
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

            //get balance of safe lock
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM safe_lock");
            $stmt->execute();
            $result = $stmt->get_result();
            $safeLockBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get main account balance
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM account");
            $stmt->execute();
            $result = $stmt->get_result();
            $mainAccountBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get unread messages count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS unread_notifications FROM notifications WHERE user_id = $userID AND notification_status = 'Unread'");
            $stmt->execute();
            $result = $stmt->get_result();
            $unreadMessagesCount = intval($result->fetch_assoc()['unread_notifications']);
            $stmt->close();

            //get active budget categories count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS active_budget_categories_count FROM budget_categories WHERE budget_category_status = 'Active'");
            $stmt->execute();
            $result = $stmt->get_result();
            $activeBudgetCategoriesCount = intval($result->fetch_assoc()['active_budget_categories_count']);
            $stmt->close();

            //get successful transactions count
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS transactions_count FROM transactions");
            $stmt->execute();
            $result = $stmt->get_result();
            $transactionsCount = intval($result->fetch_assoc()['transactions_count']);
            $stmt->close();

            //get successful transactions count that exceeded budget
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS exceeds_budget_count FROM transactions WHERE transaction_budget_status = 'Exceeds Budget'");
            $stmt->execute();
            $result = $stmt->get_result();
            $exceedsBudgetCount = intval($result->fetch_assoc()['exceeds_budget_count']);
            $stmt->close();

            //get successful transactions count that within budget
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS within_budget_count FROM transactions WHERE transaction_budget_status = 'Within Budget'");
            $stmt->execute();
            $result = $stmt->get_result();
            $withinBudgetCount = intval($result->fetch_assoc()['within_budget_count']);
            $stmt->close();

            //select total Credits
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit'");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalCredits = intval($result->fetch_assoc()['total_credits']);
            $stmt->close();

            //select total debits
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit'");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalDebits = intval($result->fetch_assoc()['total_debits']);
            $stmt->close();

            //select total service charges
            $stmt = $mysqli->prepare("SELECT COALESCE(ROUND(SUM(transaction_fee), 2), 0) AS total_service_charges FROM transactions");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalServiceCharges = intval($result->fetch_assoc()['total_service_charges']);
            $stmt->close();

            //select total admins
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE role = 'Admin'");
            $stmt->execute();
            $result = $stmt->get_result();
            $adminCount = intval($result->fetch_assoc()['admin_count']);
            $stmt->close();

            //select total customers
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS customer_count FROM users WHERE role = 'Customer'");
            $stmt->execute();
            $result = $stmt->get_result();
            $customerCount = intval($result->fetch_assoc()['customer_count']);
            $stmt->close();

            //select total customers
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS account_officer_count FROM users WHERE role = 'Account Officer'");
            $stmt->execute();
            $result = $stmt->get_result();
            $accountOfficerCount = intval($result->fetch_assoc()['account_officer_count']);
            $stmt->close();

            $meta = [
                'transaction_chart_data'                =>              $transactionChartData,
                'safe_lock_balance'                     =>              $safeLockBalance,
                'main_account_balance'                  =>              $mainAccountBalance,
                'unread_messages_count'                 =>              $unreadMessagesCount,
                'active_budget_categories_count'        =>              $activeBudgetCategoriesCount,
                'transactions_count'                    =>              $transactionsCount,
                'exceeds_budget_count'                  =>              $exceedsBudgetCount,
                'within_budget_count'                   =>              $withinBudgetCount,
                'total_credits'                         =>              $totalCredits,
                'total_debits'                          =>              $totalDebits,
                'admin_count'                           =>              $adminCount,
                'customer_count'                        =>              $customerCount,
                'account_officer_count'                 =>              $accountOfficerCount,
                'total_service_charges'                 =>              $totalServiceCharges
            ];

            $response['error'] = false;
            $response['dashboard'] = $meta;

            break;
        case 'account_officer_dashboard':
            if (!checkAvailability(array('token'))) {
                throw new Exception('Invalid request: Token is required');
            }

            if (!verifyJWT('sha256', $_GET['token'], TOKEN_SECRET)) {
                throw new Exception('Invalid authorization token provided');
            }

            // Get and sanitize user input
            $token = $mysqli->real_escape_string($_GET['token']);
            $userID = payloadClaim($token, 'user_id');

            //instantiate my account IDs array
            $myAccountIDs = array();

            //get all account holders under my
            $stmt = $mysqli->prepare("SELECT * FROM account_view WHERE account_officer_id = $userID");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $myAccountIDs[] = $row['account_id'];
            }
            $implodedAccountIDs = count($myAccountIDs) > 0 ? implode(', ', $myAccountIDs) : null;
            $stmt->close();

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
                $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE 1 = 0") : $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit' AND DATE(created_at) = '$period' AND account_id IN ($implodedAccountIDs)");
                $stmt->execute();
                $result = $stmt->get_result();
                $totalCredits = intval($result->fetch_assoc()['total_credits']);
                $stmt->close();

                //select daily debits
                $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE 1 = 0") : $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit' AND DATE(created_at) = '$period' AND account_id IN ($implodedAccountIDs)");
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

            //get balance of safe lock
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM safe_lock WHERE 1 = 0") : $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM safe_lock WHERE account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $safeLockBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get main account balance
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM account WHERE 1 = 0") : $mysqli->prepare("SELECT COALESCE(ROUND(SUM(balance), 2), 0) AS balance FROM account WHERE account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $mainAccountBalance = intval($result->fetch_assoc()['balance']);
            $stmt->close();

            //get unread messages count
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COUNT(*) AS unread_notifications FROM notifications WHERE 1 = 0") : $mysqli->prepare("SELECT COUNT(*) AS unread_notifications FROM notifications WHERE user_id = $userID AND notification_status = 'Unread'");
            $stmt->execute();
            $result = $stmt->get_result();
            $unreadMessagesCount = intval($result->fetch_assoc()['unread_notifications']);
            $stmt->close();

            //get active budget categories count
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COUNT(*) AS active_budget_categories_count FROM budget_categories WHERE 1 = 0") :  $mysqli->prepare("SELECT COUNT(*) AS active_budget_categories_count FROM budget_categories WHERE budget_category_status = 'Active' AND account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $activeBudgetCategoriesCount = intval($result->fetch_assoc()['active_budget_categories_count']);
            $stmt->close();

            //get successful transactions count
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COUNT(*) AS transactions_count FROM transactions WHERE 1 = 0") : $mysqli->prepare("SELECT COUNT(*) AS transactions_count FROM transactions WHERE account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $transactionsCount = intval($result->fetch_assoc()['transactions_count']);
            $stmt->close();

            //get successful transactions count that exceeded budget
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COUNT(*) AS exceeds_budget_count FROM transactions WHERE 1 = 0") :  $mysqli->prepare("SELECT COUNT(*) AS exceeds_budget_count FROM transactions WHERE transaction_budget_status = 'Exceeds Budget' AND account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $exceedsBudgetCount = intval($result->fetch_assoc()['exceeds_budget_count']);
            $stmt->close();

            //get successful transactions count that within budget
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COUNT(*) AS within_budget_count FROM transactions WHERE 1 = 0") : $mysqli->prepare("SELECT COUNT(*) AS within_budget_count FROM transactions WHERE transaction_budget_status = 'Within Budget' AND account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $withinBudgetCount = intval($result->fetch_assoc()['within_budget_count']);
            $stmt->close();

            //select total Credits
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE 1 = 0") :  $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_credits FROM transactions WHERE transaction_type = 'Credit' AND account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalCredits = intval($result->fetch_assoc()['total_credits']);
            $stmt->close();

            //select total debits
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE 1 = 0") :  $mysqli->prepare("SELECT COALESCE(ROUND(SUM(amount), 2), 0) AS total_debits FROM transactions WHERE transaction_type = 'Debit' AND account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalDebits = intval($result->fetch_assoc()['total_debits']);
            $stmt->close();

            //select total service charges
            $stmt = !$implodedAccountIDs ? $mysqli->prepare("SELECT COALESCE(ROUND(SUM(transaction_fee), 2), 0) AS total_service_charges FROM transactions WHERE 1 = 0") :  $mysqli->prepare("SELECT COALESCE(ROUND(SUM(transaction_fee), 2), 0) AS total_service_charges FROM transactions WHERE account_id IN ($implodedAccountIDs)");
            $stmt->execute();
            $result = $stmt->get_result();
            $totalServiceCharges = intval($result->fetch_assoc()['total_service_charges']);
            $stmt->close();

            $meta = [
                'transaction_chart_data'                =>              $transactionChartData,
                'safe_lock_balance'                     =>              $safeLockBalance,
                'main_account_balance'                  =>              $mainAccountBalance,
                'unread_messages_count'                 =>              $unreadMessagesCount,
                'active_budget_categories_count'        =>              $activeBudgetCategoriesCount,
                'transactions_count'                    =>              $transactionsCount,
                'exceeds_budget_count'                  =>              $exceedsBudgetCount,
                'within_budget_count'                   =>              $withinBudgetCount,
                'total_credits'                         =>              $totalCredits,
                'total_debits'                          =>              $totalDebits,
                'customer_count'                        =>              count($myAccountIDs),
                'total_service_charges'                 =>              $totalServiceCharges
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
