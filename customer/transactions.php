<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Customer | Transactions</title>

    <style type="text/css">
        .account-verification-box-success {
            display:flex; 
            justify-content:center; 
            align-items:center; 
            width:100%; 
            height:60px; 
            background-color:#1FD9511F; 
            border-radius:15px; 
            font-style:italic
        }
        .account-verification-box-error {
            display:flex; 
            justify-content:center; 
            align-items:center; 
            width:100%; 
            height:60px; 
            background-color:#FB23231F; 
            border-radius:15px; 
            font-style:italic
        }
        .account-verification-box-default {
            display:flex; 
            justify-content:center; 
            align-items:center; 
            width:100%; 
            height:60px; 
            background-color:#E7E8EC; 
            border-radius:15px; 
            font-style:italic
        }

        .account-verification-box-hidden {
            display:none; 
        }
    </style>

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
                        <h2 class="text-black mb-0 font-w700">Transactions</h2>
                        <p class="mb-0">Seamless, Secure, and Swift Transactions!!!</p>
                    </div>
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#transactionModal" class="btn btn-primary btn-rounded mb-3"><i class="fa fa-user-plus me-3"></i>New Transaction</a>
                    <!-- Add Category -->
                    <div class="modal fade" id="transactionModal">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">New Transaction</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="new-transaction-form">
                                        <div class="form-group">
                                            <label class="text-black font-w500">Account Number</label>
                                            <input type="number" class="form-control destination_account_number required" placeholder="Numeric values only" name="destination_account_number">
                                        </div>
                                        <div class="form-group account-verification-box-default" id="account-verification-box">
                                            ...verifying account
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Amount</label>
                                            <input type="number" class="form-control amount required" placeholder="Numeric values only" name="amount">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Choose Source</label>
                                            <select class="form-control required source default-select" id="source" name="source">
                                                <option value="">Please select</option>
                                                <option value="Main Account">Main Account</option>
                                                <option value="Safe Lock">Safe Lock</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Choose Budget Category</label>
                                            <select class="form-control required budget_category_id default-select" id="budget_category_id" name="budget_category_id">
                                                <option value="">Please select</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Transaction PIN</label>
                                            <input type="number" class="form-control pin required" placeholder="Numeric values only" name="pin">
                                        </div>
                                        <div class="form-group" id="otpSection" style="display:none">
                                            <label class="text-black font-w500">OTP</label>
                                            <input type="number" class="form-control otp" placeholder="Numeric values only" name="otp">
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Transfer Funds</button>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="editCategoryModal">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Budget Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="edit-budget-category-form">
                                        <div class="form-group" id="newCategoryWrapper">
                                            <label class="text-black font-w500">Category Name</label>
                                            <input type="text" class="form-control category_name required" placeholder="Unique category name" name="category_name">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Budget Limit</label>
                                            <input type="number" class="form-control budget_limit required" placeholder="0.0" name="budget_limit">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Budget Limit Start Time</label>
                                            <input type="datetime-local" class="form-control required budget_limit_start_time datetime" placeholder="Saturday 24 June 2017 - 21:44" name="budget_limit_start_time">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Budget Limit End Time</label>
                                            <input type="datetime-local" class="form-control required budget_limit_end_time datetime" placeholder="Saturday 24 June 2017 - 21:44" name="budget_limit_end_time">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Budget Category Color Code</label>
                                            <input type="color" class="form-control required color_code" placeholder="#ffffff" name="color_code" value="#ffffff">
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Category Status</label>
                                            <select class="form-control required default-select" id="budget_category_status" name="budget_category_status">
                                                <option value="">Please select</option>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Description</label>
                                            <textarea class="form-control required description" placeholder="Brief description" name="description" rows="5"></textarea>
                                        </div>
                                        <input type="hidden" class="required category_id" name="category_id" />
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
    <script src="../js/pages/customer/transactions.js"></script>


</body>

</html>