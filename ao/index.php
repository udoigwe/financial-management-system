<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Account Officer | Dashboard</title>
    <link rel="stylesheet" href="../vendor/chartist/css/chartist.min.css">
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
        <?php include('../includes/account-officer-sidebar.php'); ?>
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
                        <h2 class="text-black mb-0 font-w700">Dashboard</h2>
                        <p class="mb-0">Welcome back <span style="font-weight: bold;" class="logged-user-name"></span> !!!</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-4 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 main-account-balance"></h2>
                                        <p class="mb-0 text-black font-w600">Main Account Balance</p>
                                        <span><!-- <b class="text-success me-1">+0,5%</b> -->All Account Holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M50 15H10C7.23858 15 5 17.2386 5 20V40C5 42.7614 7.23858 45 10 45H50C52.7614 45 55 42.7614 55 40V20C55 17.2386 52.7614 15 50 15ZM50 40H10V20H50V40Z" fill="#1E33F2" />
                                            <path d="M45 27.5C43.067 27.5 41.5 29.067 41.5 31C41.5 32.933 43.067 34.5 45 34.5C46.933 34.5 48.5 32.933 48.5 31C48.5 29.067 46.933 27.5 45 27.5Z" fill="#1E33F2" />
                                        </svg>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 safe-lock-balance"></h2>
                                        <p class="mb-0 text-black font-w600">Safe Lock Balance</p>
                                        <span><!-- <b class="text-success me-1">+0,5%</b> -->All Account Holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M50 15H10C7.23858 15 5 17.2386 5 20V40C5 42.7614 7.23858 45 10 45H50C52.7614 45 55 42.7614 55 40V20C55 17.2386 52.7614 15 50 15ZM50 40H10V20H50V40Z" fill="#1E33F2" />
                                            <path d="M45 27.5C43.067 27.5 41.5 29.067 41.5 31C41.5 32.933 43.067 34.5 45 34.5C46.933 34.5 48.5 32.933 48.5 31C48.5 29.067 46.933 27.5 45 27.5Z" fill="#1E33F2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 total-service-charges">0</h2>
                                        <p class="mb-0 text-black font-w600">Total Service Charges</p>
                                        <span><!-- <b class="text-success me-1">+0,5%</b> -->From SafeLock Transactions Within Lock Periods under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 20H50V50H10V20Z" stroke="#1E33F2" stroke-width="2" stroke-linejoin="round" />
                                            <path d="M30 10V20" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" />
                                            <path d="M20 10H40" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" />
                                            <circle cx="30" cy="35" r="5" stroke="#1E33F2" stroke-width="2" />
                                            <path d="M30 30V27" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" />
                                            <path d="M30 43V40" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-sm-flex d-block pb-0 border-0">
                            <div class="me-auto pe-3">
                                <h4 class="text-black fs-24 font-w700">Daily Transactions</h4>
                            </div>
                        </div>
                        <div class="card-body pb-0">
                            <div id="areaChart" class="area-theme"></div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 unread-messages-count"></h2>
                                        <p class="mb-0 text-black font-w600">Unread Notifications</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->From Inception</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M50 12.5H10C7.23858 12.5 5 14.7386 5 17.5V42.5C5 45.2614 7.23858 47.5 10 47.5H50C52.7614 47.5 55 45.2614 55 42.5V17.5C55 14.7386 52.7614 12.5 50 12.5ZM50 17.5L30 32.5L10 17.5H50ZM50 42.5H10V22.5L30 37.5L50 22.5V42.5Z" fill="#1E33F2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 transactions-count"></h2>
                                        <p class="mb-0 text-black font-w600">Successful Transactions</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->Across All Account Holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 20H38.59L33.29 14.71C32.9 14.32 32.9 13.68 33.29 13.29C33.68 12.9 34.32 12.9 34.71 13.29L42.71 21.29C43.1 21.68 43.1 22.32 42.71 22.71L34.71 30.71C34.32 31.1 33.68 31.1 33.29 30.71C32.9 30.32 32.9 29.68 33.29 29.29L38.59 24H10C9.45 24 9 23.55 9 23C9 22.45 9.45 22 10 22V20ZM50 36H21.41L26.71 41.29C27.1 41.68 27.1 42.32 26.71 42.71C26.32 43.1 25.68 43.1 25.29 42.71L17.29 34.71C16.9 34.32 16.9 33.68 17.29 33.29L25.29 25.29C25.68 24.9 26.32 24.9 26.71 25.29C27.1 25.68 27.1 26.32 26.71 26.71L21.41 32H50C50.55 32 51 32.45 51 33C51 33.55 50.55 34 50 34V36Z" fill="#1E33F2" />
                                        </svg>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 exceeds-budget-count"></h2>
                                        <p class="mb-0 text-black font-w600">Transactions</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->Exceeding Budget</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10 48V40C10 39.4477 10.4477 39 11 39H15C15.5523 39 16 39.4477 16 40V48C16 48.5523 15.5523 49 15 49H11C10.4477 49 10 48.5523 10 48ZM22 48V32C22 31.4477 22.4477 31 23 31H27C27.5523 31 28 31.4477 28 32V48C28 48.5523 27.5523 49 27 49H23C22.4477 49 22 48.5523 22 48ZM34 48V20C34 19.4477 34.4477 19 35 19H39C39.5523 19 40 19.4477 40 20V48C40 48.5523 39.5523 49 39 49H35C34.4477 49 34 48.5523 34 48ZM46 48V27C46 26.4477 46.4477 26 47 26H51C51.5523 26 52 26.4477 52 27V48C52 48.5523 51.5523 49 51 49H47C46.4477 49 46 48.5523 46 48Z" fill="#1E33F2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 customer-count"></h2>
                                        <p class="mb-0 text-black font-w600">Customers</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->Account holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="30" cy="20" r="8" stroke="#1E33F2" stroke-width="2" />
                                            <path d="M15 44C15 38.4772 19.4772 34 25 34H35C40.5228 34 45 38.4772 45 44V46C45 47.1046 44.1046 48 43 48H17C15.8954 48 15 47.1046 15 46V44Z" stroke="#1E33F2" stroke-width="2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 within-budget-count"></h2>
                                        <p class="mb-0 text-black font-w600">Transactions</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->Within Budget</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="8" y="16" width="44" height="28" rx="4" stroke="#1E33F2" stroke-width="2" />
                                            <path d="M38 24H48C49.1046 24 50 24.8954 50 26V34C50 35.1046 49.1046 36 48 36H38V24Z" stroke="#1E33F2" stroke-width="2" />
                                            <circle cx="43" cy="30" r="1.5" fill="#1E33F2" />
                                            <path d="M18 30L22 34L30 26" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 total-credits"></h2>
                                        <p class="mb-0 text-black font-w600">Total Credits</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->From account holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="8" y="18" width="44" height="24" rx="3" stroke="#1E33F2" stroke-width="2" />
                                            <rect x="8" y="24" width="44" height="6" fill="#1E33F2" />
                                            <rect x="14" y="32" width="10" height="4" fill="#1E33F2" />
                                            <rect x="28" y="32" width="8" height="4" fill="#1E33F2" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 total-debits"></h2>
                                        <p class="mb-0 text-black font-w600">Total Debits</p>
                                        <span><!-- <b class="text-danger me-1">-2%</b> -->From account holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="8" y="18" width="44" height="24" rx="3" stroke="#1E33F2" stroke-width="2" />
                                            <rect x="8" y="24" width="44" height="6" fill="#1E33F2" />
                                            <path d="M30 32V38M30 38L27 35M30 38L33 35" stroke="#1E33F2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-6 col-sm-6">
                        <div class="card card-bx">
                            <div class="card-body">
                                <div class="media align-items-center">
                                    <div class="media-body me-3">
                                        <h2 class="text-black font-w700 active-budget-categories-count"></h2>
                                        <p class="mb-0 text-black font-w600">Budget Categories</p>
                                        <span><!-- <b class="text-success me-1">+0,5%</b> -->Across All Account Holders under you</span>
                                    </div>
                                    <div class="d-inline-block">
                                        <svg class="primary-icon" width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M45 10H39.18C38.58 7.67 36.42 6 34 6H26C23.58 6 21.42 7.67 20.82 10H15C12.24 10 10 12.24 10 15V50C10 52.76 12.24 55 15 55H45C47.76 55 50 52.76 50 50V15C50 12.24 47.76 10 45 10ZM26 10H34C34.55 10 35 10.45 35 11C35 11.55 34.55 12 34 12H26C25.45 12 25 11.55 25 11C25 10.45 25.45 10 26 10ZM45 50H15V15H20V18C20 19.1 20.9 20 22 20H38C39.1 20 40 19.1 40 18V15H45V50Z" fill="#1E33F2" />
                                            <path d="M30 27C27.79 27 26 28.79 26 31H29C29 30.45 29.45 30 30 30C30.55 30 31 30.45 31 31C31 31.55 30.55 32 30 32C27.79 32 26 33.79 26 36C26 38.21 27.79 40 30 40C32.21 40 34 38.21 34 36H31C31 36.55 30.55 37 30 37C29.45 37 29 36.55 29 36C29 35.45 29.45 35 30 35C32.21 35 34 33.21 34 31C34 28.79 32.21 27 30 27Z" fill="#1E33F2" />
                                        </svg>
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
    <!-- Apex Chart -->
    <script src="../vendor/apexchart/apexchart.js"></script>
    <script src="../js/pages/ao/index.js"></script>


</body>

</html>