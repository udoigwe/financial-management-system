$(function () {
	("use strict");

	$(document).ready(function () {
		loadUsers();

		$("#new-user-form").on("submit", function (e) {
			e.preventDefault();
			newUser();
		});

		$("#update-user-form").on("submit", function (e) {
			e.preventDefault();
			updateUserAccount();
		});

		$("#existing-users").on("click", ".btn-edit", function () {
			var userID = $(this).attr("data-id");
			var editModal = $("#editUserModal");
			var token = sessionStorage.getItem("token");

			blockUI();

			//fetch user details
			$.ajax({
				url: `${API_URL_ROOT}/users?call=get_all_users&user_id=${userID}&token=${token}`,
				type: "GET",
				dataType: "json",
				headers: { "x-access-token": token },
				success: function (response) {
					if (response.error == false) {
						var users = response.users;
						const user = users[0];

						editModal
							.find(".modal-title")
							.text(`${user.first_name} ${user.last_name}`);
						editModal.find(".first_name").val(user.first_name);
						editModal.find(".last_name").val(user.last_name);
						editModal.find(".email").val(user.email);
						editModal.find(".phone").val(user.phone);
						editModal
							.find("#gender")
							.selectpicker("val", user.gender);
						editModal.find(".address").val(user.address);
						editModal.find(".dob").val(user.dob);
						editModal
							.find("#identification")
							.selectpicker("val", user.identification);
						editModal
							.find(".identification_number")
							.val(user.identification_number);
						editModal
							.find("#account_status")
							.selectpicker("val", user.account_status);
						editModal.find(".user_id").val(user.user_id);

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

	function newUser() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to create this user?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#new-user-form");
				var email = form.find(".email").val();
				var password = form.find(".password").val();
				var repassword = form.find(".re-password").val();
				var role = form.find("select.role").val();
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

				if (password !== repassword) {
					unblockUI();
					showSimpleMessage(
						"Attention",
						"Passwords don't match",
						"error"
					);
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/users?call=create_user&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							loadUnreadMessages();
							loadUsers();
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

	function loadUsers() {
		var token = sessionStorage.getItem("token");

		blockUI();

		if (token) {
			$.ajax({
				type: "GET",
				url: `${API_URL_ROOT}/users?call=get_all_users&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const users = response.users;
						let html = "";

						for (let i = 0; i < users.length; i++) {
							const user = users[i];

							html += `
								<tr>
                                    <td>${i + 1}</td>
                                    <td>${user.first_name}</td>
                                    <td>${user.last_name}</td>
                                    <td>${user.gender}</td>
                                    <td>${moment(user.dob).format(
										"Do MMMM, YYYY"
									)}</td>
                                    <td>${user.email}</td>
                                    <td>${user.phone}</td>
                                    <td>${user.identification}</td>
                                    <td>${user.identification_number}</td>
                                    <td><span class="badge badge-rounded ${
										user.role === "Admin"
											? "badge-warning"
											: user.role === "Account Officer"
											? "badge-info"
											: "badge-default"
									}">${user.role}</span></td>
                                    <td><span class="badge badge-rounded ${
										user.account_status === "Active"
											? "badge-success"
											: "badge-danger"
									}">${user.account_status}</span></td>
                                    <td>${
										moment(user.last_seen).isValid()
											? moment(user.last_seen).format(
													"Do MMMM, YYYY hh:mm:ss"
											  )
											: "-"
									}</td>
                                    <td>${moment(user.created_at).format(
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
													user.user_id
												}" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</a>
                                            </div>
                                        </div>
                                    </td>
								</tr>
							`;
						}
						$("#existing-users tbody").html(html);
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

	function updateUserAccount() {
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
				var form = $("#update-user-form");
				var email = form.find(".email").val();
				var userUpdateModal = $("#editUserModal");
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
					url: `${API_URL_ROOT}/users?call=user_update&token=${token}`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
							loadUnreadMessages();
							loadUsers();
							userUpdateModal.find(".btn-close").click();
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
});
