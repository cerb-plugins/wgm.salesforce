<?php
if(class_exists('Extension_PageMenuItem')):
class WgmSalesforce_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmsalesforce.setup.menu.plugins.salesforce';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.salesforce::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmSalesforce_SetupSection extends Extension_PageSection {
	const ID = 'wgmsalesforce.setup.salesforce';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'salesforce');
		
		$credentials = DevblocksPlatform::getPluginSetting('wgm.salesforce','credentials',false,true,true);
		$tpl->assign('credentials', $credentials);
		
		// Template
		
		$tpl->display('devblocks:wgm.salesforce::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$consumer_key = DevblocksPlatform::importGPC($_REQUEST['consumer_key'],'string','');
			@$consumer_secret = DevblocksPlatform::importGPC($_REQUEST['consumer_secret'],'string','');
			
			if(empty($consumer_key) || empty($consumer_secret))
				throw new Exception("Both the 'Client ID' and 'Client Secret' are required.");
			
			$credentials = [
				'consumer_key' => $consumer_key,
				'consumer_secret' => $consumer_secret,
			];
			
			DevblocksPlatform::setPluginSetting('wgm.salesforce', 'credentials', $credentials, true, true);
			
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
		if(false == ($credentials = DevblocksPlatform::getPluginSetting('wgm.salesforce','credentials',false,true,true)))
			return;
		
		@$this->_client_id = $credentials['consumer_key'];
		@$this->_client_secret = $credentials['consumer_secret'];
		$this->_oauth = DevblocksPlatform::services()->oauth($this->_client_id, $this->_client_secret);
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

class ServiceProvider_Salesforce extends Extension_ServiceProvider implements IServiceProvider_OAuth, IServiceProvider_OAuthRefresh, IServiceProvider_HttpRequestSigner {
	const ID = 'wgm.salesforce.service.provider';

	function renderConfigForm(Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl->assign('account', $account);
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.salesforce::provider/salesforce.tpl');
	}
	
	function saveConfigForm(Model_ConnectedAccount $account, array &$params) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		$encrypt = DevblocksPlatform::services()->encryption();
		
		// Decrypt OAuth params
		if(isset($edit_params['params_json'])) {
			if(false == ($outh_params_json = $encrypt->decrypt($edit_params['params_json'])))
				return "The connected account authentication is invalid.";
				
			if(false == ($oauth_params = json_decode($outh_params_json, true)))
				return "The connected account authentication is malformed.";
			
			if(is_array($oauth_params))
			foreach($oauth_params as $k => $v)
				$params[$k] = $v;
		}
		
		return true;
	}
	
	private function _getAppKeys() {
		if(false == ($credentials = DevblocksPlatform::getPluginSetting('wgm.salesforce','credentials',false,true,true)))
			return false;
		
		@$consumer_key = $credentials['consumer_key'];
		@$consumer_secret = $credentials['consumer_secret'];
		
		if(empty($consumer_key) || empty($consumer_secret))
			return false;
		
		return array(
			'key' => $consumer_key,
			'secret' => $consumer_secret,
		);
	}
	
	function oauthRender() {
		$url_writer = DevblocksPlatform::services()->url();
		
		@$form_id = DevblocksPlatform::importGPC($_REQUEST['form_id'], 'string', '');
		
		// Store the $form_id in the session
		$_SESSION['oauth_form_id'] = $form_id;
		
		// [TODO] Report about missing app keys
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		$oauth = DevblocksPlatform::services()->oauth($app_keys['key'], $app_keys['secret']);
		
		// Persist the view_id in the session
		$_SESSION['oauth_state'] = CerberusApplication::generatePassword(24);
		
		// OAuth callback
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_Salesforce::ID), true);
		
		$url = sprintf("%s?response_type=code&client_id=%s&redirect_uri=%s&state=%s&scope=%s&display=popup&prompt=consent", 
			WgmSalesforce_API::SALESFORCE_AUTHENTICATE_URL,
			$app_keys['key'],
			rawurlencode($redirect_url),
			$_SESSION['oauth_state'],
			rawurlencode('api refresh_token')
		);
		
		header('Location: ' . $url);
		exit;
	}
	
	function oauthCallback() {
		@$form_id = $_SESSION['oauth_form_id'];
		
		@$oauth_state = $_SESSION['oauth_state'];
		unset($_SESSION['oauth_form_id']);
		
		@$code = DevblocksPlatform::importGPC($_REQUEST['code'], 'string', '');
		@$state = DevblocksPlatform::importGPC($_REQUEST['state'], 'string', '');
		@$error = DevblocksPlatform::importGPC($_REQUEST['error'], 'string', '');
		@$error_msg = DevblocksPlatform::importGPC($_REQUEST['error_description'], 'string', '');
		
		$active_worker = CerberusApplication::getActiveWorker();
		$url_writer = DevblocksPlatform::services()->url();
		$encrypt = DevblocksPlatform::services()->encryption();
		
		$redirect_url = $url_writer->write(sprintf('c=oauth&a=callback&ext=%s', ServiceProvider_Salesforce::ID), true);
		
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		// Compare $state
		if($oauth_state != $state)
			return false;
		
		$oauth = DevblocksPlatform::services()->oauth($app_keys['key'], $app_keys['secret']);
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
			$label = $json['display_name'];
		
		// Output
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('form_id', $form_id);
		$tpl->assign('label', $label);
		$tpl->assign('params_json', $encrypt->encrypt(json_encode($params)));
		$tpl->display('devblocks:cerberusweb.core::internal/connected_account/oauth_callback.tpl');
	}

	function oauthRefreshAccessToken(Model_ConnectedAccount $account) {
		$credentials = $account->decryptParams();
		
		if(false == ($app_keys = $this->_getAppKeys()))
			return false;
		
		$oauth = DevblocksPlatform::services()->oauth($app_keys['key'], $app_keys['secret']);
		
		$params = $oauth->getRefreshToken(WgmSalesforce_API::SALESFORCE_REQUEST_TOKEN_URL, [
			'grant_type' => 'refresh_token',
			'refresh_token' => $credentials['refresh_token'],
			'client_id' => $app_keys['key'],
			'client_secret' => $app_keys['secret'],
			'format' => 'json',
		]);
		
		if(!$params || !is_array($params))
			return false;
		
		if(isset($params['error']) && !empty($params['error']))
			return false;
		
		DAO_ConnectedAccount::setAndEncryptParams($account->id, $params);
		
		return true;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['access_token'])
		)
			return false;
		
		// Add a bearer token
		$headers[] = sprintf('Authorization: %s %s', $credentials['token_type'], $credentials['access_token']);
		
		return true;
	}
}