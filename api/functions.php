<?php

// Include PHPMailer classes
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//function to base64UrlEncode data
function base64UrlEncode($data)
{
    $urlSafeData = strtr(base64_encode($data), '+/', '-_');
    return rtrim($urlSafeData, '=');
}

//function to base64UrlDecode data
function base64UrlDecode($data)
{
    $urlUnsafeData = strtr($data, '-_', '+/');
    $paddedData = str_pad($urlUnsafeData, strlen($urlUnsafeData) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($paddedData);
}

//function to generate JWT
function generateJWT($algorithm, $header, $payload, $secret)
{
    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    //delimit with period(.)
    $dataEncoded = $headerEncoded . "." . $payloadEncoded;
    $rawSignature = hash_hmac($algorithm, $dataEncoded, $secret, true);
    $signatureEncoded = base64UrlEncode($rawSignature);

    //delimit with second (.)
    $jwt = $dataEncoded . "." . $signatureEncoded;

    return $jwt;
}

//verify JWT
function verifyJWT($algorithm, $jwt, $secret)
{
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
    $dataEncoded = $headerEncoded . "." . $payloadEncoded;
    $signature = base64UrlDecode($signatureEncoded);
    $rawSignature = hash_hmac($algorithm, $dataEncoded, $secret, true);

    return hash_equals($rawSignature, $signature);
}

// return payload
function returnPayload($jwt)
{
    $dataArray = explode(".", $jwt);
    $payloadEncoded = $dataArray[1];
    $payloadDecoded = json_decode(base64_decode($payloadEncoded), true);

    return $payloadDecoded;
}

// return payload claim
function payloadClaim($jwt, $param)
{
    $dataArray = explode(".", $jwt);
    $payloadEncoded = $dataArray[1];
    $payloadDecoded = json_decode(base64_decode($payloadEncoded), true);
    $claim = $payloadDecoded[$param];

    return $claim;
}

//check availability of parameters
function checkAvailability($params)
{
    foreach ($params as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            return false;
        }
    }

    return true;
}

function sec_session_start()
{
    $session_name = 'sec_session_id';   // Set a custom session name
    /*Sets the session name. 
     *This must come before session_set_cookie_params due to an undocumented bug/feature in PHP. 
     */
    session_name($session_name);

    $secure = false;
    // This stops JavaScript being able to access the session id. SET TO true to disalow javascript access
    $httponly = false;
    // Forces sessions to only use cookies.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../pages/error.php");
        exit();
    }
    // Gets current cookies params.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);

    session_start();            // Start the PHP session 
    session_regenerate_id(true);    // regenerated the session, delete the old one. 
}

function pinVerification($pin, $pinRef, $username, $mysqli)
{
    $stmt = $mysqli->prepare("SELECT * FROM scratch_pins WHERE pin = ? AND pinSerial = ? LIMIT 1");
    $stmt->bind_param('ss', $pin, $pinRef);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();

        if ($record['status'] == "Used" && $record['user'] != $username) {
            return "In Use";
        } elseif ($record['user'] == $username || $record['user'] == "") {
            return true;
        }
    } else {
        return "Invalid";
    }
}

function checkbrute($user_id, $user_type, $mysqli)
{
    // Get timestamp of current time 
    $now = time();

    // All login attempts are counted from the past 2 hours. 
    $valid_attempts = $now - (2 * 60 * 60);

    if ($stmt = $mysqli->prepare("SELECT time_stamp FROM login_attempts WHERE user_id = ? AND time_stamp > '" . $valid_attempts . "' AND user_type = '" . $user_type . "'")) {
        $stmt->bind_param('i', $user_id);

        // Execute the prepared query. 
        $stmt->execute();
        $stmt->store_result();

        // If there have been more than 5 failed logins 
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    }
}

function esc_url($url)
{
    if ('' == $url) {
        return $url;
    }

    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;

    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }

    $url = str_replace(';//', '://', $url);

    $url = htmlentities($url);

    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);

    if ($url[0] !== '/') {
        // We're only interested in relative links from $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function redirect_to($url)
{
    header("Location: {$url}");
}

function ordinal($student_position)
{
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    if ((($student_position % 100) >= 11) && (($student_position % 100) <= 13)) {
        return $student_position . 'th';
    } else {
        return $student_position . $ends[$student_position % 10];
    }
}

function grade($totalScore, $class)
{
    $seniorClassArray = array('SS3', 'SS2', 'SS1');
    $juniorClassArray = array('JSS3', 'JSS2', 'JSS1');
    $primaryClassArray = array('PRY6', 'PRY5', 'PRY4', 'PRY3', 'PRY2', 'PRY1');
    $nurseryClassArray = array('NUR3', 'NUR2', 'NUR2');

    if (in_array($class, $seniorClassArray)) {
        if ($totalScore < 40) {
            return "F9";
        } elseif ($totalScore >= 40 && $totalScore < 45) {
            return "E8";
        } elseif ($totalScore >= 45 && $totalScore < 50) {
            return "D7";
        } elseif ($totalScore >= 50 && $totalScore < 54) {
            return "C6";
        } elseif ($totalScore >= 54 && $totalScore < 57) {
            return "C5";
        } elseif ($totalScore >= 57 && $totalScore < 60) {
            return "C4";
        } elseif ($totalScore >= 60 && $totalScore < 65) {
            return "B2";
        } elseif ($totalScore >= 65 && $totalScore < 70) {
            return "B3";
        } elseif ($totalScore >= 70) {
            return "A1";
        }
    } else {
        if ($totalScore < 40) {
            return "F";
        } elseif ($totalScore >= 40 && $totalScore < 50) {
            return "P";
        } elseif ($totalScore >= 50 && $totalScore < 60) {
            return "C";
        } elseif ($totalScore >= 60 && $totalScore < 70) {
            return "B";
        } elseif ($totalScore >= 70) {
            return "A";
        }
    }
}

function remark($grade)
{
    if ($grade == "F" || $grade == "F9") {
        return "<b>Fail</b>";
    } elseif ($grade == "E" || $grade == "E8" || $grade == "P") {
        return "<b>Poor Pass</b>";
    } elseif ($grade == "D7") {
        return "<b>Pass</b>";
    } elseif ($grade == "C6" || $grade == "C5") {
        return "<b>Fair</b>";
    } elseif ($grade == "C" || $grade == "C4") {
        return "<b>Good</b>";
    } elseif ($grade == "B" || $grade == "B2" || $grade == "B3") {
        return "<b>Very Good</b>";
    } elseif ($grade == "A" || $grade == "A1") {
        return "<b>Excellent</b>";
    }
}

function returnAverage($username, $session_term, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $session = substr($session_term, 0, 9);
    $term = substr($session_term, 10);
    $query = ($term != "3rd_Term") ? $mysqli->query("SELECT GROUP_CONCAT(subjects) AS subjects FROM registered_subjects WHERE campus_id = '" . $campusID . "' AND session = '" . $session . "' AND term = '" . $term . "' AND username = '" . $username . "'") : $mysqli->query("SELECT GROUP_CONCAT(subjects) AS subjects FROM registered_subjects WHERE campus_id = '" . $campusID . "' AND session = '" . $session . "' AND username = '" . $username . "'");

    if ($query->num_rows > 0) {
        $row = $query->fetch_array();
        $subjects = $row['subjects'];
        $subjectsArray = explode(",", $subjects);
        $subjectsCount = count($subjectsArray);
        $fetch = ($term != "3rd_Term") ? $mysqli->query("SELECT SUM(total_score) AS totalScore FROM scores WHERE campus_id = '" . $campusID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "'") : $mysqli->query("SELECT SUM(total_score) AS totalScore FROM scores WHERE campus_id = '" . $campusID . "' AND SUBSTRING(session_term, 1, 9) = '" . $session . "' AND username = '" . $username . "'");

        if ($fetch) {
            $result = $fetch->fetch_array();
            $totalScore = intval($result['totalScore']);
            $averageScore = round(($totalScore / $subjectsCount), 2);

            //return $averageScore;
        } else {
            $averageScore = 0;
        }
    } else {
        $averageScore = 0;
    }

    return $averageScore;
}

function returnTotalScore($username, $session_term, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $session = substr($session_term, 0, 9);
    $term = substr($session_term, 10);
    $query = ($term != "3rd_Term") ? $mysqli->query("SELECT SUM(total_score) AS sum FROM scores WHERE campus_id = '" . $campusID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "'") : $mysqli->query("SELECT SUM(total_score) AS sum FROM scores WHERE campus_id = '" . $campusID . "' AND SUBSTRING(session_term, 1, 9) = '" . $session . "' AND username = '" . $username . "'");

    if ($query) {
        $row = $query->fetch_array();
        $sum = $row['sum'];

        if ($sum != NULL) {
            $totalScore = $sum;
        } else {
            $totalScore = 0;
        }
    } else {
        $totalScore = 0;
    }

    return $totalScore;
}

function returnSubjectTotalScore($username, $session_term, $campusID, $subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $query = $mysqli->query("SELECT total_score FROM scores WHERE campus_id = '" . $campusID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "' AND subject_id = '" . $subjectID . "' LIMIT 1");

    if ($query->num_rows > 0) {
        $row = $query->fetch_array();
        $score = $row['total_score'];
    } else {
        $score = 0;
    }

    return $score;
}

function returnSubjectCumAverageScore($username, $session_term, $campusID, $subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $session = substr($session_term, 0, 9);
    $term = substr($session_term, 10);
    $query = $mysqli->query("SELECT (SUM(total_score) / 3) AS average FROM scores WHERE campus_id = '" . $campusID . "' AND SUBSTRING(session_term, 1, 9) = '" . $session . "' AND username = '" . $username . "' AND subject_id = '" . $subjectID . "'");

    if ($query->num_rows > 0) {
        $row = $query->fetch_array();
        $averageScore = round($row['average'], 2);
    } else {
        $averageScore = 0;
    }

    return $averageScore;
}

function returnSubjectCumTotalScore($username, $session_term, $campusID, $subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $session = substr($session_term, 0, 9);
    $term = substr($session_term, 10);
    $query = $mysqli->query("SELECT SUM(total_score) AS sum FROM scores WHERE campus_id = '" . $campusID . "' AND SUBSTRING(session_term, 1, 9) = '" . $session . "' AND username = '" . $username . "' AND subject_id = '" . $subjectID . "'");

    if ($query->num_rows > 0) {
        $row = $query->fetch_array();
        $sum = $row['sum'];
    } else {
        $sum = 0;
    }

    return $sum;
}

//function to get client ip address
function get_client_ip_server()
{
    $ip = '';
    if ($_SERVER['HTTP_CLIENT_IP']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_CLIENT_IP']);
    } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_X_FORWARDED_FOR']);
    } elseif ($_SERVER['HTTP_X_FORWARDED']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_X_FORWARDED']);
    } elseif ($_SERVER['HTTP_FORWARDED']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['HTTP_FORWARDED']);
    } elseif ($_SERVER['REMOTE_ADDR']) {
        $ip = preg_replace('#[^0-9.]#', '', $_SERVER['REMOTE_ADDR']);
    } else {
        $ip = 'UNKNOWN';
    }

    return $ip;
}

function isValidDateTimeString($str_dt, $str_dateformat)
{
    $date = DateTime::createFromFormat($str_dateformat, $str_dt);
    return $date && DateTime::getLastErrors()["warning_count"] == 0 && DateTime::getLastErrors()["error_count"] == 0;
}

function paginate_function($item_per_page, $current_page, $total_records, $total_pages)
{
    $pagination = '';
    if ($total_pages > 0 && $total_pages != 1 && $current_page <= $total_pages) {
        //verify totl pages and current page number
        $pagination .= '<ul class="pagination pagination-sm pull-right push-down-20 push-up-20">';
        $right_links = $current_page + 3;
        $previous = $current_page - 3; //previous link
        $next = $current_page + 1; //next link
        $first_link = true; //boolean variable to decide our first link

        if ($current_page > 1) {
            $previous_link = ($previous == 0) ? 1 : $previous;
            $pagination .= '<li class="first"><a href="#" data-page="1" title="First">&laquo;</a></li>'; //first link
            $pagination .= '<li><a href="#" data-page="' . $previous_link . '" title="Previous">&lt;</a></li>'; //Previous Link
            for ($i = ($current_page - 2); $i < $current_page; $i++) {
                //create left-hand side links
                if ($i > 0) {
                    $pagination .= '<li><a href="#" data-page="' . $i . '" title="Page' . $i . '">' . $i . '</a></li>';
                }
            }
            $first_link = false; //set first link to false
        }
    }

    if ($first_link) {
        //if current active page is first link
        $pagination .= '<li class="first active">' . $current_page . '</li>';
    } elseif ($current_page == $total_pages) {
        //if its the last active link
        $pagination .= '<li class="last active">' . $current_page . '</li>';
    } else {
        //regular current link
        $pagination .= '<li class="active">' . $current_page . '</li>';
    }

    for ($i = $current_page + 1; $i < $right_links; $i++) {
        //create right-hand side links
        if ($i <= $total_pages) {
            $pagination .= '<li><a href="#" data-page="' . $i . '" title="Page ' . $i . '">' . $i . '</a></li>';
        }
    }

    if ($current_page < $total_pages) {
        $next_link = ($i > $total_pages) ? $total_pages : $i;
        $pagination .= '<li><a href="#" data-page="' . $next_link . '" title="Next">»</a></li>'; //nect link
        $pagination .= '<li class="last"><a href="#" data-page="' . $total_pages . '" title="Last">&raquo;</a></li>'; //last link
    }
    $pagination .= '</ul>';
    return $pagination; //return pagination links
}

function attendanceRate($session_term, $username, $subjectID, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $attendanceRate = "";
    $stmt = $mysqli->query("SELECT id FROM students_attendance_register WHERE session_term = '" . $session_term . "' AND subject_id = '" . $subjectID . "' AND username = '" . $username . "' AND campus_id = '" . $campusID . "'");
    $sql = $mysqli->query("SELECT COUNT(*) AS present FROM students_attendance_register WHERE subject_id = '" . $subjectID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "' AND campus_id = '" . $campusID . "' AND status = '1'");

    if ($stmt->num_rows > 0) {
        $count = $sql->fetch_array();
        $present = $count['present'];
        $total = $stmt->num_rows;
        $attendanceRate = (($present / $total) * 100);
    } else {
        $attendanceRate = "-";
    }

    return $attendanceRate;
}

function relationship($relationship, $gender)
{
    if (($relationship == "Father" || $relationship == "Mother") && $gender == "Male") {
        return "son";
    } elseif (($relationship == "Father" || $relationship == "Mother") && $gender == "Female") {
        return "daughter";
    } elseif (($relationship == "Uncle" || $relationship == "Aunt") && $gender == "Male") {
        return "nephew";
    } elseif (($relationship == "Uncle" || $relationship == "Aunt") && $gender == "Female") {
        return "Neice";
    } elseif (($relationship == "Brother" || $relationship == "Sister") && $gender == "Male") {
        return "brother";
    } elseif (($relationship == "Brother" || $relationship == "Sister") && $gender == "Female") {
        return "sister";
    } elseif (($relationship == "Step-Father" || $relationship == "Step-Mother") && $gender == "Male") {
        return "step-son";
    } elseif (($relationship == "Step-Father" || $relationship == "Step-Mother") && $gender == "Female") {
        return "step-daughter";
    } elseif (($relationship == "Husband" || $relationship == "Wife") && $gender == "Male") {
        return "husband";
    } elseif (($relationship == "Husband" || $relationship == "Wife") && $gender == "Female") {
        return "Wife";
    }
}

function personal_pronoun($gender)
{
    switch ($gender) {
        case 'Male':
            return "he";
            break;

        case 'Female':
            return "she";
            break;

        default:
            return "he/she";
            break;
    }
}

function personal_pronoun1($gender)
{
    switch ($gender) {
        case 'Male':
            return "him";
            break;

        case 'Female':
            return "her";
            break;

        default:
            return "him/her";
            break;
    }
}

function possessive_pronoun($gender)
{
    switch ($gender) {
        case 'Male':
            return "his";
            break;

        case 'Female':
            return "her";
            break;

        default:
            return "his/her";
            break;
    }
}

function deleteScores($username, $section, $class, $stream, $subjects, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT SUBSTRING(session_term, 1, 9) AS session, SUBSTRING(session_term, 11) AS term, result_status FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $session = $row['session'];
        $term = $row['term'];
        $resultStatus = $row['result_status'];
        $session_term = $session . "_" . $term;

        if ($resultStatus == "Unpublished") {
            $fetch = $mysqli->query("SELECT * FROM registered_subjects WHERE session = '" . $session . "' AND term = '" . $term . "' AND username = '" . $username . "' AND campus_id = '" . $campusID . "' LIMIT 1");

            if ($fetch->num_rows > 0) {
                $result = $fetch->fetch_array();
                $studentSection = $result['section'];
                $studentClass = $result['student_class'];
                $studentStream = $result['stream'];
                $studentSubjects = $result['subjects'];
                $id = $result['id'];

                if ($section !== $studentSection || $class !== $studentClass || $stream !== $studentStream || $subjects !== $studentSubjects) {
                    $deleteScores = $mysqli->query("DELETE FROM scores WHERE campus_id = '" . $campusID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "'");

                    if ($deleteScores) {
                        $update = $mysqli->query("UPDATE registered_subjects SET section = '" . $section . "', student_class = '" . $class . "', stream = '" . $stream . "', subjects = '" . $subjects . "' WHERE id = '" . $id . "'");

                        if ($update) {
                            return true;
                        } else {
                            return "Update not successful";
                        }
                    } else {
                        return "Scores Update Unsuccessful";
                    }
                } else {
                    return true;
                }
            } else {
                $query = $mysqli->query("SELECT graduation_status FROM registered_students WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "' LIMIT 1");
                $result = $query->fetch_array();
                $status = $result['graduation_status'];

                if ($status == "Not Graduated") {
                    $insert = $mysqli->query("INSERT INTO `registered_subjects` (`section`, `student_class`, `stream`, `campus_id`, `subjects`, `session`, `term`, `username`) VALUES ('" . $section . "', '" . $class . "', '" . $stream . "', '" . $campusID . "', '" . $subjects . "', '" . $session . "', '" . $term . "', '" . $username . "')");

                    if ($insert) {
                        return true;
                    } else {
                        return "Error in subjects registration";
                    }
                }
            }
        } else {
            return "Result Published";
        }
    } else {
        return false;
    }
}

function deleteScores2($username, $section, $class, $stream, $subjects, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT SUBSTRING(session_term, 1, 9) AS session, SUBSTRING(session_term, 11) AS term, result_status FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $session = $row['session'];
        $term = $row['term'];
        $resultStatus = $row['result_status'];
        $session_term = $session . "_" . $term;

        if ($resultStatus == "Unpublished") {
            $fetch = $mysqli->query("SELECT * FROM registered_subjects WHERE session = '" . $session . "' AND term = '" . $term . "' AND username = '" . $username . "' AND campus_id = '" . $campusID . "' LIMIT 1");

            if ($fetch->num_rows > 0) {
                $result = $fetch->fetch_array();
                $studentSection = $result['section'];
                $studentClass = $result['student_class'];
                $studentStream = $result['stream'];
                $studentSubjects = $result['subjects'];
                $id = $result['id'];

                $currentSubjectsArray = explode(",", $studentSubjects);
                $selectedSubjectsArray = explode(",", $subjects);
                $scoresToRemoveArray = array();

                foreach ($currentSubjectsArray as $currentRegSubject) {
                    if (!in_array($currentRegSubject, $selectedSubjectsArray)) {
                        $scoresToRemoveArray[] = $currentRegSubject;
                    }
                }

                if (count($scoresToRemoveArray) > 0) {
                    $scoresToRemoveImploded = implode(",", $scoresToRemoveArray);
                    $deleteScores = $mysqli->query("DELETE FROM scores WHERE campus_id = '" . $campusID . "' AND session_term = '" . $session_term . "' AND username = '" . $username . "' AND subject_id IN('" . $scoresToRemoveImploded . "')");

                    if ($deleteScores) {
                        $update = $mysqli->query("UPDATE registered_subjects SET section = '" . $section . "', student_class = '" . $class . "', stream = '" . $stream . "', subjects = '" . $subjects . "' WHERE id = '" . $id . "'");

                        if ($update) {
                            return true;
                        } else {
                            return "Update not successful";
                        }
                    } else {
                        return "Scores Update Unsuccessful";
                    }
                } else {
                    return true;
                }
            } else {
                $query = $mysqli->query("SELECT graduation_status FROM registered_students WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "' LIMIT 1");
                $result = $query->fetch_array();
                $status = $result['graduation_status'];

                if ($status == "Not Graduated") {
                    $insert = $mysqli->query("INSERT INTO `registered_subjects` (`section`, `student_class`, `stream`, `campus_id`, `subjects`, `session`, `term`, `username`) VALUES ('" . $section . "', '" . $class . "', '" . $stream . "', '" . $campusID . "', '" . $subjects . "', '" . $session . "', '" . $term . "', '" . $username . "')");

                    if ($insert) {
                        return true;
                    } else {
                        return "Error in subjects registration";
                    }
                }
            }
        } else {
            return "Result Published";
        }
    } else {
        return false;
    }
}

function session_of_graduation($class, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT SUBSTRING(session_term, 1, 9) AS session, SUBSTRING(session_term, 1, 4) AS first_half, SUBSTRING(session_term, 6, 4) AS second_half, SUBSTRING(session_term, 11) AS term FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $session = $row['session'];
        $term = $row['term'];
        $first_half = intval($row['first_half']);
        $second_half = intval($row['second_half']);

        if ($class == "NUR1") {
            $session_of_graduation = ($first_half + 2) . "/" . ($second_half + 2);
        } elseif ($class == "NUR2") {
            $session_of_graduation = ($first_half + 1) . "/" . ($second_half + 1);
        } elseif ($class == "NUR3") {
            $session_of_graduation = ($first_half + 0) . "/" . ($second_half + 0);
        } elseif ($class == "PRY1") {
            $session_of_graduation = ($first_half + 5) . "/" . ($second_half + 5);
        } elseif ($class == "PRY2") {
            $session_of_graduation = ($first_half + 4) . "/" . ($second_half + 4);
        } elseif ($class == "PRY3") {
            $session_of_graduation = ($first_half + 3) . "/" . ($second_half + 3);
        } elseif ($class == "PRY4") {
            $session_of_graduation = ($first_half + 2) . "/" . ($second_half + 2);
        } elseif ($class == "PRY5") {
            $session_of_graduation = ($first_half + 1) . "/" . ($second_half + 1);
        } elseif ($class == "PRY6") {
            $session_of_graduation = ($first_half + 0) . "/" . ($second_half + 0);
        } elseif ($class == "JSS1") {
            $session_of_graduation = ($first_half + 5) . "/" . ($second_half + 5);
        } elseif ($class == "JSS2") {
            $session_of_graduation = ($first_half + 4) . "/" . ($second_half + 4);
        } elseif ($class == "JSS3") {
            $session_of_graduation = ($first_half + 3) . "/" . ($second_half + 3);
        } elseif ($class == "SS1") {
            $session_of_graduation = ($first_half + 2) . "/" . ($second_half + 2);
        } elseif ($class == "SS2") {
            $session_of_graduation = ($first_half + 1) . "/" . ($second_half + 1);
        } elseif ($class == "SS3") {
            $session_of_graduation = ($first_half + 0) . "/" . ($second_half + 0);
        }

        return $session_of_graduation;
    }
}

function deleteStudentRecords($username, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $deleteQuery = "DELETE FROM borrowers WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM disciplinary_cases WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM memo WHERE receipient = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM memoreceipients WHERE receipient = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM registered_subjects WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM school_fees WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM scores WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "';";
    $deleteQuery .= "DELETE FROM students_attendance_register WHERE username = '" . $username . "' AND campus_id = '" . $campusID . "'";

    if (mysqli_multi_query($mysqli, $deleteQuery)) {
        return true;
    } else {
        return false;
    }
}

function searchStudentRecord($username, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $search = $mysqli->query("SELECT username FROM (SELECT username, campus_id FROM borrowers UNION ALL SELECT username, campus_id FROM disciplinary_cases UNION ALL SELECT username, campus_id FROM school_fees UNION ALL SELECT username, campus_id FROM scores UNION ALL SELECT username, campus_id FROM students_attendance_register)X WHERE campus_id = '" . $campusID . "' AND username = '" . $username . "' LIMIT 1");

    if ($search->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}

function subjectStatus($subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetch = $mysqli->query("SELECT status FROM subjects WHERE id = '" . $subjectID . "' LIMIT 1");

    if ($fetch->num_rows > 0) {
        $row = $fetch->fetch_array();
        $status = $row['status'];

        if ($status == "Activated") {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function authorizeStudentDelete($studentID, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetch = $mysqli->query("SELECT username FROM registered_students WHERE id = '" . $studentID . "' AND campus_id = '" . $campusID . "' LIMIT 1");

    if ($fetch->num_rows > 0) {
        $row = $fetch->fetch_array();
        $studentUsername = $row['username'];
        $query = $mysqli->query("SELECT student_delete_rights FROM campuses WHERE id = '" . $campusID . "' LIMIT 1");

        if ($query->num_rows > 0) {
            $result = $query->fetch_array();
            $rights = $result['student_delete_rights'];

            if ($rights == "Denied") {
                if (searchStudentRecord($studentUsername, $campusID) === true) {
                    return false;
                } else {
                    return true;
                }
            } else {
                if (deleteStudentRecords($studentUsername, $campusID) === true) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function returnCurrentSessionTerm($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT session_term FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $session_term = $row['session_term'];
        return $session_term;
    }
}

function returnCurrentTerm($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT SUBSTRING(session_term, 11) AS term FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $term = $row['term'];
        return $term;
    } else {
        return "-";
    }
}

function returnCurrentSession($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT SUBSTRING(session_term, 1, 9) AS session FROM sessions_terms WHERE campus_id = '" . $campusID . "' ORDER BY id DESC LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $session = $row['session'];
        return $session;
    } else {
        return "-";
    }
}

function returnSubjectTitle($subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT subject FROM subjects WHERE id = '" . $subjectID . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $subjectTitle = $row['subject'];

        return $subjectTitle;
    } else {
        return "-";
    }
}

function returnSubjectTitles($subjectIDs)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $subjectsArray = explode(",", $subjectIDs);
    $subjects = array();

    foreach ($subjectsArray as $subject) {
        $subjects[] = returnSubjectTitle($subject);
    }

    $subjectsImploded = implode(", ", $subjects);
    return $subjectsImploded;
}

function boardingFacilities($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetch = $mysqli->query("SELECT boarding FROM campuses WHERE id = '" . $campusID . "' LIMIT 1");
    $row = $fetch->fetch_array();

    if ($row['boarding'] == "Yes") {
        return true;
    } else {
        return false;
    }
}

function returnSubjectCordinator($subjectID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT CONCAT(surname,' ',firstname,' ',middlename) AS name FROM academic_staff WHERE FIND_IN_SET('" . $subjectID . "', subject) LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $staffName = $row['name'];

        return $staffName;
    } else {
        return "-";
    }
}

function sendSms($key, $to, $message, $originator)
{
    $URL = "https://smstube.ng/api/sms/send?key=" . $key . "&to=" . $to;
    $URL .= "&text=" . urlencode($message) . "&from=" . urlencode($originator) . "&type=json";
    $fp = fopen($URL, 'r');
    return fread($fp, 1024);
}

function deleteElement($element, $array)
{
    $index = array_search($element, $array);

    if ($index !== false) {
        unset($array[$index]);
    }
}

function deleteElement1($element, $array)
{
    $index = array_search($element, $array);

    if ($index !== false) {
        unset($array[$index]);
        return $array;
    }
}

function placeHolder($value)
{
    $placeHolder = ($value == "") ? '-' : $value;
    return $placeHolder;
}

function holder($value)
{
    $holder = ($value == 0 || $value == NULL) ? '<b style="color:red">None</b>' : $value;
    return $holder;
}

function clearStudent($username)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetchStudent = $mysqli->query("SELECT id FROM registered_students WHERE username = '" . $username . "' LIMIT 1");

    if ($fetchStudent->num_rows > 0) {
        $row = $fetchStudent->fetch_array();
        $searchRegCourses = $mysqli->query("SELECT id FROM registered_courses WHERE username = '" . $username . "' LIMIT 1");
        $searchFees = $mysqli->query("SELECT id FROM school_fees WHERE username = '" . $username . "' LIMIT 1");
        $search = $mysqli->query("SELECT id FROM (SELECT id FROM borrowers WHERE username = '" . $username . "' AND status = 'Unreturned' UNION ALL SELECT id FROM disciplinary_cases WHERE username = '" . $username . "' AND status = 'Unresolved' UNION ALL SELECT id FROM referenced_courses WHERE username = '" . $username . "' UNION ALL SELECT id FROM school_fees WHERE username = '" . $username . "' AND status = 'Unpaid' UNION ALL SELECT id FROM vandals WHERE username = '" . $username . "' AND status = 'Uncleared')X LIMIT 1");

        if ($search->num_rows > 0) {
            return false;
        } elseif ($searchRegCourses->num_rows == 0) {
            return false;
        } elseif ($searchFees->num_rows == 0) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function revenue($paymentType, $session_term, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $session = substr($session_term, 0, 9);
    $term = substr($session_term, 10);
    $revenue = "";

    if ($paymentType == "Part Payment") {
        $sql = $mysqli->query("SELECT SUM(part_payment) AS part_payment FROM school_fees WHERE session = '" . $session . "' AND term = '" . $term . "' AND status = 'Part' AND campus_id = '" . $campusID . "'");

        if ($sql) {
            $result = $sql->fetch_array();
            $revenue = $result['part_payment'];

            if ($revenue == NULL) {
                $revenue = 0;
            } else {
                $revenue = $revenue;
            }
        }
    } else {
        $sql = $mysqli->query("SELECT SUM(prospective_fees) AS full_payment FROM school_fees WHERE session = '" . $session . "' AND term = '" . $term . "' AND status = 'Paid' AND campus_id = '" . $campusID . "'");

        if ($sql) {
            $result = $sql->fetch_array();
            $revenue = $result['full_payment'];

            if ($revenue == NULL) {
                $revenue = 0;
            } else {
                $revenue = $revenue;
            }
        }
    }

    return $revenue;
}

function webmasterRevenueReport($paymentType)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetchCampuses = $mysqli->query("SELECT * FROM campuses");
    $revenue = array();

    if ($fetchCampuses->num_rows > 0) {
        while ($campus = $fetchCampuses->fetch_assoc()) {
            $fetchSession = $mysqli->query("SELECT SUBSTRING(session_term, 1, 9) AS session, SUBSTRING(session_term, 11) AS term FROM sessions_terms WHERE campus_id = '" . $campus['id'] . "' ORDER BY id DESC LIMIT 1");

            if ($fetchSession->num_rows > 0) {
                $row = $fetchSession->fetch_array();
                $session = $row['session'];
                $term = $row['term'];

                if ($paymentType == "Part Payment") {
                    $sql = $mysqli->query("SELECT SUM(part_payment) AS part_payment FROM school_fees WHERE session = '" . $session . "' AND term = '" . $term . "' AND status = 'Part' AND campus_id = '" . $campus['id'] . "'");
                    $result = $sql->fetch_array();
                    $partPayment = $result['part_payment'];

                    if ($partPayment == NULL) {
                        $revenue[] = 0;
                    } else {
                        $revenue[] = $partPayment;
                    }
                } else {
                    $sql = $mysqli->query("SELECT SUM(prospective_fees) AS full_payment FROM school_fees WHERE session = '" . $session . "' AND term = '" . $term . "' AND status = 'Paid' AND campus_id = '" . $campus['id'] . "'");
                    $result = $sql->fetch_array();
                    $fullPayment = $result['full_payment'];

                    if ($fullPayment == NULL) {
                        $revenue[] = 0;
                    } else {
                        $revenue[] = $fullPayment;
                    }
                }
            } else {
                $revenue[] = 0;
            }
        }
    } else {
        $revenue[] = 0;
    }

    return array_sum($revenue);
}

function paymentRate($class, $session, $term, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT COUNT(*) AS total FROM registered_students WHERE graduation_status = 'Not Graduated' AND verification_status = 'Verified' AND current_class = '" . $class . "' AND campus_id = '" . $campusID . "'");
    $sqli = $mysqli->query("SELECT COUNT(DISTINCT(student_username)) AS count FROM transactions WHERE campus_id = '" . $campusID . "' AND session = '" . $session . "' AND term = '" . $term . "' AND transaction_type != 'Invoice' AND transaction_title = 'Tuition Fees' AND transaction_category = 'Student Payment' AND student_class = '" . $class . "' AND status = 'Completed'");
    $totalStudents = $sql->fetch_array()['total'];
    $paid = $sqli->fetch_array()['count'];
    $rate = $totalStudents == 0 ? 0 : ($paid / $totalStudents) * 100;

    return $rate;
}

function returnCampus($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sqli = $mysqli->query("SELECT campus FROM campuses WHERE id = '" . $campusID . "' LIMIT 1");
    $campus = "";

    if ($sqli->num_rows > 0) {
        $row = $sqli->fetch_array();
        $campus = $row['campus'];
    } else {
        $campus = "-";
    }

    return $campus;
}

function authenticateCampusDelete($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT id FROM (SELECT id FROM registered_students WHERE campus_id = '" . $campusID . "'  UNION ALL SELECT id FROM academic_staff WHERE campus_id = '" . $campusID . "' UNION ALL SELECT id FROM non_academicstaff WHERE campus_id = '" . $campusID . "' UNION ALL SELECT id FROM registered_subjects WHERE campus_id = '" . $campusID . "' UNION ALL SELECT id FROM scores WHERE campus_id = '" . $campusID . "' UNION ALL SELECT id FROM sessions_terms WHERE campus_id = '" . $campusID . "')X LIMIT 1");

    if ($sql->num_rows > 0) {
        return false;
    } else {
        return true;
    }
}

function monthlyBorrowing($month, $user_type, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $currentYear = date('Y');

    if ($user_type == "reg") {
        $sql = $mysqli->query("SELECT COUNT(*) AS count FROM borrowers WHERE FROM_UNIXTIME(borrowing_timestamp, '%Y-%m') = '" . $currentYear . "-" . $month . "' AND registered_user = 'Yes' AND campus_id = '" . $campusID . "'");
        $count = $sql->fetch_array();
        $borrowers = intval($count['count']);
        return $borrowers;
    } else {
        $sql = $mysqli->query("SELECT COUNT(*) AS count FROM borrowers WHERE FROM_UNIXTIME(borrowing_timestamp, '%Y-%m') = '" . $currentYear . "-" . $month . "' AND registered_user = 'No' AND campus_id = '" . $campusID . "'");
        $count = $sql->fetch_array();
        $borrowers = intval($count['count']);
        return $borrowers;
    }
}

function hostelCapacity($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT capacity FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");
    $hostel = $sql->fetch_array();
    $capacity = $hostel['capacity'];
    return $capacity;
}

function roomCapacity($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT room_capacity FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");
    $hostel = $sql->fetch_array();
    $capacity = $hostel['room_capacity'];
    return $capacity;
}

function rooms($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT no_of_rooms FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");
    $hostel = $sql->fetch_array();
    $rooms = $hostel['no_of_rooms'];
    return $rooms;
}

function hostelFee($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT fee FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");
    $hostel = $sql->fetch_array();
    $fee = $hostel['fee'];
    return $fee;
}

function returnHostel($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT name FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $hostel = $sql->fetch_array();
        $Hostel = $hostel['name'];
        return $Hostel;
    } else {
        return "-";
    }
}

function returnHostelType($hostelID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT type FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");
    $type = $sql->fetch_array()['type'];

    return $type;
}

function returnStaff($staffUsername)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT CONCAT(surname,' ',firstname,' ',middlename) AS name FROM non_academicstaff WHERE username = '" . $staffUsername . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $staff = $sql->fetch_array();
        $staffName = $staff['name'];
        return $staffName;
    } else {
        return "-";
    }
}

function returnQrcode($data)
{
    include "phpqrcode/qrlib.php";

    //set it to writable location, a place for temp generated PNG files
    $PNG_TEMP_DIR = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;

    //html PNG location prefix
    $PNG_WEB_DIR = 'temp/';
    //ofcourse we need rights to create temp dir
    if (!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);

    $filename = $PNG_TEMP_DIR . 'test.png';

    QRcode::png($data, $filename, 'H', 4, 1);

    return '<img src="../api/' . $PNG_WEB_DIR . basename($filename) . '" width="200" height="200" class="qrcode"/>';
}

function returnStudentName($studentUsername)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT CONCAT(surname,' ',firstname,' ',middlename) AS name FROM registered_students WHERE username = '" . $studentUsername . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $student = $sql->fetch_array();
        $studentName = $student['name'];
        return $studentName;
    } else {
        return "-";
    }
}

function returnStudentRegNo($studentUsername)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT reg_no FROM registered_students WHERE username = '" . $studentUsername . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $student = $sql->fetch_array();
        $regNo = $student['reg_no'];
        return $regNo;
    } else {
        return "-";
    }
}

function allotmentRate($hostelID, $session, $semester)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT capacity FROM hostels WHERE id = '" . $hostelID . "' LIMIT 1");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_array();
        $capacity = intval($row['capacity']);
        $sqli = $mysqli->query("SELECT COUNT(*) AS count FROM accommodations WHERE hostel_id = '" . $hostelID . "' AND session = '" . $session . "' AND semester = '" . $semester . "'");
        $record = $sqli->fetch_array();
        $count = $record['count'];

        $allotmentRate = round((($count / $capacity) * 100), 1);
        //$allotmentRate = ceil(($count / $capacity) * 100);

        return $allotmentRate;
    }
}

function levelRate($level)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE graduation_status = 'Not Graduated'");
    $row = $sql->fetch_array();
    $population = intval($row['count']);
    $sqli = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE current_level = '" . $level . "' AND graduation_status = 'Not Graduated'");
    $count = $sqli->fetch_array();
    $population1 = $count['count'];

    $levelRate = round((($population1 / $population) * 100), 1);
    //$allotmentRate = ceil(($count / $capacity) * 100);

    return $levelRate;
}

function campusLevelRate($class, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE graduation_status = 'Not Graduated' AND campus_id = '" . $campusID . "'");
    $row = $sql->fetch_array();
    $population = intval($row['count']);
    $sqli = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE current_class = '" . $class . "' AND graduation_status = 'Not Graduated' AND campus_id = '" . $campusID . "'");
    $count = $sqli->fetch_array();
    $population1 = $count['count'];

    if ($population != 0) {
        $classRate = round((($population1 / $population) * 100), 1);
    } else {
        $classRate = 0;
    }

    //$allotmentRate = ceil(($count / $capacity) * 100);
    return $classRate;
}

function campusLevelRate1($class)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE graduation_status = 'Not Graduated'");
    $row = $sql->fetch_array();
    $population = intval($row['count']);
    $sqli = $mysqli->query("SELECT COUNT(*) AS count FROM registered_students WHERE current_class = '" . $class . "' AND graduation_status = 'Not Graduated'");
    $count = $sqli->fetch_array();
    $population1 = $count['count'];

    if ($population != 0) {
        $classRate = round((($population1 / $population) * 100), 1);
    } else {
        $classRate = 0;
    }

    //$allotmentRate = ceil(($count / $capacity) * 100);
    return $classRate;
}


function authenticateSessionDelete($sessionID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $mysqli->query("SELECT session_term, campus_id FROM sessions_terms WHERE id = '" . $sessionID . "' LIMIT 1");

    if ($stmt->num_rows > 0) {
        $row = $stmt->fetch_array();
        $session_term = $row['session_term'];
        $campusID = $row['campus_id'];
        $term = substr($session_term, 10);
        $session = substr($session_term, 0, 9);
        $sql = $mysqli->query("SELECT id FROM (SELECT id FROM `accommodations` WHERE session = '" . $session . "' AND term = '" . $term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM `question_bank` WHERE session = '" . $session . "' AND term = '" . $term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM `registered_students` WHERE session_term_of_admission = '" . $session_term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM school_fees WHERE session = '" . $session . "' AND term = '" . $term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM `scores` WHERE session_term = '" . $session_term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM `students_attendance_register` WHERE session_term = '" . $session_term . "' AND campus_id = '" . $campusID . "' UNION ALL SELECT id FROM `students_daily_attendance` WHERE session_term = '" . $session_term . "' AND campus_id = '" . $campusID . "')X LIMIT 1");

        if ($sql->num_rows > 0) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function emailUsername($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fetch = $mysqli->query("SELECT email_username FROM campuses WHERE id = '" . $campusID . "' LIMIT 1");

    if ($fetch->num_rows > 0) {
        $row = $fetch->fetch_array();
        $emailUsername = strtolower($row['email_username']);

        return $emailUsername;
    } else {
        return "no-reply";
    }
}

function incoming_class($class)
{
    switch ($class) {
        case 'NUR2':
            return "NUR1";
            break;

        case 'NUR3':
            return "NUR2";
            break;

        case 'PRY1':
            return "NUR3";
            break;

        case 'PRY2':
            return "PRY1";
            break;

        case 'PRY3':
            return "PRY2";
            break;

        case 'PRY4':
            return "PRY3";
            break;

        case 'PRY5':
            return "PRY4";
            break;

        case 'PRY6':
            return "PRY5";
            break;

        case 'JSS1':
            return "PRY6";
            break;

        case 'JSS2':
            return "JSS1";
            break;

        case 'JSS3':
            return "JSS2";
            break;

        case 'SS1':
            return "JSS3";
            break;

        case 'SS2':
            return "SS1";
            break;

        case 'SS3':
            return "SS2";
            break;

        default:
            return "None";
            break;
    }
}

function returnSection($class)
{
    if ($class == "NUR1" || $class == "NUR2" || $class == "NUR3") {
        return "Nursery";
    } elseif ($class == "PRY1" || $class == "PRY2" || $class == "PRY3" || $class == "PRY4" || $class == "PRY5" || $class == "PRY6") {
        return "Primary";
    } elseif ($class == "JSS1" || $class == "JSS2" || $class == "JSS3") {
        return "Junior";
    } elseif ($class == "SS1" || $class == "SS2" || $class == "SS3") {
        return "Senior";
    } else {
        return "-";
    }
}

function availableAccommodations($hostelID, $session, $term, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $sql = $mysqli->query("SELECT COUNT(*) AS count FROM accommodations WHERE hostel_id = '" . $hostelID . "' AND session = '" . $session . "' AND term = '" . $term . "' AND campus_id = '" . $campusID . "'");
    $data = $sql->fetch_array();
    $placeHolder = ($data['count'] == 0) ? 'None' : $data['count'];

    return $placeHolder;
}

function eligibilityCheck($username, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $error = 0;

    //check accounts records
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS count FROM transactions WHERE student_username = ? AND campus_id = ?");
    $stmt->bind_param('si', $username, $campusID);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count > 0) {
        $error++;
    }

    $stmt->close();

    //check debts
    $stmt = $mysqli->prepare("SELECT SUM(expected_amount) AS expected, SUM(amount) AS paid FROM transactions WHERE transaction_category = 'Student Payment' AND student_username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $debt = intval($record['expected']) - intval($record['paid']);

    if ($debt > 0) {
        $error++;
    }

    $stmt->close();

    //check library records
    $stmt = $mysqli->prepare("SELECT COUNT(*) AS count FROM borrowers WHERE username = ? AND campus_id = ? AND status = 'Unreturned'");
    $stmt->bind_param('si', $username, $campusID);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc()['count'];

    if ($data > 0) {
        $error++;
    }

    $stmt->close();

    if ($error > 0) {
        return false;
    } else {
        return true;
    }
}

function accountStatus($campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $stmt = $mysqli->prepare("SELECT account_status FROM campuses WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $campusID);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_array()['account_status'];
}

function fetchStudentTotalDebt($studentUsername, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $sessionsTerms = array();
    $feesArray = array('Tuition Fees');
    $debtArray = array();

    $stmt = $mysqli->prepare("SELECT session_term FROM sessions_terms WHERE campus_id = ?");
    $stmt->bind_param('i', $campusID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($st = $result->fetch_assoc()) {
            $sessionsTerms[] = $st['session_term'];
        }

        $stmt->close();

        $stmt = $mysqli->prepare("SELECT fee FROM other_fees WHERE campus_id = ?");
        $stmt->bind_param('i', $campusID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($otherFee = $result->fetch_assoc()) {
                array_push($feesArray, $otherFee['fee']);
            }
        }

        $stmt->close();

        foreach ($sessionsTerms as $sessionTerm) {
            $session = substr($sessionTerm, 0, 9);
            $term = substr($sessionTerm, 10);

            foreach ($feesArray as $fee) {
                $stmt = $mysqli->prepare("SELECT balance FROM transactions WHERE campus_id = ? AND session = ? AND term = ? AND  transaction_title = ? AND transaction_category = 'Student Payment' AND student_username = ? ORDER BY id DESC LIMIT 1");
                $stmt->bind_param('issss', $campusID, $session, $term, $fee, $studentUsername);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $balance = intval($result->fetch_assoc()['balance']);

                    $debtArray[] = $balance;
                }
            }
        }

        $totalDebt = array_sum($debtArray);

        return $totalDebt;
    } else {
        return '-';
    }
}

function fetchStudentTotalPayment($studentUsername, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $stmt = $mysqli->prepare("SELECT SUM(amount) AS total FROM transactions WHERE campus_id = ? AND transaction_category = 'Student Payment' AND student_username = ?");
    $stmt->bind_param('is', $campusID, $studentUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $totalAmountPaid = intval($result->fetch_assoc()['total']);

    return $totalAmountPaid;
}

function fetchStudentTotalExpectedPayment($studentUsername, $campusID)
{
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    $sessionsTerms = array();
    $feesArray = array('Tuition Fees');
    $expectedArray = array();

    $stmt = $mysqli->prepare("SELECT session_term FROM sessions_terms WHERE campus_id = ?");
    $stmt->bind_param('i', $campusID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($st = $result->fetch_assoc()) {
            $sessionsTerms[] = $st['session_term'];
        }

        $stmt->close();

        $stmt = $mysqli->prepare("SELECT fee FROM other_fees WHERE campus_id = ?");
        $stmt->bind_param('i', $campusID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($otherFee = $result->fetch_assoc()) {
                array_push($feesArray, $otherFee['fee']);
            }
        }

        $stmt->close();

        foreach ($sessionsTerms as $sessionTerm) {
            $session = substr($sessionTerm, 0, 9);
            $term = substr($sessionTerm, 10);

            foreach ($feesArray as $fee) {
                $stmt = $mysqli->prepare("SELECT expected_amount FROM transactions WHERE campus_id = ? AND session = ? AND term = ? AND  transaction_title = ? AND transaction_category = 'Student Payment' AND student_username = ? ORDER BY id LIMIT 1");
                $stmt->bind_param('issss', $campusID, $session, $term, $fee, $studentUsername);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $expectedAmount = intval($result->fetch_assoc()['expected_amount']);

                    $expectedArray[] = $expectedAmount;
                }
            }
        }

        $totalExpectedPayment = array_sum($expectedArray);

        return $totalExpectedPayment;
    } else {
        return '-';
    }
}

function sendMail($to, $message, $subject, $attachments = [])
{
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USER;  // Replace with your email
        $mail->Password   = GMAIL_PASSWORD;   // Use an App Password, NOT your actual password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // Use SSL
        $mail->Port       = 465;

        // Sender & Recipient
        $mail->setFrom('no-reply@noreply.com', 'No Reply');
        $mail->addAddress($to);

        // Attachments (if any)
        if (!empty($attachments)) {
            foreach ($attachments as $filePath) {
                $mail->addAttachment($filePath);
            }
        }

        // Email Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message); // Plain text fallback
        /* $mail->Debugoutput = 'html'; // Debug output in HTML
        $mail->SMTPDebug = 4; // Enable debugging (1 = errors, 2 = full output) */
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        // Send Email
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Mailer Error: " . $mail->ErrorInfo;
    }
}
