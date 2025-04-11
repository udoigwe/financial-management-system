$(function () {
	("use strict");

	$(document).ready(function ($) {
		pageAuth();
		passwordRecovery();
	});

	const params = new URLSearchParams(window.location.search);

	function pageAuth() {
		const email = params.get("email"); // Retrieves the value of 'name'
		const salt = params.get("salt"); // Retrieves the value of 'name'

		blockUI();

		if (!email) {
			unblockUI();
			window.location.href = "index";

			return false;
		}

		if (!salt) {
			unblockUI();
			window.location.href = "index";

			return false;
		}

		unblockUI();
		$("#email").val(email);
		$("#salt").val(salt);
	}

	function passwordRecovery() {
		$("#password-reset-form").on("submit", function (e) {
			e.preventDefault();
			var form = $(this);
			var email = form.find("#email").val();
			var salt = form.find("#salt").val();
			var new_pass = form.find("#new-pass").val();
			var repassword = form.find("#re-pass").val();
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

			if (new_pass !== repassword) {
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
				url: `${API_URL_ROOT}/auth?call=password_recovery`,
				data: form.serialize(),
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();
						showSimpleMessage(
							"Success",
							response.message,
							"success"
						);
						form.get(0).reset();
						//alert(response.message);
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
		});
	}
});
