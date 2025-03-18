<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include('includes/head.php'); ?>
    <title>Finovate | Forgot Password</title>

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
                                    <h4 class="text-center mb-4 mt-4">Forgot Password</h4>
                                    <form action="#" id="recovery-form">
                                        <div class="form-group">
                                            <label class="mb-1"><strong>Email</strong></label>
                                            <input type="email" class="form-control required" id="email" name="email" placeholder="Valid email address">
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-primary btn-block">Send Recovery Email</button>
                                        </div>
                                    </form>
                                    <div class="new-account mt-3">
                                        <p>I have an account? <a class="text-primary" href="index">Sign In</a></p>
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