{* {combine_script id='tf_script' load='footer' path="{$TF_PATH}js/profile.js"}
{combine_css path="{$TF_PATH}css/profile.css" order=-10} *}
<div id="tf_profile">
  <p class="tf-title">Two Factor Authentification</p>
  {* <p class="tf-button" id="tf_profile_toggle">{if $TF_ACTIVATED}Edit{else}Activate{/if}</p> *}
  <p class="tf-button" id="tf_profile_toggle">Enable 2FA</p>
</div>


<div id="tf_modal" class="tf-modal">
  <div class="tf-modal-content">
    <div class="tf-modal-body">
      {* Enable 2FA *}
      <div id="tf_enabling">
        <p class="tf-enabling-title">Protect your account by enabling 2FA</p>
        <p>Choose one of the methods to activate</p>
        <div class="tf-methods">
          <p class="tf-method" data-method="auth_app">Authentificator app</p>
          <p class="tf-method" data-method="pwg_mobile">Piwigo Mobile</p>
        </div>
      </div>

      {* Authentificator app method *}
      <div id="tf_auth_app">
        <div class="tf-authentificator">
          <p class="tf-title">Setup authentificator app</p>
          <p>
            Authentificator apps and browser extentions like 1Password, Bitwarden, Authy, etc. generate
            one-time passwords that are used as a second factor to verify yout identify when prompted
            during sign-in.
          </p>
        </div>
        <div class="tf-setup">
          <p class="tf-title">Scan the QR Code</p>
          <img id="tf_qrcode" class="tf-qrcode" src="" />
          <div>
            <p id="tf_get_setup_key">Unable to scan ? Get the <span id="tf_sk" class="tf-sk">setup key</span>.</p>
            <input id="tf_setup_key" class="tf-setup-key" type="text" value="" />
          </div>
        </div>
        <div class="tf-code">
          <p>Verify the code from the app</p>
          <input id="tf_code_totp" placeholder="XXXXXX" type="text" />
        </div>
      </div>

      {* Piwigo Mobile method *}
    </div>
    <div class="tf-modal-footer">
      <p id="tf_modal_cancel" class="tf-modal-btn cancel">Cancel</p>
      <div class="tf-modal-footer-r">
        <p id="tf_error" class="tf-error"></p>
        <p id="tf_modal_continue" class="tf-modal-btn continue disabled">Continue</p>
      </div>
    </div>
  </div>
</div>
