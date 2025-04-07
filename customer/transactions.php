<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Customer | Transactions</title>

    <style type="text/css">
        .account-verification-box-success {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 60px;
            background-color: #1FD9511F;
            border-radius: 15px;
            font-style: italic
        }

        .account-verification-box-error {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 60px;
            background-color: #FB23231F;
            border-radius: 15px;
            font-style: italic
        }

        .account-verification-box-default {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 60px;
            background-color: #E7E8EC;
            border-radius: 15px;
            font-style: italic
        }

        .account-verification-box-hidden {
            display: none;
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
                                            <label class="text-black font-w500">Choose Budget Category</label>
                                            <select class="form-control required budget_category_id default-select" id="budget_category_id" name="budget_category_id">
                                                <option value="">Please select</option>
                                            </select>
                                        </div>
                                        <div id="account-number-box">
                                            <div class="form-group">
                                                <label class="text-black font-w500">Account Number</label>
                                                <input type="text" class="form-control destination_account_number required" placeholder="Numeric values only" name="destination_account_number">
                                            </div>
                                            <div class="form-group account-verification-box-hidden" id="account-verification-box">
                                                ...verifying account
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="text-black font-w500">Amount</label>
                                            <input type="text" class="form-control amount required" placeholder="Numeric values only" name="amount">
                                        </div>
                                        <div class="form-group" id="source-box">
                                            <label class="text-black font-w500">Choose Source</label>
                                            <select class="form-control required source default-select" id="source" name="source">
                                                <option value="">Please select</option>
                                                <option value="Main Account">Main Account</option>
                                                <option value="Safe Lock">Safe Lock</option>
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
                    <div class="modal fade" id="accountStatementGenerationModal">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Generate Account Statement</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="account-statement-form">
                                        <div class="form-group">
                                            <label class="text-black font-w500">Date Range (Start)</label>
                                            <input type="date" class="form-control required from_ datetime" placeholder="Saturday 24 June 2017 - 21:44" name="budget_limit_start_time">
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
                <div class="col-xl-12 col-lg-12 col-xxl-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Transactions Filter</h4>
                        </div>
                        <div class="card-body">
                            <form class="needs-validation" id="transactions-filter-form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="transaction_type">Transaction Type</label>
                                        <select class="d-block default-select w-100" id="transaction_type">
                                            <option value="">Choose...</option>
                                            <option value="Debit">Debit</option>
                                            <option value="Credit">Credit</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="transaction_budget_status">Transaction Budget Status</label>
                                        <select class="d-block default-select w-100" id="transaction_budget_status">
                                            <option value="">Choose...</option>
                                            <option value="Within Budget">Within Budget</option>
                                            <option value="Exceeds Budget">Exceeds Budget</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="budget_category_id2">Budget Category</label>
                                        <select class="d-block default-select w-100" id="budget_category_id2">
                                            <option value="">Choose...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="source">Source</label>
                                        <select class="d-block default-select w-100" id="source">
                                            <option value="">Choose...</option>
                                            <option value="Main Account">Main Account</option>
                                            <option value="Safe Lock">Safe Lock</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="from_created_at">Transaction Date Range (Start)</label>
                                        <input type="date" class="form-control" id="from_created_at" placeholder="Start Date Range">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="to_created_at">Transaction Date Range (End)</label>
                                        <input type="date" class="form-control" id="to_created_at" placeholder="End Date Range">
                                    </div>
                                </div>

                                <hr class="mb-4">
                                <button class="btn btn-primary btn-lg btn-block" type="submit">Filter</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12 col-lg-12 col-xxl-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">My Transactions</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive recentorderTable">
                                <table class="table verticle-middle table-striped table-responsive-md my-transactions" id="my-transactions">
                                    <thead>
                                        <tr>
                                            <th scope="col">SNO</th>
                                            <th scope="col">Transaction Date</th>
                                            <th scope="col">Transaction Type</th>
                                            <th scope="col">Transacted Amount</th>
                                            <th scope="col">Transaction Fee</th>
                                            <th scope="col">Balance</th>
                                            <th scope="col">Sender</th>
                                            <th scope="col">Sender Phone</th>
                                            <th scope="col">Source</th>
                                            <th scope="col">Destination</th>
                                            <th scope="col">Budget Category</th>
                                            <th scope="col">Transaction Budget Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th scope="col">SNO</th>
                                            <th scope="col">Transaction Date</th>
                                            <th scope="col">Transaction Type</th>
                                            <th scope="col">Transacted Amount</th>
                                            <th scope="col">Transaction Fee</th>
                                            <th scope="col">Balance</th>
                                            <th scope="col">Sender</th>
                                            <th scope="col">Sender Phone</th>
                                            <th scope="col">Source</th>
                                            <th scope="col">Destination</th>
                                            <th scope="col">Budget Category</th>
                                            <th scope="col">Transaction Budget Status</th>
                                        </tr>
                                    </tfoot>
                                </table>
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
    <script src="../vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="../js/pages/customer/transactions.js"></script>


</body>

</html>