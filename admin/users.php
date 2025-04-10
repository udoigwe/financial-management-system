<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Users | Transactions</title>
</head>

<body onload="isAuthenticated(); displayProfile(); loadUnreadMessages(); generateAccountStatement(); print1();">

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
        <?php include('../includes/admin-sidebar.php'); ?>
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
                        <h2 class="text-black mb-0 font-w700">Users</h2>
                        <p class="mb-0">Create & update, read and remove users</p>
                    </div>
                </div>
                <div class="col-xl-12 col-lg-12 col-xxl-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">New User</h4>
                        </div>
                        <div class="card-body">
                            <div class="settings-form">
                                <form id="new-user-form">
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
                                        <div class="form-group col-md-4">
                                            <label>DOB</label>
                                            <input type="date" placeholder="Date of Birth" class="form-control required dob" name="dob">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Gender</label>
                                            <select class="form-control required gender default-select" id="gender" name="gender">
                                                <option value="">Please select</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Role</label>
                                            <select class="form-control required role default-select" id="role" name="role">
                                                <option value="">Please select</option>
                                                <option value="Admin">Admin</option>
                                                <option value="Account Officer">Account Officer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Means Of Identification</label>
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
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Password</label>
                                            <input type="password" placeholder="Temporary password" class="form-control required password" name="password">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Confirm Password</label>
                                            <input type="password" placeholder="Confirm Password" class="form-control required re-password" name="re-pass">
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Create User</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12 col-lg-12 col-xxl-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Existing Users</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive recentorderTable">
                                <table class="table verticle-middle table-striped table-responsive-md existing-users" id="existing-users">
                                    <thead>
                                        <tr>
                                            <th scope="col">SNO</th>
                                            <th scope="col">First Name</th>
                                            <th scope="col">Last Name</th>
                                            <th scope="col">Gender</th>
                                            <th scope="col">DOB</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>
                                            <th scope="col">Identification</th>
                                            <th scope="col">Identification Number</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Last Seen</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th scope="col">SNO</th>
                                            <th scope="col">First Name</th>
                                            <th scope="col">Last Name</th>
                                            <th scope="col">Gender</th>
                                            <th scope="col">DOB</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>
                                            <th scope="col">Identification</th>
                                            <th scope="col">Identification Number</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Last Seen</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="editUserModal">
                    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Budget Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal">
                                </button>
                            </div>
                            <div class="modal-body">
                                <form id="update-user-form">
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
                                            <input type="date" placeholder="Date of Birth" class="form-control required dob" name="dob">
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
                                        <div class="form-group col-md-4">
                                            <label>Means Of Identification</label>
                                            <select class="form-control required identification default-select" id="identification" name="identification">
                                                <option value="">Please select</option>
                                                <option value="Drivers License">Drivers License</option>
                                                <option value="SSN">SSN</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Identification Number</label>
                                            <input type="text" placeholder="Identification number" class="form-control required identification_number" name="identification_number">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Account Status</label>
                                            <select class="form-control required account_status default-select" id="account_status" name="account_status">
                                                <option value="">Please select</option>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" class="required user_id" name="user_id" />
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update Category</button>
                            </div>
                            </form>
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

        <!--**********************************
            Global modals
        ***********************************-->
        <?php include('../includes/globalModals.php'); ?>
        <!--**********************************
            Global modals
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
    <script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="../js/pages/admin/users.js"></script>


</body>

</html>