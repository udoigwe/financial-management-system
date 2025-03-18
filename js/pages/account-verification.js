$(function () {
  ("use strict");

  $(document).ready(function ($) {
    accountVerification();
  });

  const params = new URLSearchParams(window.location.search);

  function accountVerification() {
    const email = params.get("email"); // Retrieves the value of 'name'
    const salt = params.get("salt"); // Retrieves the value of 'name'

    blockUI();

    if (!email) {
      unblockUI();
      $(".verification-message").text("Email is required");
      $(".nav-btn").css({ display: "block" });

      return false;
    }

    if (!salt) {
      unblockUI();
      $(".verification-message").text("Salt is required");
      $(".nav-btn").css({ display: "block" });

      return false;
    }

    $.ajax({
      type: "POST",
      url: `${API_URL_ROOT}/auth?call=account_verification`,
      data: { email, salt },
      dataType: "json",
      /* contentType: "application/json", */
      success: function (response) {
        unblockUI();
        $(".verification-message").text(response.message);
        $(".nav-btn").css({ display: "block" });
      },
      error: function (req, status, err) {
        //showSimpleMessage("Attention", req.statusText, "error");
        alert(req.statusText);
        unblockUI();
      },
    });
  }
});
