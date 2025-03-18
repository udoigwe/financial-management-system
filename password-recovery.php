<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include('includes/head.php'); ?>
    <title>Finovate | Password Recovery</title>

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
                                    <h4 class="text-center mb-4 mt-4">Reset Password</h4>
                                    <form action="#" id="password-reset-form">
                                        <div class="form-group">
                                            <label class="mb-1"><strong>New Password</strong></label>
                                            <input type="password" class="form-control required" id="new-pass" name="new_pass">
                                        </div>
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Confirm Password</strong></label>
                                            <input type="password" class="form-control required" id="re-pass" name="re_pass">
                                        </div>
                                        <input type="hidden" id="email" name="email" class="required" />
                                        <input type="hidden" id="salt" name="salt" class="required" />
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>I have my password? <a class="text-primary" href="index">Sign In</a></p>
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
    <script src="js/pages/password-recovery.js"></script>

</body>

</html>