window.toasterOnStart = window.toasterOnStart || [];
let loadingMail = false;
let timeBeforeResent = 60;
let timeoutBeforeResent;
let canSentMail = true;
let tf_pwg_token;
$(function() {
  tf_pwg_token = $('#pwg_token').val();
  $('#tf_validate_email, #tf_send_again').on('click', function() {
    $('#tf_use_method').text(str_use_email);
    $('#method_totp').val('email');

    if (loadingMail) return;
    if (canSentMail) {
      tfsendMail();
    } else {
      const text = sprintf(str_email_wait_until, timeBeforeResent);
      pwgToaster({ text: text, icon: 'error' });
    }
  });
  $('#tf_validate_app').on('click', function() {
    $('#tf_use_method').text(str_use_app);
    $('#method_totp').val('external_app');
    tfNextStep('external_app');
  });

  const startOn = window.tfStartOn ?? false;
  if ('email' == startOn) {
    // $('#tf_validate_email').trigger('click');
    $('#tf_use_method').text(str_use_email);
    $('#method_totp').val('email');
    $('#tf_send_again_in').hide();
    tfNextStep('email');
  } else if ('external_app' == startOn) {
    $('#tf_validate_app').trigger('click');
  } else {
    $('#tf_select_method').show();
  }

  $('#tf_go_back').on('click', function() {
    tfResetStep();
  });

  window.toasterOnStart.forEach((t, i) => {
    setTimeout(() => {
      pwgToaster({ text: t.text, icon: t.icon});
    }, 200)
  })

  const inputs = $('#tf_verify_code .input-container input');
  inputs.each(function(i, input) {
    // write code
    $(input).on('input', function(e) {
      let val = $(input).val();
      val = val.replace(/[^0-9]/g, '');
      $(input).val(val);

      if (val.length === 1 && i + 1 < inputs.length) {
        $(inputs[i + 1]).focus();
      }
      updateInput();
    });

    // paste code
    $(input).on('paste', function(e) {
      e.preventDefault();

      const clipboardData = (e.originalEvent || e).clipboardData || window.clipboardData;
      if (!clipboardData) return;

      let pasted = clipboardData.getData('text');
      pasted = pasted.replace(/[^0-9]/g, '');
      if (!pasted) return;

      const chars = pasted.split('');
      let j = i;

      chars.forEach(char => {
        if (j < inputs.length) {
          $(inputs[j]).val(char);
          j++;
        }
      });

      if (j <= inputs.length) {
        $(inputs[j - 1]).focus();
      }

      updateInput();
    });

    // navigation
    $(input).on('keydown', function(e) {
      // go back
      if ((e.key === "Backspace" || e.key === "Delete" || e.key === "ArrowLeft") && !$(input).val() && i > 0) {
        $(inputs[i - 1]).focus();
      }

      // go forward
      if (e.key === "ArrowRight" && !$(input).val() && i+1 < inputs.length) {
        $(inputs[i + 1]).focus();
      }
    });
  });
});

function updateInput() {
  let value = ''
  $('#tf_verify_code .otp-input').each((i, input) => {
    value += $(input).val().length == 1 ? $(input).val() : ''
  });
  $('#full_totp').val(value);
  if (value.length == 6) {
    $('#tf_verify').trigger('click');
  }
}

function tfNextStep(method) {
  $('#tf_select_method').fadeOut(200, () => {
    if (method === 'email') {
      $('#tf_totp_external_app').hide();
      $('#tf_totp_email, #tf_contact_admin').show();
    } else {
      $('#tf_totp_email, #tf_contact_admin').hide();
      $('#tf_totp_external_app').show();
      tfEventRecoveryCode();
    }

    $('#tf_select_desc').hide();
    $('#tf_verify_code').show();
    $('#pwg_token').val(tf_pwg_token);
    $('#otp_1').trigger('focus');
  });
}

function tfResetStep() {
  $('#tf_verify_code').fadeOut(200, () => {
    $('#tf_totp_external_app, #tf_recovery_code, #tf_verify_code').hide();
    $('#tf_select_desc, #tf_select_method, #tf_contact_admin').show();
    $('#tf_verify_code input').val('');
    $('#full_totp').val('');
    tfClearEventRecoveryCode();
  });
}

function tfsendMail() {
  $('#tf_loading_email').show();
  loadingMail = true;
  $.ajax({
    url: 'ws.php?format=json&method=twofactor.sendEmail',
    type: 'POST',
    dataType: 'json',
    data: {
      tf_send_mail: true,
      pwg_token: tf_pwg_token
    },
    success: function(res) {
      $('#tf_loading_email').hide();
      loadingMail = false;

      if (res) {
        tfNextStep('email');
        timeBeforeResent = 60;
        tfResentEmail();
        return;
      }
      pwgToaster({ text: res.message ?? str_handle_error, icon: 'error' });
    },
    error: function(e) {
      $('#tf_loading_email').hide();
      loadingMail = false;
      pwgToaster({ text: e.responseJSON?.message ?? str_handle_error, icon: 'error' });
    }
  })
}

function tfResentEmail() {
  clearTimeout(timeoutBeforeResent);

  if (timeBeforeResent === 0) {
    canSentMail = true;
    $('#tf_send_again_in').hide(() => {
      $('#tf_send_again').show();
    });
    return;
  }

  canSentMail = false;
  $('#tf_send_again').hide();
  const text = sprintf(str_send_again_in, timeBeforeResent);
  $('#tf_send_again_in').text(text).show();
  timeBeforeResent--;

  timeoutBeforeResent = setTimeout(tfResentEmail, 1000);
}

function tfEventRecoveryCode() {
  $('#tf_totp_external_app u').off('click').on('click', function() {
    $('#tf_verify_code').hide();
    // $('#pwg_token_recovery').val(tf_pwg_token);
    $('#tf_recovery_input').val('');
    $('#tf_recovery_code').show();
  });

  $('#tf_reset_recovery').off('click').on('click', function() {
    tfResetStep();
  });
}

function tfClearEventRecoveryCode() {
  $('#tf_totp_external_app u').off('click');
}