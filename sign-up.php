<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include('includes/head.php'); ?>
    <title>FinHive | Sign Up</title>

</head>

<body class="vh-100">
    <div class="authincation h-100" style="background: url('./images/landing.jpg') no-repeat; background-size: cover; background-position: center;">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
                                    <div class="text-center mb-3">
                                        <img src="images/finovate-logo.png" width="150" height="150" alt="">
                                    </div>
                                    <h4 class="text-center mb-4">Create Account</h4>
                                    <form action="#" id="sign-up-form">
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Full Name</strong></label>
                                            <input type="text" class="form-control required" placeholder="Surname first" id="name" name="name">
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