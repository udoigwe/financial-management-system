<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('../includes/head2.php'); ?>
    <title>FinHive | Customer | Budget Categories</title>
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
                        <h2 class="text-black mb-0 font-w700">Budget Categories</h2>
                        <p class="mb-0">Create budget categories</p>
                    </div>
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addCategoryModal" class="btn btn-primary btn-rounded mb-3"><i class="fa fa-user-plus me-3"></i>New Budget Category</a>
                    <!-- Add Category -->
                    <div class="modal fade" id="addCategoryModal">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Budget Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="new-budget-category-form">
                                        <div class="form-group">
                                            <label class="text-black font-w500">Choose From Existing</label>
                                            <select class="form-control required default-select" id="choose-from-existing" name="Existing">
                                                <option value="">Please select</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                        <div class="form-group" id="existingCategoryWrapper" style="display: none;">
                                            <label class="text-black font-w500">Existing Budget Categories</label>
                                            <select class="form-control category_name default-select" id="existingCategory" name="category_name">
                                                <option value="">Please select</option>
                                                <option value="Rent">Rent</option>
                                                <option value="Housing/Mortgage">Housing/Mortgage</option>
                                                <option value="Food/Groceries">Food/Groceries</option>
                                                <option value="Transportation/Fuel">Transportation/Fuel</option>
                                                <option value="Entertainment/Subscriptions">Entertainment/Subscriptions</option>
                                                <option value="Investments">Investments</option>
                                                <option value="Clothing/Lifestyle">Clothing/Lifestyle</option>
                                                <option value="Travel/Vacations">Travel/Vacations</option>
                                                <option value="Credit/Loans">Credit/Loans</option>
                                                <option value="Miscellaneous">Miscellaneous</option>
                                                <option value="Health/Fitness">Health/Fitness</option>
                                                <option value="Childcare">Childcare</option>
                                            </select>
                                        </div>
                                        <div class="form-group" id="newCategoryWrapper" style="display: none;">
                                            <label class="text-black font-w500">Category Name</label>
                                            <input type="text" class="form-control category_name" placeholder="Unique category name" id="newCategory" name="category_name">
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
                                            <label class="text-black font-w500">Description</label>
                                            <textarea class="form-control required description" placeholder="Brief description" name="description" rows="5"></textarea>
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Create Category</button>
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
                <div class="col-xl-12 col-lg-12 col-xxl-12 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">My Budget Categories</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive recentorderTable">
                                <table class="table verticle-middle table-striped table-responsive-md my-budget-categories" id="my-budget-categories">
                                    <thead>
                                        <tr>
                                            <th scope="col">SNO</th>
                                            <th scope="col">ACCOUNT NUMBER</th>
                                            <th scope="col">CATEGORY</th>
                                            <th scope="col">BUDGET LIMIT</th>
                                            <th scope="col">BUDGET LIMIT START TIME</th>
                                            <th scope="col">BUDGET LIMIT END TIME</th>
                                            <th scope="col">STATUS</th>
                                            <th scope="col">CREATED AT</th>
                                            <th scope="col">LAST EDITED AT</th>
                                            <th scope="col">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
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
    <script src="../js/pages/customer/budget-categories.js"></script>


</body>

</html>