# AetGoogleAdSense

AetGoogleAdSense (Mediawiki Google Adsense Extension)
* 미디어위키에 구글 애드센스를 삽입하고, 몇 가지 도움이 될 수 있는 설정을 할 수 있는 확장 기능입니다. 
* Git : https://github.com/exizt/mw-ext-googleadsense



# 설정

## 옵션
- `client_id` : 애드센스 id key 값. (예: ca-pub-xxxxxxxxx)
- `unit_id_content_top` : 콘텐츠 상단에 표시할 애드센스 광고 단위 아이디 (예: xxxxxxx)
- `unit_id_content_bottom` : 콘텐츠 히단에 표시할 애드센스 광고 단위 아이디 (예: xxxxxxx)
- `anon_only` : '비회원'만 애드센스 노출하기. (기본값: `false`)
- `exclude_ip_list` : 애드센스를 보여주지 않을 IP 목록.
- `min_length` : 애드센스가 보여질 최소 문서의 본문 길이. (기본값: `500`)


참고
1. `client_id`가 입력되고, `unit_id_content_top`과 `unit_id_content_bottom`이 입력 안 된 경우는 => 자동 광고만 허가.
2. `client_id`가 빈 값이거나 없는 경우에는 동작하지 않음.