# AetGoogleAdSense
The AetGoogleAdSense extension lets you display Google AdSense in wiki pages and you can set up some settings.

Links
* Git : https://github.com/exizt/mw-ext-googleadsense



## Requirements
* PHP 7.4.3 or later (tested up to 7.4.30)
* MediaWiki 1.35 or later (tested up to 1.35)


## Installation
1. Download and place the files in a directory called `AetGoogleAdSense` in your `extensions/` folder.
2. Add the following code at the bottom of your `LocalSettings.php`:
```
wfLoadExtension( 'AetGoogleAdSense' );
```


## Configuration
주요 설정
- `$wgAetGoogleAdsense['client_id']`
    - Google AdSense Client Id. (eg: `'ca-pub-xxx...'`)
        - `required`
        - type : `string`
        - default : `''`
- `$wgAetGoogleAdsense['unit_id_content_top']`
    - 콘텐츠 상단에 표시할 애드센스 광고 단위 아이디 (eg: `xxx...`)
        - type : `string`
        - default : `''`
- `$wgAetGoogleAdsense['unit_id_content_bottom']`
    - 콘텐츠 히단에 표시할 애드센스 광고 단위 아이디 (eg: `xxx...`)
        - type : `string`
        - default : `''`
- `$wgAetGoogleAdsense['anon_only']`
    - '비회원'만 애드센스 노출하기.
        - type : `bool`
        - default : `false`
- `$wgAetGoogleAdsense['exclude_ip_list']`
    - 애드센스를 보여주지 않을 IP 목록.
        - type : `array`
        - default : `[]`
- `$wgAetGoogleAdsense['min_length']`
    - 애드센스가 보여질 최소 문서의 본문 길이.
        - type : `int`
        - default : `500`



사용할 훅 설정
- `$wgAetGoogleAdsenseHooks['ArticleViewHeader']`
    - type : `bool`
    - default : `true`
- `$wgAetGoogleAdsenseHooks['SiteNoticeAfter']`
    - type : `bool`
    - default : `false`
- `$wgAetGoogleAdsenseHooks['ArticleViewFooter']`
    - type : `bool`
    - default : `true`
- `$wgAetGoogleAdsenseHooks['SkinAfterContent']`
    - type : `bool`
    - default : `false`



참고
1. `client_id`가 입력되고, `unit_id_content_top`과 `unit_id_content_bottom`이 입력 안 된 경우는 => 자동 광고만 허가.
2. `client_id`가 빈 값이거나 없는 경우에는 동작하지 않음.
