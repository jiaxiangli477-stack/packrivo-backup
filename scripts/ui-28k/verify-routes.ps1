$ErrorActionPreference = 'Stop'
$tmp = 'E:\破甲工作区\.tmp\packrivo-28k'
$localHome = Join-Path $tmp 'local-ar-home.html'
$productionHome = Join-Path $tmp 'production-ar-home.html'
$localStatus = & curl.exe -sS --max-time 20 -o $localHome -w '%{http_code}' 'http://localhost:8081/ar/'
if ($LASTEXITCODE -ne 0) { throw 'Local home request failed' }
$localQuote = & curl.exe -sS --max-time 20 -o (Join-Path $tmp 'local-ar-quote.html') -w '%{http_code}' 'http://localhost:8081/ar/get-a-quote/'
if ($LASTEXITCODE -ne 0) { throw 'Local quote request failed' }
$prodStatus = & curl.exe -sS --max-time 20 -o $productionHome -w '%{http_code}' 'https://www.packrivo.com/ar/'
if ($LASTEXITCODE -ne 0) { throw 'Production read-only home request failed' }
$prodQuote = & curl.exe -sS --max-time 20 -o (Join-Path $tmp 'production-ar-quote.html') -w '%{http_code}' 'https://www.packrivo.com/ar/get-a-quote/'
if ($LASTEXITCODE -ne 0) { throw 'Production read-only quote request failed' }
$localHtml = Get-Content -LiteralPath $localHome -Raw
$prodHtml = Get-Content -LiteralPath $productionHome -Raw
$localEnglishQuote = [regex]::Matches($localHtml, 'href=["''][^"'']*/get-a-quote/[^"'']*["'']').Count
$productionArabicInquiry = [regex]::Matches($prodHtml, 'href=["''][^"'']*/ar/inquiry/[^"'']*["'']').Count
$quoteButtons = [regex]::Matches($prodHtml, '<a\b[^>]*href=["''][^"'']*/ar/inquiry/[^"'']*["''][^>]*>[\s\S]*?</a>', 'IgnoreCase') | Where-Object { [System.Net.WebUtility]::HtmlDecode([regex]::Replace($_.Value, '<[^>]+>', ' ')) -match 'احصل على عرض سعر' }
$productionQuoteButtons = @($quoteButtons).Count
$paths = Get-Content -LiteralPath 'E:\破甲工作区\packrivo-backup\deploy\i18n-merged\paths.json' -Raw | ConvertFrom-Json
'local_ar_home_status=' + $localStatus
'local_ar_quote_status=' + $localQuote
'local_english_quote_href_count=' + $localEnglishQuote
'local_paths_get_a_quote_present=' + [bool]($paths -contains '/get-a-quote/')
'production_ar_home_status=' + $prodStatus
'production_ar_quote_status=' + $prodQuote
'production_ar_inquiry_href_count=' + $productionArabicInquiry
'production_ar_quote_inquiry_buttons=' + $productionQuoteButtons
if ($localStatus -ne '200' -or $localQuote -ne '404' -or $prodStatus -ne '200' -or $prodQuote -ne '404' -or $localEnglishQuote -lt 1 -or $productionArabicInquiry -lt 1 -or $productionQuoteButtons -lt 1) { throw 'Unexpected current route behavior; investigate before relying on these observations.' }
