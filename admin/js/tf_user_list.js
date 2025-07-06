const iconActivated = 'icon-ok-circled tf-icon icon-green';
const iconDeactivated = 'icon-circle-empty tf-icon icon-red';
const iconDeactivatedNext = 'icon-dot-circled tf-icon icon-yellow';
const iconDeactivateInConfig = 'icon-cancel-circled tf-icon';

let loadingDeactivate = false;
$(function() {
  if (!TF_CONFIG.external_app.enabled && !TF_CONFIG.email.enabled) {
    $('#tf_config').hide();
    $('#tf_no_config').show();
  } else {
    $('#tf_icon_external_app').removeClass().addClass(TF_CONFIG.external_app.enabled ? '' : iconDeactivateInConfig);
    $('#tf_icon_email').removeClass().addClass(TF_CONFIG.email.enabled ? '' : iconDeactivateInConfig);
  }

  plugin_add_tab_in_user_modal(
    tf_str_title,
    'tf_area',
    null,
    null,
    () => {
      if (!TF_CONFIG.external_app.enabled && !TF_CONFIG.email.enabled) return;
      console.log(current_users.filter((u) => u.id == last_user_id));
      $.ajax({
        url: `ws.php?format=json&method=twofactor.status&user_id=${last_user_id}`,
        type: 'GET',
        dataType: 'json',
        success: function(res) {
          if (res.stat == 'ok') {
            tfHideError();
            if (res.result.external_app && TF_CONFIG.external_app.enabled) {
              $('#tf_icon_external_app').removeClass().addClass(iconActivated);
            } else if (TF_CONFIG.external_app.enabled) {
              $('#tf_icon_external_app').removeClass().addClass(iconDeactivated);
            }
            if (res.result.email && TF_CONFIG.email.enabled) {
              $('#tf_icon_email').removeClass().addClass(iconActivated);
            } else if (TF_CONFIG.email.enabled) {
              $('#tf_icon_email').removeClass().addClass(iconDeactivated);
            }
            deactivateEvents(last_user_id);
            return;
          } else {
            tfshowError();
          }
        },
        error: function(e) {
          tfshowError();
        }
      });
    }
  );
});

function tfshowError() {
  $('#tf_config').hide();
  $('#tf_no_config').hide();
  $('#tf_config_error').show();
}

function tfHideError() {
  $('#tf_no_config').hide();
  $('#tf_config_error').hide();
  $('#tf_config').show();
}

function tfDeactivate(user_id) {
    loadingDeactivate = true;
  $.ajax({
    url: 'ws.php?format=json&method=twofactor.adminDeactivate',
    type: 'POST',
    dataType: 'json',
    data: {
      pwg_token,
      user_id
    },
    success: function(res) {
      loadingDeactivate = false;
      if (res.stat === 'ok' && res.result) {
        if (TF_CONFIG.external_app.enabled) {
          $('#tf_icon_external_app').removeClass().addClass(iconDeactivated);
        }
        
        if (TF_CONFIG.email.enabled) {
          $('#tf_icon_email').removeClass().addClass(iconDeactivated);
        }

        $("#tf_deactivate_success").fadeIn();
        setTimeout(() => {
          $("#tf_deactivate_success").fadeOut();
        }, 2000);
        return;
      }
      $("#tf_deactivate_error").text(errorStr).fadeIn();
      setTimeout(() => {
        $("#tf_deactivate_error").fadeOut();
      }, 1000)
    },
    error: function(e) {
      loadingDeactivate = false;
      $("#tf_deactivate_error").text(e.responseJSON?.message).fadeIn();
      setTimeout(() => {
        $("#tf_deactivate_error").fadeOut();
      }, 3000)
    }
  });
}

function deactivateEvents(user_id) {
  $('#tf_btn_deactivate').off('click').on('click', function() {
    if (loadingDeactivate) return;
    tfDeactivate(user_id);
  });
}
