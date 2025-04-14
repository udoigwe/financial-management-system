$(function () {
	("use strict");

	$(document).ready(function () {
		loadAccounts();
		loadAccountOfficers();

		$("#email-form").on("submit", function (e) {
			e.preventDefault();
			sendEmail();
		});

		$("#existing-customers").on("click", ".btn-edit", function () {
			var accountID = $(this).attr("data-id");
			var editModal = $("#emailModal");
			var token = sessionStorage.getItem("token");

			blockUI();

			//fetch user details
			$.ajax({
				url: `${API_URL_ROOT}/users?call=get_accounts&account_id=${accountID}&token=${token}`,
				type: "GET",
				dataType: "json",
				headers: { "x-access-token": token },
				success: function (response) {
					if (response.error === false) {
						var accounts = response.accounts;
						const account = accounts[0];

						editModal
							.find(".modal-title")
							.text(
								`${account?.first_name} ${account?.last_name}`
							);
						editModal.find(".to").val(account?.email);
						//editModal.find(".to").val("udoigweuchechukwu@gmail.com");
						editModal.find(".user_id").val(account?.user_id);

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
				error: function (req, status, error) {
					showSimpleMessage(
						"Attention",
						"ERROR - " + req.status + " : " + req.statusText,
						"error"
					);
					unblockUI();
				},
			});
		});

		$("#account-filter-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
			var account_officer_id = form.find("#account_officer_id").val();
			var gender = form.find("#gender").val();
			var identification = form.find("#identification").val();

			blockUI();

			loadAccounts(account_officer_id, gender, identification);
		});
	});

	function sendEmail() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to send an email to this customer?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#email-form");
				var email = form.find(".to").val();
				var fields = form.find(
					"input.required, select.required, textarea.required"
				);
				var token = sessionStorage.getItem("token");
				var emailModal = $("#emailModal");

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
					url: `${API_URL_ROOT}/users?call=send_support_response_email&token=${token}`,
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
							emailModal.find(".btn-close").click();
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

	function loadAccounts(
		account_officer_id = "",
		gender = "",
		identification = ""
	) {
		var token = sessionStorage.getItem("token");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/users?call=get_accounts&account_officer_id=${account_officer_id}&gender=${gender}&identification=${identification}&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const accounts = response.accounts;
						let html = "";

						for (let i = 0; i < accounts.length; i++) {
							const account = accounts[i];

							html += `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${account.account_id}</td>
                        <td>${account.first_name}</td>
                        <td>${account.last_name}</td>
                        <td>${account.gender}</td>
                        <td>${moment(account.dob).format("Do MMMM, YYYY")}</td>
                        <td>${account.email}</td>
                        <td>${account.phone}</td>
                        <td>${account.identification}</td>
                        <td>${account.identification_number}</td>
                        <td>${account.account_officer_first_name} ${
								account.account_officer_last_name
							}</td>
                        <td>${account.account_officer_email}</td>
                        <td>${account.account_officer_phone}</td>
                        <td><span class="badge badge-rounded ${
							account.account_status === "Active"
								? "badge-success"
								: "badge-danger"
						}">${account.account_status}</span></td>
                        <td>${
							moment(account.last_seen).isValid()
								? moment(account.last_seen).format(
										"Do MMMM, YYYY hh:mm:ss"
								  )
								: "-"
						}</td>
                        <td>${moment(account.created_at).format(
							"Do MMMM, YYYY hh:mm:ss"
						)}</td>
                        <td>
                            <div class="dropdown custom-dropdown mb-0">
                                <div class="btn sharp btn-primary tp-btn" data-bs-toggle="dropdown">
                                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18px" height="18px" viewBox="0 0 24 24" version="1.1">
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <rect x="0" y="0" width="24" height="24" />
                                            <circle fill="#000000" cx="12" cy="5" r="2" />
                                            <circle fill="#000000" cx="12" cy="12" r="2" />
                                            <circle fill="#000000" cx="12" cy="19" r="2" />
                                        </g>
                                    </svg>
                                </div>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item btn-edit" data-id="${
										account.account_id
									}" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#emailModal">Send Email</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
						}
						$("#existing-customers tbody").html(html);
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

	function loadAccountOfficers() {
		var token = sessionStorage.getItem("token");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/users?call=get_all_users&role=Account Officer&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const users = response.users;
						let html = `<option value="">Choose...</option>`;

						for (let i = 0; i < users.length; i++) {
							const user = users[i];

							html += `
                                <option value="${user.user_id}">${user.first_name} ${user.last_name}</option>
							`;
						}
						$("#account_officer_id").html(html);
						$("#account_officer_id").selectPicker("refresh");
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
