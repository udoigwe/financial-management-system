$(function () {
	("use strict");

	$(document).ready(function () {
		loadDashboard();
	});

	function loadDashboard() {
		const token = sessionStorage.getItem("token");
		$.ajax({
			type: "GET",
			url: `${API_URL_ROOT}/dashboards?call=account_officer_dashboard&token=${token}`,
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
					$(".customer-count").text(
						formatNumber(dashboard.customer_count)
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
});
