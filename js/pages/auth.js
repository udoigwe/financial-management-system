$(function () {
	"use strict";

	$(document).ready(function ($) {
		//remember me
		//rememberMe();
		//login
		login();
		//register
		//load countries
		//loadCountries();
		//recover password
		recoverPassword();

		//register
		$("#sign-up-form").on("submit", function (e) {
			e.preventDefault(); //prevent default form submission event
			register(); //Internal function for form submission
		});
	});

	function login() {
		$("#login-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
			var email = $("#email").val();
			var password = $("#password").val();
			var fields = form.find("input.required, select.required");

			blockUI();

			for (var i = 0; i < fields.length; i++) {
				if (fields[i].value === "") {
					/*alert(fields[i].id)*/
					unblockUI();
					showSimpleMessage(
						"Attention",
						`${fields[i].name} is required`,
						"error"
					);
					//alert(`${fields[i].name} is required`);
					$("#" + fields[i].id).focus();
					return false;
				}
			}

			if (!validateEmail(email)) {
				//alert("All fields are required");
				showSimpleMessage(
					"Attention",
					"Please provide a valid email address",
					"error"
				);
				//alert("Please provide a valid email address");
				unblockUI();
				return false;
			} else {
				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=sign_in`,
					data: form.serialize(),
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							var token = response.token; //generated access token from request
							sessionStorage.removeItem("token");
							sessionStorage.setItem("token", token); //set access token

							if (payloadClaim(token, "role") === "Customer") {
								window.location.href = "customer/";
								return false;
							}

							if (payloadClaim(token, "role") === "Admin") {
								window.location.href = "admin/";
								return false;
							}

							if (
								payloadClaim(token, "role") ===
								"Account Officer"
							) {
								window.location.href = "ao/";
								return false;
							}
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
						//alert(req.statusText);
						unblockUI();
					},
				});
			}
		});
	}

	function register() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure all details provided are accurate?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#sign-up-form");
				var email = form.find("#email").val();
				var password = form.find("#password").val();
				var repassword = form.find("#confirm-password").val();
				var fields = form.find(
					"input.required, select.required, textarea.required"
				);

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
					//alert("Passwords don't match");
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=sign_up`,
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

							setTimeout(() => {
								window.location.href = "/finhive";
							}, 1000);
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
						//alert(req.statusText);
						unblockUI();
					},
				});
			} else {
				showSimpleMessage("Canceled", "Process Abborted", "error");
			}
		});
	}

	function recoverPassword() {
		$("#recovery-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
			var email = $("#email").val();
			var fields = form.find("input.required, select.required");

			blockUI();

			for (var i = 0; i < fields.length; i++) {
				if (fields[i].value === "") {
					/*alert(fields[i].id)*/
					unblockUI();
					showSimpleMessage(
						"Attention",
						`${fields[i].name} is required`,
						"error"
					);
					$("#" + fields[i].id).focus();
					return false;
				}
			}

			if (!validateEmail(email)) {
				//alert("All fields are required");
				showSimpleMessage(
					"Attention",
					"Please provide a valid email address",
					"error"
				);
				unblockUI();
				return false;
			} else {
				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/auth?call=send_recovery_email`,
					data: { email },
					dataType: "json",
					success: function (response) {
						if (response.error === false) {
							unblockUI();
							form.get(0).reset();
							//alert(response.message);
							showSimpleMessage(
								"Success",
								response.message,
								"success"
							);
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
						//alert(req.statusText);
						unblockUI();
					},
				});
			}
		});
	}
});
