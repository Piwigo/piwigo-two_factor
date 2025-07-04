{combine_script id="tf_user_list" load="footer" path="{$TF_PATH}admin/js/tf_user_list.js"}
{footer_script}
const tf_str_title = "{"two_factor_js"|translate|escape:javascript}";
{/footer_script}
<div id="tf_area">
  <p id="tf_no_config">{"Please enable at least one authentication method in the plugin configuration."|translate}</p>
  <p id="tf_config_error">{"Unable to retrieve information for this user, please try again."|translate}</p>
  <div class="tf-config" id="tf_config">
    <div class="tf-method">
      <p class="user-property-label">{"2FA by application:"|translate}</p>
      <i class="icon-circle-empty tf-icon icon-red" id="tf_icon_external_app"></i>
    </div>
    <div class="tf-method">
      <p class="user-property-label">{"2FA by email:"|translate}</p>
      <i class="icon-circle-empty tf-icon icon-red" id="tf_icon_email"></i>
    </div>
    <div class="tf-deactivate-btn">
      <button class="user-property-button head-button-2" id="tf_btn_deactivate">
        {"Disable 2FA authentication"|translate}
      </button>
      <span class="update-user-fail icon-cancel" id="tf_deactivate_error"></span>
    </div>
      {* <button class="user-property-button head-button-2">
        {"Disable 2FA authentication for next connection"|translate}
      </button> *}
    <div class="tf-infos">
      <p><i class="icon-ok-circled tf-icon icon-green"></i>{"Activated"|translate}</p>
      {* <p><i class="icon-dot-circled tf-icon icon-yellow"></i>{"Deactivated for next connection"|translate}</p> *}
      <p><i class="icon-circle-empty tf-icon icon-red"></i>{"Deactivated"|translate}</p>
      <p><i class="icon-cancel-circled tf-icon"></i>{"Disable in plugin configuration"|translate}</p>
    </div>
  </div>
</div>
{html_style}
#tf_area {
  width: 100%;
  height: 100%;
}
#tf_area p {
  margin: 0;
}

.tf-config {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

#tf_no_config,
#tf_config_error,
#tf_deactivate_error {
  display: none;
  text-align: center;
}

#tf_area .user-property-button {
  width: fit-content;
  margin-bottom: 0;
}

.tf-method,
.tf-deactivate-btn {
  display: flex;
  align-items: center;
}

.tf-deactivate-btn {
  gap: 10px;
}

.tf-method i {
  font-size: 32px
}

.tf-infos {
  position: absolute;
  bottom: 0;
  gap: 10px;
}

.tf-icon {
  background-color: transparent;
}
{/html_style}