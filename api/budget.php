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

            // Check if category exists
            $stmt = $mysqli->prepare("SELECT * FROM budget_categories WHERE account_id = ? AND category_id = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ii', $accountID, $categoryID);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Category does not exist');
            }
            $existingCategoryName = $result->fetch_assoc()['category_name'];
            $stmt->close();

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

            if ($existingCategoryName === "Savings" && $categoryName !== "Savings") {
                throw new Exception('Sorry!!! You are not allowed to change the savings category name');
            }

            // update budget category
            $stmt = $mysqli->prepare("UPDATE budget_categories SET `category_name` = ?, `category_description` = ?, `budget_limit` = ?, `budget_limit_start_time` = ?, `budget_limit_end_time` = ?, `color_code` = ?, `budget_category_status` = ? WHERE category_id = ?");
            if (!$stmt) {
                throw new Exception('Database error: ' . $mysqli->error);
            }
            $stmt->bind_param('ssissssi', $categoryName, $description, $budgetLimit, $budgetLimitStartTime, $budgetLimitEndTime, $colorCode, $budgetCategoryStatus, $categoryID);
            $stmt->execute();
            $stmt->close();

            //update safe lock start and end times if budget category is SAVINGS
            if ($categoryName === "Savings") {
                $stmt = $mysqli->prepare("UPDATE safe_lock SET `lock_start_time` = ?, `lock_end_time` = ? WHERE account_id = ?");
                if (!$stmt) {
                    throw new Exception('Database error: ' . $mysqli->error);
                }
                $stmt->bind_param('ssi', $budgetLimitStartTime, $budgetLimitEndTime, $accountID);
                $stmt->execute();
                $stmt->close();
            }

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
