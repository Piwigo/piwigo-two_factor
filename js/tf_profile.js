let setupExternalAppSettings = false;
let timeBeforeResent = 30;
let timeoutBeforeResent;
let canSentMail = true;
$(function () {
  
  $('#save_account').on('click.tfplugin', function() {
    setTimeout(function() {
      if (!user?.email) {
        $('#tf_missing_mail').show();
      } else {
        $('#tf_missing_mail').hide();
      }
    }, 500);
  });

  if (!user?.email) {
    $('#tf_missing_mail').show();
  }

  //for debug
  //console.log(window.tf_twofactor);
  if (window.tf_twofactor.enabled.email) {
    // toggle email on icon setting click
    eventEmailAlreadySetup();
  } else {
    // toggle email on switch label click
    $('#tf_mail').on('change', toggleEmailSetup);
  }

  if (window.tf_twofactor.enabled.external_app) {
    eventExternalAlreadySetup();
  } else {
    $('#tf_auth_app').on('change', toggleAppSetup);
  }
});

function openCollapse(selector, reset = false) {
  if (!selector) return;

  const checkbox = $(`#${selector}`);
  const isOpen = checkbox.data('open');
  const dataCollapse = checkbox.data('collapse');
  const collapse = $(`#${dataCollapse}`);

  if (!isOpen || reset) {
    // close other slider
    collapse.get(0).style.maxHeight = collapse.get(0).scrollHeight + 'px';
    collapse.removeClass('close');
  } else {
    collapse.get(0).style.maxHeight = '0px';
    collapse.addClass('close');
  }

  if (!reset) {
    checkbox.data('open', !isOpen)
  }
  resetTFSection(reset);
}

function closeCollapse(selector) {
  const checkbox = $(`#${selector}`);
  const dataCollapse = checkbox.data('collapse');
  const collapse = $(`#${dataCollapse}`);
  
  collapse.get(0).style.maxHeight = '0px';
  collapse.addClass('close');
  checkbox.data('open', false);
}

function resetTFSection(reset = false) {
  const id = $('#tf_container').data('tf_id');
  const displayId = `${id}-display`;
  const collapse = $(`#${displayId}`);
  resetSection(displayId, false, true);
  if (reset) return;
  setTimeout(() => {
    const el = collapse.get(0);
    el.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }, 200);
}

function eventSetupEmail() {
  $('#tf_send_email, #tf_send_again').off('click').on('click', function() {
    const email = $('#tf_email_orig').val();
    const emailConf = $('#tf_conf_email').val();

    if (!emailConf) {
      $('#tf_email_error_text').text(str_must_not_empty);
      $('#tf_email_error').show();
      return;
    }

    if (email !== emailConf) {
      $('#tf_email_error_text').text(window.tf_twofactor.str_email_dont_match);
      $('#tf_email_error').show();
      return;
    }
    
    // send code by mail
    if (canSentMail) {
      setupEmail(email);
    } else {
      const text = sprintf(window.tf_twofactor.str_email_waint_until, timeBeforeResent);
      pwgToaster({ text: text, icon: 'error' });
    }
  });
}

function eventFinalSetupEmail() {
  $('#tf_send_email_code').off('click').on('click', function () {
    const code = $('#tf_totp_email').val();

    if (!code) {
      $('#tf_email_totp_error').show();
      return;
    }

    setupEmail(null, code);
  });

  $('#tf_send_email_cancel').off('click').on('click', function() {
    closeCollapse('tf_mail');
    clearEventSetupEmail();
  });
}

function eventEmailAlreadySetup() {
  clearEventSetupEmail();
  $('#tf_mail').off('change');

  $('#tf_email_setting').show().on('click', toggleEmailSetup);
  $('#tf_mail').prop('checked', true);
}

function clearEventSetupEmail() {
  $('#tf_send_email, #tf_send_again').off('click');
  $('#tf_send_email_code').off('click');

  $('#tf_send_email').show();
  $('#tf_verify_email').fadeOut();
  $('#tf_conf_email').val('');
  $('#tf_email_error').hide();
  $('#tf_totp_email').val('');
}

function toggleEmailSetup() {
  if (user?.email) {
    $('#tf_email_orig').val(user.email);
    openCollapse('tf_mail');
    const open = $('#tf_mail').data('open');
    if (open) {
      eventSetupEmail();
    } else {
      clearEventSetupEmail();
    }
  } else {
    $('#tf_mail').prop('checked', false);
    showErrorEmailSetup();
  }
}

function showErrorEmailSetup() {
  setTimeout(() => {
    $('#tf_mail').prop('checked', false);
  }, 300);
  pwgToaster({ text: window.tf_twofactor.str_add_email_before, icon: 'error' });
}

function resentEmail() {
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
  const text = sprintf(window.tf_twofactor.str_send_again_in, timeBeforeResent);
  $('#tf_send_again_in').text(text).show();
  timeBeforeResent--;

  timeoutBeforeResent = setTimeout(resentEmail, 1000);
}

function setupEmail(email = null, code = null) {
  $('#tf_send_email').off('click');
  $('#tf_send_email_code').off('click');
  let data = {
    pwg_token: PWG_TOKEN
  }

  if (email) {
    data.email = email
  } else if (code) {
    data.code = code
  }

  $.ajax({
    url: 'ws.php?format=json&method=twofactor.setup.email',
    type: "POST",
    dataType: 'json',
    data: data,
    success: function (res) {
      if ('ok' == res.stat) {
        if (email) {
          $('#tf_send_email').hide();
          $('#tf_verify_email').show();
          eventFinalSetupEmail();
          openCollapse('tf_mail', true);
          timeBeforeResent = 30;
          resentEmail();
        } else if (code) {
          if (!res.result) {
            pwgToaster({ text: window.tf_twofactor.str_invalid_code, icon: 'error' });
            eventFinalSetupEmail();
            return;
          }
            pwgToaster({ text: window.tf_twofactor.str_email_setup_success, icon: 'success' });
          closeCollapse('tf_mail');
          eventEmailAlreadySetup();
        }
        return;
      }
      pwgToaster({ text: res.message ?? str_handle_error, icon: 'error' });
      eventSetupEmail();
      eventFinalSetupEmail();
    },
    error: function(e) {
      pwgToaster({ text: e.responseJSON?.message ?? str_handle_error, icon: 'error' });
      eventSetupEmail();
      eventFinalSetupEmail();
    }
  })
}

function toggleAppSetup() {
  openCollapse('tf_auth_app');
  
  const open = $('#tf_auth_app').data('open');
  if (open) {
    setupExternalApp();
  } else {
    setTimeout(() => {
      $('.tf-loading-qrcode').show();
      $('#tf_img_qrcode').hide();
    }, 200);
  }

  $('#tf_get_setup_key').off('click').on('click', function() {
    $('#tf_get_setup_key_input').show(() => {
      openCollapse('tf_auth_app', true);
    });
  });

  $('#tf_copy_recovery_codes').off('click').on('click', function() {
    if (!setupExternalAppSettings) return;

    const stringCode = setupExternalAppSettings.recovery_codes.join(" ");
    copyToClipboard(stringCode, window.tf_twofactor.str_code_recovery_copy);
  });
}

function eventSetupExtenalApp() {
  $('#tf_send_totp_code').off('click').on('click', function() {
    const code = $('#tf_app_totp').val();

    if (!code) {
      $('#tf_send_totp_error').show();
      return;
    }

    $('#tf_send_totp_code').off('click');
    setupExternalApp(code);
  });
}

function eventFinalExternalApp() {
  setupExternalAppSettings.recovery_codes.forEach((code, i) => {
    $('#tf_app_recovery_code').append(`<span>${code}</span>`);
  })

  $('#tf_app_send').hide();
  $('#tf_app_recovery_codes').show();
  // openCollapse('tf_auth_app', true);

  $('#tf_app_done').off('click').on('click', function () {
    pwgToaster({ text: window.tf_twofactor.str_external_setup_success, icon: 'success' });
    closeCollapse('tf_auth_app');
    eventExternalAlreadySetup();
  });
}

function eventExternalAlreadySetup() {
  $('#tf_external_app_setting').show().on('click', toggleAppSetup);
  $('#tf_auth_app').prop('checked', true);
}

function setupExternalApp(code = null) {
  if (setupExternalAppSettings && !code) {
    $('.tf-loading-qrcode').hide(() => {
      $('#tf_img_qrcode').attr('src', setupExternalAppSettings.qrcode).show();
      $('#tf_setup_key').val(setupExternalAppSettings.tmp_secret);
    });
    return;
  }

  let data = {
    pwg_token: PWG_TOKEN
  }
  if (code) {
    data.code = code
  }

  $.ajax({
    url: 'ws.php?format=json&method=twofactor.setup.externalApp',
    type: "POST",
    dataType: 'json',
    data: data,
    success: function(res) {
      if ('ok' == res.stat) {
        if (code) {
           if (!res.result) {
            pwgToaster({ text: window.tf_twofactor.str_invalid_code, icon: 'error' });
            return;
          }
          eventFinalExternalApp();
          return;
        }

        $('.tf-loading-qrcode').hide(() => {
          $('#tf_img_qrcode').attr('src', res.result.qrcode).show();
          $('#tf_setup_key').val(res.result.tmp_secret);
        });
        setupExternalAppSettings = {};
        setupExternalAppSettings.qrcode = res.result.qrcode;
        setupExternalAppSettings.tmp_secret = res.result.tmp_secret;
        setupExternalAppSettings.recovery_codes = res.result.recovery_codes;

        return;
      }
      pwgToaster({ text: res.message ?? str_handle_error, icon: 'error' });
    },
    error: function(e) {
      pwgToaster({ text: e.responseJSON?.message ?? str_handle_error, icon: 'error' });
    }
  });

  eventSetupExtenalApp();
}