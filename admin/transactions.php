<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Admin | Transactions</title>
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
                        <h2 class="text-black mb-0 font-w700">Transactions</h2>
                        <p class="mb-0">Access all transactions across account holders!!!</p>
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
                                        <label for="account_id">Account Holder</label>
                                        <select class="d-block default-select w-100" id="account_id">
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
                                            <th scope="col">Account Holder</th>
                                            <th scope="col">Transaction Date</th>
                                            <th scope="col">Transaction Type</th>
                                            <th scope="col">Transacted Amount</th>
                                            <th scope="col">Transaction Fee</th>
                                            <th scope="col">Balance</th>
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
                                            <th scope="col">Account Holder</th>
                                            <th scope="col">Transaction Date</th>
                                            <th scope="col">Transaction Type</th>
                                            <th scope="col">Transacted Amount</th>
                                            <th scope="col">Transaction Fee</th>
                                            <th scope="col">Balance</th>
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
    <script src="../js/pages/admin/transactions.js"></script>


</body>

</html>