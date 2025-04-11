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
                    <div class="form-group account-holder-box">
                        <label class="text-black font-w500" for="account_id">Account Holder</label>
                        <select class="d-block default-select w-100 account_id">
                            <option value="">Choose...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="text-black font-w500">Date Range (Start)</label>
                        <input type="date" class="form-control required start_date_range datetime" name="start_date_range">
                    </div>
                    <div class="form-group">
                        <label class="text-black font-w500">Date Range (End)</label>
                        <input type="date" class="form-control required end_date_range datetime" name="end_date_range">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Generate Statement</button>
            </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="accountStatementModal">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Account Statement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body">
                <div class="col-lg-12 print-area">
                    <div class="card mt-3">
                        <div class="card-header spender-class-box">
                            <span class="float-end">
                                <strong>Status:</strong> <span class="spender-class"></span>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5">
                                <div class="mt-4 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                                    <h6>From:</h6>
                                    <div class="account-statement-start-time"></div>
                                </div>
                                <div class="mt-4 col-xl-3 col-lg-6 col-md-6 col-sm-6">
                                    <h6>to:</h6>
                                    <div class="account-statement-end-time"></div>
                                </div>
                                <div class="mt-4 col-xl-6 col-lg-12 col-md-12 col-sm-12 d-flex justify-content-lg-end justify-content-md-center justify-content-xs-start">
                                    <div class="row align-items-center">
                                        <div class="col-sm-9">
                                            <span><strong class="d-block statement-customer-name"></strong>
                                                <strong class="statement-account-id"></strong></span><br>
                                            <span><strong class="d-block statement-customer-phone"></strong>
                                                <strong class="statement-customer-email"></strong></span><br>
                                        </div>
                                        <div class="col-sm-3 mt-3"> <img src="../images/finhive-logo.png" alt="" class="img-fluid width110"> </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="account-statement">
                                    <thead>
                                        <tr>
                                            <th class="center">#</th>
                                            <th>Transaction Date</th>
                                            <th>Transaction Type</th>
                                            <th class="right">Amount</th>
                                            <th class="center">Fee</th>
                                            <th class="right">Balance</th>
                                            <th class="right">Source</th>
                                            <th class="right">Budget Category</th>
                                            <th class="right">Spending Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-sm-5"> </div>
                                <div class="col-lg-4 col-sm-5 ms-auto">
                                    <table class="table table-clear">
                                        <tbody>
                                            <!-- <tr>
                                                <td class="left"><strong>Opening Main Account Balance</strong></td>
                                                <td class="right opening-main-account-balance">$8.497,00</td>
                                            </tr>
                                            <tr>
                                                <td class="left"><strong>Closing Main Account Balance</strong></td>
                                                <td class="right closing-main-account-balance">$1,699,40</td>
                                            </tr> -->
                                            <tr>
                                                <td class="left"><strong>Total Main Account Debit</strong></td>
                                                <td class="right total-main-account-debit">$1,699,40</td>
                                            </tr>
                                            <tr>
                                                <td class="left"><strong>Total Main Account Credit</strong></td>
                                                <td class="right total-main-account-credit">$1,699,40</td>
                                            </tr>
                                            <!-- <tr>
                                                <td class="left"><strong>Opening Safe Lock Balance</strong></td>
                                                <td class="right opening-safe-lock-balance">$679,76</td>
                                            </tr>
                                            <tr>
                                                <td class="left"><strong>Closing Safe Lock Balance</strong></td>
                                                <td class="right closing-safe-lock-balance">$679,76</td>
                                            </tr> -->
                                            <tr>
                                                <td class="left"><strong>Total Safe Lock Debit</strong></td>
                                                <td class="right total-safe-lock-debit">$679,76</td>
                                            </tr>
                                            <tr>
                                                <td class="left"><strong>Total Safe Lock Credit</strong></td>
                                                <td class="right total-safe-lock-credit">$679,76</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-generate-statement" onclick="handlePrint('.print-area', 'Account Statement');">Generate Statement</button>
            </div>
        </div>
    </div>
</div>