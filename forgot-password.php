<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php include('includes/head.php'); ?>
    <title>FinHive | Forgot Password</title>

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
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
                                    <div class="text-center mb-3">
                                        <img src="images/finhive-logo.png" width="150" alt="">
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