<?php
/**
 * Cleantalk base class
 *
 * @version 2.1.1
 * @package Cleantalk
 * @subpackage Base
 * @author Cleantalk team (welcome@cleantalk.org)
 * @copyright (C) 2014 CleanTalk team (http://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @see https://github.com/CleanTalk/php-antispam 
 *
 */

/**
* Load JSON functions if they are not exists 
*/
if(!function_exists('json_encode')) {
    require_once 'JSON.php';

    function json_encode($data) {
        $json = new Services_JSON();
        return( $json->encode($data) );
    }

}
if(!function_exists('json_decode')) {
    require_once 'JSON.php';

    function json_decode($data) {
        $json = new Services_JSON();
        return( $json->decode($data) );
    }
}

/**
 * Response class
 */
class CleantalkResponse {

    /**
     *  Is stop words
     * @var int
     */
    public $stop_words = null;
    
    /**
     * Cleantalk comment
     * @var string
     */
    public $comment = null;

    /**
     * Is blacklisted
     * @var int
     */
    public $blacklisted = null;

    /**
     * Is allow, 1|0
     * @var int
     */
    public $allow = null;

    /**
     * Request ID
     * @var int
     */
    public $id = null;

    /**
     * Request errno
     * @var int
     */
    public $errno = null;

    /**
     * Error string
     * @var string
     */
    public $errstr = null;

    /**
     * Is fast submit, 1|0
     * @var string
     */
    public $fast_submit = null;

    /**
     * Is spam comment
     * @var string
     */
    public $spam = null;

    /**
     * Is JS
     * @var type 
     */
    public $js_disabled = null;

    /**
     * Sms check
     * @var type 
     */
    public $sms_allow = null;

    /**
     * Sms code result
     * @var type 
     */
    public $sms = null;
	
    /**
     * Sms error code
     * @var type 
     */
    public $sms_error_code = null;
	
    /**
     * Sms error code
     * @var type 
     */
    public $sms_error_text = null;
    
	/**
     * Stop queue message, 1|0
     * @var int  
     */
    public $stop_queue = null;
	
    /**
     * Account shuld by deactivated after registration, 1|0
     * @var int  
     */
    public $inactive = null;

    /**
     * Account status 
     * @var int  
     */
    public $account_status = -1;

    /**
     * Create server response
     *
     * @param type $response
     * @param type $obj
     */
    function __construct($response = null, $obj = null) {
        if ($response && is_array($response) && count($response) > 0) {
            foreach ($response as $param => $value) {
                $this->{$param} = $value;
            }
        } else {
            $this->errno = $obj->errno;
            $this->errstr = $obj->errstr;

			$this->errstr = preg_replace("/.+(\*\*\*.+\*\*\*).+/", "$1", $this->errstr);

            $this->stop_words = isset($obj->stop_words) ? utf8_decode($obj->stop_words) : null;
            $this->comment = isset($obj->comment) ? utf8_decode($obj->comment) : null;
            $this->blacklisted = (isset($obj->blacklisted)) ? $obj->blacklisted : null;
            $this->allow = (isset($obj->allow)) ? $obj->allow : 0;
            $this->id = (isset($obj->id)) ? $obj->id : null;
            $this->fast_submit = (isset($obj->fast_submit)) ? $obj->fast_submit : 0;
            $this->spam = (isset($obj->spam)) ? $obj->spam : 0;
            $this->js_disabled = (isset($obj->js_disabled)) ? $obj->js_disabled : 0;
            $this->sms_allow = (isset($obj->sms_allow)) ? $obj->sms_allow : null;
            $this->sms = (isset($obj->sms)) ? $obj->sms : null;
            $this->sms_error_code = (isset($obj->sms_error_code)) ? $obj->sms_error_code : null;
            $this->sms_error_text = (isset($obj->sms_error_text)) ? $obj->sms_error_text : null;
            $this->stop_queue = (isset($obj->stop_queue)) ? $obj->stop_queue : 0;
            $this->inactive = (isset($obj->inactive)) ? $obj->inactive : 0;
            $this->account_status = (isset($obj->account_status)) ? $obj->account_status : -1;

            if ($this->errno !== 0 && $this->errstr !== null && $this->comment === null)
                $this->comment = '*** ' . $this->errstr . ' Antispam service cleantalk.org ***'; 
        }
    }

}

/**
 * Request class
 */
class CleantalkRequest {

     /**
     *  All http request headers
     * @var string
     */
     public $all_headers = null;
     
     /**
     *  IP address of connection
     * @var string
     */
     //public $remote_addr = null;
     
     /**
     *  Last error number
     * @var integer
     */
     public $last_error_no = null;
     
     /**
     *  Last error time
     * @var integer
     */
     public $last_error_time = null;
     
     /**
     *  Last error text
     * @var string
     */
     public $last_error_text = null;

    /**
     * User message
     * @var string
     */
    public $message = null;

    /**
     * Post example with last comments
     * @var string
     */
    public $example = null;

    /**
     * Auth key
     * @var string
     */
    public $auth_key = null;

    /**
     * Engine
     * @var string
     */
    public $agent = null;

    /**
     * Is check for stoplist,
     * valid are 0|1
     * @var int
     */
    public $stoplist_check = null;

    /**
     * Language server response,
     * valid are 'en' or 'ru'
     * @var string
     */
    public $response_lang = null;

    /**
     * User IP
     * @var strings
     */
    public $sender_ip = null;

    /**
     * User email
     * @var strings
     */
    public $sender_email = null;

    /**
     * User nickname
     * @var string
     */
    public $sender_nickname = null;

    /**
     * Sender info JSON string
     * @var string
     */
    public $sender_info = null;

    /**
     * Post info JSON string
     * @var string
     */
    public $post_info = null;

    /**
     * Is allow links, email and icq,
     * valid are 1|0
     * @var int
     */
    public $allow_links = null;

    /**
     * Time form filling
     * @var int
     */
    public $submit_time = null;
    
    public $x_forwarded_for = '';
    public $x_real_ip = '';

    /**
     * Is enable Java Script,
     * valid are 0|1|2
	 * Status:
	 *  null - JS html code not inserted into phpBB templates
	 *  0 - JS disabled at the client browser
	 *  1 - JS enabled at the client broswer
     * @var int
     */
    public $js_on = null;

    /**
     * user time zone
     * @var string
     */
    public $tz = null;

    /**
     * Feedback string,
     * valid are 'requset_id:(1|0)'
     * @var string
     */
    public $feedback = null;

    /**
     * Phone number
     * @var type 
     */
    public $phone = null;
    
    /**
    * Method name
    * @var string
    */
    public $method_name = 'check_message'; 

    /**
     * Fill params with constructor
     * @param type $params
     */
    public function __construct($params = null) {
        if (is_array($params) && count($params) > 0) {
            foreach ($params as $param => $value) {
                $this->{$param} = $value;
            }
        }
    }

}

/**
 * Cleantalk class create request
 */
class Cleantalk {

    /**
     * Debug level
     * @var int
     */
    public $debug = 0;
	
    /**
	* Maximum data size in bytes
	* @var int
	*/
	private $dataMaxSise = 32768;
	
	/**
	* Data compression rate 
	* @var int
	*/
	private $compressRate = 6;
	
    /**
	* Server connection timeout in seconds 
	* @var int
	*/
	private $server_timeout = 6;

    /**
     * Cleantalk server url
     * @var string
     */
    public $server_url = null;

    /**
     * Last work url
     * @var string
     */
    public $work_url = null;

    /**
     * WOrk url ttl
     * @var int
     */
    public $server_ttl = null;

    /**
     * Time wotk_url changer
     * @var int
     */
    public $server_changed = null;

    /**
     * Flag is change server url
     * @var bool
     */
    public $server_change = false;

    /**
     * Use TRUE when need stay on server. Example: send feedback
     * @var bool
     */
    public $stay_on_server = false;
    
    /**
     * Codepage of the data 
     * @var bool
     */
    public $data_codepage = null;
    
    /**
     * API version to use 
     * @var string
     */
    public $api_version = '/api2.0';
    
    /**
     * Use https connection to servers 
     * @var bool 
     */
    public $ssl_on = false;
    
    /**
     * Path to SSL certificate 
     * @var string
     */
    public $ssl_path = '';

    /**
     * Minimal server response in miliseconds to catch the server
     *
     */
    public $min_server_timeout = 50;

    /**
     * Function checks whether it is possible to publish the message
     * @param CleantalkRequest $request
     * @return type
     */
    public function isAllowMessage(CleantalkRequest $request) {
        $this->filterRequest($request);
        $msg = $this->createMsg('check_message', $request);
        return $this->httpRequest($msg);
    }

    /**
     * Function checks whether it is possible to publish the message
     * @param CleantalkRequest $request
     * @return type
     */
    public function isAllowUser(CleantalkRequest $request) {
        $this->filterRequest($request);
        $msg = $this->createMsg('check_newuser', $request);
        return $this->httpRequest($msg);
    }

    /**
     * Function sends the results of manual moderation
     *
     * @param CleantalkRequest $request
     * @return type
     */
    public function sendFeedback(CleantalkRequest $request) {
        $this->filterRequest($request);
        $msg = $this->createMsg('send_feedback', $request);
        return $this->httpRequest($msg);
    }

    /**
     *  Filter request params
     * @param CleantalkRequest $request
     * @return type
     */
    private function filterRequest(CleantalkRequest &$request) {
        // general and optional
        foreach ($request as $param => $value) {
            if (in_array($param, array('message', 'example', 'agent',
                        'sender_info', 'sender_nickname', 'post_info', 'phone')) && !empty($value)) {
                if (!is_string($value) && !is_integer($value)) {
                    $request->$param = NULL;
                }
            }

            if (in_array($param, array('stoplist_check', 'allow_links')) && !empty($value)) {
                if (!in_array($value, array(1, 2))) {
                    $request->$param = NULL;
                }
            }
            
            if (in_array($param, array('js_on')) && !empty($value)) {
                if (!is_integer($value)) {
                    $request->$param = NULL;
                }
            }

            if ($param == 'sender_ip' && !empty($value)) {
                if (!is_string($value)) {
                    $request->$param = NULL;
                }
            }

            if ($param == 'sender_email' && !empty($value)) {
                if (!is_string($value)) {
                    $request->$param = NULL;
                }
            }

            if ($param == 'submit_time' && !empty($value)) {
                if (!is_int($value)) {
                    $request->$param = NULL;
                }
            }
        }
    }
    
	/**
     * Compress data and encode to base64 
     * @param type string
     * @return string 
     */
	private function compressData($data = null){
		
		if (strlen($data) > $this->dataMaxSise && function_exists('gzencode') && function_exists('base64_encode')){

			$localData = gzencode($data, $this->compressRate, FORCE_GZIP);

			if ($localData === false)
				return $data;
			
			$localData = base64_encode($localData);
			
			if ($localData === false)
				return $data;
			
			return $localData;
		}

		return $data;
	} 

    /**
     * Create msg for cleantalk server
     * @param type $method
     * @param CleantalkRequest $request
     * @return \xmlrpcmsg
     */
    private function createMsg($method, CleantalkRequest $request) {
        switch ($method) {
            case 'check_message':
                // Convert strings to UTF8
                $request->message = $this->stringToUTF8($request->message, $this->data_codepage);
                $request->example = $this->stringToUTF8($request->example, $this->data_codepage);
                $request->sender_email = $this->stringToUTF8($request->sender_email, $this->data_codepage);
                $request->sender_nickname = $this->stringToUTF8($request->sender_nickname, $this->data_codepage);

                $request->message = $this->compressData($request->message);
				$request->example = $this->compressData($request->example);
                break;

            case 'check_newuser':
                // Convert strings to UTF8
                $request->sender_email = $this->stringToUTF8($request->sender_email, $this->data_codepage);
                $request->sender_nickname = $this->stringToUTF8($request->sender_nickname, $this->data_codepage);
                break;

            case 'send_feedback':
                if (is_array($request->feedback)) {
                    $request->feedback = implode(';', $request->feedback);
                }
                break;
        }
        
        $request->method_name = $method;
        
        //
        // Removing non UTF8 characters from request, because non UTF8 or malformed characters break json_encode().
        //
        foreach ($request as $param => $value) {
            if (!preg_match('//u', $value)) {
                $request->{$param} = 'Nulled. Not UTF8 encoded or malformed.'; 
            }
        }
        
        return $request;
    }
    
    /**
     * Send JSON request to servers 
     * @param $msg
     * @return boolean|\CleantalkResponse
     */
    private function sendRequest($data = null, $url, $server_timeout = 3) {
        // Convert to array
        $data = (array)json_decode(json_encode($data), true);

        // Convert to JSON
        $data = json_encode($data);
        
        if (isset($this->api_version)) {
            $url = $url . $this->api_version;
        }
        
        // Switching to secure connection
        if ($this->ssl_on && !preg_match("/^https:/", $url)) {
            $url = preg_replace("/^(http)/i", "$1s", $url);
        }

        $result = false;
        $curl_error = null;
		if(function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $server_timeout);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // resolve 'Expect: 100-continue' issue
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            // see http://stackoverflow.com/a/23322368
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            
            // Disabling CA cert verivication
            // Disabling common name verification
            if ($this->ssl_on && $this->ssl_path=='') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            else if ($this->ssl_on && $this->ssl_path!='') {
            	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
                curl_setopt($ch, CURLOPT_CAINFO, $this->ssl_path);
            }

            $result = curl_exec($ch);
            if (!$result) {
                $curl_error = curl_error($ch);
            }
            
            curl_close($ch); 
        }

        if (!$result) {
            $allow_url_fopen = ini_get('allow_url_fopen');
            if (function_exists('file_get_contents') && isset($allow_url_fopen) && $allow_url_fopen == '1') {
                $opts = array('http' =>
                  array(
                    'method'  => 'POST',
                    'header'  => "Content-Type: text/html\r\n",
                    'content' => $data,
                    'timeout' => $server_timeout
                  )
                );

                $context  = stream_context_create($opts);
                $result = @file_get_contents($url, false, $context);
            }
        }
        
        if (!$result || !cleantalk_is_JSON($result)) {
            $response = null;
            $response['errno'] = 1;
            if ($curl_error) {
                $response['errstr'] = sprintf("CURL error: '%s'", $curl_error); 
            } else {
                $response['errstr'] = 'No CURL support compiled in'; 
            }
            $response['errstr'] .= ' or disabled allow_url_fopen in php.ini.'; 
            $response = json_decode(json_encode($response));
            
            return $response;
        }
        
        $errstr = null;
        $response = json_decode($result);
        if ($result !== false && is_object($response)) {
            $response->errno = 0;
            $response->errstr = $errstr;
        } else {
            $errstr = 'Unknown response from ' . $url . '.' . ' ' . $result;
            
            $response = null;
            $response['errno'] = 1;
            $response['errstr'] = $errstr;
            $response = json_decode(json_encode($response));
        } 
        
        
        return $response;
    }

    /**
     * httpRequest 
     * @param $msg
     * @return boolean|\CleantalkResponse
     */
    private function httpRequest($msg) {
        $result = false;
        $msg->all_headers=json_encode(apache_request_headers());
        //$msg->remote_addr=$_SERVER['REMOTE_ADDR'];
        //$msg->sender_info['remote_addr']=$_SERVER['REMOTE_ADDR'];
        $si=(array)json_decode($msg->sender_info,true);
        if(defined('IN_PHPBB'))
        {
        	global $request;
        	if(method_exists($request,'server'))
        	{
        		$si['remote_addr']=$request->server('REMOTE_ADDR');
        		$msg->x_forwarded_for=$request->server('X_FORWARDED_FOR');
        		$msg->x_real_ip=$request->server('X_REAL_IP');
        	}
        }
        else
        {
        	$si['remote_addr']=$_SERVER['REMOTE_ADDR'];
        	$msg->x_forwarded_for=@$_SERVER['X_FORWARDED_FOR'];
        	$msg->x_real_ip=@$_SERVER['X_REAL_IP'];
        }
        $msg->sender_info=json_encode($si);
        if (((isset($this->work_url) && $this->work_url !== '') && ($this->server_changed + $this->server_ttl > time()))
				|| $this->stay_on_server == true) {
	        
            $url = (!empty($this->work_url)) ? $this->work_url : $this->server_url;
					
            $result = $this->sendRequest($msg, $url, $this->server_timeout);
        }

        if (($result === false || $result->errno != 0) && $this->stay_on_server == false) {
            // Split server url to parts
            preg_match("@^(https?://)([^/:]+)(.*)@i", $this->server_url, $matches);
            $url_prefix = '';
            if (isset($matches[1]))
                $url_prefix = $matches[1];

            $pool = null;
            if (isset($matches[2]))
                $pool = $matches[2];
            
            $url_suffix = '';
            if (isset($matches[3]))
                $url_suffix = $matches[3];
            
            if ($url_prefix === '')
                $url_prefix = 'http://';

            if (empty($pool)) {
                return false;
            } else {
                // Loop until find work server
                foreach ($this->get_servers_ip($pool) as $server) {
                    if ($server['host'] === 'localhost' || $server['ip'] === null) {
                        $work_url = $server['host'];
                    } else {
                        $server_host = $server['ip'];
                        $work_url = $server_host;
                    }
                    $work_url = $url_prefix . $work_url; 
                    if (isset($url_suffix)) 
                        $work_url = $work_url . $url_suffix;
                    
                    $this->work_url = $work_url;
                    $this->server_ttl = $server['ttl'];
                    
                    $result = $this->sendRequest($msg, $this->work_url, $this->server_timeout);

                    if ($result !== false && $result->errno === 0) {
                        $this->server_change = true;
                        break;
                    }
                }
            }
        }

        $response = new CleantalkResponse(null, $result);

        if (!empty($this->data_codepage) && $this->data_codepage !== 'UTF-8') {
            if (!empty($response->comment))
            $response->comment = $this->stringFromUTF8($response->comment, $this->data_codepage);
            if (!empty($response->errstr))
            $response->errstr = $this->stringFromUTF8($response->errstr, $this->data_codepage);
            if (!empty($response->sms_error_text))
            $response->sms_error_text = $this->stringFromUTF8($response->sms_error_text, $this->data_codepage);
        }

        return $response;
    }
    
    /**
     * Function DNS request
     * @param $host
     * @return array
     */
    public function get_servers_ip($host) {
        $response = null;
        if (!isset($host))
            return $response;

        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_A);

            if ($records !== FALSE) {
                foreach ($records as $server) {
                    $response[] = $server;
                }
            }
        }

        if (count($response) == 0 && function_exists('gethostbynamel')) {
            $records = gethostbynamel($host);

            if ($records !== FALSE) {
                foreach ($records as $server) {
                    $response[] = array("ip" => $server,
                        "host" => $host,
                        "ttl" => $this->server_ttl
                    );
                }
            }
        }

        if (count($response) == 0) {
            $response[] = array("ip" => null,
                "host" => $host,
                "ttl" => $this->server_ttl
            );
        } else {
            // $i - to resolve collisions with localhost
            $i = 0;
            $r_temp = null;
            $fast_server_found = false;
            foreach ($response as $server) {
                
                // Do not test servers because fast work server found
                if ($fast_server_found) {
                    $ping = $this->min_server_timeout; 
                } else {
                    $ping = $this->httpPing($server['ip']);
                    $ping = $ping * 1000;
                }
                
                // -1 server is down, skips not reachable server
                if ($ping != -1) {
                    $r_temp[$ping + $i] = $server;
                }
                $i++;
                
                if ($ping < $this->min_server_timeout) {
                    $fast_server_found = true;
                }
            }
            if (count($r_temp)){
                ksort($r_temp);
                $response = $r_temp;
            }
        }

        return $response;
    }

    /**
     * Function to get the message hash from Cleantalk.ru comment
     * @param $message
     * @return null
     */
    public function getCleantalkCommentHash($message) {
        $matches = array();
        if (preg_match('/\n\n\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
            return $matches[1];
        else if (preg_match('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
            return $matches[1];

        return NULL;
    }

    /**
     * Function adds to the post comment Cleantalk.ru
     * @param $message
     * @param $comment
     * @return string
     */
    public function addCleantalkComment($message, $comment) {
        $comment = preg_match('/\*\*\*(.+)\*\*\*/', $comment, $matches) ? $comment : '*** ' . $comment . ' ***';
        return $message . "\n\n" . $comment;
    }

    /**
     * Function deletes the comment Cleantalk.ru
     * @param $message
     * @return mixed
     */
    public function delCleantalkComment($message) {
        $message = preg_replace('/\n\n\*\*\*.+\*\*\*$/', '', $message);

        // DLE sign cut
        $message = preg_replace('/<br\s?\/><br\s?\/>\*\*\*.+\*\*\*$/', '', $message);

        $message = preg_replace('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+\*\*\*$/', '', $message);
        
        return $message;
    }

    /**
    *   Get user IP behind proxy server
    */
    public function ct_session_ip( $data_ip ) {
        if (!$data_ip || !preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $data_ip)) {
            return $data_ip;
        }
        /*if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            
            $forwarded_ip = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);

            // Looking for first value in the list, it should be sender real IP address
            if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $forwarded_ip[0])) {
                return $data_ip;
            }

            $private_src_ip = false;
            $private_nets = array(
                '10.0.0.0/8',
                '127.0.0.0/8',
                '176.16.0.0/12',
                '192.168.0.0/16',
            );

            foreach ($private_nets as $v) {

                // Private IP found
                if ($private_src_ip) {
                    continue;
                }
                
                if ($this->net_match($v, $data_ip)) {
                    $private_src_ip = true;
                }
            }
            if ($private_src_ip) {
                // Taking first IP from the list HTTP_X_FORWARDED_FOR 
                $data_ip = $forwarded_ip[0]; 
            }
        }

        return $data_ip;*/
        return cleantalk_get_real_ip();
    }

    /**
    * From http://php.net/manual/en/function.ip2long.php#82397
    */
    public function net_match($CIDR,$IP) { 
        list ($net, $mask) = explode ('/', $CIDR); 
        return ( ip2long ($IP) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($net); 
    } 
    
    /**
    * Function to check response time
    * param string
    * @return int
    */
    function httpPing($host){

        // Skip localhost ping cause it raise error at fsockopen.
        // And return minimun value 
        if ($host == 'localhost')
            return 0.001;

        $starttime = microtime(true);
        $file      = @fsockopen ($host, 80, $errno, $errstr, $this->server_timeout);
        $stoptime  = microtime(true);
        $status    = 0;
        if (!$file) {
            $status = -1;  // Site is down
        } else {
            fclose($file);
            $status = ($stoptime - $starttime);
            $status = round($status, 4);
        }
        
        return $status;
    }
    
    /**
    * Function convert string to UTF8 and removes non UTF8 characters 
    * param string
    * param string
    * @return string
    */
    function stringToUTF8($str, $data_codepage = null){
        if (!preg_match('//u', $str) && function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
            
            if ($data_codepage !== null)
                return mb_convert_encoding($str, 'UTF-8', $data_codepage);

            $encoding = mb_detect_encoding($str);
            if ($encoding)
                return mb_convert_encoding($str, 'UTF-8', $encoding);
        }
        
        return $str;
    }
    
    /**
    * Function convert string from UTF8 
    * param string
    * param string
    * @return string
    */
    function stringFromUTF8($str, $data_codepage = null){
        if (preg_match('//u', $str) && function_exists('mb_convert_encoding') && $data_codepage !== null) {
            return mb_convert_encoding($str, $data_codepage, 'UTF-8');
        }
        
        return $str;
    }
    
    /**
     * Function gets information about spam active networks 
     *
     * @param string api_key
     * @return JSON/array 
     */
    public function get_2s_blacklists_db ($api_key) {
        $request=Array();
        $request['method_name'] = '2s_blacklists_db'; 
        $request['auth_key'] = $api_key;
        $url='https://api.cleantalk.org';
        $result=sendRawRequest($url,$request);
        return $result;
    }
}

/**
 * Function gets access key automatically
 *
 * @param string website admin email
 * @param string website host
 * @param string website platform
 * @return type
 */

function getAutoKey($email, $host, $platform)
{
	$request=Array();
	$request['method_name'] = 'get_api_key'; 
	$request['email'] = $email;
	$request['website'] = $host;
	$request['platform'] = $platform;
	$url='https://api.cleantalk.org';
	$result=sendRawRequest($url,$request);
	return $result;
}

/**
 * Function gets information about renew notice
 *
 * @param string api_key
 * @return type
 */

function noticePaidTill($api_key)
{
	$request=Array();
	$request['method_name'] = 'notice_paid_till'; 
	$request['auth_key'] = $api_key;
	$url='https://api.cleantalk.org';
	$result=sendRawRequest($url,$request);
	return $result;
}

/**
 * Function sends raw request to API server
 *
 * @param string url of API server
 * @param array data to send
 * @param boolean is data have to be JSON encoded or not
 * @param integer connect timeout
 * @return type
 */

function sendRawRequest($url,$data,$isJSON=false,$timeout=3)
{
	$result=null;
	if(!$isJSON)
	{
		$data=http_build_query($data);
	}
	else
	{
		$data= json_encode($data);
	}
	$curl_exec=false;
	if (function_exists('curl_init') && function_exists('json_decode'))
	{
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		
		// receive server response ...
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// resolve 'Expect: 100-continue' issue
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		$result = @curl_exec($ch);
		if($result!==false)
		{
			$curl_exec=true;
		}
		@curl_close($ch);
	}
	if(!$curl_exec)
	{
		$opts = array(
		    'http'=>array(
		        'method'=>"POST",
		        'content'=>$data)
		);
		$context = stream_context_create($opts);
		$result = @file_get_contents($url, 0, $context);
	}
	return $result;
}

if( !function_exists('apache_request_headers') )
{
	function apache_request_headers()
	{
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach($_SERVER as $key => $val)
		{
			if( preg_match($rx_http, $key) )
			{
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = array();
				$rx_matches = explode('_', $arh_key);
				if( count($rx_matches) > 0 and strlen($arh_key) > 2 )
				{
					foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return( $arh );
	}
}

function cleantalk_get_real_ip()
{
	if ( function_exists( 'apache_request_headers' ) )
	{
		$headers = apache_request_headers();
	}
	else
	{
		$headers = $_SERVER;
	}
	if ( array_key_exists( 'X-Forwarded-For', $headers ) )
	{
		$the_ip=explode(",", trim($headers['X-Forwarded-For']));
		$the_ip = trim($the_ip[0]);
	}
	elseif ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ))
	{
		$the_ip=explode(",", trim($headers['HTTP_X_FORWARDED_FOR']));
		$the_ip = trim($the_ip[0]);
	}
	else
	{
		$the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}
	return $the_ip;
}

function cleantalk_is_JSON($string)
{
    return ((is_string($string) && (is_object(json_decode($string)) || is_array(json_decode($string))))) ? true : false;
}