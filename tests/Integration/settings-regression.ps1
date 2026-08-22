# Real WordPress settings regression for WooCommerce Product Schema.
$ErrorActionPreference = 'Stop'
$base = 'http://127.0.0.1:8088'
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Get-SettingsPage {
	return Invoke-WebRequest -Uri "$base/wp-admin/admin.php?page=local-seo-settings" -WebSession $session -UseBasicParsing
}

function Get-Nonce([string]$html) {
	if ($html -notmatch 'name="_wpnonce" value="([^"]+)"') { throw 'nonce missing' }
	return $Matches[1]
}

function Save-Settings([hashtable]$fields) {
	$page = Get-SettingsPage
	$nonce = Get-Nonce $page.Content
	$body = @{
		option_page = 'local_seo_by_ankit_rawat_group'
		action = 'update'
		_wpnonce = $nonce
		_wp_http_referer = '/wp-admin/admin.php?page=local-seo-settings'
	}
	foreach ($k in $fields.Keys) { $body[$k] = $fields[$k] }
	return Invoke-WebRequest -Uri "$base/wp-admin/options.php" -Method POST -Body $body -WebSession $session -UseBasicParsing -MaximumRedirection 5
}

Write-Host 'LOGIN'
$pre = Invoke-WebRequest -Uri "$base/wp-login.php" -WebSession $session -UseBasicParsing
Write-Host ("LOGIN_GET status={0} len={1}" -f $pre.StatusCode, $pre.RawContentLength)
$login = Invoke-WebRequest -Uri "$base/wp-login.php" -Method POST -WebSession $session -UseBasicParsing -MaximumRedirection 5 -Body @{
	log = 'admin'
	pwd = 'admin'
	'wp-submit' = 'Log In'
	redirect_to = "$base/wp-admin/"
	testcookie = '1'
}
Write-Host ("LOGIN_POST status={0} len={1} uri={2}" -f $login.StatusCode, $login.RawContentLength, $login.BaseResponse.ResponseUri)
if ($login.Content -notmatch 'Dashboard' -and $login.Content -notmatch 'index.php') {
	$snippet = $login.Content.Substring(0, [Math]::Min(500, $login.Content.Length))
	Write-Host $snippet
	throw 'login failed'
}
Write-Host 'LOGIN_OK'

$settings = Get-SettingsPage
$html = $settings.Content
$hasWrap = $html.Contains('lsar-woocommerce-available')
$hasField = $html.Contains('woocommerce_product_schema')
$hasHidden0 = $html -match 'name="local_seo_by_ankit_rawat_options\[woocommerce_product_schema\]" value="0"'
$wcChecked = $html -match 'id="lsar-woocommerce_product_schema"[^>]*checked'
$storeSelected = $html -match 'value="Store"[^>]*selected'
Write-Host "GET_STORE wrap=$hasWrap field=$hasField hidden0=$hasHidden0 wcChecked=$wcChecked storeSelected=$storeSelected"

$common = @{
	'local_seo_by_ankit_rawat_options[schema_enabled]' = '1'
	'local_seo_by_ankit_rawat_options[business_type]' = 'Store'
	'local_seo_by_ankit_rawat_options[street_address]' = '123 Test Street'
	'local_seo_by_ankit_rawat_options[locality]' = 'Rishikesh'
	'local_seo_by_ankit_rawat_options[region]' = 'Uttarakhand'
	'local_seo_by_ankit_rawat_options[postal_code]' = '249201'
	'local_seo_by_ankit_rawat_options[country]' = 'IN'
	'local_seo_by_ankit_rawat_options[phone]' = '+91 9999999999'
	'local_seo_by_ankit_rawat_options[price_range]' = ''
	'local_seo_by_ankit_rawat_options[logo]' = ''
	'local_seo_by_ankit_rawat_options[images]' = ''
	'local_seo_by_ankit_rawat_options[social_profiles]' = ''
	'local_seo_by_ankit_rawat_options[latitude]' = ''
	'local_seo_by_ankit_rawat_options[longitude]' = ''
	'local_seo_by_ankit_rawat_options[place_id]' = ''
	'local_seo_by_ankit_rawat_options[woocommerce_currency]' = ''
}

function Show-WcState([string]$label) {
	$page = Get-SettingsPage
	$html = $page.Content
	$checked = $html -match 'id="lsar-woocommerce_product_schema"[^>]*checked'
	$nameOk = $html.Contains('Settings Regression Keep WC')
	$wrap = $html.Contains('lsar-woocommerce-available')
	$field = $html.Contains('woocommerce_product_schema')
	Write-Host "$label wrap=$wrap field=$field wcChecked=$checked nameInPage=$nameOk"
}

Write-Host 'SAVE_KEEP_WC_ON_CHANGE_NAME'
$fields = $common.Clone()
$fields['local_seo_by_ankit_rawat_options[business_name]'] = 'Settings Regression Keep WC'
$fields['local_seo_by_ankit_rawat_options[woocommerce_product_schema]'] = '1'
Save-Settings $fields | Out-Null
Show-WcState 'AFTER_KEEP_ON'

Write-Host 'SAVE_WC_OFF'
$fields = $common.Clone()
$fields['local_seo_by_ankit_rawat_options[business_name]'] = 'Settings Regression Keep WC'
$fields['local_seo_by_ankit_rawat_options[woocommerce_product_schema]'] = '0'
Save-Settings $fields | Out-Null
Show-WcState 'AFTER_OFF'

Write-Host 'SAVE_LOCALBUSINESS_WC_ON'
$fields = $common.Clone()
$fields['local_seo_by_ankit_rawat_options[business_type]'] = 'LocalBusiness'
$fields['local_seo_by_ankit_rawat_options[business_name]'] = 'Settings Regression Keep WC'
$fields['local_seo_by_ankit_rawat_options[woocommerce_product_schema]'] = '1'
Save-Settings $fields | Out-Null

$lb = Get-SettingsPage
$lbHtml = $lb.Content
$lbWrap = $lbHtml.Contains('lsar-woocommerce-available')
$lbField = $lbHtml.Contains('woocommerce_product_schema')
$lbType = $lbHtml -match 'value="LocalBusiness"[^>]*selected'
$lbChecked = $lbHtml -match 'id="lsar-woocommerce_product_schema"[^>]*checked'
Write-Host "GET_LOCALBUSINESS wrap=$lbWrap field=$lbField typeLB=$lbType wcChecked=$lbChecked"

Write-Host 'SAVE_STORE_WC_ON_RESTORE'
$fields = $common.Clone()
$fields['local_seo_by_ankit_rawat_options[business_type]'] = 'Store'
$fields['local_seo_by_ankit_rawat_options[business_name]'] = ''
$fields['local_seo_by_ankit_rawat_options[woocommerce_product_schema]'] = '1'
Save-Settings $fields | Out-Null
Write-Host 'DONE'
