$(function () {
	("use strict");

	$(document).ready(function () {
		loadTransactions();
		loadBudgetCategories();
		accountVerification();
		safeLockPeriodValidityCheck();

		$("#new-transaction-form").on("submit", function (e) {
			e.preventDefault();
			newFundsTransfer();
		});

		$("#edit-budget-category-form").on("submit", function (e) {
			e.preventDefault();
			updateBudgetCategory();
		});

		$("#my-budget-categories").on("click", ".btn-edit", function () {
			var categoryID = $(this).attr("data-id");
			var editModal = $("#editCategoryModal");
			var token = sessionStorage.getItem("token");

			blockUI();

			//fetch category details
			$.ajax({
				url: `${API_URL_ROOT}/budget?call=get&category_id=${categoryID}&token=${token}`,
				type: "GET",
				dataType: "json",
				headers: { "x-access-token": token },
				success: function (response) {
					if (response.error == false) {
						var categories = response.categories;
						const category = categories[0];

						editModal
							.find(".modal-title")
							.text(category.category_name);
						editModal
							.find(".category_name")
							.val(category.category_name);
						editModal
							.find(".budget_limit")
							.val(category.budget_limit);
						editModal
							.find(".budget_limit_start_time")
							.val(category.budget_limit_start_time);
						editModal
							.find(".budget_limit_end_time")
							.val(category.budget_limit_end_time);
						editModal.find(".color_code").val(category.color_code);
						editModal
							.find(".description")
							.val(category.category_description);
						editModal
							.find(".category_id")
							.val(category.category_id);

						editModal
							.find("#budget_category_status")
							.selectpicker(
								"val",
								category.budget_category_status
							);

						unblockUI();
					} else {
						showSimpleMessage(
							"Attention",
							response.message,
							"error"
						);
					}
				},
				error: function (req, status, error) {
					showSimpleMessage(
						"Attention",
						"ERROR - " + req.status + " : " + req.statusText,
						"error"
					);
				},
			});
		});

		$("#budget_category_id").on("change", function () {
			var selectedText = $(this).find("option:selected").text(); // Use text instead of val
			var token = sessionStorage.getItem("token");
			var accountID = payloadClaim(token, "account_id");

			if (selectedText === "Savings") {
				$("#account-number-box").slideUp();
				$("#account-number-box")
					.find(".destination_account_number")
					.val(accountID);

				$("#account-verification-box")
					.removeClass(
						"account-verification-box-success account-verification-box-error account-verification-box-default"
					)
					.addClass("account-verification-box-hidden")
					.text("verifying account...");

				$("#source-box").slideUp();
				$("#source").selectpicker("val", "Main Account");
			} else {
				$("#account-number-box").slideDown();
				$("#account-number-box")
					.find(".destination_account_number")
					.val("");

				$("#account-verification-box")
					.removeClass(
						"account-verification-box-success account-verification-box-error account-verification-box-default"
					)
					.addClass("account-verification-box-hidden")
					.text("verifying account...");

				$("#source-box").slideDown();
				$("#source").selectpicker("val", "");
			}
		});

		$("#transactions-filter-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
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
				transaction_type,
				transaction_budget_status,
				budget_category_id,
				source,
				from_created_at,
				to_created_at
			);
		});
	});

	function newFundsTransfer() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to continue with this funds transfer?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#new-transaction-form");
				var amount = form.find(".amount").val();
				var fields = form.find(
					"input.required, select.required, textarea.required"
				);
				var token = sessionStorage.getItem("token");

				blockUI();

				for (var i = 0; i < fields.length; i++) {
					if (fields[i].value == "") {
						/*alert(fields[i].id)*/
						unblockUI();
						form.find("#" + fields[i].id).focus();
						showSimpleMessage(
							"Attention",
							`${fields[i].name} is required`,
							"error"
						);
						//alert(`${fields[i].name} is required`);
						return false;
					}
				}

				if (isNaN(amount)) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Amount must be a number",
						"error"
					);
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/transactions?call=funds_transfer&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							unblockUI();
							//alert(response.message);
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							form.get(0).reset();
							$("#account-verification-box")
								.removeClass(
									"account-verification-box-success account-verification-box-error account-verification-box-default"
								)
								.addClass("account-verification-box-hidden")
								.text("verifying account...");
							$("#budget_category_id").selectpicker("refresh");
							$("#source").selectpicker("refresh");
							$("#transactionModal").find(".btn-close").click();
							loadUnreadMessages();
							loadTransactions();
						} else {
							unblockUI();
							if (response.message === "OTP_REQUIRED") {
								$("#otpSection").slideDown();
								form.find(".otp").addClass("required");

								showSimpleMessage(
									"Attention",
									"An OTP has been sent to your email address",
									"error"
								);
							} else {
								showSimpleMessage(
									"Attention",
									response.message,
									"error"
								);
							}
						}
					},
					error: function (req, status, err) {
						showSimpleMessage("Attention", req.statusText, "error");
						unblockUI();
					},
				});
			} else {
				showSimpleMessage("Canceled", "Process Abborted", "error");
			}
		});
	}

	function updateBudgetCategory() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to updated this budget category?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#edit-budget-category-form");
				var budgetLimitStartTime = form
					.find(".budget_limit_start_time")
					.val();
				var budgetLimitEndTime = form
					.find(".budget_limit_end_time")
					.val();
				var fields = form.find(
					"input.required, select.required, textarea.required"
				);
				var token = sessionStorage.getItem("token");

				blockUI();

				for (var i = 0; i < fields.length; i++) {
					if (fields[i].value == "") {
						/*alert(fields[i].id)*/
						unblockUI();
						form.find("#" + fields[i].id).focus();
						showSimpleMessage(
							"Attention",
							`${fields[i].name} is required`,
							"error"
						);
						//alert(`${fields[i].name} is required`);
						return false;
					}
				}

				const start = moment(budgetLimitStartTime);
				const end = moment(budgetLimitEndTime);

				if (start.isAfter(end)) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Budget start time cannot come after budget end time",
						"error"
					);
					return false;
				}

				if (end.isBefore(start)) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Budget end time cannot come before budget start time",
						"error"
					);
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/budget?call=update&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							unblockUI();
							//alert(response.message);
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							form.get(0).reset();
							loadUnreadMessages();
							loadBudgetCategories();
						} else {
							unblockUI();
							//alert(response.message);
							showSimpleMessage(
								"Attention",
								response.message,
								"error"
							);
						}
					},
					error: function (req, status, err) {
						showSimpleMessage("Attention", req.statusText, "error");
						unblockUI();
					},
				});
			} else {
				showSimpleMessage("Canceled", "Process Abborted", "error");
			}
		});
	}

	function loadBudgetCategories() {
		var token = sessionStorage.getItem("token");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/budget?call=get&budget_category_status=Active&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const categories = response.categories;
						let html = '<option value="">Please select</option>';
						let html2 = '<option value="">Choose...</option>';

						for (let i = 0; i < categories.length; i++) {
							const category = categories[i];

							html += `
										<option value="${category.category_id}">${category.category_name}</option>
								`;
							html2 += `
										<option value="${category.category_id}">${category.category_name}</option>
								`;
						}
						$("#budget_category_id").html(html);
						$("#budget_category_id").selectpicker("refresh");

						$("#budget_category_id2").html(html2);
						$("#budget_category_id2").selectpicker("refresh");
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

	function accountVerification() {
		let debounceTimeout = null;
		$(".destination_account_number").on("keyup", function () {
			let accountId = $(this).val().trim(); // Get input value
			console.log(accountId);
			const token = sessionStorage.getItem("token");

			// Reset to default if input is empty
			if (accountId === "") {
				$("#account-verification-box")
					.removeClass(
						"account-verification-box-success account-verification-box-error account-verification-box-default"
					)
					.addClass("account-verification-box-hidden")
					.text("verifying account...");
				return;
			}

			$("#account-verification-box")
				.removeClass(
					"account-verification-box-success account-verification-box-error"
				)
				.addClass("account-verification-box-default")
				.text("Verifying account...");

			// Clear previous debounce
			clearTimeout(debounceTimeout);

			// Wait 500ms after last keyup before making the API call
			debounceTimeout = setTimeout(() => {
				// Call API to check if the account exists
				$.ajax({
					url: `${API_URL_ROOT}/users?call=get_accounts&account_id=${accountId}&token=${token}`,
					type: "GET",
					dataType: "json",
					success: function (response) {
						const account = response?.accounts?.[0];
						if (response.error === false && account) {
							console.log(account);
							// Account found - Show success
							$("#account-verification-box")
								.removeClass(
									"account-verification-box-hidden account-verification-box-default account-verification-box-error"
								)
								.addClass("account-verification-box-success")
								.text(
									`${account.first_name} ${account.last_name} ✅`
								);
						} else {
							// Account not found - Show error
							$("#account-verification-box")
								.removeClass(
									"account-verification-box-hidden account-verification-box-default account-verification-box-success"
								)
								.addClass("account-verification-box-error")
								.text("Account not found ❌");
						}
					},
					error: function () {
						// Handle API failure
						$("#account-verification-box")
							.removeClass(
								"account-verification-box-hidden account-verification-box-success account-verification-box-default"
							)
							.addClass("account-verification-box-error")
							.text("Error checking account ❌");
					},
				});
			}, 500);
		});
	}

	function safeLockPeriodValidityCheck() {
		$("#source").on("change", function () {
			const selectedOption = $(this).val();
			const token = sessionStorage.getItem("token");

			if (selectedOption === "Safe Lock") {
				blockUI();
				$.ajax({
					url: `${API_URL_ROOT}/transactions?call=check_safe_lock_period&token=${token}`,
					type: "GET",
					dataType: "json",
					success: function (response) {
						unblockUI();

						if (
							response.error === true &&
							response.message === "LOCK_ACTIVE"
						) {
							showSimpleMessage(
								"Attention",
								"Safe Lock period is still active. This transaction will attract a service charge fee of 5% of the transaction amount.",
								"error"
							);
						}
					},
					error: function (response) {
						unblockUI();
						showSimpleMessage(
							"Attention",
							"An error ocured",
							"error"
						);
					},
				});
			}
		});
	}

	function loadTransactions(
		transaction_type = "",
		transaction_budget_status = "",
		budget_category_id = "",
		source = "",
		from_created_at = "",
		to_created_at = ""
	) {
		var token = sessionStorage.getItem("token");
		var accountID = payloadClaim(token, "account_id");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/transactions?call=get_transactions
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
										<td>${
											transaction.transaction_type ===
											"Credit"
												? `${transaction.sender_first_name} ${transaction.sender_last_name}`
												: "-"
										} </td>
										<td>${
											transaction.transaction_type ===
											"Credit"
												? `${transaction.sender_phone}`
												: "-"
										} </td>
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
