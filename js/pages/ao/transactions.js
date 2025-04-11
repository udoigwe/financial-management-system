$(function () {
	("use strict");

	$(document).ready(function () {
		loadTransactions();
		loadAccounts();

		$("#transactions-filter-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
			var account_id = form.find("#account_id").val();
			var transaction_type = form.find("#transaction_type").val();
			var transaction_budget_status = form
				.find("#transaction_budget_status")
				.val();
			var budget_category_id = form.find("#budget_category_id2").val();
			var source = form.find("#source").val();
			var from_created_at = form.find("#from_created_at").val();
			var to_created_at = form.find("#to_created_at").val();
			var formattedStartDate = from_created_at
				? moment(from_created_at)
				: null;
			var formattedEndDate = to_created_at ? moment(to_created_at) : null;

			blockUI();

			if (
				formattedStartDate &&
				formattedEndDate &&
				formattedStartDate.isAfter(formattedEndDate)
			) {
				unblockUI();
				showSimpleMessage(
					"Attention",
					`Start dates cannot be after end dates`,
					"error"
				);
				return false;
			}
			if (
				formattedStartDate &&
				formattedEndDate &&
				formattedEndDate.isBefore(formattedStartDate)
			) {
				unblockUI();
				showSimpleMessage(
					"Attention",
					`End dates cannot be before start dates`,
					"error"
				);
				return false;
			}

			loadTransactions(
				account_id,
				transaction_type,
				transaction_budget_status,
				budget_category_id,
				source,
				from_created_at,
				to_created_at
			);
		});
	});

	function loadAccounts() {
		var token = sessionStorage.getItem("token");
		var userID = payloadClaim(token, "user_id");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/users?call=get_accounts&account_officer_id=${userID}&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const accounts = response.accounts;
						let html = '<option value="">Please select</option>';
						let html2 = '<option value="">Choose...</option>';

						for (let i = 0; i < accounts.length; i++) {
							const account = accounts[i];

							html += `
										<option value="${account.account_id}">${account.first_name} ${account.last_name} (${account.account_id})</option>
								`;
							html2 += `
										<option value="${account.account_id}">${account.first_name} ${account.last_name} (${account.account_id})</option>
								`;
						}
						$("#account_id").html(html);
						$("#account_id").selectpicker("refresh");

						$("#account_id2").html(html2);
						$("#account_id2").selectpicker("refresh");
					} else {
						unblockUI();
						console.log(response);
					}
				},
				error: function (req, status, err) {
					showSimpleMessage("Attention", req.statusText, "error");
					unblockUI();
				},
			});
		}
	}

	function loadTransactions(
		accountID = "",
		transaction_type = "",
		transaction_budget_status = "",
		budget_category_id = "",
		source = "",
		from_created_at = "",
		to_created_at = ""
	) {
		var token = sessionStorage.getItem("token");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/transactions?call=get_transactions_3
				&account_id=${accountID}
				&token=${token}
				&transaction_type=${transaction_type}
				&transaction_budget_status=${transaction_budget_status}
				&budget_category_id=${budget_category_id}
				&transaction_source=${source}
				&from_created_at=${from_created_at}
				&to_created_at=${to_created_at}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const transactions = response.transactions.transactions;
						let html = "";

						for (let i = 0; i < transactions.length; i++) {
							const transaction = transactions[i];

							html += `
								<tr>
										<td>${i + 1}</td>
                                        <td>${transaction.first_name} ${
								transaction.last_name
							}</td>
										<td>${moment(transaction.created_at).format("Do MMMM, YYYY HH:mm:ss")}</td>
										<td><span class="badge badge-rounded ${
											transaction.transaction_type ===
											"Debit"
												? "badge-warning"
												: "badge-success"
										}">${
								transaction.transaction_type
							}</span></td>
										<td style="font-weight: bolder; color: black">$${transaction.amount}</td>
										<td style="font-weight: bolder; color: black">$${
											transaction.transaction_fee
										}</td>
										<td style="font-weight: bolder; color: black">$${
											transaction.balance_after_transaction
										}</td>
										<td>${transaction.transaction_source}</td>
										<td>${transaction.transaction_destination}</td>
										<td>${
											transaction.budget_category_id
												? `<span class="badge badge-rounded" style="background-color: ${transaction.color_code}">${transaction.category_name}</span>`
												: "-"
										}</td>
										<td>${
											transaction.transaction_budget_status ===
											"Within Budget"
												? '<span class="badge badge-rounded badge-info">Within Budget</span>'
												: transaction.transaction_budget_status ===
												  "Exceeds Budget"
												? '<span class="badge badge-rounded badge-danger">Exceeds Budget</span>'
												: "-"
										}</td>
								</tr>
							`;
						}
						$("#my-transactions tbody").html(html);
					} else {
						unblockUI();
						console.log(response);
					}
				},
				error: function (req, status, err) {
					showSimpleMessage("Attention", req.statusText, "error");
					unblockUI();
				},
			});
		}
	}
});
