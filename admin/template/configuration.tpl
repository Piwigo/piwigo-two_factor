{combine_script id='tf_script_config' load='footer' path="{$TF_PATH}admin/js/tf_config.js"}
{combine_css path="{$TF_PATH}/admin/css/admin.css" order=0}
{footer_script}
const PWG_TOKEN = "{$PWG_TOKEN}";
{/footer_script}
<section class="tf-container {if $themeconf['colorscheme'] == 'dark'}dark{/if}">
  <div class="tf-config">
    <div class="tf-config-container">
      <p class="tf-icon-header">
        <span class="tf-icon icon-cog-alt icon-green"></span>
        <span class="tf-icon-text">{'General'|translate}</span>
      </p>

      <div class="tf-config-general">
        <div class="tf-input-container">
          <label for="max_attempts">{"Maximum number of failed attempts before lockout"|translate|escape:html}</label>
          <input id="max_attempts" name="max_attempts" type="number" />
        </div>

        <div class="tf-input-container">
          <label
            for="lockout_duration">{"Lockout duration in seconds after max attempts (300 = 5 minutes)"|translate|escape:html}</label>
          <input id="lockout_duration" name="lockout_duration" type="number" />
        </div>

      </div>
    </div>


    <div class="tf-config-container tf-app">
      <p class="tf-icon-header">
        <span class="tf-icon icon-users-cog icon-blue""></span>
      <span class=" tf-icon-text">{'Two Factor Authentication'|translate}</span>
      </p>

      <div class="tf-config-method">
        <div class="tf-method">
          <label class="switch">
            <input type="checkbox" name="external_app" id="external_app">
            <span class="slider round"></span>
          </label>
          <label for="external_app">{'Enable 2FA by application'|translate}</label>
        </div>

        <div class="tf-method">
          <label class="switch">
            <input type="checkbox" name="tf_email" id="tf_email">
            <span class="slider round"></span>
          </label>
          <label for="tf_email">{'Enable 2FA by email'|translate}</label>
        </div>

      </div>
    </div>
  </div>
</section>
<section class="tf-save {if $themeconf['colorscheme'] == 'dark'}dark{/if}">

  <div class="badge-container" id="tf_error_changes">
    <div class="badge-error">
      <i class="icon-cancel"></i>
      {"an error happened"|translate}
    </div>
  </div>

  <div class="badge-container" id="tf_unsaved_changes">
    <div class="badge-unsaved">
      <i class="icon-attention"></i>
      {'You have unsaved changes'|translate}
    </div>
  </div>

  <div class="badge-container" id="tf_saving_changes">
    <div class="badge-succes">
      <i class="icon-ok"></i>
      {"Changes saved"|translate}
    </div>
  </div>

  <button class="buttonLike" id="tf_save_settings">{'Save Settings'|translate}</button>
</section>