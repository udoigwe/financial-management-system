$(function () {
  ("use strict");

  $(document).ready(function () {
    loadLoggedUser();

    $("#account-update-form").on("submit", function (e) {
      e.preventDefault();
      updateAccount();
    });

    $("#password-update-form").on("submit", function (e) {
      e.preventDefault();
      updatePassword();
    });
  });

  function loadLoggedUser() {
    const token = sessionStorage.getItem("token");
    $(".logged-user-name").text(
      `${payloadClaim(token, "first_name")} ${payloadClaim(token, "last_name")}`
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
    $("#account-update-form").find(".email").val(payloadClaim(token, "email"));
    $("#account-update-form").find(".phone").val(payloadClaim(token, "phone"));
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

              showSimpleMessage("Success", response.message, "success");
              loadUnreadMessages();
              unblockUI();
            } else {
              showSimpleMessage("Attention", response.message, "error");
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
          showSimpleMessage("Attention", "Passwords don't match", "error");
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
              showSimpleMessage("Success", response.message, "success");
              form.get(0).reset();
              loadUnreadMessages();
              unblockUI();
            } else {
              showSimpleMessage("Attention", response.message, "error");
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
});
