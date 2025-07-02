{combine_script id="tf_user_list" load="footer" path="{$TF_PATH}admin/js/tf_user_list.js"}
{footer_script}
const tf_str_title = "{"two_factor_js"|translate|escape:javascript}";
{/footer_script}
<div id="tf_area">
  <p>tf in adminnn</p>
</div>
{html_style}
#tf_area {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
  height: 100%;
}
#tf_area p {
  margin: 0;
}
{/html_style}