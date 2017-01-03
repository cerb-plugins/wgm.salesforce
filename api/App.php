<?php
if(class_exists('Extension_PageMenuItem')):
class WgmSalesforce_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmsalesforce.setup.menu.plugins.salesforce';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.salesforce::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmSalesforce_SetupSection extends Extension_PageSection {
	const ID = 'wgmsalesforce.setup.salesforce';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'salesforce');
		
		$params = array(
			'consumer_key' => DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_key',''),
			'consumer_secret' => DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_secret',''),
		);
		$tpl->assign('params', $params);
		
		// Template
		
		$tpl->display('devblocks:wgm.salesforce::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the 'Client ID' and 'Client Secret' are required.");
			
			DevblocksPlatform::setPluginSetting('wgm.salesforce', 'consumer_key', $consumer_key);
			DevblocksPlatform::setPluginSetting('wgm.salesforce', 'consumer_secret', $consumer_secret);
			
			echo json_encode(array('status' => true, 'message' => 'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status' => false, 'error' => $e->getMessage()));
			return;
		}
	}
	
	/*
	function testAction() {
		$access_token = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'access_token', null);
		$instance_url = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'instance_url', null);
		
		$query = "SELECT Name, Id FROM Account LIMIT 100";
		$url = $instance_url . '/services/data/v20.0/query?q=' . urlencode($query);
		
		$ch = DevblocksPlatform::curlInit($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: OAuth $access_token"
		));
		
		$json_response = DevblocksPlatform::curlExec($ch);
		curl_close($ch);
		
		$response = json_decode($json_response, true);
		
		$total_size = $response ['totalSize'];
		
		echo "$total_size record(s) returned<br/><br/>";
		foreach ( ( array ) $response['records'] as $record) {
			echo $record ['Id'] . ", " . $record ['Name'] . "<br/>";
		}
		echo "<br/>";
	}
	
	function createAction() {
		$access_token = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'access_token', null);
		$instance_url = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'instance_url', null);
		
		//$query = "SELECT Name, Id FROM Account LIMIT 100";
		
		$url = $instance_url . '/services/data/v20.0/sobjects/Account';
		
		$ch = DevblocksPlatform::curlInit($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
			'Name' => 'Example, Inc',
		)));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: OAuth $access_token",
			"Content-Type: application/json",
		));
		
		$json_response = DevblocksPlatform::curlExec($ch);
		curl_close($ch);
		
		$response = json_decode($json_response, true);
		
		var_dump($response);
	}
	
	function metaAction() {
		$access_token = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'access_token', null);
		$instance_url = DevblocksPlatform::getPluginSetting('wgm.salesforce', 'instance_url', null);
		
		$url = $instance_url . '/services/data/v20.0/sobjects/Account/describe';
		
		$ch = DevblocksPlatform::curlInit($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Authorization: OAuth $access_token",
		));
		
		$json_response = DevblocksPlatform::curlExec($ch);
		curl_close($ch);
		
		$response = json_decode($json_response, true);
		
		var_dump($response);
	}
	*/
};
endif;

class WgmSalesforce_API {
	const SALESFORCE_BASE_URL = "https://login.salesforce.com/services/data/v20.0/";
	const SALESFORCE_REQUEST_TOKEN_URL = "https://login.salesforce.com/services/oauth2/token";
	const SALESFORCE_AUTHENTICATE_URL = "https://login.salesforce.com/services/oauth2/authorize";
	
	static $_instance = null;
	private $_oauth = null;
	private $_client_id = null;
	private $_client_secret = null;
	//private $_instance_url = null;
	
	private function __construct() {
		$this->_client_id = DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_key','');
		$this->_client_secret = DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_secret','');
		$this->_oauth = DevblocksPlatform::getOAuthService($this->_client_id, $this->_client_secret);
	}
	
	/**
	 * @return WgmSalesforce_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmSalesforce_API();
		}

		return self::$_instance;
	}
	
	public function setToken($token) {
		$this->_oauth->setTokens($token);
	}
	
	public function post($path, $params) {
		$this->_fetch($path, 'POST', $params);
	}
	
	public function get($path) {
		return $this->_fetch($path, 'GET');
	}
	
	private function _fetch($url, $method = 'GET', $params = array()) {
		// [TODO] URL on top of instance
		return $this->_oauth->executeRequestWithToken($method, $url, $params, 'Bearer');
	}
};

class ServiceProvider_Salesforce extends Extension_ServiceProvider implements IServiceProvider_OAuth {
	const ID = 'wgm.salesforce.service.provider';

	private function _getAppKeys() {
		$consumer_key = DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_key','');
		$consumer_secret = DevblocksPlatform::getPluginSetting('wgm.salesforce','consumer_secret','');
		
		if(empty($consumer_key) || empty($consumer_secret))
			return false;
		
		return array(
			'key' => $consumer_key,
			'secret' => $consumer_secret,
		);
	}
	
	function renderPopup() {
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'], 'string', '');
		
		$url_writer = DevblocksPlatform::getUrlService();
		
		// [TODO] Report about missing app keys
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		$oauth = DevblocksPlatform::getOAuthService($app_keys['key'], $app_keys['secret']);
		
		// Persist the view_id in the session
		$_SESSION['oauth_view_id'] = $view_id;
		$_SESSION['oauth_state'] = CerberusApplication::generatePassword(24);
		
		// OAuth callback
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_Salesforce::ID), true);
		
		$url = sprintf("%s?response_type=code&client_id=%s&redirect_uri=%s&state=%s&scope=%s&display=popup&prompt=consent", 
			WgmSalesforce_API::SALESFORCE_AUTHENTICATE_URL,
			$app_keys['key'],
			rawurlencode($redirect_url),
			$_SESSION['oauth_state'],
			rawurlencode('api')
		);
		
		header('Location: ' . $url);
	}
	
	function oauthCallback() {
		// [TODO] Do this everywhere?
		@$view_id = $_SESSION['oauth_view_id'];
		@$oauth_state = $_SESSION['oauth_state'];
		
		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		@$state = DevblocksPlatform::importGPC($_REQUEST['state'], 'string', '');
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		@$error_msg = DevblocksPlatform::importGPC($_REQUEST['error_description'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::getUrlService();
		
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_Salesforce::ID), true);
		
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		// [TODO] Check $error state
		// [TODO] Compare $state
		
		$oauth = DevblocksPlatform::getOAuthService($app_keys['key'], $app_keys['secret']);
		$oauth->setTokens($code);
		
		$params = $oauth->getAccessToken(WgmSalesforce_API::SALESFORCE_REQUEST_TOKEN_URL, array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirect_url,
			'client_id' => $app_keys['key'],
			'client_secret' => $app_keys['secret'],
		));
		
		if(!is_array($params) || !isset($params['access_token'])) {
			return false;
		}
		
		$salesforce = WgmSalesforce_API::getInstance();
		$salesforce->setToken($params['access_token']);
		
		$label = 'Salesforce';
		
		// Load their profile
		
		$json = $salesforce->get($params['id']);
		
		// Die with error
		if(!is_array($json))
			return false;
		
		if(isset($json['display_name']))
			$label .= sprintf(" @%s", $json['display_name']);
		
		// Save the account
		
		$id = DAO_ConnectedAccount::create(array(
			DAO_ConnectedAccount::NAME => $label,
			DAO_ConnectedAccount::EXTENSION_ID => ServiceProvider_Salesforce::ID,
			DAO_ConnectedAccount::OWNER_CONTEXT => CerberusContexts::CONTEXT_WORKER,
			DAO_ConnectedAccount::OWNER_CONTEXT_ID => $active_worker->id,
		));
		
		DAO_ConnectedAccount::setAndEncryptParams($id, $params);
		
		if($view_id) {
			echo sprintf("<script>window.opener.genericAjaxGet('view%s', 'c=internal&a=viewRefresh&id=%s');</script>",
				rawurlencode($view_id),
				rawurlencode($view_id)
			);
			
			C4_AbstractView::setMarqueeContextCreated($view_id, CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $id);
		}
		
		echo "<script>window.close();</script>";
	}
}
