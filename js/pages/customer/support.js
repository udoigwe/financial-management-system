$(function () {
	("use strict");

	$(document).ready(function () {
		const token = sessionStorage.getItem("token");
		//const to = payloadClaim(token, "account_officer_email");
		const to = "udoigweuchechukwu@gmail.com";
		$("#support-form .to").val(to);

		$("#support-form").on("submit", function (e) {
			e.preventDefault();
			sendSupportMail();
		});

		$(".btn-descard").on("click", function (e) {
			$("#support-form").find(".subject").val("");
			$("#support-form").find(".message").val("");
		});
	});

	function sendSupportMail() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to send this mail?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#support-form");
				var to = form.find(".to").val();
				var token = sessionStorage.getItem("token");
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

				if (!validateEmail(to)) {
					//alert("All fields are required");
					showSimpleMessage(
						"Attention",
						"Please provide a valid email address",
						"error"
					);
					//alert("Please provide a valid email address");
					unblockUI();
					return false;
				}

				$.ajax({
					type: "POST",
					url: `${API_URL_ROOT}/resources?call=support&token=${token}`,
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
							form.find(".subject").val("");
							form.find(".message").val("");

							loadUnreadMessages();
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
});
