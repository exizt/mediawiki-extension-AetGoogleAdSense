<?php
/**
 * AetGoogleAdSense
 *
 * @link https://github.com/exizt/mw-ext-AetGoogleAdSense
 * @author exizt
 * @license GPL-2.0-or-later
 */
class AetGoogleAdSense {
	# 설정값을 갖게 되는 멤버 변수
	private static $config = null;
	
	# 이용 가능한지 여부 (isEnabled 메소드에서 체크함)
	private static $isEnabled = false;

	# 검증이 필요한지 여부
	private static $shouldValidate = true;

	/**
	 * 'BeforePageDisplay' 후킹.
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/Hook/BeforePageDisplayHook.php
	 */
	public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
		# 최소 유효성 체크
		if( self::isValid() ){
			# 설정값 조회
			$config = self::getConfiguration();
	
			# 유효성 체크
			if( self::isEnabledWithCheck( $config, $skin->getContext() ) ){
				# HTML 문자열 생성
				$html = self::getHeaderHTML( $config, $skin->getContext() );
				if( !empty($html) ){
					$out->addHeadItem('gads', $html);
				}
			}
		}
		return;
	}

	/**
	 * 'ArticleViewHeader' 후킹.
	 *
	 * 상단 (본문 바로 위 영역)에 광고를 노출하고 싶을 때 사용.
	 * 
	 * @param Article $article
	 * @param bool|ParserOutput &$outputDone
	 * @param bool &$pcache
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/page/Hook/ArticleViewHeaderHook.php
	 */
	public static function onArticleViewHeader( &$article, &$outputDone, &$pcache ){
		# 최소 유효성 체크
		if( self::isValid() ){
			# 설정값 조회
			$config = self::getConfiguration();
			if( $config['hook_enabled']['ArticleViewHeader'] ){
				$banner = self::getTopBanner( $config, $article->getContext() );
				if( !empty($banner) ){
					$article->getContext()->getOutput()->addHTML($banner);
				}
			}
		}
		return;
	}

	/**
	 * 'SiteNoticeAfter'후킹.
	 *
	 * 상단(공지 영역)에 광고를 노출하고 싶을 때 사용.
	 *
	 * @param string &$siteNotice
	 * @param Skin $skin
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SiteNoticeAfter
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/skins/Hook/SiteNoticeAfterHook.php
	 */
	public static function onSiteNoticeAfter( &$siteNotice, $skin ){
		# 최소 유효성 체크
		if( self::isValid() ){
			# 설정값 조회
			$config = self::getConfiguration();
			if( $config['hook_enabled']['SiteNoticeAfter'] ){
				$banner = self::getTopBanner( $config, $skin->getContext() );
				if( !empty($banner) ){
					$siteNotice .= $banner;
				}
			}
		}
		return;
	}

	/**
	 * 'ArticleViewFooter' 후킹.
	 *
	 * 본문 하단(분류 바로 위)에 광고를 노출하고 싶을 때 사용.
	 *
	 * @param Article $article
	 * @param bool $patrolFooterShown
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewFooter
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/page/Hook/ArticleViewFooterHook.php
	 */
	public static function onArticleViewFooter( $article, bool $patrolFooterShown ){
		# 최소 유효성 체크
		if( self::isValid() ){
			# 설정값 조회
			$config = self::getConfiguration();
			if( $config['hook_enabled']['ArticleViewFooter'] ){
				$banner = self::getBottomBanner( $config, $article->getContext() );
				if( !empty($banner) ){
					$article->getContext()->getOutput()->addHTML($banner);
				}
			}
		}
		return;
	}

	/**
	 * 'SkinAfterContent'후킹.
	 * 
	 * 본문 영역보다 좀 더 하위에 광고를 노출하고 싶을 때 사용.
	 *
	 * @param string &$data
	 * @param Skin $skin
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 * @see https://github.com/wikimedia/mediawiki/blob/master/includes/skins/Hook/SkinAfterContentHook.php
	 */
	public static function onSkinAfterContent(&$data, $skin) {
		# 최소 유효성 체크
		if( self::isValid() ){
			# 설정값 조회
			$config = self::getConfiguration();
			if( $config['hook_enabled']['SkinAfterContent'] ){
				$banner = self::getBottomBanner( $config, $skin->getContext() );
				if($banner){
					$data .= $banner;
				}
			}
			# return true;
		}
		# return false;
		return;
	}

	/**
	 * 생성될 헤더 HTML 
	 * 
	 * @param array $config
	 * @param IContextSource $context
	 * @return string
	 */
	private static function getHeaderHTML( $config, $context ): string{
		return self::makeHeaderHTML($config['client_id']);
	}

	/**
	 * 컨텐츠 상단에 표시될 HTML (상단 유닛 광고)
	 * 
	 * @param array $config
	 * @param IContextSource $context
	 * @return string
	 */
	private static function getTopBanner( $config, $context ): string {
		self::debugLog('::getTopBanner');

		# 해당되는 slot id가 지정되어있을 때에만
		if( self::isValidAdsId( $config['unit_id_content_top'] ) ){
			# 활성화 여부
			if( self::isEnabledWithCheck($config, $context) ){
				return self::makeBannerHTML($config['client_id'], $config['unit_id_content_top']);
			}
		}
		return '';
	}

	/**
	 * 컨텐츠 하단에 표시될 HTML (하단 유닛 광고 or 자동 광고 스크립트)
	 * 
	 * @param array $config
	 * @param IContextSource $context
	 * @return string
	 */
	private static function getBottomBanner( $config, $context ): string{
		self::debugLog('::getBottomBanner');

		# bottom_id가 지정되어있는 경우에만 출력.
		if( self::isValidAdsId( $config['unit_id_content_bottom'] ) ){
			# 활성화 여부
			if( self::isEnabledWithCheck($config, $context) ){
				return self::makeBannerHTML($config['client_id'], $config['unit_id_content_bottom']);
			}
		}
		return '';
	}

	/**
	 * GoogleAdSense의 상단 헤더 스크립트 HTML.
	 * 
	 * '자동 광고'와 '단위 광고'에서 script 호출 부분은 동일함.
	 * 
	 * @param string $clientId 애드센스 클라이언트 아이디
	 * @return string HTML 문자열
	 */
	private static function makeHeaderHTML( $clientId ): string{
		if(! $clientId ){
			return '';
		}
		$html = <<<EOT
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$clientId}"
				crossorigin="anonymous"></script>
		EOT;
		return $html;
	}

	/**
	 * '배너 단위 광고'의 HTML 생성
	 * 
	 * @param string $clientId 애드센스 클라이언트 아이디
	 * @param string $unitId 애드센스 광고 유닛 아이디
	 * @return string HTML 문자열
	 */
	private static function makeBannerHTML( $clientId, $unitId ): string{
		if(! $clientId || ! $unitId ){
			return '';
		}
		# $html = <<<EOT
		# <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$clientId}"
		# 		crossorigin="anonymous"></script>
		# EOT;

		$html = <<< EOT
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="{$clientId}"
     data-ad-slot="{$unitId}"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
EOT;
		return $html;
	}

	/**
	 * 최소 조건 체크.
	 * - 확장 기능이 동작할 수 있는지에 대한 최소 조건 체크. 성능상 부담이 없도록 구성.
	 * - 인자값을 따로 받지 않음.
	 * 
	 * @return bool 검증 결과
	 */
	private static function isValid(): bool{
		if (self::$shouldValidate){
			# 전역 설정 로드
			$settings = self::readSettings();

			# 설정되어 있지 않음
			if ( ! isset($settings) ){
				return self::disable();
			}

			# 'client_id'가 유효함
			$clientId = $settings['client_id'] ?? '';
			if ( self::isValidAdsId($clientId) ){
				if($settings['auto_ads'] || self::isValidAdsId( $settings['unit_id_content_bottom'])
					|| self::isValidAdsId( $settings['unit_id_content_top'] )){
						return true;
				}
			}

			# 검증을 통과하지 못하였으므로 disabled
			return self::disable();

		} else {
			# 이미 한 번 검증했으므로 결과값을 그대로 반환.
			return self::$isEnabled;
		}
	}

	/**
	 * 조건 체크 및 활성화 여부 반환
	 * 
	 * @param array $config
	 * @param IContextSource $context
	 * @return bool 검증 결과
	 */
	private static function isEnabledWithCheck( $config, $context ): bool{
		if( self::$shouldValidate ){
			# 익명 사용자에게만 보여지게 하는 옵션이 있으면, 익명 사용자에게만 보여준다.
			if ( $config['anon_only'] && $context->getUser()->isRegistered() ) {
				return self::disable();
			}
	
			# 특정 아이피에서는 애드센스를 노출하지 않도록 한다. (예를 들어, 관리자)
			if ( ! empty($config['exclude_ip_list']) ){
				$remoteAddr = $_SERVER["REMOTE_ADDR"] ?? '';
				if( in_array($remoteAddr, $config['exclude_ip_list']) ){
					return self::disable();
				}
			}

			# self::debugLog("isEnabled");
			# self::debugLog($ns);
	
			$titleObj = $context->getTitle();
	
			// 메인 이름공간의 페이지에서만 나오도록 함. 특수문서 등에서 나타나지 않도록.
			if( $titleObj->getNamespace() != NS_MAIN ){
				return self::disable();
			}
	
			# 대문 페이지에서도 안 나오게하기
			if( $titleObj->isMainPage() ){
				return self::disable();
			}
	
			# 본문의 길이가 짧을 때에는 광고를 출력하지 않도록 설정.
			if( $titleObj->getLength() <= $config['min_length'] ) {
				return self::disable();
			}

			# 검증을 통과하였고 isEnabled=true이다.
			self::$shouldValidate = false;
			self::$isEnabled = true;
			return true;

		} else {
			# 이미 한 번 검증했으므로 결과값을 그대로 반환.
			return self::$isEnabled;
		}
	}
	
	/**
	 * AdSense의 ID가 제대로된 입력값인지 확인.
	 * 
	 * @param $id 애드센스 ID 값
	 * @return bool 검증 결과
	 */
	private static function isValidAdsId( $id ): bool{
		if( ! is_string($id) || strlen($id) < 5 ) {
			return false;
		}
		return true;
	}

	/**
	 * 설정을 로드함.
	 */
	private static function getConfiguration(){
		# 한 번 로드했다면, 그 후에는 로드하지 않도록 처리.
		if(self::$config){
			return self::$config;
		}
		self::debugLog('::getConfiguration');

		global $wgAetGoogleAdsense, $wgAetGoogleAdsenseHooks;

		/*
		* 설정 기본값
		* 
		* client_id : 애드센스 id key 값. (예: ca-pub-xxxxxxxxx)
		* unit_id_content_top : 콘텐츠 상단에 표시할 애드센스 광고 단위 아이디 (예: xxxxxxx)
		* unit_id_content_bottom : 콘텐츠 히단에 표시할 애드센스 광고 단위 아이디 (예: xxxxxxx)
		* anon_only : '비회원'만 애드센스 노출하기.
		* exclude_ip_list : 애드센스를 보여주지 않을 IP 목록.
		*/
		$config = [
			'client_id' => '',
			'unit_id_content_top' => '',
			'unit_id_content_bottom' => '',
			'auto_ads' => false,
			'anon_only' => false,
			'exclude_ip_list' => array(),
			'min_length' => 500,
			'debug' => false
		];

		$configHookEnabled = [
			'ArticleViewHeader' => true,
			'SiteNoticeAfter' => false,
			'ArticleViewFooter' => true,
			'SkinAfterContent' => false,
		];
		
		# 설정값 병합
		if (isset($wgAetGoogleAdsense)){
			foreach ($wgAetGoogleAdsense as $key => $value) {
				if( array_key_exists($key, $config) ) {
					if( gettype($config[$key]) == gettype($value) ){
						$config[$key] = $value;
					}
				}
			}
		}

		# 훅 설정 병합
		if (isset($wgAetGoogleAdsenseHooks)){
			foreach ($wgAetGoogleAdsenseHooks as $key => $value) {
				if( array_key_exists($key, $configHookEnabled) ) {
					if( gettype($configHookEnabled[$key]) == gettype($value) ){
						$configHookEnabled[$key] = $value;
					}
				}
			}
		}

		$config['hook_enabled'] = $configHookEnabled;
		self::$config = $config;
		return $config;
	}

	/**
	 * 전역 설정값 조회
	 * 
	 * @return array|null 설정된 값 또는 undefined|null를 반환
	 */
	private static function readSettings(){
		global $wgAetGoogleAdsense;
		return $wgAetGoogleAdsense;
	}

	/**
	 * '사용 안 함'을 설정.
	 * 
	 * @return false false 반환.
	 */
	private static function disable(): bool{
		self::$shouldValidate = false;
		self::$isEnabled = false;
		return false;
	}

	/**
	 * 디버그 로깅 관련
	 * 
	 * @param string|object $msg 디버깅 메시지 or 오브젝트
	 */
	private static function debugLog($msg){
		global $wgDebugToolbar;

		# 디버그툴바 사용중일 때만 허용.
		$isDebugToolbarEnabled = $wgDebugToolbar ?? false;
		if( !$isDebugToolbarEnabled ){
			return;
		}
		
		# 로깅
		$settings = self::readSettings() ?? [];
		$isDebug = $settings['debug'] ?? false;
		if($isDebug){
			if(is_string($msg)){
				wfDebugLog(static::class, $msg);
			} else {
				wfDebugLog(static::class, json_encode($msg));
			}
		}
	}
}
