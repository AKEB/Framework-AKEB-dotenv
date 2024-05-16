<?php

namespace AKEB;

class CurlGet {
	public $connectTimeout = 10;
	public $timeout = 60;

	public $responseCode;
	public $responseTime;
	public $responseContentType;
	public $responseHeader;
	public $responseBody;
	public $responseError;
	public $responseErrorNum;

	private $sslVerify = true;
	private $sslCert = '';
	private $curl;
	private $scheme;
	private $host;
	private $port;
	private $user;
	private $pass;
	private $path;
	private $fragment;
	private $GET=[];
	private $POST=[];
	private $BODY;
	private $HEADER=[];

	private $debug=false;
	private $debugDir = null;
	private $debugFile = null;
	private $processName = null;
	private $followLocation = true;

	public function __construct($url='', $GET=[], $POST=[], $HEADER=[]) {
		$this->curl = curl_init();
		$this->GET = [];
		if (isset($POST)) $this->setPOST($POST);
		if (isset($HEADER)) $this->setHEADER($HEADER);
		$this->setUrl($url);
		if (isset($GET)) $this->addGET($GET);
		$this->sslCert = dirname(__FILE__).'/cacert.pem';
	}

	/**
	 * setDebug
	 *
	 * @param  boolean $debug
	 * @param  string $dirName
	 * @param  string $fileName
	 * @return void
	 */
	public function setDebug($debug, $dirName=null, $fileName=null, $processName=null) {
		$this->debug = $debug;
		$this->debugDir = $dirName;
		$this->debugFile = $fileName;
		$this->processName = $processName;
	}

	public function setSslVerify($verify) {
		$this->sslVerify = $verify;
	}
	public function setSslCert($cert) {
		$this->sslCert = $cert;
	}

	public function setCurlopt($CURLOPT, $value) {
		curl_setopt($this->curl, $CURLOPT, $value);
	}

	public function setFollowLocation($follow) {
		$this->followLocation = $follow ? true : false;
	}

	public function setUrl($URL='') {
		$urlParse = parse_url($URL);
		if (!$urlParse || !is_array($urlParse)) return false;

		$this->scheme = $urlParse['scheme'] ?? null;
		$this->host = $urlParse['host'] ?? null;

		$this->port = $urlParse['port'] ?? null;
		$this->user = $urlParse['user'] ?? null;
		$this->pass = $urlParse['pass'] ?? null;

		$this->path = $urlParse['path'] ?? null;
		$this->fragment = $urlParse['fragment'] ?? null;

		$GET = [];
		parse_str($urlParse['query'] ?? '', $GET);
		$this->addGET($GET);
	}

	public function addGET($GET=[]) {
		if ($GET) $this->GET = \array_merge($this->GET, $GET);
	}

	public function setGET($GET=[]) {
		$this->GET = [];
		$this->addGET($GET);
	}

	public function addPOST($POST=[]) {
		$this->POST = \array_merge($this->POST, $POST);
	}

	public function setPOST($POST=[]) {
		$this->POST = [];
		$this->addPOST($POST);
	}

	public function addHEADER($HEADER=[]) {
		$this->HEADER = \array_merge($this->HEADER, $HEADER);
	}

	public function setHEADER($HEADER=[]) {
		$this->HEADER = [];
		$this->addHEADER($HEADER);
	}

	public function unparse_url() {
		$scheme   = isset($this->scheme) ? $this->scheme . '://' : '';
		$host     = isset($this->host) ? $this->host : '';
		$port     = isset($this->port) ? ':' . $this->port : '';
		$user     = isset($this->user) ? $this->user : '';
		$pass     = isset($this->pass) ? ':' . $this->pass  : '';
		$pass     = ($user || $pass) ? "$pass@" : '';
		$path     = isset($this->path) ? $this->path : '';
		$query    = $this->GET ? '?' . http_build_query($this->GET) : '';
		$fragment = isset($this->fragment) ? '#' . $this->fragment : '';
		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}

	public function sendFile($file) {
		$fp = fopen($file, 'r');
		curl_setopt($this->curl, CURLOPT_INFILESIZE, filesize($file));
		curl_setopt($this->curl, CURLOPT_INFILE, $fp);
	}

	public function setBody($body) {
		$this->BODY = $body;
	}

	public function setMethod($method) {
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	}

	public function useProxy($proxy, $proxyType='') {
		if ($proxy) curl_setopt($this->curl, CURLOPT_PROXY, $proxy);
		if ($proxyType) curl_setopt($this->curl, CURLOPT_PROXYTYPE, $proxyType);
	}

	public function exec() {
		$url = $this->unparse_url();
		if (!$url) return false;

		$this->responseHeader = null;
		$this->responseBody = null;
		$this->responseErrorNum = null;
		$this->responseError = null;
		$this->responseContentType = null;
		$this->responseCode = null;
		$this->responseTime = null;

		$log = 'CurlGet URL: '.$url.' ';

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);
		curl_setopt($this->curl, CURLOPT_CAINFO, $this->sslCert);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		if ($this->POST) {
			$postData = http_build_query($this->POST);
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
			$log .= 'POST: '.$postData.' ';
		} elseif($this->BODY) {
			curl_setopt($this->curl, CURLOPT_POST, true);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->BODY);
			$log .= 'BODY: '.$this->BODY.' ';
		}

		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $this->followLocation);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->HEADER ?: []);
		if ($this->HEADER) {
			$log .= 'HEADER: '.implode('; ',$this->HEADER).' ';
		}
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
		$startTime = microtime(true);
		$response = curl_exec($this->curl);
		$endTime = microtime(true);


		$header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $header_size);
		$this->responseBody = substr($response, $header_size);
		$log .= 'RESPONSE: '.$this->responseBody.' ';
		$headers = explode("\n", str_replace("\r\n", "\n", $headers));
		if (is_array($headers)) {
			foreach($headers as $header) {
				$header = explode(':', $header, 2);
				if (count($header) < 2) continue;
				$this->responseHeader[strtolower(trim($header[0]))][] = trim($header[1]);
			}
		}

		$this->responseErrorNum = curl_errno($this->curl);
		$this->responseError = curl_error($this->curl);

		$this->responseContentType = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
		$this->responseCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		$this->responseTime = max(0, $endTime - $startTime);
		$log .= 'RESPONSE CODE: '.$this->responseCode.' ';
		// if ($this->responseErrorNum !== 0) {
		// 	error_log('akeb/CurlGet ERROR: '. $this->responseErrorNum .' '. $this->responseError.' ('.$url.')');
		// }
		// if ($this->responseCode >= 400) {
		// 	error_log('akeb/CurlGet ERROR: '. $this->responseCode . ' ('.$url.')');
		// }
		if ($this->responseErrorNum == 0 && $this->responseContentType == 'application/json' && $this->responseBody) {
			$this->responseBody = @json_decode($this->responseBody, true);
		}
		if ($this->debug) {
			if ($this->debugFile || $this->debugDir) {
				if ($this->debugDir == 'syslog') {
					$bt = debug_backtrace();
					$logger = new \AKEB\Logger\Logger();

					$bt = array_reverse($bt);
					array_shift($bt); // remove {main}
					array_pop($bt); // remove call to this method
					$trace = [];
					foreach($bt as $item) {
						$trace[] = basename($item['file']).':'.$item['line'];
					}

					$logger->routes->attach(new \AKEB\Logger\Routes\SyslogRoute([
						'isEnable' => true,
						'filePath' => $this->debugFile,
						'processName' => $this->processName ?? '',
						'template' => (\Psr\Log\LogLevel::DEBUG)." ".(implode('->',$trace))." {message}",
					]));
					$logger->log(\Psr\Log\LogLevel::DEBUG, $log);
				} else {
					$this->_addToLog($log, $this->debugDir, $this->debugFile);
				}
			} else {
				error_log($log);
			}
		}
		return $this->responseErrorNum == 0 ? $this->responseBody : false;
	}


	private function _addToLog($message, $dir=null, $file=null) {
		$current_dir = sys_get_temp_dir().'/';
		if ($dir) $current_dir = $dir;

		@mkdir($current_dir,0775,true);
		$k = intval(date('i')/30);
		$currentFile = date('Y_m_d__H_').sprintf("%02d",$k).'.log';
		if ($file) $currentFile = $file;

		$fp = fopen($current_dir.$currentFile, 'a+');
		if (!$fp) {
			error_log("akeb/CurlGet ERROR!!! Ошибка открытия файла %s",$current_dir.$currentFile);
			return false;
		}
		if (flock($fp, LOCK_EX)) {
			$log = [
				date("Y-m-d H:i:s"),
				time(),
				$message,
			];
			fwrite($fp,implode(' || ', $log)."\n");
			flock($fp, LOCK_UN);
		} else {
			error_log("akeb/CurlGet ERROR!!! Ошибка получения блокировки на файл %s",$current_dir.$currentFile);
			fclose($fp);
			return false;
		}
		fclose($fp);
		chmod($current_dir.$currentFile, 0664);
		return true;
	}


	public function __destruct() {
		curl_close($this->curl);
		unset($this->curl);
	}
}