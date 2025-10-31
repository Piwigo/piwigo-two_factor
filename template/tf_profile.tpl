{combine_script id='tf_script' load='footer' path="{$TF_PATH}js/tf_profile.js"}
{combine_css path="{$TF_PATH}css/tf_profile.css" order=-10}
{combine_css path="admin/themes/default/fontello/css/animation.css" order=10}
{footer_script}
window.tf_twofactor = window.tf_twofactor || {};
window.tf_twofactor = {
  str_add_email_before: "{"Please add an email first before activating two factor authentication by email."|translate|escape:javascript}",
  str_email_dont_match: "{"Emails don't match"|translate|escape:javascript}",
  str_invalid_code: "{"The code is invalid"|translate|escape:javascript}",
  str_email_setup_success: "{"Email two-factor authentication has been successfully enabled"|translate|escape:javascript}",
  str_external_setup_success: "{"Two-factor authentication by application has been successfully activated."|translate|escape:javascript}",
  str_code_recovery_copy: "{"The recovery codes have been copied."|translate|escape:javascript}",
  str_send_again: "{"Send again."|translate|escape:javascript}",
  str_send_again_in: "{"Send it again in %s seconds."|translate|escape:javascript}",
  str_email_waint_until: "{"Please wait %s seconds before sending an email again."|translate|escape:javascript}",
  str_deactivate_email: "{"Do you really want to disable two factor authentication by email?"|translate|escape:javascript}",
  str_deactivate_external: "{"Do you really want to disable two factor authentication by application?"|translate|escape:javascript}",
  str_deactivate_email_success: "{"Two-factor authentication by email has been successfully deactivated"|translate|escape:javascript}",
  str_deactivate_external_success: "{"Two-factor authentication by application has been successfully deactivated'"|translate|escape:javascript}",

  enabled: {
    external_app: {$TF_STATUS_EXTERNAL_APP},
    email: {$TF_STATUS_EMAIL}
  }
};
{/footer_script}
<div class="column-flex tf-container" data-tf_id="{$k_block}" id="tf_container">
  <span class="infos-message">
    {"<b>Enabling Two-Factor Authentication</b> means that you’ll <b>need an API key to connect from external applications</b>, including the Piwigo iOS and Android apps, the Lightroom Plugin, Piwigo Remote Sync, etc."|translate}<br>
    {"You will find more information in our documentation."|translate}
  </span>

  {if true === $TF_CONFIG.external_app.enabled}
  <div class="tf-setup">
    <label class="switch tf-switch">
      <input type="checkbox" id="tf_auth_app" data-collapse="tf_app_setup" data-open="false">
      <span class="slider round tf-slider"></span>
    </label>

    <div class="column-flex tf-content">
      <label for="tf_auth_app" class="tf-title" id="tf_app_setup_title">
        <p class="row-flex tf-setup-title">{'Set up using an authentication app'|translate|escape:html} <span
            class="tf-recommanded">{'Recommanded'|translate|escape:html}</span></p>
        <p class="tf-setup-subtitle">
          {'Use an external authentication application to obtain authentication codes.'|translate|escape:html}</p>
      </label>

      <div class="tf-collapse close" id="tf_app_setup">
        <div class="" id="tf_app_send">
          <p class="tf-text">{'Auth app'|translate|escape:html}
          </p>
          <p class="tf-scan-code">{'Scan the QR code'|translate|escape:html}</p>
          <p class="tf-text">
            {'Use an authentication application or browser extension to scan it. <a>Find out more about activating 2FA.</a>'|translate}
          </p>

          <div class="tf-qr-code">
            <p class="tf-loading-qrcode">
              <i class="icon-spin6 animate-spin"></i>
            </p>
            <img id="tf_img_qrcode" src="" />
            <div class="tf-setup-key">
              <p id="tf_get_setup_key">{'Unable to scan ? Get the <u>setup key</u>'|translate}</p>
              <div class="input-container tf-setup-key-input" id="tf_get_setup_key_input">
                <input id="tf_setup_key" type="text" value="" />
              </div>
            </div>
          </div>

          <div class="column-flex">
            <label for="tf_app_totp">{'Enter the code'|translate}</label>
            <div class="row-flex input-container tf-app-totp">
              <i class="icon-key"></i>
              <input type="text" id="tf_app_totp" placeholder="XXXXXX" />
            </div>
            <p id="tf_send_totp_error" class="error-message"><i class="gallery-icon-attention-circled"></i>
              {'must not be empty'|translate}</p>
          </div>

          <div class="save">
            <button class="btn btn-main" id="tf_send_totp_code">{'Continue'|translate}</button>
          </div>
        </div>

        <div id="tf_app_recovery_codes">
          <p class="tf-save-recovery">{"Save your recovery codes in a safe place"|translate|escape:html}</p>
          <p class="tf-red">{"They will not be displayed again."|translate|escape:html}
          </p>
          <div class="input-container tf-recovery-codes">
            <i id="tf_copy_recovery_codes" class="icon-clone"></i>
            <p id="tf_app_recovery_code" class="tf-recovery-code"></p>
          </div>
          <div class="save">
            <button class="btn btn-main" id="tf_app_done">{'Done'|translate}</button>
          </div>
        </div>
      </div>
    </div>

    <div id="tf_external_app_setting" class="tf-settings">
      <i class="icon-cog"></i>
    </div>
  </div>
  {/if}

  {if true === $TF_CONFIG.email.enabled}
  <div class="tf-setup">
    <label class="switch tf-switch">
      <input type="checkbox" id="tf_mail" data-collapse="tf_mail_setup" data-open="false">
      <span class="slider round tf-slider"></span>
    </label>

    <div class="column-flex tf-content">
      <label for="tf_mail" class="tf-title" id="tf_mail_setup_title">
        <p class="tf-setup-title row-flex">{'Set up using Email'|translate|escape:html}
          <span id="tf_missing_mail" class="tf-warning">{'The email address is missing'|translate|escape:html}</span></p>
        <p class="tf-setup-subtitle">
          {'We will send you the authentication code by email. This method is not the most secure.'|translate|escape:html}</p>
      </label>

      <div class="tf-collapse close" id="tf_mail_setup">

        <div class="column-flex">
          <label for="tf_email_orig">{'Your Email'|translate}</label>
          <div class="row-flex input-container">
            <i class="icon-mail-alt"></i>
            <input type="email" name="tf_mail_address" id="tf_email_orig" value="{$EMAIL}" disabled />
          </div>
        </div>

        <div class="column-flex">
          <label for="tf_conf_email">{'Confirm your email'|translate}</label>
          <div class="row-flex input-container tf-conf-mail">
            <i class="icon-mail-alt"></i>
            <input type="email" name="tf_conf_mail_address" id="tf_conf_email" />
          </div>
          <p id="tf_email_error" class="error-message"><i class="gallery-icon-attention-circled"></i>
            <span id="tf_email_error_text">{'must not be empty'|translate}</span></p>
        </div>

        <div class="save">
          <button class="btn btn-main" id="tf_send_email">{'Send email'|translate}</button>
        </div>

        <div class="" id="tf_verify_email">
          <p class="tf-send-again">
            {'You didn’t get the Email?'|translate}
            <span id="tf_send_again" class="tf-underline">{'Send again.'|translate}</span>
            <span id="tf_send_again_in">{'Send it again in %s seconds.'|translate}</span>
          </p>

          <div class="column-flex">
            <label for="tf_totp_email">{'Enter the code'|translate}</label>
            <div class="row-flex input-container">
              <i class="icon-key"></i>
              <input type="text" name="tf_totp_email" id="tf_totp_email" placeholder="XXXXXX" />
            </div>
            <p id="tf_email_totp_error" class="error-message"><i class="gallery-icon-attention-circled"></i>
              {'must not be empty'|translate}</p>
          </div>

          <div class="save">
            <button class="btn btn-cancel" id="tf_send_email_cancel">{'Cancel'|translate}</button>
            <button class="btn btn-main" id="tf_send_email_code">{'Save'|translate}</button>
          </div>
        </div>

      </div>

    </div>

    <div id="tf_email_setting" class="tf-settings">
      <i class="icon-cog"></i>
    </div>
  </div>
  {/if}

  <div class="tf-bg-modal">
    <div class="tf-modal">
      <p class="tf-modal-title" id="tf_modal_title">
          {'Do you really want to disable two factor authentication by email?'|translate|escape:html}</p>
      <div id="tf_save_modal" class="save tf-modal-save" data-modal="email">
        <button class="btn btn-cancel" id="tf_deactivate_cancel">{'Cancel'|translate}</button>
        <button class="btn btn-main" id="tf_deactivate">{'Yes'|translate}</button>
      </div>
    </div>
  </div>

</div>