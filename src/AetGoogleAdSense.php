<?php
/**
 * Hooks for Example extension.
 *
 * @file
 */

class AetGoogleAdSense {
	// 설정값을 갖게 되는 멤버 변수
	private static $config = null;
	// 이용 가능한지 여부 (isAvailable 메소드에서 체크함)
	private static $_isAvailable = true;

	/**
	 * 'ArticleViewHeader' 후킹.
	 * 
	 * 상단 (본문 바로 위 영역)에 광고를 노출하고 싶을 때 사용.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
	 */
	public static function onArticleViewHeader( &$article, &$outputDone, &$pcache ){
		# 최소 유효성 체크
		if( !self::isValid() ){
			return false;
		}

		# 설정값 조회
		$config = self::getConfiguration();
		if( $config['article_view_header_hook'] ){
			$result = self::getTopAdsHTML( $config, $article->getContext() );
			if($result){
				$article->getContext()->getOutput()->addHTML($result);
			}
		}
	}

	/**
	 * 'SiteNoticeAfter'후킹.
	 *
	 * 상단(공지 영역)에 광고를 노출하고 싶을 때 사용.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SiteNoticeAfter
	 */
	public static function onSiteNoticeAfter( &$siteNotice, $skin ){
		# 최소 유효성 체크
		if( !self::isValid() ){
			return false;
		}

		# 설정값 조회
		$config = self::getConfiguration();
		if($config['site_notice_after_hook'] ){
			$result = self::getTopAdsHTML( $config, $skin->getContext() );
			if($result){
				$siteNotice .= $result;
			}
		}
	}


	public static function onArticleViewFooter( $article, bool $patrolFooterShown ){
		# 최소 유효성 체크
		if( !self::isValid() ){
			return false;
		}

		# 설정값 조회
		$config = self::getConfiguration();
		if( $config['hook_enabled']['ArticleViewFooter'] ){
			$result = self::getBottomAdsHTML( $config, $article->getContext() );
			if($result){
				$article->getContext()->getOutput()->addHTML($result);
			}
		}
	}

	/**
	 * 컨텐츠 하단에 나타나는 애드센스 단위 광고.
	 * 
	 * 'SkinAfterContent'후킹 이용.
	 * 
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 */
	public static function onSkinAfterContent(&$data, $skin) {
		# 최소 유효성 체크
		if( !self::isValid() ){
			return false;
		}

		# 설정값 조회
		$config = self::getConfiguration();
		$result = self::getBottomAdsHTML( $config, $skin->getContext() );
		if($result){
			$data .= $result;
		}
		return true;
	}

	/**
	 * 컨텐츠 상단에 표시될 HTML (상단 유닛 광고)
	 */
	private static function getTopAdsHTML( $config, $context ) {
		self::debugLog('::getTopAdsHTML');

		# 해당되는 slot id가 지정되지 않았으면 보이지 않게 함
		if( ! self::isValidAdsId($config, 'unit_id_content_top') ){
			return false;
		}

		// 유효성 체크
		if( !self::isAvailable($config, $context) ){
			return false;
		}

		return self::makeBannerHTML($config['client_id'], $config['unit_id_content_top']);
	}

	/**
	 * 컨텐츠 하단에 표시될 HTML (하단 유닛 광고 or 자동 광고 스크립트)
	 */
	private static function getBottomAdsHTML( $config, $context ){
		self::debugLog('::getBottomAdsHTML');

		# auto_ads가 설정되어있거나, bottom id가 지정되어있는 것이 아닐 때에는 보이지 않게 함.
		if( !$config['auto_ads'] && !self::isValidAdsId($config, 'unit_id_content_bottom') ){
			return false;
		}

		# 유효성 체크
		if( !self::isAvailable($config, $context) ){
			return false;
		}

		# bottom_id가 지정되어있는 경우에만 출력.
		if( self::isValidAdsId($config, 'unit_id_content_bottom') ){
			$result = self::makeBannerHTML($config['client_id'], $config['unit_id_content_bottom']);
			if($result){
				return $result;
			}
		} else if( $config['auto_ads'] && !self::isValidAdsId($config, 'unit_id_content_top') ){
			# 자동 광고가 설정되어있고, top과 bottom 둘 다 사용되지 않을 때, 여기서 코드를 추가.
			return self::makeAutoAdsHTML( $config['client_id'] );
		}
		return false;
	}

	/**
	 * 자동광고의 HTML 생성
	 */
	private static function makeAutoAdsHTML( $clientId ){
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
	 * 배너 단위 광고의 HTML 생성
	 */
	private static function makeBannerHTML( $clientId, $unitId ){
		if(! $clientId || ! $unitId ){
			return '';
		}
		$html = <<<EOT
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$clientId}"
				crossorigin="anonymous"></script>
		EOT;

		$html .= <<< EOT
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
	 * 
	 * 확장 기능이 동작할 수 있는지에 대한 최소 조건 체크. 성능상 부담이 없도록 구성.
	 */
	private static function isValid(){
		global $wgEzxGoogleAdsense;

		# 기존의 체크에서 false 가 되었던 것이 있다면, 바로 false 리턴.
		if( !self::$_isAvailable ){
			return false;
		}

		# 설정되어 있지 않음
		if ( ! isset($wgEzxGoogleAdsense) ){
			self::setDisabled();
			return false;
		}

		# 코드 변경의 번거로움을 줄이기 위해서, 설정값 복사
		$settings = $wgEzxGoogleAdsense;

		# 'client_id'가 설정되어 있지 않음
		if ( ! isset($settings['client_id']) ){
			self::setDisabled();
			return false;
		}

		# 'client_id'가 유효함
		if ( is_string($settings['client_id']) && strlen($settings['client_id']) > 1){
			return true;
		}

		self::setDisabled();
		return false;
	}

	/**
	 * '사용 안 함'을 설정.
	 */
	private static function setDisabled(){
		self::$_isAvailable = false;
	}

	/**
	 * 조건 체크
	 */
	private static function isAvailable($config, $context){
		
		# 기존의 체크에서 false 가 되었던 것이 있다면, 바로 false 리턴.
		if( !self::$_isAvailable ){
			return false;
		}

		# 'client_id'가 지정되지 않았으면 보여지지 않도록 한다.
		if( ! self::isValidAdsId($config, 'client_id') ){
			self::setDisabled();
			return false;
		}

		# 익명 사용자에게만 보여지게 하는 옵션이 있으면, 익명 사용자에게만 보여준다.
		if ( $context->getUser()->isRegistered() && $config['anon_only'] ) {
			self::setDisabled();
			return false;
		}

		# 특정 아이피에서는 애드센스를 노출하지 않도록 한다. (예를 들어, 관리자)
		if ( ! empty($config['exclude_ip_list']) ){
			$remoteAddr = $_SERVER["REMOTE_ADDR"] ?? '';
			if( in_array($remoteAddr, $config['exclude_ip_list']) ){
				self::setDisabled();
				return false;
			}
		}

		# self::debugLog("isAvailable");
		# self::debugLog($ns);

		$titleObj = $context->getTitle();

		// 메인 이름공간의 페이지에서만 나오도록 함. 특수문서 등에서 나타나지 않도록.
		if( $titleObj->getNamespace() != NS_MAIN ){
			self::setDisabled();
			return false;
		}

		# 대문 페이지에서도 안 나오게하기
		if( $titleObj->isMainPage() ){
			self::setDisabled();
			return false;
		}

		# 본문의 길이가 짧을 때에는 광고를 출력하지 않도록 설정.
		if( $titleObj->getLength() <= $config['min_length'] ) {
			self::setDisabled();
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
			if(isset(self::$config['client_id'])){
				return self::$config;
			}
		}
		self::debugLog('::getConfiguration');

		global $wgEzxGoogleAdsense;

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
			'article_view_header_hook' => true,
			'site_notice_after_hook' => false,
			'anon_only' => false,
			'exclude_ip_list' => array(),
			'min_length' => 500,
			'debug' => false
		];
		
		# 설정값 병합
		if (isset($wgEzxGoogleAdsense)){
			self::debugLog('isset $wgEzxGoogleAdsense');
			$config = array_merge($config, $wgEzxGoogleAdsense);
		}

		self::$config = $config;
		return $config;
	}

	/**
	 * AdSense의 ID가 제대로된 입력값인지 확인.
	 */
	private static function isValidAdsId($config, $name){
		if( !isset($config[$name]) ){
			return false;
		}
		$keyId = $config[$name];

		if( is_string($keyId) && strlen($keyId) > 5 ) {
			return true;
		}
		return false;
	}

	/**
	 * 로깅 관련
	 */
	private static function debugLog($msg){
		global $wgDebugToolbar, $wgEzxGoogleAdsense;

		# 디버그툴바 사용중일 때만 허용.
		$useDebugToolbar = $wgDebugToolbar ?? false;
		if( !$useDebugToolbar ){
			return false;
		}

		// 로깅
		$isDebug = $wgEzxGoogleAdsense['debug'] ?? false;
		if($isDebug){
			if(is_string($msg)){
				wfDebugLog('AetGoogleAdSense', $msg);
			} else if(is_object($msg) || is_array($msg)){
				wfDebugLog('AetGoogleAdSense', json_encode($msg));
			} else {
				wfDebugLog('AetGoogleAdSense', json_encode($msg));
			}
		}
		return false;
	}
}
