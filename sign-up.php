<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include('includes/head.php'); ?>
    <title>FinHive | Sign Up</title>

    <style type="text/css">
        body {
            height: 100%;
            overflow-y: hidden;
        }

        /* Hide the scrollbar by default */
        .scroll-container {
            scrollbar-width: none;
            /* For Firefox */
            transition: scrollbar-width 0.7s ease-in-out, width 0.7s ease-in-out;
            /* Add transition */
        }

        .scroll-container::-webkit-scrollbar {
            width: 0px;
            /* For Chrome, Safari, and Edge */
            transition: width 0.7s ease-in-out;
            /* Add transition */
        }

        /* Show scrollbar on hover */
        .scroll-container:hover {
            scrollbar-width: thin;
            /* For Firefox */
        }

        .scroll-container:hover::-webkit-scrollbar {
            width: 4px;
            /* Adjust width as needed */
        }

        /* Optional: Customize scrollbar appearance */
        .scroll-container:hover::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.158);
            /* Change color as needed */
            border-radius: 20px;
        }
    </style>

</head>

<body class="vh-100">
    <div class="authincation h-100" style="background: url('./images/landing.jpg') no-repeat; background-size: cover; background-position: center;">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center scroll-container" style="overflow-y: auto;">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
                                    <div class="text-center mb-3">
                                        <img src="images/finhive-logo.png" width="150" alt="">
                                    </div>
                                    <h4 class="text-center mb-4">Create Account</h4>
                                    <form action="#" id="sign-up-form">
                                        <div class="form-group">
                                            <label class="mb-1"><strong>First Name</strong></label>
                                            <input type="text" class="form-control required" placeholder="First name" id="first_name" name="first_name">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Last Name</strong></label>
                                            <input type="text" class="form-control required" placeholder="Last name" id="last_name" name="last_name">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Email</strong></label>
                                            <input type="email" class="form-control required" placeholder="Valid email address" id="email" name="email">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Phone Number</strong></label>
                                            <input type="text" class="form-control required" placeholder="Contry codes apply" id="phone" name="phone">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Contact Address</strong></label>
                                            <input type="text" class="form-control required" placeholder="Address" id="address" name="address">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Date Of Birth</strong></label>
                                            <input type="date" class="form-control required" placeholder="YYYY-MM-DD" id="dob" name="dob">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Gender</strong></label>
                                            <select class="form-control required" id="gender" name="gender">
                                                <option value="">Please Select</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Identification</strong></label>
                                            <select class="form-control required" id="identification" name="identification">
                                                <option value="">Please Select</option>
                                                <option value="Drivers License">Drivers Licence</option>
                                                <option value="SSN">SSN</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Identification Number</strong></label>
                                            <input type="text" class="form-control required" placeholder="Identification Number" id="identification_number" name="identification_number">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Password</strong></label>
                                            <input type="password" class="form-control required" id="password" name="password">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Confirm Password</strong></label>
                                            <input type="password" class="form-control required" id="confirm-password" name="confirm-password">
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>Have an account? <a class="text-primary" href="index">Sign In</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <?php include('includes/scripts.php'); ?>
    <script src="js/pages/auth.js"></script>

</body>

</html>