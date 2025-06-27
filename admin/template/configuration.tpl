<div>
  <p>base url : {$url}</p>
  <p>Secret 1 : {$secret1}</p>
  <p>Code 1 : {$code1}</p>
  <p>Url 1 : {$url1}</p>
  <br />
  <br />
  <p>Secret 2 : {$secret2}</p>
  <p>Code 2 : {$code2}</p>
  <p>Url 2 : {$url2}</p>
  <img src="{$qrcode}" />
  <form method="post">
    <input type="hidden" name="TEST_MAIL_TOTP" value="1" />
    {$posted}
    <button type="submit">Test send mail totp</button>
  </form>
</div>