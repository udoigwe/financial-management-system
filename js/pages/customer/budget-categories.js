$(function () {
	("use strict");

	$(document).ready(function () {
		loadBudgetCategories();
		$("#choose-from-existing").change(function () {
			var selectedOption = $(this).val();

			if (selectedOption === "Yes") {
				$("#existingCategoryWrapper").slideDown();
				$("#newCategoryWrapper").slideUp();
				$("#existingCategory").prop("required", true);
				$("#newCategory").prop("required", false).val("");
				$("#existingCategory").addClass("required");
				$("#newCategory").removeClass("required");
				$("#existingCategory").attr("name", "category_name");
				$("#newCategory").removeAttr("name");
			} else if (selectedOption === "No") {
				$("#newCategoryWrapper").slideDown();
				$("#existingCategoryWrapper").slideUp();
				$("#newCategory").prop("required", true);
				$("#existingCategory").prop("required", false).val("");
				$("#newCategory").addClass("required");
				$("#existingCategory").removeClass("required");
				$("#newCategory").attr("name", "category_name");
				$("#existingCategory").removeAttr("name");
			} else {
				$("#existingCategoryWrapper, #newCategoryWrapper").slideUp();
				$("#existingCategory, #newCategory")
					.prop("required", false)
					.val("");
				$("#existingCategory, #newCategory").addClass("required");
				$("#existingCategory, #newCategory").attr(
					"name",
					"category_name"
				);
			}
		});

		$("#new-budget-category-form").on("submit", function (e) {
			e.preventDefault();
			createNewBudgetCategory();
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
	});

	function createNewBudgetCategory() {
		Swal.fire({
			title: "Attention",
			text: "Are you sure you want to create this budget category?",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes!",
			cancelButtonText: "No!",
		}).then(function (result) {
			if (result.value) {
				var form = $("#new-budget-category-form");
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
				var addCategoryModal = $("#addCategoryModal");

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
					url: `${API_URL_ROOT}/budget?call=create&token=${token}`,
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
							addCategoryModal.find(".btn-close").click();
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
				var editModal = $("#editCategoryModal");

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
							editModal.find(".btn-close").click();
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
				url: `${API_URL_ROOT}/budget?call=get&token=${token}`,
				dataType: "json",
				success: function (response) {
					if (response.error === false) {
						unblockUI();

						const categories = response.categories;
						let html = "";

						for (let i = 0; i < categories.length; i++) {
							const category = categories[i];

							html += `
                                <tr>
                                    <td>${i + 1}</td>
                                    <td>${category.account_id}</td>
                                    <td><span class="badge badge-rounded" style="background-color: ${
										category.color_code
									}">${category.category_name}</span></td>
                                    <td style="font-weight: bolder; color: black">$${
										category.budget_limit
									}</td>
                                    <td>${moment(
										category.budget_limit_start_time
									).format("Do MMMM, YYYY HH:mm:ss")}</td>
                                    <td>${moment(
										category.budget_limit_end_time
									).format("Do MMMM, YYYY HH:mm:ss")}</td>
                                    <td><span class="badge badge-rounded ${
										category.budget_category_status ===
										"Active"
											? "badge-success"
											: "badge-danger"
									}">${
								category.budget_category_status
							}</span></td>
                                    <td>${moment(category.created_at).format(
										"Do MMMM, YYYY HH:mm:ss"
									)}</td>
                                    <td>${
										moment(category.edited_at).isValid()
											? moment(category.edited_at).format(
													"Do MMMM, YYYY HH:mm:ss"
											  )
											: "-"
									}</td>
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
													category.category_id
												}" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editCategoryModal">Edit</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;
						}
						$(".my-budget-categories tbody").html(html);
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
