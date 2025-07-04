const maxAttempts = $('#max_attempts');
const lockoutDuration = $('#lockout_duration');
const externalApp = $('#external_app');
const plusExternalApp = $('#collapse_external_app');
const externalAppCodeLifetime = $('#external_app_code_lifetime')
const externalAppTotpWindow = $('#external_app_totp_window');
const tfEmail = $('#tf_email');
const plusEmail = $('#collapse_email');
const emailCodeLifetime = $('#email_code_lifetime');
const emailTotpWindow = $('#email_totp_window');
const emailSetupDelay = $('#email_setup_delay');
const emailVerifyDelay = $('#email_verify_delay');
const btnSaveSettings = $('#tf_save_settings');
const unsavedChanges = $('#tf_unsaved_changes');
const tfsaveChanges = $('#tf_saving_changes');
const tferrorsChanges = $('#tf_error_changes');

let loadingSaveSettings = false;
let timeout;

$(function () {
  setEvents();
  fillConfig(TF_CONFIG);
})

function fillConfig(config) {
  maxAttempts.val(config.general.max_attempts);
  lockoutDuration.val(config.general.lockout_duration);

  externalApp.prop('checked', config.external_app.enabled);
  if (config.external_app.enabled) {
    plusExternalApp.show();
  } else {
    plusExternalApp.hide();
  }
  externalAppCodeLifetime.val(config.external_app.code_lifetime);
  externalAppTotpWindow.val(config.external_app.totp_window);

  tfEmail.prop('checked', config.email.enabled);
  if (config.email.enabled) {
    plusEmail.show();
  } else {
    plusEmail.hide();
  }
  emailCodeLifetime.val(config.email.code_lifetime);
  emailTotpWindow.val(config.email.totp_window);
  emailSetupDelay.val(config.email.setup_delay);
  emailVerifyDelay.val(config.email.verify_delay);
}

function getConfig() {
  const config = {
    general: {
      max_attempts: Number(maxAttempts.val()),
      lockout_duration: Number(lockoutDuration.val())
    },
    external_app: {
      enabled: externalApp.prop('checked'),
      totp_window: Number(externalAppTotpWindow.val()),
      code_lifetime: Number(externalAppCodeLifetime.val())
    },
    email: {
      enabled: tfEmail.prop('checked'),
      totp_window: Number(emailTotpWindow.val()),
      code_lifetime: Number(emailCodeLifetime.val()),
      setup_delay: Number(emailSetupDelay.val()),
      verify_delay: Number(emailVerifyDelay.val())
    },
  }

  // const config = {
  //   general: {
  //     max_attempts: maxAttempts.val(),
  //     lockout_duration: lockoutDuration.val()
  //   },
  //   external_app: {
  //     enabled: externalApp.prop('checked'),
  //     totp_window: externalAppTotpWindow.val(),
  //     code_lifetime: externalAppCodeLifetime.val()
  //   },
  //   email: {
  //     enabled: tfEmail.prop('checked'),
  //     totp_window: emailTotpWindow.val(),
  //     code_lifetime: emailCodeLifetime.val(),
  //     setup_delay: emailSetupDelay.val(),
  //     verify_delay: emailVerifyDelay.val()
  //   },
  // }

  return config;
}

function setEvents() {
  $('.tf-container input[type="number"]').off('input').on('input', function () {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      toggleChanges();
    }, 100);
  });

  externalApp.off('change').on('change', function () {
    const isChecked = $(this).is(':checked');
    if (isChecked) {
      plusExternalApp.fadeIn(100);
      plusExternalApp.get(0).scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    } else {
      plusExternalApp.fadeOut(100);
    }
    toggleChanges();
  });

  tfEmail.off('change').on('change', function () {
    const isChecked = $(this).is(':checked');
    if (isChecked) {
      plusEmail.fadeIn(100);
      plusEmail.get(0).scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    } else {
      plusEmail.fadeOut(100);
    }
    toggleChanges();
  });

  btnSaveSettings.off('click').on('click', function () {
    if (loadingSaveSettings) return;
    const config = getConfig();
    const match = deepEqual(TF_CONFIG, config)
    if (match) return;

    sendConfig(config);
  });
}

function clearEvents() {
  $('.tf-container input[type="number"]').off('input');
  externalApp.off('change');
  tfEmail.off('change');
  btnSaveSettings.off('click');
}

function deepEqual(a, b) {
  if (a === b) return true;
  if (typeof a != "object" || typeof b != "object" || a == null || b == null) return false;

  let keysA = Object.keys(a), keysB = Object.keys(b);
  if (keysA.length !== keysB.length) return false;

  for (let key of keysA) {
    if (!keysB.includes(key) || !deepEqual(a[key], b[key])) return false;
  }
  return true;
}

function toggleChanges() {
  const match = deepEqual(getConfig(), TF_CONFIG);
  if (!match) {
    tfsaveChanges.hide();
    tferrorsChanges.hide();
    unsavedChanges.fadeIn();
  } else {
    unsavedChanges.fadeOut();
  }
}

function sendConfig(config) {
  $.ajax({
    url: 'ws.php?format=json&method=twofactor.setConfig',
    type: 'POST',
    dataType: 'json',
    data: {
      config,
      pwg_token: PWG_TOKEN
    },
    success: function(res) {
      unsavedChanges.hide();
      tferrorsChanges.hide();
      if (res.stat === 'ok') {
        tfsaveChanges.fadeIn();
        TF_CONFIG = {...res.result.configuration};
        return;
      }
      tferrorsChanges.fadeIn();
      console.log(res);
    },
    error: function(e) {
      unsavedChanges.hide();
      tferrorsChanges.hide();
      tferrorsChanges.fadeIn();
    }
  });
}