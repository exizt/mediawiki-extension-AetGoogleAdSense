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
	 * 컨텐츠 상단에 나타나는 애드센스 단위 광고.
	 * 
	 * 'SiteNoticeAfter'후킹 이용.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SiteNoticeAfter
	 */
	public static function onSiteNoticeAfterGAdsCTop(&$siteNotice, $skin) {
		self::debugLog('::onSiteNoticeAfterGAdsCTop');

		// 설정 로드
		$config = self::getConfiguration();
		// self::debugLog($config);

		// 유효성 체크
		if( !self::isAvailable($config, $skin->getUser()->isRegistered(), $skin->getTitle()) ){
			return;
		}

		# 해당되는 slot id가 지정되지 않았으면 보이지 않게 함
		if( ! self::isOptionSet($config, 'unit_id_content_top') ){
			return;
		}

		// 
		$siteNotice .= <<<EOT
		<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$config['client_id']}"
				crossorigin="anonymous"></script>
		EOT;

		$siteNotice .= <<< EOT
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="{$config['client_id']}"
     data-ad-slot="{$config['unit_id_content_top']}"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
EOT;
        return true;
	}

	/**
	 * 컨텐츠 하단에 나타나는 애드센스 단위 광고.
	 * 
	 * 'SkinAfterContent'후킹 이용.
	 * 
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAfterContent
	 */
	public static function onSkinAfterContentGAdsCBottom(&$data, $skin) {
		self::debugLog('::onSkinAfterContentGAdsCBottom');

		// 설정 로드
		$config = self::getConfiguration();
		// self::debugLog($config);

		// 유효성 체크
		if( !self::isAvailable($config, $skin->getUser()->isRegistered(), $skin->getTitle()) ){
			return;
		}

		# 해당되는 slot id가 지정되지 않았으면 보이지 않게 함
		if( ! self::isOptionSet($config, 'unit_id_content_bottom') ){
			return;
		}

		$data .= <<<EOT
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$config['client_id']}"
		crossorigin="anonymous"></script>
EOT;
		
		$data .= <<< EOT
<ins class="adsbygoogle"
		style="display:block"
		data-ad-client="{$config['client_id']}"
		data-ad-slot="{$config['unit_id_content_bottom']}"
		data-ad-format="auto"
		data-full-width-responsive="true"></ins>
<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
</script>
EOT;
		return true;
	}

	/**
	 * 조건 체크
	 */
	public static function isAvailable($config, $isRegistered, $titleObj){
		
		# 기존의 체크에서 false 가 되었던 것이 있다면, 바로 false 리턴.
		if( !self::$_isAvailable ){
			return false;
		}

		# 'client_id'가 지정되지 않았으면 보여지지 않도록 한다.
		if( ! self::isOptionSet($config, 'client_id') ){
			self::$_isAvailable = false;
			return false;
		}

		# 익명 사용자에게만 보여지게 하는 옵션이 있으면, 익명 사용자에게만 보여준다.
		if ( $isRegistered && $config['anon_only'] ) {
			self::$_isAvailable = false;
			return false;
		}

		# 특정 아이피에서는 애드센스를 노출하지 않도록 한다. (예를 들어, 관리자)
		if ( ! empty($config['exclude_ip_list']) ){
			$remoteAddr = $_SERVER["REMOTE_ADDR"] ?? '';
			if( in_array($remoteAddr, $config['exclude_ip_list']) ){
				return false;
			}
		}

		# self::debugLog("isAvailable");
		# self::debugLog($ns);

		// 메인 이름공간의 페이지에서만 나오도록 함. 특수문서 등에서 나타나지 않도록.
		if( $titleObj->getNamespace() != NS_MAIN ){
			self::$_isAvailable = false;
			return false;
		}

		# 대문 페이지에서도 안 나오게하기
		if( $titleObj->isMainPage() ){
			self::$_isAvailable = false;
			return false;
		}

		# 본문의 길이가 짧을 때에는 광고를 출력하지 않도록 설정.
		if( $titleObj->getLength() <= 100 ) {
			self::$_isAvailable = false;
			return false;
		}

		return true;
	}

	/**
	 * 설정을 로드함.
	 */
	public static function getConfiguration(){
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
			'enabled' => false,
			'client_id' => '',
			'unit_id_content_top' => '',
			'unit_id_content_bottom' => '',
			'anon_only' => false,
			'exclude_ip_list' => array(),
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
	 * 옵션이 지정되어있는지 여부
	 * 
	 * @return boolean false (지정되지 않았음) /true(지정되어 있음)
	 */
	private static function isOptionSet($config, $name){
		if( !isset($config[$name]) ){
			return false;
		}
		if($config[$name] === '' || $config[$name] === 'none'
		 || $config[$name] === false || $config[$name] === NULL){
			return false;
		}
		return true;
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

		// 디버깅 여부
		if(is_array(self::$config)){
			$isDebug = self::$config['Debug'];
		} else {
			$isDebug = $wgEzxGoogleAdsense['Debug'] ?? false;
		}

		// 로깅
		if($isDebug){
			if(is_string($msg)){
				wfDebugLog('AetGoogleAdSense', $msg);
			} else if(is_object($msg) || is_array($msg)){
				wfDebugLog('AetGoogleAdSense', json_encode($msg));
			} else {
				wfDebugLog('AetGoogleAdSense', json_encode($msg));
			}
		} else {
			return false;
		}
	}
}