const maxAttempts = $('#max_attempts');
const lockoutDuration = $('#lockout_duration');
const externalApp = $('#external_app');
const tfEmail = $('#tf_email');
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
  tfEmail.prop('checked', config.email.enabled);
}

function getConfig() {
  const config = {
    general: {
      max_attempts: Number(maxAttempts.val()),
      lockout_duration: Number(lockoutDuration.val())
    },
    external_app: {
      enabled: externalApp.prop('checked'),
    },
    email: {
      enabled: tfEmail.prop('checked'),
    },
  }

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
    toggleChanges();
  });

  tfEmail.off('change').on('change', function () {
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