$(function () {
	("use strict");

	$(document).ready(function () {
		loadDashboard();
		/* loadBudgetCategories();
    accountVerification();
    safeLockPeriodValidityCheck(); */

		$("#new-transaction-form").on("submit", function (e) {
			e.preventDefault();
			newFundsTransfer();
		});

		$("#account-update-form").on("submit", function (e) {
			e.preventDefault();
			updateAccount();
		});

		$("#password-update-form").on("submit", function (e) {
			e.preventDefault();
			updatePassword();
		});

		$("#pin-update-form").on("submit", function (e) {
			e.preventDefault();
			updateTransactionPIN();
		});
	});

	function loadLoggedUser() {
		const token = sessionStorage.getItem("token");
		$(".logged-user-name").text(
			`${payloadClaim(token, "first_name")} ${payloadClaim(
				token,
				"last_name"
			)}`
		);
		$(".logged-user-email").text(`${payloadClaim(token, "email")}`);
		$(".logged-user-phone").text(`${payloadClaim(token, "phone")}`);
		$(".logged-user-gender").text(`${payloadClaim(token, "gender")}`);
		$(".logged-user-address").text(`${payloadClaim(token, "address")}`);
		$(".logged-user-role").text(`${payloadClaim(token, "role")}`);
		$(".logged-user-dob").text(
			`${moment(payloadClaim(token, "dob")).format("Do MMMM, YYYY")}`
		);

		$(".logged-user-account-number").text(
			`${moment(payloadClaim(token, "account_number")).format(
				"Do MMMM, YYYY"
			)}`
		);
		$(".logged-user-account-officer").text(
			`${payloadClaim(
				token,
				"account_officer_first_name"
			)} ${payloadClaim(token, "account_officer_last_name")}`
		);
		$(".logged-user-account-officer-phone-number").text(
			`${payloadClaim(token, "account_officer_phone")}`
		);
		$(".logged-user-account-officer-email").text(
			`${payloadClaim(token, "account_officer_email")}`
		);
		$(".logged-user-account-type").text(
			`${payloadClaim(token, "account_type")}`
		);

		//form details
		$("#account-update-form")
			.find(".first_name")
			.val(payloadClaim(token, "first_name"));
		$("#account-update-form")
			.find(".last_name")
			.val(payloadClaim(token, "last_name"));
		$("#account-update-form")
			.find(".email")
			.val(payloadClaim(token, "email"));
		$("#account-update-form")
			.find(".phone")
			.val(payloadClaim(token, "phone"));
		$("#account-update-form")
			.find(".address")
			.val(payloadClaim(token, "address"));
		$("#account-update-form").find(".dob").val(payloadClaim(token, "dob"));
		$("#account-update-form")
			.find(".identification_number")
			.val(payloadClaim(token, "identification_number"));
		$("#account-update-form")
			.find("#identification")
			.selectpicker("val", payloadClaim(token, "identification"));
		$("#account-update-form")
			.find("#gender")
			.selectpicker("val", payloadClaim(token, "gender"));
		$("#account-update-form")
			.find(".user_id")
			.val(payloadClaim(token, "user_id"));
	}

	function updateAccount() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to update this account?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#account-update-form");
				var email = form.find(".email").val();
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

				if (!validateEmail(email)) {
					//alert("All fields are required");
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Please provide a valid email address",
						"error"
					);
					//alert("Please provide a valid email address");
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=account_update&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							var token = response.token; //generated access token from request
							sessionStorage.removeItem("token");
							sessionStorage.setItem("token", token); //set access token

							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							loadUnreadMessages();
							unblockUI();
						} else {
							showSimpleMessage(
								"Attention",
								response.message,
								"error"
							);
							unblockUI();
						}
					},
					error: function (req, status, err) {
						showSimpleMessage("Attention", req.statusText, "error");
						//alert(req.statusText);
						unblockUI();
					},
				});
			} else {
				showSimpleMessage("Canceled", "Process Abborted", "error");
			}
		});
	}

	function updatePassword() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to update this password?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#password-update-form");
				var newPassword = form.find(".new_password").val();
				var confirmPassword = form.find(".confirm_password").val();
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

				if (newPassword !== confirmPassword) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Passwords don't match",
						"error"
					);
					//alert("Please provide a valid email address");
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=password_update&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							form.get(0).reset();
							loadUnreadMessages();
							unblockUI();
						} else {
							showSimpleMessage(
								"Attention",
								response.message,
								"error"
							);
							unblockUI();
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

	function updateTransactionPIN() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to update your transaction PIN?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#pin-update-form");
				var newPin = form.find(".new_pin").val();
				var confirmPin = form.find(".confirm_pin").val();
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

				if (newPin !== confirmPin) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Transaction PINS don't match",
						"error"
					);
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=pin_update&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							form.get(0).reset();
							loadUnreadMessages();
							unblockUI();
						} else {
							showSimpleMessage(
								"Attention",
								response.message,
								"error"
							);
							unblockUI();
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

	function loadDashboard() {
		const token = sessionStorage.getItem("token");
		$.ajax({
			type: "GET",
			url: `${API_URL_ROOT}/dashboards?call=admin_dashboard&token=${token}`,
			dataType: "json",
			headers: { "x-access-token": token },
			success: function (response) {
				if (response.error == false) {
					var dashboard = response.dashboard;

					transactionChartData(dashboard.transaction_chart_data);

					$(".main-account-balance").text(
						formatCurrency(dashboard.main_account_balance)
					);
					$(".safe-lock-balance").text(
						formatCurrency(dashboard.safe_lock_balance)
					);
					$(".active-budget-categories-count").text(
						formatNumber(dashboard.active_budget_categories_count)
					);
					$(".unread-messages-count").text(
						formatNumber(dashboard.unread_messages_count)
					);
					$(".transactions-count").text(
						formatNumber(dashboard.transactions_count)
					);
					$(".exceeds-budget-count").text(
						formatNumber(dashboard.exceeds_budget_count)
					);
					$(".within-budget-count").text(
						formatNumber(dashboard.within_budget_count)
					);
					$(".total-credits").text(
						formatCurrency(dashboard.total_credits)
					);
					$(".total-debits").text(
						formatCurrency(dashboard.total_debits)
					);
					$(".admin-count").text(formatNumber(dashboard.admin_count));
					$(".customer-count").text(
						formatNumber(dashboard.customer_count)
					);
					$(".account-officer-count").text(
						formatNumber(dashboard.account_officer_count)
					);
					$(".total-service-charges").text(
						formatCurrency(dashboard.total_service_charges)
					);
				} else {
					showSimpleMessage("Attention", response.message, "error");
				}
			},
			error: function (req, err, status) {
				showSimpleMessage(
					"Attention",
					"ERROR - " + req.status + " : " + req.statusText,
					"error"
				);
			},
		});
	}

	function transactionChartData(data) {
		var options = {
			series: [
				{
					name: "Credit",
					data: data.creditdataset,
				},
				{
					name: "Debit",
					data: data.debitdataset,
				},
			],
			chart: {
				height: 350,
				type: "area",
				toolbar: {
					show: false,
				},
			},
			colors: ["#1E33F2", "#FF5045"],
			dataLabels: {
				enabled: false,
			},
			fill: {
				type: "solid",
				opacity: 0.04,
			},
			stroke: {
				curve: "smooth",
			},
			xaxis: {
				categories: data.daysdataset,
				labels: {
					rotate: -65, // rotate diagonally for better readability
					show: true,
					style: {
						colors: "#b9bbbd",
					},
				},
			},
			yaxis: {
				labels: {
					show: true,
					style: {
						colors: "#b9bbbd",
					},
				},
			},
			grid: {
				show: true,
				borderColor: "#E2E2E2",
				yaxis: {
					lines: {
						show: false,
					},
				},
				xaxis: {
					lines: {
						show: true,
					},
				},
			},
			tooltip: {
				x: {
					format: "dd/MM/yy HH:mm",
				},
			},
		};

		var chart = new ApexCharts(
			document.querySelector("#areaChart"),
			options
		);
		chart.render();
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
});
