//block ui
function blockUI() {
	$.blockUI({
		message:
			'<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-loader spin"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>',
		fadeIn: 800,
		//timeout: 2000, //unblock after 2 seconds
		overlayCSS: {
			backgroundColor: "#191e3a",
			opacity: 0.8,
			zIndex: 50000,
			cursor: "wait",
		},
		css: {
			animation: "spin 2s linear infinite",
			border: 0,
			color: "#25d5e4",
			zIndex: 50001,
			padding: 0,
			backgroundColor: "transparent",
		},
	});
}

function unblockUI() {
	$.unblockUI();
}

$.fn.serializeObject = function () {
	var formData = {};
	var formArray = this.serializeArray();

	for (var i = 0, n = formArray.length; i < n; ++i)
		formData[formArray[i].name] = formArray[i].value;

	return formData;
};

function rememberMe() {
	if (localStorage.getItem("chkbx") && localStorage.getItem("chkbx") !== "") {
		$("#remember-me").attr("checked", "checked");
		$("#email").val(localStorage.getItem("email"));
		$("#password").val(localStorage.getItem("password"));
	} else {
		$("#remember-me").removeAttr("checked");
		$("#email").val("");
		$("#password").val("");
	}
}

function setRememberMe() {
	if ($("#remember-me").is(":checked")) {
		// save email and password in computer's hardrive
		localStorage.removeItem("email");
		localStorage.removeItem("password");
		localStorage.removeItem("chkbx");
		localStorage.setItem("email", $("#email").val());
		localStorage.setItem("password", $("#password").val());
		localStorage.setItem("chkbx", $("#remember-me").val());
	} else {
		//remove login details from computer's hardrivve
		localStorage.removeItem("email");
		localStorage.removeItem("password");
		localStorage.removeItem("chkbx");
	}
}

function validateEmail(email) {
	var filter = /^[\w-.+]+@[a-zA-Z0-9.-]+.[a-zA-Z0-9]{2,4}$/;

	if (filter.test(email)) {
		return true;
	} else {
		return false;
	}
}

//Show simple message
function showSimpleMessage(title, text, type) {
	Swal.fire({
		title: title,
		text: text,
		icon: type,
		confirmButtonText: "Close",
		showLoaderOnConfirm: false,
		backdrop: false,
		position: "center",
	});
}

//Show simple html message
function showSimpleHTMLMessage(title, html, type) {
	Swal.fire({
		title: title,
		html: html,
		icon: type,
		confirmButtonText: "Close",
		showLoaderOnConfirm: false,
	});
}

async function showConfirmMessage(title, text, type, callback) {
	const result = await Swal.fire({
		title: title,
		text: text,
		icon: type,
		showCancelButton: true,
		padding: "2em",
		//closeOnConfirm: false,
		//showLoaderOnConfirm: true,
	});

	if (result.value) {
		callback;
	}
}

//not logged in check
function isAuthenticated() {
	//Instantiate access token
	var token = sessionStorage.getItem("token");

	//check if the access token is empty
	if (token === null || token === "" || token === undefined) {
		//redirect to the login page
		window.location = "../";
	}
}

function payloadClaim(token, param) {
	var base64Url = token.split(".")[1];
	var base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
	var payload = JSON.parse(window.atob(base64));
	var claim = payload[param];

	return claim;
}

//display user profile
function displayProfile() {
	var token = sessionStorage.getItem("token"); //access token

	if (token !== null && token !== "") {
		var firstname = payloadClaim(token, "first_name");
		var lastname = payloadClaim(token, "last_name");
		var role = payloadClaim(token, "role");

		$(".logged-user-name").text(`${firstname} ${lastname}`);
		$(".logged-user-role").text(role);
	}
}

function updateProfilePopUp() {
	var token = sessionStorage.getItem("token"); //access token
	var profileCompletionStatus = payloadClaim(
		token,
		"profile_completion_status"
	);

	if (profileCompletionStatus == "Uncompleted") {
		$("#staticBackdrop").modal("show");
	}
}

/* async function showSignOutMessage() {
	var token = sessionStorage.getItem("token"); //access token
	var name = payloadClaim(token, "name");

	if (window.confirm(`Are you sure you want to sign ${name} out?`)) {
		signOut();
	}
} */

function showSignOutMessage() {
	var token = sessionStorage.getItem("token"); //access token
	var name = payloadClaim(token, "first_name");

	Swal.fire({
		title: "Sign Out?",
		text: `Are you sure you want to sign ${name} out?`,
		type: "warning",
		showCancelButton: true,
		padding: "2em",
		backdrop: false,
		position: "center",
		//closeOnConfirm: false,
		//showLoaderOnConfirm: true,
	}).then(function (result) {
		if (result.value) {
			signOut();
		}
	});
}

function signOut() {
	blockUI();

	//clear all stored sessions
	sessionStorage.clear();

	//redirect to login screeen
	window.location = "../";
}

function formatNumber(num) {
	return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
}

function getQueryParam(paramName) {
	const currentUrl = new URL(window.location.href);
	const urlParams = new URLSearchParams(currentUrl.search);
	return urlParams.get(paramName);
}

function loadUnreadMessages() {
	var token = sessionStorage.getItem("token");

	blockUI();

	if (token) {
		$.ajax({
			type: "GET",
			url: `${API_URL_ROOT}/resources?call=notifications&token=${token}`,
			dataType: "json",
			success: function (response) {
				if (response.error === false) {
					unblockUI();

					const data = response.data;
					const count = data.count;
					const notifications = data.notifications;
					let html = "";

					$(".unread-messages-count").text(count);

					for (let i = 0; i < notifications.length; i++) {
						const notification = notifications[i];

						html += `
							<li style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#messageModal" onclick="loadNotification(${
								notification.notification_id
							})">
								<div class="timeline-panel">
									<div class="media me-2 media-success">
										<i class="fa fa-envelope"></i>
									</div>
									<div class="media-body">
										<h6 class="mb-1">${notification.title}</h6>
										<small class="d-block">${moment(notification.created_at).format(
											"DD MMMM YYYY - hh:mm A"
										)}</small>
									</div>
								</div>
							</li>
						`;
					}
					$(".unread-messages-list").html(html);
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

function loadNotification(notificationID) {
	var token = sessionStorage.getItem("token");

	blockUI();

	if (token) {
		$.ajax({
			type: "GET",
			url: `${API_URL_ROOT}/resources?call=notifications&notification_id=${notificationID}&token=${token}`,
			dataType: "json",
			success: function (response) {
				if (response.error === false) {
					unblockUI();

					const data = response.data;
					const notification = data.notifications[0];
					var messageModal = $("#messageModal");

					messageModal.find(".modal-title").text(notification.title);
					messageModal.find(".modal-body").text(notification.message);

					loadUnreadMessages();
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

function generateAccountStatement() {
	var token = sessionStorage.getItem("token");
	var role = payloadClaim(token, "role");
	var userID = payloadClaim(token, "user_id");
	var form = $("#account-statement-form");

	if (role === "Customer") {
		form.find(".account-holder-box").slideUp();
		$(".spender-class-box").slideUp();
	}

	$.ajax({
		type: "GET",
		url:
			role === "Account Officer"
				? `${API_URL_ROOT}/users?call=get_accounts&account_officer_id=${userID}&token=${token}`
				: `${API_URL_ROOT}/users?call=get_accounts&token=${token}`,
		dataType: "json",
		success: function (response) {
			if (response.error === false) {
				unblockUI();

				const accounts = response.accounts;
				let html = '<option value="">Please select</option>';

				for (let i = 0; i < accounts.length; i++) {
					const account = accounts[i];

					html += `
										<option value="${account.account_id}">${account.first_name} ${account.last_name} (${account.account_id})</option>
								`;
				}
				form.find("select.account_id").html(html);
				form.find("select.account_id").selectpicker("refresh");
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

	$("#account-statement-form").on("submit", function (e) {
		e.preventDefault();
		var form = $(this);
		const accountStatementModal = $("#accountStatementModal");
		const accountStatementGenerationModal = $(
			"#accountStatementGenerationModal"
		);

		var startTime = form.find(".start_date_range").val();
		var endTime = form.find(".end_date_range").val();
		var fields = form.find(
			"input.required, select.required, textarea.required"
		);
		var token = sessionStorage.getItem("token");
		var role = payloadClaim(token, "role");
		var accountID =
			role === "Customer"
				? payloadClaim(token, "account_id")
				: form.find("select.account_id").val();

		blockUI();

		for (var i = 0; i < fields.length; i++) {
			if (fields[i].value == "") {
				/*alert(fields[i].id)*/
				unblockUI();
				showSimpleMessage(
					"Attention",
					`${fields[i].name} is required`,
					"error"
				);
				//alert(`${fields[i].name} is required`);
				return false;
			}
		}

		if (role !== "Customer" && !accountID) {
			showSimpleMessage(
				"Attention",
				`Please provide an account holder`,
				"error"
			);
			return false;
		}

		const start = moment(startTime);
		const end = moment(endTime);

		if (start.isAfter(end)) {
			unblockUI();
			showSimpleMessage(
				"Attention",
				"Start date range cannot come after end date range",
				"error"
			);
			return false;
		}

		if (end.isBefore(start)) {
			unblockUI();
			showSimpleMessage(
				"Attention",
				"End date range cannot come before start date range",
				"error"
			);
			return false;
		}

		$.ajax({
			type: "GET",
			url:
				role === "Customer"
					? `${API_URL_ROOT}/transactions?call=get_transactions
				&account_id=${accountID}
				&token=${token}
				&from_created_at=${startTime}
				&to_created_at=${endTime}`
					: `${API_URL_ROOT}/transactions?call=get_transactions_2
				&account_id=${accountID}
				&token=${token}
				&from_created_at=${startTime}
				&to_created_at=${endTime}`,
			dataType: "json",
			success: function (response) {
				if (response.error === false) {
					unblockUI();

					const transactions = response.transactions.transactions;
					const summary = response.transactions.summary;
					console.log(summary);
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
										<td>${transaction.transaction_source}</td>
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
					$("#account-statement tbody").html(html);
					$(".opening-main-account-balance").text(
						formatCurrency(summary.opening_main_account_balance)
					);
					$(".closing-main-account-balance").text(
						formatCurrency(summary.closing_main_account_balance)
					);
					$(".opening-safe-lock-balance").text(
						formatCurrency(summary.opening_safe_lock_balance)
					);
					$(".closing-safe-lock-balance").text(
						formatCurrency(summary.closing_safe_lock_balance)
					);
					$(".total-main-account-debit").text(
						formatCurrency(summary.total_main_account_debit)
					);
					$(".total-main-account-credit").text(
						formatCurrency(summary.total_main_account_credit)
					);
					$(".total-safe-lock-debit").text(
						formatCurrency(summary.total_safe_lock_debit)
					);
					$(".total-safe-lock-credit").text(
						formatCurrency(summary.total_safe_lock_credit)
					);
					$(".account-statement-start-time").text(
						moment(startTime).format("Do MMMM, YYYY")
					);
					$(".account-statement-end-time").text(
						moment(endTime).format("Do MMMM, YYYY")
					);
					$(".statement-customer-name").text(
						`${response.transactions.first_name} ${response.transactions.last_name}`
					);
					accountStatementModal
						.find(".statement-account-id")
						.text(response.transactions.account_id);
					accountStatementModal
						.find(".statement-customer-phone")
						.text(response.transactions.phone);
					accountStatementModal
						.find(".statement-customer-email")
						.text(response.transactions.email);
					accountStatementModal
						.find(".spender-class")
						.html(
							summary.spender_category === "METICULOUS SPENDER"
								? `<span class="badge badge-rounded badge-success">${summary.spender_category}</span>`
								: `<span class="badge badge-rounded badge-danger">${summary.spender_category}</span>`
						);

					form.get(0).reset();
					form.find("select.account_id").selectpicker("val", "");
					accountStatementGenerationModal.modal("hide");
					accountStatementModal.modal("show");
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
	});
}

function formatCurrency(amount, currencyCode = "USD", locale = "en-US") {
	const formatter = new Intl.NumberFormat(locale, {
		style: "currency",
		currency: currencyCode,
	});

	return formatter.format(amount);
}

function handlePrint(
	selector = "#printableContent",
	documentTitle = "Account Statement"
) {
	const $content = $(selector);

	if ($content.length) {
		const printArea = $content.html();
		const printWindow = window.open("", "_blank", "width=800,height=600");

		if (printWindow) {
			printWindow.document.open();

			// Clone all <link> and <style> tags from the current document
			let styles = "";
			$('link[rel="stylesheet"], style').each(function () {
				styles += this.outerHTML;
			});

			printWindow.document.write(`
				<html>
					<head>
					<title>${documentTitle}</title>
					${styles}
					<style>
						@media print {
							@page {
								margin: 0;
							}
							body {
								margin: 0 !important;
								padding: 0 !important;
							}
						}
					</style>
					</head>
					<body>
					${printArea}
					</body>
				</html>
			`);

			printWindow.document.close();

			// Wait for new window to finish rendering content
			const interval = setInterval(() => {
				if (printWindow.document.readyState === "complete") {
					clearInterval(interval);
					printWindow.focus();
					printWindow.print();
					// printWindow.close(); // optional
				}
			}, 300); // Check every 300ms
		}
	} else {
		console.warn("Print content not found.");
	}
}
