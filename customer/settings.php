<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Customer | Profile & Settings</title>
</head>

<body onload="isAuthenticated(); displayProfile(); loadUnreadMessages();">

    <!--*******************
        Preloader start
    ********************-->
    <?php include('../includes/preloader.php'); ?>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
        <?php include('../includes/nav-header.php'); ?>
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Chat box start
        ***********************************-->
        <?php include('../includes/chat-box.php'); ?>
        <!--**********************************
            Chat box End
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
        <?php include('../includes/header.php'); ?>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <?php include('../includes/customer-sidebar.php'); ?>
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <div class="container-fluid">
                <div class="form-head d-flex flex-wrap mb-sm-4 mb-3 align-items-center">
                    <div class="me-auto  d-lg-block mb-3">
                        <h2 class="text-black mb-0 font-w700">Settings & Security</h2>
                        <p class="mb-0">Access your profile & update account settings</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="profile card card-body px-3 pt-3 pb-0">
                            <div class="profile-head">
                                <div class="photo-content">
                                    <div class="cover-photo"></div>
                                </div>
                                <div class="profile-info">
                                    <div class="profile-photo">
                                        <img src="../images/avatar.png" class="img-fluid rounded-circle" alt="">
                                    </div>
                                    <div class="profile-details">
                                        <div class="profile-name px-3 pt-2">
                                            <h4 class="text-primary mb-0 logged-user-name">Mitchell C. Shay</h4>
                                            <p class="logged-user-role">UX / UI Designer</p>
                                        </div>
                                        <div class="profile-email px-2 pt-2">
                                            <h4 class="text-muted mb-0 logged-user-email">info@example.com</h4>
                                            <p>Email</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card h-auto">
                            <div class="card-body">
                                <div class="profile-tab">
                                    <div class="custom-tab-1">
                                        <ul class="nav nav-tabs" role="tablist">
                                            <li class="nav-item" role="presentation"><a href="#about-me" data-bs-toggle="tab" class="nav-link active show" aria-selected="true" role="tab">About Me</a>
                                            </li>
                                            <li class="nav-item" role="presentation"><a href="#profile-settings" data-bs-toggle="tab" class="nav-link" aria-selected="false" tabindex="-1" role="tab">Account Settings</a>
                                            </li>
                                            <li class="nav-item" role="presentation"><a href="#password-settings" data-bs-toggle="tab" class="nav-link" aria-selected="false" tabindex="-1" role="tab">Password Settings</a>
                                            </li>
                                            <li class="nav-item" role="presentation"><a href="#pin-settings" data-bs-toggle="tab" class="nav-link" aria-selected="false" tabindex="-1" role="tab">Transaction PIN Settings</a>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div id="about-me" class="tab-pane fade active show" role="tabpanel">
                                                <!-- <div class="profile-about-me">
                                                    <div class="pt-4 border-bottom-1 pb-3">
                                                        <h4 class="text-primary">About Me</h4>
                                                        <p class="mb-2">A wonderful serenity has taken possession of my entire soul, like these sweet mornings of spring which I enjoy with my whole heart. I am alone, and feel the charm of existence was created for the bliss of souls like mine.I am so happy, my dear friend, so absorbed in the exquisite sense of mere tranquil existence, that I neglect my talents.</p>
                                                        <p>A collection of textile samples lay spread out on the table - Samsa was a travelling salesman - and above it there hung a picture that he had recently cut out of an illustrated magazine and housed in a nice, gilded frame.</p>
                                                    </div>
                                                </div>
                                                <div class="profile-skills mb-5">
                                                    <h4 class="text-primary mb-2">Skills</h4>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Admin</a>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Dashboard</a>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Photoshop</a>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Bootstrap</a>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Responsive</a>
                                                    <a href="javascript:void(0);" class="btn btn-primary light btn-xs mb-1">Crypto</a>
                                                </div>
                                                <div class="profile-lang  mb-5">
                                                    <h4 class="text-primary mb-2">Language</h4>
                                                    <a href="javascript:void(0);" class="text-muted pe-3 f-s-16"><i class="flag-icon flag-icon-us"></i> English</a>
                                                    <a href="javascript:void(0);" class="text-muted pe-3 f-s-16"><i class="flag-icon flag-icon-fr"></i> French</a>
                                                    <a href="javascript:void(0);" class="text-muted pe-3 f-s-16"><i class="flag-icon flag-icon-bd"></i> Bangla</a>
                                                </div> -->
                                                <div class="profile-personal-info mt-5">
                                                    <h4 class="text-primary mb-4">Personal Information</h4>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Name <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-name"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Email <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-email"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Phone Number <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-phone"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Gender <span class="pull-right">:</span></h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-gender"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Date Of Birth <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-dob"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-4">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Address <span class="pull-right">:</span></h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-address"><span></span>
                                                        </div>
                                                    </div>
                                                    <h4 class="text-primary mb-4">Bank Account Information</h4>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Account Number <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-account-number"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Account Type <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-account-type"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Account Officer <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-account-officer"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Account Officer Phone Number <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-account-officer-phone-number"><span></span>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-sm-3 col-5">
                                                            <h5 class="f-w-500">Account Officer Email <span class="pull-right">:</span>
                                                            </h5>
                                                        </div>
                                                        <div class="col-sm-9 col-7 logged-user-account-officer-email"><span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="profile-settings" class="tab-pane fade" role="tabpanel">
                                                <div class="pt-3">
                                                    <div class="settings-form">
                                                        <h4 class="text-primary">Account Settings</h4>
                                                        <form id="account-update-form">
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label>First Name</label>
                                                                    <input type="text" placeholder="First name" class="form-control required first_name" name="first_name">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label>Last Name</label>
                                                                    <input type="text" placeholder="Last name" class="form-control required last_name" name="last_name">
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label>Email</label>
                                                                    <input type="email" placeholder="Valid email address" class="form-control required email" name="email">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label>Phone Number</label>
                                                                    <input type="text" placeholder="International codes apply" class="form-control required phone" name="phone">
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Address</label>
                                                                <input type="text" placeholder="1234 Main St" class="form-control required address" name="address">
                                                            </div>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label>DOB</label>
                                                                    <input type="date" placeholder="Email" class="form-control required dob" name="dob">
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label>Gender</label>
                                                                    <select class="form-control required gender default-select" id="gender" name="gender">
                                                                        <option value="">Please select</option>
                                                                        <option value="Male">Male</option>
                                                                        <option value="Female">Female</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="form-group col-md-6">
                                                                    <label>Identification</label>
                                                                    <select class="form-control required identification default-select" id="identification" name="identification">
                                                                        <option value="">Please select</option>
                                                                        <option value="Drivers License">Drivers License</option>
                                                                        <option value="SSN">SSN</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group col-md-6">
                                                                    <label>Identification Number</label>
                                                                    <input type="text" placeholder="Identification number" class="form-control required identification_number" name="identification_number">
                                                                </div>
                                                            </div>
                                                            <input type="hidden" class="required user_id" name="user_id" />
                                                            <button class="btn btn-primary" type="submit">Update Account</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="password-settings" class="tab-pane fade" role="tabpanel">
                                                <div class="pt-3">
                                                    <div class="settings-form">
                                                        <h4 class="text-primary">Password Setting</h4>
                                                        <form id="password-update-form">
                                                            <div class="form-group">
                                                                <label>Current Password</label>
                                                                <input type="password" class="form-control required current_password" name="current_password">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>New Password</label>
                                                                <input type="password" class="form-control required new_password" name="new_password">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Confirm Password</label>
                                                                <input type="password" class="form-control required confirm_password" name="confirm_password">
                                                            </div>
                                                            <button class="btn btn-primary" type="submit">Update Password</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="pin-settings" class="tab-pane fade" role="tabpanel">
                                                <div class="pt-3">
                                                    <div class="settings-form">
                                                        <h4 class="text-primary">Transaction PIN Settings</h4>
                                                        <form id="pin-update-form">
                                                            <div class="form-group">
                                                                <label>Current Transaction PIN</label>
                                                                <input type="password" class="form-control required current_pin" name="current_pin">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>New Transaction PIN</label>
                                                                <input type="password" class="form-control required new_pin" name="new_pin">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Confirm Transaction PIN</label>
                                                                <input type="password" class="form-control required confirm_pin" name="confirm_pin">
                                                            </div>
                                                            <button class="btn btn-primary" type="submit">Update Transaction PIN</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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
            Content body end
        ***********************************-->

        <!--**********************************
            Footer start
        ***********************************-->
        <?php include('../includes/footer.php'); ?>
        <!--**********************************
            Footer end
        ***********************************-->

        <!--**********************************
           Support ticket button start
        ***********************************-->

        <!--**********************************
           Support ticket button end
        ***********************************-->

        <!--**********************************
           Unread message modal start
        ***********************************-->

        <?php include('../includes/unread-message-modal.php'); ?>

        <!--**********************************
           Unread message modal end
        ***********************************-->

    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <?php include('../includes/scripts2.php'); ?>
    <script src="../js/pages/customer/settings.js"></script>


</body>

</html>