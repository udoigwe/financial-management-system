<?php
function accountVerificationHTML($recipient, $salt, $account)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Recover Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            Verify your email address
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Congratulations, <b>' . $account['first_name'] . '</b>!!! Your account has been created successfully with details as follows: <br/><br/>
                            Account Number: <b>' . $account['account_id'] . '</b><br/>
                            Account Name: <b>' . $account['first_name'] . ' ' . $account['last_name'] . '</b><br/>
                            Account Type: <b>' . $account['account_type'] . '</b><br/>
                            Account PIN: <b>' . $account['pin'] . '</b><br/>
                            Account Officer: <b>' . $account['account_officer_first_name'] . ' ' . $account['account_officer_last_name'] . '</b><br/>
                            Account Officer Phone Number: <b>' . $account['account_officer_phone'] . '</b><br/>
                            Account Officer Email: <b>' . $account['account_officer_email'] . '</b><br/>
                            Current Account Balance: <b>$' . $account['balance'] . '</b><br/><br/>
                            For security purposes, please ensure your account transaction pin is changed immediately you login.
                            It might also interest you to know that we have automatically created a <b>SAVINGS</b> budget category for you as this is required before any funds transfer happens. We want to help you achieve financial success with this approach. <br/><br/>
                            You’ve entered <b>' . $recipient . '</b> as the email address for your account. Please verify email address by clicking the link below
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 40px;">
                        <!-- Start Button -->
                        <table width="170" cellpadding="0" cellspacing="0" align="center" border="0"> 
                            <tr>
                                <td width="170" height="46" bgcolor="#10a7e8" align="center" valign="middle" style="font-size: 16px; color: #ffffff; line-height:18px; -webkit-border-radius: 50px; -moz-border-radius: 50px; border-radius: 50px; font-weight:bold;" mc:label="the_btnText" mc:edit="the_btnText">
                                    <a href="' . BASE_URL . '/account-verification?email=' . $recipient . '&salt=' . $salt . '" target="_blank" alias="" style="text-decoration: none; color: #ffffff;">Verify Account <span style="font-size:23px;">&rsaquo;</span></a>
                                </td>
                            </tr>
                        </table>
                        <!-- End Button --> 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function resetPasswordLink($recipient, $salt)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Recover Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            Reset Your Password
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            You’ve entered <b>' . $recipient . '</b> as a password recovery email address. Please click the link below to change your password.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 40px;">
                        <!-- Start Button -->
                        <table width="170" cellpadding="0" cellspacing="0" align="center" border="0"> 
                            <tr>
                                <td width="170" height="46" bgcolor="#10a7e8" align="center" valign="middle" style="font-size: 16px; color: #ffffff; line-height:18px; -webkit-border-radius: 50px; -moz-border-radius: 50px; border-radius: 50px; font-weight:bold;" mc:label="the_btnText" mc:edit="the_btnText">
                                    <a href="' . BASE_URL . '/password-recovery?email=' . $recipient . '&salt=' . $salt . '" target="_blank" alias="" style="text-decoration: none; color: #ffffff;">Reset Password <span style="font-size:23px;">&rsaquo;</span></a>
                                </td>
                            </tr>
                        </table>
                        <!-- End Button --> 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function supportHTML($emailMeta, $subject, $message)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Support Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            Support & Enquiries
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Hello, <b>' . $emailMeta['account_officer_first_name'] . '</b>!!! An account holder under you has requested for support. Below are details of his enquiries: <br/><br/>
                            Account Number: <b>' . $emailMeta['account_number'] . '</b><br/>
                            Account Name: <b>' . $emailMeta['first_name'] . ' ' . $emailMeta['last_name'] . '</b><br/>
                            Account Type: <b>' . $emailMeta['account_type'] . '</b><br/><br/>
                            Phone Number: <b>' . $emailMeta['phone'] . '</b><br/><br/>
                            Email Address: <b>' . $emailMeta['email'] . '</b><br/><br/>
                            Message Subject: <b>' . $subject . '</b><br/>
                            Message:<br/> <b>' . $message . '</b><br/><br/><br/>
                            Please endevor to reach out to the customer within 2 working days. The customer can be reached via <b>' . $emailMeta['email'] . '</b> OR <b>' . $emailMeta['phone'] . '</b>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function otpHTML($emailMeta, $subject)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Support Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            ' . $subject . '
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Hello, <b>' . $emailMeta['first_name'] . '</b>!!! We have noticed an attempt to transfer funds of <b>$' . $emailMeta['amount'] . '</b> from your account to <b>' . $emailMeta['destination_account_number'] . '</b>. This funds transfer will trigger an exceedance in the selected budget category.<br/><br/>
                            Please enter the OTP below to proceed with this transaction: <br/><b>' . $emailMeta['otp'] . '</b><br/>
                            Please do not share this OTP with anyone.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function debitHTML($emailMeta, $subject)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Debit Alert Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            ' . $subject . '
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Hello, <b>' . $emailMeta['first_name'] . '</b>!!! <b>$' . $emailMeta['amount'] . '</b> has been debited from your <b>' . $emailMeta['source'] . '</b>. Your current balance is <b>' . $emailMeta['originating_balance'] . '</b>.<br/><br/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function creditHTML($emailMeta, $subject)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Debit Alert Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            ' . $subject . '
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Hello, <b>' . $emailMeta['first_name'] . '</b>!!! <b>$' . $emailMeta['amount'] . '</b> has been credited to your <b>' . $emailMeta['destination'] . '</b>. Your current <b>' . $emailMeta['destination'] . '</b> balance is <b>' . $emailMeta['destination_balance'] . '</b>.<br/><br/>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function accountCreationNotificationHTML($emailMeta)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Account Creation Notification Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            Account Creation Notification
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            Hello, <b>' . $emailMeta['first_name'] . '</b>!!! We are pleased to inform you that an account has been created for you as an <b>' . $emailMeta['role'] . '</b> in <b>FinHive</b>. Please log in with the details below.<br/><br/>
                            Email: <b>' . $emailMeta['email'] . '</b><br/>
                            Password: <b>' . $emailMeta['password'] . '</b><br/>
                            Please endevor to change the default password immediately. Click the button below to be redirected to <b>FinHive</b>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 40px;">
                        <!-- Start Button -->
                        <table width="170" cellpadding="0" cellspacing="0" align="center" border="0"> 
                            <tr>
                                <td width="170" height="46" bgcolor="#10a7e8" align="center" valign="middle" style="font-size: 16px; color: #ffffff; line-height:18px; -webkit-border-radius: 50px; -moz-border-radius: 50px; border-radius: 50px; font-weight:bold;" mc:label="the_btnText" mc:edit="the_btnText">
                                    <a href="' . BASE_URL . '/" target="_blank" alias="" style="text-decoration: none; color: #ffffff;">Visit FinHive <span style="font-size:23px;">&rsaquo;</span></a>
                                </td>
                            </tr>
                        </table>
                        <!-- End Button --> 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}

function supportResponseHTML($subject, $message)
{
    $emailTemplate = '
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" sizes="16x16" href="http://3.211.15.234:6001/assets/images/engis-logo1.png">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
            <title>Support Response Email</title>
            <style>
                body {
                    margin:0; 
                    padding:0; 
                    font-family: "Poppins", sans-serif; 
                    display: flex; 
                    justify-content: 
                    center; align-items: 
                    center; 
                    height: 100vh;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                td {
                    border: 0 solid #ddd;
                    padding: 8px;
                }
                header, footer {
                    text-align: center;
                }
        
                .otp {
                    font-weight: 400; 
                    font-size: 34px; 
                    line-height: 51px; 
                    letter-spacing: 0.25%; 
                    color: #00B780; 
                    box-shadow: -3px -3px 7px 0 rgba(144, 238, 216, 0.8); 
                    border-radius: 10px; 
                    background-color: white; 
                    text-align: center; 
                    vertical-align: middle;  
                    width: 50px; 
                    height: 50px;
                }
        
                @media print {
                    body {
                        zoom: 0.67;
                    }
                }
            </style>
        </head>
        <body style="">
            <table style="width: 500px; margin-left: auto; margin-right: auto;">
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 100px;">
                        <span style="font-size: 22px; display: inline-block; font-weight: 900; border: 1px solid grey; padding: 6px; border-radius:5px; width: auto; margin-top: 10px;">
                            ' . APP_NAME . '
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 30px; letter-spacing: 1px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; line-height: 30px;">
                            ' . $subject . '
                        </h3>
                        <p style="font-weight: 400; font-size: 12px; font-style: normal; padding-top: 30px; line-height: 18px;">
                            ' . $message . '
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 45px; line-height: 18px; letter-spacing: 1px;">
                        <p style="font-weight: 400; font-size: 10px; font-style: normal; margin-bottom: 10px !important; text-transform: uppercase;">
                            FOR MORE INFORMATION, CLICK BELOW
                        </p>
                        <a href="https://www.finhive.com/" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            https://www.finhive.com/
                        </a>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: center; padding-top: 70px; line-height: 30px; letter-spacing: 0.15px;">
                        <h3 style="font-weight: 600; font-size: 20px; font-style: normal; margin-bottom: 5px !important; text-decoration: none;">
                            Need Help?
                        </h3>
                        <h5 style="font-weight: 400; font-size: 12px; font-style: normal; margin-top: 0 !important; margin-bottom: 5px !important; text-decoration: none;">
                            Please send feedback or bug info to
                        </h5>
                        <a href="mailto:info@finhive.com" style="font-weight: 400; font-size: 12px; font-style: normal; text-decoration: none;">
                            info@finhive.com
                        </a>
                    </td>
                </tr>
            </table>
        </body>
        </html>
    ';

    return $emailTemplate;
}
