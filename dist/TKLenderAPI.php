<?php

use phpDocumentor\Reflection\Types\Boolean;

/**
 * Файл с классом для работы с API Turnkey Lender,
 * используется для взаимодействия фронтенда с CRM Turnkey Lender.
 *
 * @author Игорь Стебаев <Stebaev@mail.ru>
 * @copyright Copyright (c) 2016 Artjoker Company
 * @version 1.0
 * @package DesignAPI
 * @link http://mycredit.com.ua
 */

/**
 * Класс для работы с CRM на стороне фронтенда
 */
class TKLenderAPI {

    const  
           // этот блок не изменять!
           CHECK_CRM_WORK = true,       // проверять работу CRM
           NOT_CHECK_CRM_WORK = false,  // не проверять работу CRM
           CHECK_CRM_DB = true,         // проверять работу БД CRM
           NOT_CHECK_CRM_DB = false,    // не проверять работу БД CRM
           //
           
           TIMEOUT_DEFAULT = 0,         // таймаут ожидания ответа по умолчанию
           TIMEOUT_CHECK_CRM = 1;       // таймаут ожидания ответа теста CRM
           
    /**
	 * массив конфигурации
	 * @var array
	 */
	private $config;
	
	/**
	 * url внешнего CRM
	 * @var string
	 */
	private  $url;
	
	/**
	 * ключ для подписи данных
	 * @var string $key_api 
	 */
	private $key_api;
	//private $key_api = 'FDG%$R^56#$1b%$E6poidf9208i546JLSADR$57$%jd1fhJ456EaDFGJSDTY$%45DFF$)!)2';
	
	/**
	 * данные технолога
	 * @var array $technologist
	 */
	private $technologist;
	
	/**
	 * массив данных для запроса к CRM
	 * @var array $request
	 */
	private $request = [];
	
	/**
	 * данные, получаемые от CRM
	 * @var array $response
	 */
	private $response;
	
	/**
	 * Массив превода числоваых сообщений API CRM
	 * @var array $errors
	 */
	private $errors;
	
	/**
	 * Массив переводов текстовых сообщений API CRM
	 * @var array $errorsStr
	 */
	private $errorsStr;
	
	/**
	 * Временная зона
	 * @var DateTimeZone
	 */
	private $timeZone;
	
	/**
	 * время создания экземпляра класса
	 * @var DateTime
	 */
	private $datetime;
	
	/**
	 * флаг обязательности проверки работоспособности CRM 
	 * @var boolean $flagCheckCrmWork
	 */
	private $flagCheckCrmWork = false;
	
	/**
	 * массив соответствия satellite_id имени партнера
	 * @var array
	 */
	private $satellites = [
	    '1' => 'Satellite_1',
	    '2' => 'Microzaym',
	];
	
	/**
	 * увеличивает счетчик в файле
	 * @param string $fileName
	 * @return number
	 */
	private function addCounter(string $fileName) {
	    
	    $file = fopen($fileName, "a+");
	    
	    // блокируем файл:
	    if (flock($file, LOCK_EX)) {
	   
    	    $count = (int) fread($file, 10);
    	    @$count ++;
    	    ftruncate($file, 0);   // очищаем
    	    fwrite($file, $count); // записываем
    	    fflush($file);         // сбрасываем буфер
    	    flock($file, LOCK_UN); // разблокируем
    	    fclose($file);
    	    
	    } else {

	        $count = 0;
	        
	        // подключаем логирование:
	        $log = new Log('timeout_', '_' . $this->datetime->format('H'));
	        $strlog = "TKLenderAPI.addCounter METHOD: addCounter did not lock file {$fileName}\r";
	        $log->write($strlog);
	    }
	    
	    return $count;
	}
	
	/**
	 * передает данные на внешний url
	 * @param string $url адрес внешнего CRM
	 * @param array $data передаваемые данные
	 * @param string $type тип запроса, по умолчанию "POST"
	 * @param integer $timeout время ожидания ответа
	 * @param boolean $checkCrmWork - нужно ли предварительно проверять работоспособность CRM
	 * @return mixed
	 */
	private function curl($url, $headers = [], $data = array(), $type = 'POST', $timeout = self::TIMEOUT_DEFAULT, $checkCrmWork =  self::CHECK_CRM_WORK) {
	        
		// $time_start = microtime(true);
		
	    $checkCrmWork = ($checkCrmWork && ($this->flagCheckCrmWork == 1)) ? true : false;
	    // если нужно проверить работоспособность:
	    if ($checkCrmWork && !_IS_DEV && !$this->testCRM(self::TIMEOUT_CHECK_CRM, self::CHECK_CRM_DB)) {
	            
	        // Дата время с учетом тайм-зоны:
	        $datetime = new DateTime("now", $this->timeZone);  // для лога, время создания экземпляра может не совпадать
	        
	        // подключаем логирование:
	        $log = new Log('timeout_', '_' . $datetime->format('H'));
            $strlog = "TKLenderAPI.curl  METHOD: checkCrmWork not done {$url}\r";
            $log->write($strlog);
	        
	        // записываем количество проблем:
            $dir = MODX_BASE_PATH . "/DesignAPI/logs/" . $datetime->format('Y') . "/" . $datetime->format('m') . "/";
            @mkdir($dir, 0777, true);
            $fileName = $dir . "timeoutcount_" . $datetime->format('Ymd') . "_" . $datetime->format('H') . ".log";
            $count = $this->addCounter($fileName);  // увеличить счетчик

            $lang = substr($_SERVER['REQUEST_URI'], 1, 2);
	        $lang = (in_array($lang, ['ru', 'ua'])) ? $lang : 'ru';
	        $result = json_encode([
	            'Success' => false,
	            // 'Message' => 'Данные временно недоступны.',
	            'Message' => $this->errors[$lang][11], // Ошибка соединения. Пожалуйста, повторите Ваш запрос через 15 минут
	            // 'error' => 0,
	        ]);
	        return $result;

// 	    } else {
	        
// 	        // подключаем логирование:
//	        $datetime = new DateTime("now", $this->timeZone);
//          $log = new Log('aaa_', '_' . $datetime->format('H'));
// 	        $strlog = "TKLenderAPI.curl  METHOD: not checkCrmWork checkCrmWork = $checkCrmWork timeout = $timeout {$url}\r";
// 	        $log->write($strlog);
	        
	    }
	    
	    $ch = curl_init();

		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		//curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.16 Safari/537.36");
		curl_setopt($ch, CURLOPT_USERAGENT, "MyCredit frontend");
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		switch ($type) {
			case "POST":
				if ($data) {
					curl_setopt($ch, CURLOPT_POST, true);
					if (is_array($data)) {
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
					} else {
						curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					}
				}
				break;
			
			case "PUT":
				if ($data) {
					// curl_setopt($ch, CURLOPT_PUT, true);
					// curl_setopt($ch, CURLOPT_INFILE, $data);
					// curl_setopt($ch, CURLOPT_INFILESIZE, 0);
					
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
					if (is_array($data)) {
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
					} else {
						curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					}
				}
				break;
		}

		$dateTime_start = new DateTime();
		$time_start = microtime(true);
		
		if (_IS_DEV) {
			$result = json_encode([
					'Success' => false,
					'Message' => 'Данные временно недоступны.',
					'error' => 0,
			]);
		} else {
			$result = curl_exec($ch);
			
			// если результат не получен:
			if (!$result) {
			    $lang = substr($_SERVER['REQUEST_URI'], 1, 2);
			    $lang = (in_array($lang, ['ru', 'ua'])) ? $lang : 'ru';
			    $result = json_encode([
			        'Success' => false,
			        // 'Message' => 'Данные временно недоступны.',
			        'Message' => $this->errors[$lang][3], // ошибка при работе с БД
			        'error' => 0,
			    ]);
			}
		}
				
		curl_close($ch);
		
		$time_end = microtime(true);
		$time = $time_end - $time_start;
		
		/*
		// подключаем логирование:
		$prefixFile = ($time < 10) ? 'mktime_' : 'mktime_over_';
		$timeStartStr = $dateTime_start->format('H:i:s.u');
		$log = new Log($prefixFile, '_' . $dateTime_start->format('H'));
		$strlog = "{$timeStartStr} {$time} {$url}\r";
		$log->write($strlog);
		*/
		
		return $result;
	}
	
	/**
	 * Возвращает массив требуемой для дальнейшей отправки структуры 
	 * @param array $sliderData
	 */
	private function updateSliderData($sliderData) {
		$result = [];
		foreach ($sliderData as $value) {
			switch ($value['changed']) {
				case 'money':
					// $result['moneys'][] = $value['money'];
					// $result['moneys_details'][] = [
					$result['moneys'][] = [
							'money' => $value['money'],
							'timeStopSlider' => $value['timeStopSlider'],
							'downTimeSlider' => $value['downTimeSlider'],
					];
					break;
				case 'day':
					// $result['days'][] = $value['days'];
					// $result['days_details'][] = [
					$result['days'][] = [
							'day' => $value['days'],
							'timeStopSlider' => $value['timeStopSlider'],
							'downTimeSlider' => $value['downTimeSlider'],
					];
					break;
			}
		}
		return $result;
	}
	
	/**
 	 * удаляет спецсимволы:
 	 * @param string $val
 	 * @return string
 	 */
	private function updateValue($val){
 		$val = strip_tags($val);		// Удаляет HTML и PHP-теги из строки
 		$val = htmlspecialchars($val);	// Преобразует специальные символы в HTML-сущности (< > ' " &)
 		return $val;
	}
 
	/**
	 * инициализирует класс
	 */
	public function __construct() {

		// define('_IS_DEV', false);
		
		// для локального теста
		// require_once MODX_BASE_PATH . 'classes/Crypter.php';
		// require_once MODX_BASE_PATH . '/DesignAPI/errors.php';
		// $this->config = parse_ini_file(MODX_BASE_PATH. 'config.ini');
		

		// Создается массив $errors с расшифровкой ошибок:
		//require_once MODX_BASE_PATH . '/DesignAPI/errors.php';
		
		$this->config = parse_ini_file(MODX_BASE_PATH. '/DesignAPI/config.ini');

		if ($this->config) {
		
		    // define('_IS_DEV', ($this->config['TKLenderActive'] == 0)? true : false);
		    define('_IS_DEV', (($this->config['TKLenderActive'] == 0) || ($this->config['websiteActive'] == 0)) ? true : false);
		    
		    // Дата время с учетом тайм-зоны:
		    $this->timeZone = new DateTimeZone(isset($this->config['timeZone']) ? $this->config['timeZone'] : "Europe/Kiev");
		    
		    // Дата время с учетом тайм-зоны:
		    $this->datetime = new DateTime("now", $this->timeZone);
		    
		    if (isset($this->config['url_TKLender'])) {
				$this->url = $this->config['url_TKLender'];
			}
			if (isset($this->config['key_api_TKLender'])) {
				$this->key_api = $this->config['key_api_TKLender'];
			}
			if (isset($this->config['login_TKLender'])) {
				$this->technologist['login'] = $this->config['login_TKLender'];
			}
			if (isset($this->config['password_TKLender'])) {
			    $this->technologist['password'] = $this->config['password_TKLender'];
			}
			if (isset($this->config['flag_check_crm_work'])) {
			    $this->flagCheckCrmWork = $this->config['flag_check_crm_work'];
			}
		}
		// добавляем "/", если нет
		if (!preg_match("/.*\/$/", $this->url)) {
			$this->url .= '/';
		}
		// подключаем класс платежных систем:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Payment.php';
		// подключаем класс отправки SMS:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Api_SMS.php';
		// подключаем класс BankID:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/BankidAPI.php';
		// подключаем файл вспомогательных функций:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/functions.php';
		// подключаем файл Email:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Mail/Sender.php';
		// подключаем файлы базы данных:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/DB.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Table.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/LogTable.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/CookieTable.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/GoogleStars.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/ReviewsTable.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/PagesRatingTable.php';
		require_once MODX_BASE_PATH . '/DesignAPI/classes/actions/DreamsTable.php';
		// Google Analytics
		require_once MODX_BASE_PATH . '/DesignAPI/classes/GoogleAnalytics.php';
		// подключаем класс работы с cookie:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Cookies.php';
		// подключаем класс работы с MaxMind:
		// require_once MODX_BASE_PATH . '/DesignAPI/classes/MaxMind.php';
		// подключаем класс работы с CPA:
		require_once MODX_BASE_PATH . '/DesignAPI/classes/Affiliate.php';
		// подключаем класс ошибок:
		include MODX_BASE_PATH . '/DesignAPI/errors.php';
		$this->errors = $errors;
		$this->errorsStr = $errorsStr;
	}
	
	/**
	 * отправляет результат проплаты W4p в CRM
	 * @param array $data
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function acceptChargedPay($data, $CustomerAuthToken) {
		
		// массив данных запроса:
		$request = $data;
		unset($request['LoanId']);	// удаляем параметр LoanId
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$json = json_encode($request);

		$res = $this->curl($this->url . 'PublicApi/AcceptChargedPay?loanId=' . $data['LoanId'], $headers, $json, "POST");
		//$res = $this->curl($this->url . 'PublicApi/AcceptChargedPay?loanId=' . $request['loanId'], $headers, $request, "POST");
		
		$response = json_decode($res, true);
		//$response = $res;
		
		// подключаем логирование:
		$log = new Log('w4p_');
		$strlog .= "Отправка в TKLender подтверждения проплаты (TKLenderAPI.acceptChargedPay)  METHOD: acceptChargedPay " . 
			"\ndata = " . print_r($data, true). 
			//"\nhref=" . $this->url . 'PublicApi/AcceptChargedPay?loanId=' . $data['LoanId'] .
			//"\njson=" . print_r($json, true). 
			"\nheaders = " . print_r($headers, true). 
			//"\nres=" . print_r($res, true) . 
			//"\nresponse=" . print_r($response, true); 
			"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);

		return $response;
		
	}
	
	/**
	 * отправляет данные для проплаты W4p через TKLender в CRM
	 * @param array $data
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function acceptPay($data, $CustomerAuthToken) {
		
		// массив данных запроса:
		$request = $data;

		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		//$json = json_encode($data);

		$res = $this->curl($this->url . 'PublicApi/AcceptPay', $headers, $request, "POST");
		//$res = $this->curl($this->url . 'PublicApi/AcceptPay', $headers, $json, "POST");
		
		$response = json_decode($res, true);
		//$this->response = $res;
		
		// подключаем логирование:
		$log = new Log('w4p_');
		$strlog .= "Отправка в TKLender подтверждения проплаты (TKLenderAPI.acceptPay)  METHOD: acceptPay " . 
			"\ndata = " . print_r($data, true). 
			"\nheaders = " . print_r($headers, true). 
			"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res) ;
		$log->write($strlog);
		
		return $response;
		
	}
	
	/**
	 * отправляет результат верификации в CRM
	 * @param array $data
	 * @param string $CustomerAuthToken
	 * @return JsonSerializable json
	 */
	public function acceptVerification($data, $CustomerAuthToken) {
		
		// массив данных запроса:
		$request = [
				'data' => $data,
		];
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$json = json_encode($data);
		//$res = $this->curl($this->url . 'PublicApi/AcceptVerification', $headers, $this->request, "POST");
		$res = $this->curl($this->url . 'Wayforpay/AcceptVerification', $headers, $json, "POST");
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('w4p_');
		$strlog .= "Отправка в TKLender подтверждения верификации (TKLenderAPI.acceptVerification)  METHOD: acceptVerification " . 
			"\ndata = " . print_r($data, true). 
			"\njson = " . print_r($json, true). 
			"\nheaders = " . print_r($headers, true). 
			"\nresponse = " . ((isJSON($res)) ? print_r(json_decode($res), true) : $res);
		$log->write($strlog);
		
		return $res;
		
	}
	
	/**
	 * запрос на выбранный тип пролонгации
	 * @param int $loanId
	 * @param int $rolloverId
	 * @param string $comments
	 * @return array
	 */
	public function applyProlongation(int $loanId, int $rolloverId, string $comments = null) {
	    
	    // проверка существования данных:
	    if (!$loanId || !$_SESSION ['token'] || !$rolloverId) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	            'error' => 100,
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$_SESSION ['token']}",
	        ];
	    
	    $request = [
	        'loanId' => $loanId,
	        'rolloverId' => $rolloverId,
	        'comments' => $comments,
	    ];
	    
	    // непосредственно отправка:
	    $url = $this->url . 'PublicApi/ApplyRollover';
	    $res = $this->curl($url, $headers, $request, "POST");
	    $response = json_decode($res, true);
	        
        // подключаем логирование:
        $log = new Log('prolong_');
        $strlog .= "TKLenderAPI.applyProlongation  METHOD: ApplyRollover"
            . "\nurl = " . $url
            . "\nrequest = " . print_r($request, true)
            . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
        $log->write($strlog);
            
        return $response;
	}
	
	/**
	 * Создает новую заявку на кредит 
	 * @param float $amount
	 * @param int $term
	 * @param string $creditProduct
	 * @param int $maxAmount
	 * @param string $CustomerAuthToken
	 * @param string $promoCode
	 * @param string $culture - языковый региональный пареметр
	 * @return array (LoanId: an internal unique identifier of a newly created loan)
	 */
	public function applyforLoan($amount, $term, $creditProduct, $maxAmount, $CustomerAuthToken, $promoCode, $culture) {
		
		// проверка существования данных:
		if (!isset($amount) || !isset($term) || !isset($creditProduct) || !isset($CustomerAuthToken) || (strlen($CustomerAuthToken) == 0))	{
	
			$request = [
					'amount' => $amount,
					'term' => $term,
					'creditProduct' => $creditProduct,
					'AvailableAmount' => $maxAmount,
					'CustomerAuthToken' => $CustomerAuthToken,
					'promoCode' => $promoCode,
					'culture' => $culture,
			];
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
				
			$requestApply = $request;
			$responseApply = $response;
			$res = json_encode([]);
				
		} else {
	
			// массив данных запроса:
			$request = [
					'Amount' => $amount,
					'Term' => $term,
					'CreditProduct' => $creditProduct,
					// 'AvailableAmount' => $maxAmount,
					'AvailableAmount' => (int) ceil($maxAmount), // округлили до целого в большую сторону.
					'Culture' => $culture,
					// 'SessionId' => session_id(),
			];
			if ($promoCode) $request['PromoCode'] = trim($promoCode);
			
			// массив заголовков:
			$headers = [
					"tkLender_ApiKey: {$this->key_api}",
					"tkLender_CustomerAuthToken: $CustomerAuthToken",
			];
			
			// непосредственно отправка:
			// $res = '';
			$res = $this->curl($this->url . 'PublicApi/ApplyForLoan', $headers, $request);
			$response = json_decode($res, true);

			$requestApply = $request;
			$responseApply = $response;
	
			// если заявка создана:
			if ($response['Success'] && $response['LoanId']) {
				
				// запись Cookie:
				$myCookie = new Cookies('', $response['LoanId']);
				
				// отправка данных сессии:
				// $this->sendUserInfo();								// первоначальная запись
				$this->sendUserInfo(['DateUpdated' => date('c')]);	// запись обновления
				
				// массив данных запроса привязки номера сессии:
				$requestSession = [
						'sessionId' => session_id(),
						'loanId' => $response['LoanId'],
				];
				//$requestSession = json_encode($requestSession);
				
				// непосредственно отправка номера сессии при отправке заявки на кредит:
				$resSession = $this->curl($this->url . 'PublicApi/ClientSessions/AssignLoan', $headers, $requestSession, "PUT");
				
				// отправляем данные партнеру о создании заявки:
				$resAffiliate = Affiliate::send($response['LoanId'], 'applyforLoan');
			}
		}
		
		// подключаем логирование:
		$log = new Log('addLoan_');
		$strlog = "TKLenderAPI.applyforLoan METHOD: addLoan "
				. "\nrequest = " . print_r($requestApply, true)
				. "\nresponse = " . ((isJSON($res)) ? print_r($responseApply, true) : $res)
				. "\nrequestSession = " . print_r($requestSession, true)
				. "\nresponseSession = " . ((isJSON($resSession)) ? print_r(json_decode($resSession, true), true) : $resSession)
				. "\nresAffiliate = " . $resAffiliate;
		$log->write($strlog);

		return $response;
	}
	
	/**
	 * Меняет пароль залогиненного пользователя
	 * @param string $currentPassword
	 * @param string $newPassword
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function changeCustomerPassword($currentPassword, $newPassword, $CustomerAuthToken) {
		
		// проверка существования данных:
		if (!isset($currentPassword) || !isset($newPassword) || !isset($CustomerAuthToken))	{

			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
	
		// массив данных запроса:
		$request = [
				'currentPassword' => $currentPassword,
				'newPassword' => $newPassword,
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/ChangeCustomerPassword', $headers, $request);
		// $res = $this->curl($this->url . 'PublicApi/Customers/ChangeCustomerPassword', $headers, $request);
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('changePwd_');
		$password = '******';	// удаляем реальный пароль из лога
		if ($request['currentPassword']) $request['currentPassword'] = $password;	// удаляем реальный пароль из лога
		if ($request['newPassword']) $request['newPassword'] = $password;	// удаляем реальный пароль из лога
		$strlog = "METHOD: forgotPass (TKLenderAPI.forgotPass) " .
				"\nlogin=" . $login . " type=" . $type . " code=" . $code . " password=" . $password . 
				"\nurl=" . $url .
				"\nrequest=" . print_r($request, true) .
				//"\nres=" . print_r($res, true) .
				// "\nresponse=" . print_r($response, true); 
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * проверка карты в платежной системе
	 * @param array $data (индекс id)
	 * @return array
	 */
	public function checkCard($data, $returnUrl) {
		
		if (isset($data['id'])) {
			
			$error = 777;
			
			// получаем order reference из CRM:
			$res = $this->getOrderReference($_SESSION['token'], $data);
			
			if ($res['Data']["orderReference"]) {
				$data['orderReference'] = $res['Data']["orderReference"];
				$data['returnUrl'] = $returnUrl;
				
				$payment = Payment::getProvider($this->config);
				$res = $payment->verify($data);
				$error = $res['error'];
					
				if ($error == 777) {
					$response['form'] = $res['form'];
					$response['widget'] = $res['widget'];
				} else {
					$response['response_payments'] = $res;
				}
			} else {
				$error = 100; // Нет входных данных
			}

		} else {
			$error = 100; // Нет входных данных
		}
		
		$response['error'] = $error;
		
		return $response;
	}
	
	/**
	 * проверяет существование телефона в базе
	 * @param string $phone
	 */
	public function checkPhone($phone) {
		
		// проверка существования данных:
		if (!$phone) {
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		$request = [
				'phone' => $phone,
		];
		
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/CheckPhone', $headers, $request, 'POST');
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('reg_');
		$strlog .= "TKLenderAPI.CheckPhone METHOD: CheckPhone "
				. "\nphone = " . $phone
				. "\nurl = " . $this->url . 'PublicApi/CheckPhone'
				//. "\nresponse = " . print_r($response, true)
				//. "\nres =" . $res ;
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * Проверяет полноту заполнения данных пользователем
	 * @param string $CustomerAuthToken
	 * @param string $lang
	 * @return array
	 */
	public function checkCompleteUserData ($CustomerAuthToken, $lang = 'ru') {
		
		// массив полей для проверки заполнения адреса. В значении поля должно находится описание поля
		$arrFieldsForCheckAddress = [
//				"Id" => '',
 				"Street" => '',
// 				"Apartment" => '',
 				"City" => '',
// 				"State" => '',
// 				"ZipCode" => '',
// 				"ResidentialStatus" => '',
// 				"ResidedAtAddressYear" => '',
// 				"ResidedAtAddressMonth" => '',
// 				"TypeOfSettlement" => '',
 				"House" => '',
// 				"Building" => '',
// 				"ResidentialMatchesRegistration" => '',
// 				"TextValue" => '',
// 				"IPAddress" => '',
		];

		// массив полей для проверки заполнения. В значении поля должно находится описание поля
		$arrFieldsForCheck = [
//				"SecondAddress" => '',
//				"WorkAddress" => '',
				"MaritalStatus" => '',
//				"NumberOfDependents" => '',
//				"Citizenship" => '',
//				"CountryOfCitizenship" => '',
				"Education" => '',
				"SocialSecurityNumber" => '',
//				"CarOwner" => '',
//				"DriverLicenseID" => '',
//				"StateOfIssue" => '',
//				"DriverLicenseType" => '',
//				"IssuedDriverLicense" => '',
				"Phone" => '',
//				"AlternativePhone" => '',
//				"IncomeType" => '',
//				"GrossMonthlyIncome" => '',
//				"GrossMonthlyExpenses" => '',
//				"Employer" => '',
//				"JobTitle" => '',
//				"HireDate" => '',
//				"WorkPhone" => '',
//				"WorkPhoneExt" => '',
//				"SizeCompany" => '',
//				"EmployeeVerificationPhone" => '',
//				"EmployeeVerificationPhoneExt" => '',
//				"HowOftenPaidEmployed" => '',
//				"CompanyName" => '',
//				"CompanyPhone" => '',
//				"CompanyPhoneExt" => '',
//				"CompanyBeginningDate" => '',
//				"BenefitStartDate" => '',
//				"NextPayDate" => '',
//				"HowOftenPaidSocial" => '',
//				"IncomeReceivedFrom" => '',
//				"HowOftenPaidPension" => '',
//				"IncomeStartDate" => '',
//				"IncomeVerificationPhone" => '',
//				"IncomeVerificationPhoneExt" => '',
//				"HowOftenPaidOther" => '',
//				"CurrentExperience" => '',
//				"PreviousExperience" => '',
//				"Occupation" => '',
//				"Position" => '',
				"BusynessType" => '',
				"Passport" => '',
				"PassportIssuedBy" => '',
				"PassportRegistration" => '',
//				"SubdivisionCode" => '',
				"RealEstate" => '',
//				"PeriodResidence" => '',
//				"NextPay" => '',
//				"OftenPay" => '',
//				"CostFamily" => '',
				"SumPayLoans" => '',
				"PurposeLoan" => '',
//				"NameUniversity" => '',
//				"SpecializationFaculty" => '',
//				"IsBudget" => '',
//				"FormTraining" => '',
//				"IsFirstEducation" => '',
//				"BeginLearn" => '',
//				"GroupDisability" => '',
//				"ReasonDismissal" => '',
//				"PlanNewJob" => '',
				"MainSource" => '',
				"PassportType" => '',
//				"PassportReestr" => '',
//				"PassportNumberDoc" => '',
//				"QualificationAfterGraduation" => '',
//				"Id" => '',
//				"IsSign" => '',
				"FirstName" => '',
				"MiddleName" => '',
				"LastName" => '',
//				"FullName" => '',
//				"FullNameShort" => '',
//				"Suffix" => '',
				"BirthDate" => '',
//				"Gender" => '',
				"Email" => '',
//				"IsAgreedWithMailSubscription" => '',
//				"CreationDate" => '',
//				"test" => "тестовое значение",
		];
		
		$result = [];
		
		// $res = $this->getCustomerDetails($CustomerAuthToken);
		$res = [];
		$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
		$res ['Data'] = $_SESSION['api']['client'];
		if ($res['Success']) {
			$data = $res['Data'];
			// добаваляем поля для проверки в зависимости от данных:
			if ($data['MainSource'] != 9) { // 9 - нет дохода
				$arrFieldsForCheck['GrossMonthlyIncome'] = '';
				// $arrFieldsForCheck['OftenPay'] = '';
				$arrFieldsForCheck['CostFamily'] = '';
			}
			$result['Success'] = true;
			foreach ($arrFieldsForCheck as $key => $value) {
				if (!$data[$key]) {
					$result['Success'] = false;
					$result['Message'] = $this->errors[$lang][158];  // 158 => 'Не заполнены обязательные поля в разделе ‘Мои данные‘'
					$result['Message'] .= " $value";
					break;
				}
			}
			foreach ($arrFieldsForCheckAddress as $key => $value) {
				if (!$data['Address'][$key]) {
					$result['Success'] = false;
					$result['Message'] = $this->errors[$lang][158];  // 158 => 'Не заполнены обязательные поля в разделе ‘Мои данные‘'
					$result['Message'] .= " $value";
					break;
				}
			}
				
		} else {
			$result['Success'] = false;
			$result['Message'] = $errors[$lang][113];    // 113 => 'Данные не найдены'
		}
		return $result;
	}
	
	/**
	 * проверяет существование промокода в базе
	 * @param string $promoCode
	 */
	public function checkPromoCode($promoCode, $token = '') {
	
		// проверка существования данных:
		if (!$promoCode) {
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
	
		if ($token !== '') {
			$token = '&token=' . urlencode($token);
		}
		
		$request = [
		];
	
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
	
		// для теста
		//$promoCode="234234";
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/ValidatePromoCode?promocode=' . trim($promoCode) . $token, $headers, $request, 'GET');
		$response = json_decode($res, true);
		if (($response['Success']) && ($response['Data'])) {
			$response = [
					'Success' => true,
					'Message' => "Промокод верен",
			];
		} else {
			$response = [
					'Success' => false,
					'Message' => "",
					'error' => 193,	// Промокод не верный
			];
		}
	
		// подключаем логирование:
		$log = new Log('promo_');
		$strlog .= "TKLenderAPI.checkPromoCode METHOD: checkPromoCode "
				. "\npromoCode = " . $promoCode
				. "\nurl = " . $this->url . 'PublicApi/ValidatePromoCode?promocode=' . trim($promoCode) . $token
				//. "\nresponse = " . print_r($response, true)
				//. "\nres =" . $res 
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
	
		return $response;
	}
	
	/**
	 * посылает в CRM запрос о статусе запроса верификации карты
	 * @param string $cardNumber
	 * @return array
	 */
	function checkStatusCard($cardNumber = '') {

		// проверка существования данных:
		if ((strlen($cardNumber) == 0) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) < 10)) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		//$requestJson = json_encode($request);
		
		// непосредственно отправка:
		//$url = $this->url . 'PublicApi/Is3ds?card=' . $cardNumber;
		$url = $this->url . 'PublicApi/GetCardVerificationResult?card=' . $cardNumber;
		$res = $this->curl($url, $headers, $request, "GET");
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('cardReg_');
		$strlog = "TKLenderAPI.checkStatusCard  METHOD: GetCardVerificationResult "
				. "\nurl = " . $url
				// . "\nkey = {$this->key_api}"
				// . "\ntoken = {$_SESSION ['token']}"
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
				$log->write($strlog);
				
		return $response;
	}
	
	/**
	 * Проверяет возможность подать заявку
	 * Если true - можно оформлять заявку
	 * @param string $CustomerAuthToken
	 * @return boolean 
	 */
	public function checkToAddLoan($CustomerAuthToken) {
		
		// статусы, влияющие на выдачу: 
		$arrListStatuses = [
				'Approved', 'Origination', 'Active', 'PastDue', 'AutoProcessing', 'Restructured', 'Reprocessing',
				'RolloverRequested', 'DisbursementInProgress', 'WaitingForApproval', 'WaitingForAgreement', 'Saled',
		];
		$response = true;
		
		// получаем список кредитов:
		//$res = $this->getCustomerLoans($CustomerAuthToken);
		$res = [];
		$res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
		$res ['Data'] = $_SESSION['api']['credits'];
		
		if($res['Success']) {
				
			$credits = (isset($res['Data'])) ? $res['Data'] : [];
			
			// проверим статусы кредитов. Если есть заявки, или активные кредиты - возвращаем false:
			foreach ($credits as $key => $value) {
				if (in_array($value['Status'], $arrListStatuses)) $response = false;
				
				if (in_array($value['Status'], ['Rejected',])) {
					$days = ($this->config['change_status_rejected'])? : '20';	// Дней до смены статуса "Отклонен"
					$dateCreated = new DateTime($value['CreationDate']);
					$dateCreated->modify('+' . $days . ' days');
					$dateToday = new DateTime();
					if ($dateCreated > $dateToday) {
						$response = false;
					}
				}
			}
		} else {
			$credits = [];
		}
			
		// подключаем логирование:
		$log = new Log('addLoan_');
		$strlog = "TKLenderAPI.checkToAddLoan METHOD: checkToAddLoan "
			. "\ntoken = " . $CustomerAuthToken
			. "\ncredits = ";
		foreach ($credits as $key => $value) {
			if (in_array($value['Status'], $arrListStatuses)) $strlog .= $value['Status'] . " ";
			if (in_array($value['Status'], ['Rejected',])) $strlog .= $value['Status'] . " ";
		}
		$strlog .= "\nresponse = " . $response;
		$log->write($strlog);
		
		// return true; // для теста

		return $response;
	}
	
	/**
	* Подтверждает договор кредита в API CRM
	* @param int $credid
	* @param string $CustomerAuthToken
	* @return array
	*/
	public function confirmContract($credid, $CustomerAuthToken) {
	
		// массив данных запроса:
		$request = [
				'loanId' => $credid,
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/AcceptLoanAgreement', $headers, $request, 'POST');
		$response = json_decode($res, true);
		
		// отправляем данные партнеру при успешной транзакции:
		if ($response['Success']) {
			$res1 = Affiliate::send($request['loanId'], 'confirmContract');
		}
		
		// отправляем данные в Google Analytics:
		if ($response['Success']) {
			$res2 = $this->sendGoogleAnalytics($request['loanId'], $CustomerAuthToken);
		}
		
		// подключаем логирование:
		$log = new Log('addLoan_');
		$strlog .= "TKLenderAPI.confirmContract METHOD: confirmContract " .
				"\nrequest = " . print_r($request, true) .
				//"\nresponse = " . print_r($this->response, true) .
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res) .
				"\nsend Affiliate = " . (($res1) ? 1 : 0);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * устанавливает пароль пользователю. Если указан только $login - генерирует код, и отсылает по SMS, или email
	 * @param string $login
	 * @param string $type 'phone' | 'email'
	 * @param string $lang
	 * @param string $code
	 * @param string $password
	 * @return array
	 */
	public function forgotPass($login, $type, $lang, $code = null, $password = null) {
	
		$error = 777;
		
		// проверка существования данных:
		if (!$login || !$type ) {
			$error = 100; // нет входных данных
		}
		elseif (isset($password) && (strlen($password) < 3)) {
			$error = 104; // Пароль не удовлетворяет требованиям
		}
		
		// если была ошибка
		if ($error != 777)	{
			$this->response = [
					'error' => $error,
			];
			return $this->response;
		}
	
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
			
		if (!isset($password)) { // отправляем запрос на восстановление пароля
			$this->request = [
				'lang' => $lang,
			];
			if ($type == 'phone') $this->request['phone'] = $login;
			if ($type == 'email') $this->request['email'] = $login;
			$url = $this->url . 'PublicApi/SendPasswordResetRequest'; 
			// $url = $this->url . 'PublicApi/Customers/SendPasswordResetRequest'; 
		} else { // отправляем код и новый пароль
			$this->request = [
				'newPassword' => $password,
			];
			if ($type == 'phone') {
				$this->request['phone'] = $login;
				$this->request['code'] = $code;
			}
			if ($type == 'email') {
				$this->request['login'] = $login;
				$this->request['token'] = $code;
			}
			$url = $this->url . 'PublicApi/ResetPassword';
			//$url = $this->url . 'PublicApi/Customers/ResetPassword';
		}
		
		// удаляем спецсимволы:
		$this->request = $this->preprocessing($this->request);
		
		// непосредственно отправка:
		$res = $this->curl($url, $headers, $this->request);
		$this->response = json_decode($res, true);
		//$this->response = ['Success' => true];
		
		// подключаем логирование:
		$log = new Log('forgot_');
		if ($password) $password = '******';	// удаляем реальный пароль из лога
		if ($this->request['newPassword']) $this->request['newPassword'] = $password;	// удаляем реальный пароль из лога
		$strlog = "METHOD: forgotPass (TKLenderAPI.forgotPass) " .
				"\nlogin=" . $login . " type=" . $type . " code=" . $code . " password=" . $password . 
				"\nurl=" . $url .
				"\nrequest=" . print_r($this->request, true) .
				//"\nres=" . print_r($res, true) .
				// "\nresponse=" . print_r($this->response, true); 
				"\nresponse = " . ((isJSON($res)) ? print_r($this->response, true) : $res);
		$log->write($strlog);
		
		return $this->response;
	}
	
	
	/**
	 * запрашивает список транзакций по выбранному кредиту залогиненного пользователя в API CRM (по всем системам)
	 * @param string $CustomerAuthToken
	 * @param string $loanId
	 * @return array
	 */
	public function getAllPayments($CustomerAuthToken, $loanId) {
		
		// массив данных запроса:
		$request = [
		];
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$json = json_encode($request);
		$res = $this->curl($this->url . 'PublicApi/GetAllPayments?loanId=' . $loanId, $headers, $request, "GET");
		
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('getPayment_');
		$strlog = "METHOD: getAllPayments (TKLenderAPI.GetAllPayments) "
			. "\nloanId={$loanId}"
			. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * запрашивает список платежных карт залогиненного пользователя в API CRM (Wayforpay)
	 * @param string $CustomerAuthToken
	 * @param boolean $verify - true - если нужны только верифицированные карты 
	 * @return array
	 */
	public function getCards($CustomerAuthToken, $verify = false) {
		
		// массив данных запроса:
		$request = [
		];
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$json = json_encode($request);
		$res = $this->curl($this->url . 'PublicApi/GetCardForCustomer', $headers, $request, "POST");
		//$res = $this->curl($this->url . 'PublicApi/GetCardForCustomer', $headers, $json, "POST");
			
		$res = json_decode($res, true);

		if (($res['Success']) && ($res['Data']['panCode'])) {
			$response = [
								[
									'Success' => true,
									'id' => $res['Data']['loanId'],
									'number' => $res['Data']['panCode'],
									'reasonCode' => $res['Data']['reasonCode'],
									'verify' => true,
								],
						];
		} else {
			$response = [];
		}
		
		// подключаем логирование:
		$log = new Log('getCard_');
		$strlog = "METHOD: getCards (TKLenderAPI.getCards) " .
				"\nres=" . print_r($res, true) .
				"\nresponse=" . print_r($response, true); 
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	* запрашивает договор кредита в API CRM
	* @param int $credid
	* @return array
	*/
	public function getContract($credid, $CustomerAuthToken) {
	
		// массив данных запроса:
		$request = [
				'loanId' => $credid,
		//		'loanId' => 12,
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetLoanAgreement?loanId=' . $credid, $headers, $request, 'GET');
		$response = json_decode($res, true);
		
		/*
		// подключаем логирование:
		$log = new Log('getContract_');
		$strlog .= "TKLenderAPI.getContract METHOD: getContract " .
				"\nloanId = " .	$credid .
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res) ;
		$log->write($strlog);
		*/
		
		return $response;
	}
	
	/**
	 * Запрашивает детальный список кредитных продуктов
	 * @param string $promoCode
	 * @return array
	 */
	public function getCreditProducts($promoCode = null, $CustomerAuthToken = null) {
	
		$request = [
		];
		$param = ($promoCode) ? '?promoCode=' . trim($this->updateValue($promoCode)) : '';
		$prefix = (strlen($param) == 0) ? '?' : '&';
		$param .= ($CustomerAuthToken) ? ($prefix . 'customerAuthToken=' . urlencode($CustomerAuthToken)) : '';
		
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
	
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetCreditProducts' . $param, $headers, $request, 'GET');
		$response = json_decode($res, true);
		$result = $response;
		
		// подготовим один из полученных продуктов:
		if ($response['Success']) {
			$products = $response['Data']; 
			$product = $products[0]; 			
			foreach ($products as $key => $value) {
				if ($value['IsDefault']) {
					$product = $value;
					break;
				}
			}
			$childProducts = [];	// список дочерних продуктов
			if(isset($product['ChildProductNames']) && is_array($product['ChildProductNames'])) {
				$childProductNames = $product['ChildProductNames'];
			} else {
				$childProductNames = [];
			}
			// заполним массив дочерних кредитных продуктов:
			foreach ($products as $key => $value) {
				if (in_array($value['Name'], $childProductNames)) {
					$childProduct = $value;
					$childProduct['InterestRate'] =  number_format((float) $childProduct['InterestRate'] * 100, 4, '.', '');	// преобразум (умножим на 100)
					$childProducts[] = $childProduct;
				}
			}
			
			$product['InterestRate'] =  number_format((float) $product['InterestRate'] * 100, 4, '.', '');	// преобразум (умножим на 100)
			$result['Data'] = $product;	// заменяем массив продуктов на один выбранный продукт

// для теста, удалить 
/*
$product['MinAmount'] = 1;
$product['MaxAmount'] = 1000;
$product['MinTerm'] = 0.04;
$product['MaxTerm'] = 5;
$product['InterestRate'] = 0;
$product['Name'] = 'Тест 500/0';
$childProducts[] = $product;	// для теста
// для теста, удалить
$product['MinAmount'] = 1;
$product['MaxAmount'] = 52;
$product['MinTerm'] = 0;
$product['MaxTerm'] = 8;
$product['InterestRate'] = 100;
$product['Name'] = 'Тест 500/1';
$childProducts[] = $product;	// для теста
*/

			if (!empty($childProducts))
				$result['Data']['ChildProducts'] = $childProducts;
		}
		
		// подключаем логирование:
		$log = new Log('getProduct_');
		$strlog = "TKLenderAPI.getCreditProducts METHOD: getCreditProducts "
				. "\nparam = " .	$param
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res)
				. "\nresult = " . ((isJSON($res)) ? print_r($result, true) : '');
		$log->write($strlog);
					
		return $result;
	}
	
	/**
	 * Возвращает информацию о залогиненном пользователе
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function getCustomerDetails($CustomerAuthToken) {
		
		// проверка существования данных:
		if (strlen($CustomerAuthToken) == 0 ) {

			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив данных запроса:
		$request = [
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetCustomerDetails', $headers, $request, "GET");
		// $res = $this->curl($this->url . 'PublicApi/Customers/GetCustomerDetails', $headers, $request, "GET");
		$response = json_decode($res, true);
		
		if (isset($response['Data']['AdditionalJsonData']) && isJSON($response['Data']['AdditionalJsonData']))
			$response['Data']['AdditionalJsonData'] = json_decode($response['Data']['AdditionalJsonData'], true);
		if ($response['Data']['AdditionalData']) {
			$arrAdditionalData = $response['Data']['AdditionalData'];
			foreach ($arrAdditionalData as $key => $value) {
				$AdditionalData[$value['Key']] = $value['Value'];
			}
			$response['Data']['AdditionalData'] = $AdditionalData;
		}

		// подключаем логирование:
		$log = new Log('getUser_');
		$strlog .= "TKLenderAPI.getCustomerDetails METHOD: getCustomerDetails "
				//. "\nres=" . print_r($res, true)
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * Возвращает информацию о кредитах залогиненного пользователя
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function getCustomerLoans($CustomerAuthToken) {
		
		// проверка существования данных:
		if (strlen($CustomerAuthToken) == 0 ) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив данных запроса:
		$request = [
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetCustomerLoans', $headers, $request, "GET");
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('getLoans_');
		$strlog .= "TKLenderAPI.getCustomerLoans METHOD: getCustomerLoans " .
				//"\nres=" . print_r($res, true).
				//"\nresponse=" .	print_r($response, true);
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * Возвращает данные инициализации 
	 * контактные телефоны, рекламные акции, конфигурационные данные, и пр.
	 * @return array
	 */
	public function getInitCRM() {
	
	    /**
		 * 	массив данных, возвращаемый из API во внешний фронтенд
		 * @var array $response
		 */
		$response = [];
	
		$error = 777; // всё ок
	
		//$this->config = parse_ini_file(MODX_BASE_PATH. '/DesignAPI/config.ini');
		
		if ($this->config) {
	
			$response['credit_percent_main'] = ($this->config['credit_percent_main'])? : 1;	// размер процента кредита
					
			$response['credit_days_min'] = ($this->config['credit_days_min'])? : 1;			// Мин кол-во дней
			$response['credit_days_max'] = ($this->config['credit_days_max'])? : 30; 		// Макс кол-во дней
			$response['credit_amount_min'] = ($this->config['credit_amount_min'])? : 100;	// Минимальная сумма займа
			$response['credit_amount_max'] = ($this->config['credit_amount_max'])? : 3000;	// Максимальная сумма
			$response['credit_amount_step'] = ($this->config['credit_amount_step'])? : 50;	// Шаг кредита
			$response['credit_time_resolution'] = ($this->config['credit_time_resolution'])? : 1;	// Шаг кредита
			$response['credit_amount_select'] = ($this->config['credit_amount_select'])? : 100;	// Начальная сумма займа в калькуляторе
			$response['credit_days_select'] = ($this->config['credit_days_select'])? : 1;	// Начальное кол-во дней в калькуляторе
			$response['credit_long_max'] = ($this->config['credit_long_max'])? : 1;			// Макс кол-во дней продления кредита
			$response['credit_long_step'] = ($this->config['credit_long_step'])? : 10;		// Шаг продления кредита
			
			$response['change_status_rejected'] = ($this->config['change_status_rejected'])? : 20;		// Дней до смены статуса "Отклонен"
			$response['change_status_agrExpired'] = ($this->config['change_status_agrExpired'])? : 5;	// Дней до смены статуса "Истек срок согласования"
			$response['change_status_closed'] = ($this->config['change_status_closed'])? : 2;			// Дней до смены статуса "Закрыт"
					
			$response['intervalStopSlider'] = (isset($this->config['intervalStopSlider']))? $this->config['intervalStopSlider'] : 5;	// Время простоя слайдера в секундах до изменения
					
			$response['email_support'] = ($this->config['email_support'])? : '';		// почта саппорта
			
			$response['lifeTimeToken'] = ($this->config['TKLender_lifeTimeToken'])? : '';		// время жизни токена
			
			$response['interval_refresh_page'] = ($this->config['interval_refresh_page'])? : '';	// частота запроса на обновление страницы в секундах
					
			$response['affiliates']['daysExpires'] = ($this->config['Affiliate_daysExpires'])? : 0;	// по умолчанию пвремя жизни куки
			
			$response['affiliate']['SalesDoubler']['url'] = ($this->config['SalesDoubler_url'])? : '';	// url SalesDoubler
			$response['affiliate']['SalesDoubler']['token'] = ($this->config['SalesDoubler_token'])? : '';	// token SalesDoubler
			$response['affiliate']['SalesDoubler']['daysExpires'] = ($this->config['SalesDoubler_daysExpires'])? : 0;	// время жизни куки
					
			$response['affiliate']['PrimeLead']['url'] = ($this->config['PrimeLead_url'])? : '';	// url PrimeLead
			$response['affiliate']['PrimeLead']['daysExpires'] = ($this->config['PrimeLead_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Admitad']['url'] = ($this->config['Admitad_url'])? : '';	// url Admitad
			$response['affiliate']['Admitad']['daysExpires'] = ($this->config['Admitad_daysExpires'])? : 0;	// время жизни куки
			
			// $response['affiliate']['Loangate']['url'] = ($this->config['Loangate_url'])? : '';	// url Loangate
			// $response['affiliate']['Loangate']['daysExpires'] = ($this->config['Loangate_daysExpires'])? : '';	// время жизни куки
			
			$response['affiliate']['Linkprofit']['url'] = ($this->config['Linkprofit_url'])? : '';	// url Linkprofit
			$response['affiliate']['Linkprofit']['daysExpires'] = ($this->config['Linkprofit_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['DoAffiliate']['url'] = ($this->config['DoAffiliate_url'])? : '';	// url DoAffiliate
			$response['affiliate']['DoAffiliate']['daysExpires'] = ($this->config['DoAffiliate_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Leads_su']['url'] = ($this->config['Leads_su_url'])? : '';	// url Leads.su
			$response['affiliate']['Leads_su']['daysExpires'] = ($this->config['Leads_su_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Targetme']['url'] = ($this->config['Targetme_url'])? : '';	// url Targetme
			$response['affiliate']['Targetme']['daysExpires'] = ($this->config['Targetme_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Finline']['url'] = ($this->config['Finline_url'])? : '';	// url Finline
			$response['affiliate']['Finline']['daysExpires'] = ($this->config['Finline_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Kadam']['url'] = ($this->config['Kadam_url'])? : '';	// url Kadam
			$response['affiliate']['Kadam']['daysExpires'] = ($this->config['Kadam_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Letmeads']['url'] = ($this->config['Letmeads_url'])? : '';	// url Letmeads
			$response['affiliate']['Letmeads']['daysExpires'] = ($this->config['Letmeads_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Leadercpa']['url'] = ($this->config['Leadercpa_url'])? : '';	// url Leadercpa
			$response['affiliate']['Leadercpa']['daysExpires'] = ($this->config['Leadercpa_daysExpires'])? : 0;	// время жизни куки
			
			$response['affiliate']['Satellite']['daysExpires'] = ($this->config['Satellite_daysExpires'])? : 0;	// время жизни куки
			
			$response['JivositeWidgetId'] = ($this->config['Jivosite_widget_id'])? : '';	// Jivosite id
			
			$response['GoogleAnalytics_id'] = ($this->config['GoogleAnalytics_id'])? : '';	// Google Analytics Id
			$response['GoogleKey'] = ($this->config['GoogleKey'])? : '';	// Google Analytics Id
			$response['GoogleGTM'] = ($this->config['GoogleGTM'])? : '';	// Google GTM Id
			
			$response['YandexMetrikaId'] = ($this->config['YandexMetrika_id'])? : '';		// Yandex Metrika id
			$response['FacebookPixelId'] = ($this->config['FacebookPixel_id'])? : '';		// Facebook Pixel id
			$response['VK_id'] = ($this->config['VK_id'])? : '';							// VK.com id
			$response['VK_r'] = ($this->config['VK_r'])? : '';								// VK.com r
			$response['ismatlab_sys'] = ($this->config['ismatlab_sys'])? : '';				// ismatlab: Параметр sys
			$response['HotjarId'] = ($this->config['Hotjar_id'])? : '0';					// Hotjar id
			$response['PW_websiteId'] = ($this->config['PW_websiteId'])? : '0';				// Push.world
			
			$response['ReCaptcha_sitekey'] = ($this->config['ReCaptcha_sitekey'])? : '';	// идентификационный ключ ReCaptcha
			
			$response['requestCounterMin'] = ($this->config['request_counter_min'])? : '0';	// минимальное количество заявок для счетчика
			$response['requestCounterMax'] = ($this->config['request_counter_max'])? : '1';	// максимальное количество заявок для счетчика
					
		} else {
			$error = 113; // Данные не найдены
		}
	
		$response['response_code'] = $error;
	
		return $response;
	}
	
	/**
	 * получает список возможных пролонгаций
	 * @param int $loanId
	 * @return array
	 */
	public function getListProlongations($loanId) {
	    
/*
	    return [ 
            'Data' => [
                [
                    'RolloverId' => 1,
                    'RolloverDays' => 8,
                    'RolloverDiscount' => 10,
                    'RolloverFinishDate' => '2018-05-07',
                    'RolloverInterestAmount' => 201,
                ],
                [
                    'RolloverId' => 2,
                    'RolloverDays' => 16,
                    'RolloverDiscount' => 20,
                    'RolloverFinishDate' => '2018-05-08',
                    'RolloverInterestAmount' => 301,
                ],
                [
                    'RolloverId' => 3,
                    'RolloverDays' => 31,
                    'RolloverDiscount' => 30,
                    'RolloverFinishDate' => '2018-05-09',
                    'RolloverInterestAmount' => 351,
                ],
            ],
            'Success' => true,
        ];
*/
	    
	    // проверка существования данных:
	    if (!$loanId || !$_SESSION ['token']) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	            'error' => 100,
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$_SESSION ['token']}",
	        ];
	    
	    $request = [];
	    
	    // непосредственно отправка:
	    $url = $this->url . 'PublicApi/GetPossibleLoanRollovers?loanId=' . $loanId;
	    $res = $this->curl($url, $headers, $request, "GET");
	    $response = json_decode($res, true);
	    if (isJSON($response['Data']))
	        $response['Data'] = json_decode($response['Data'], true);
	        
        // подключаем логирование:
        $log = new Log('prolong_');
        $strlog .= "TKLenderAPI.getListProlongations  METHOD: GetPossibleLoanRollovers"
            . "\nurl = " . $url
            . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
        $log->write($strlog);
            
        return $response;
	}
	
	/**
	 * получает список возможных реструктуризаций
	 * @param int $loanId
	 * @return array
	 */
	public function getListRestructurings($loanId) {
		
		// проверка существования данных:
		if (!$loanId || !$_SESSION ['token']) {
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [];
		
		// удаляем спецсимволы:
		// $request = $this->preprocessing($request);
		//$requestJson = json_encode($request);
		
		// непосредственно отправка:
		$url = $this->url . 'PublicApi/PreviousResultOnRestructure?loanid=' . $loanId;
		$res = $this->curl($url, $headers, $request, "GET");
		$response = json_decode($res, true);
		if (isJSON($response['Data'])) 
			$response['Data'] = json_decode($response['Data'], true);
		
		// подключаем логирование:
		$log = new Log('restruct_');
		$strlog .= "TKLenderAPI.getListRestructurings  METHOD: PreviousResultOnRestructure"
				. "\nurl = " . $url
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
				
		return $response;
	}
	
	/**
	 * получает OrderReference из CRM для дальнейшей отправки в платежную систему
	 * @param string $CustomerAuthToken 
	 * @param array $data
	 */
	public function getOrderReference($CustomerAuthToken, $data) {
		
		// массив данных запроса:
		//$this->request = $data;
		$request = [
		];
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetVerification', $headers, $request, "GET");
		//$res = $this->curl($this->url . 'PublicApi/GetVerification?loanid=' . $data['id'], $headers, $request, "GET");
		
		$response = json_decode($res, true);
		//$response = $res;
		
		return $response;
		
	}

	/**
	 * запрашивает конкретную транзакцию по выбранному кредиту залогиненного пользователя (по всем системам),
	 * или последнюю транзакцию, если номер не указан
	 * 
	 * @param string $CustomerAuthToken
	 * @param string $loanId
	 * @param string $repaymentId
	 * @return array
	 */
	public function getPayment($CustomerAuthToken, $loanId, $repaymentId = false) {
		
		$resAllPayments = $this->getAllPayments($CustomerAuthToken, $loanId);

		$myRepayment = [];
		// найдем требуюмую транзакцию:
		if($resAllPayments['Success'] && $resAllPayments['Data']) {
			$repayments = $resAllPayments['Data'];
			$lastKey = count($repayments) - 1;
			
			if ($repaymentId) {
				$myKey = 0;	// последний ключ
				foreach ($repayments as $key => $repayment) {
					if ($repayment['Id'] === $repaymentId) { 
						$myRepayment = $repayment;
						$myKey = $key;	// последний ключ
						break;
					}
				}
			} else {
				$myKey = $lastKey;						// последний ключ
				$myRepayment = $repayments[$lastKey];	// берем последний элемент
			}
			// последняя ли это транзакция:
			if ($myKey === $lastKey) {
				$myRepayment['lastPayment'] = true;
			} else {
				$myRepayment['lastPayment'] = false;
			}
		}
		
		// подключаем логирование:
		$log = new Log('getPayment_');
		$strlog = "METHOD: getPayment (TKLenderAPI.getPayment) "
			. "\nloanId={$loanId} repaymentId={$repaymentId}"
			. "\nmyRepayment = " . print_r($myRepayment, true);
		$log->write($strlog);
				
		return $myRepayment;
	}
	
	/**
	 * читает URL CRM
	 * @return string
	 */
	public function getUrl() {
		return $this->url;	
	}
	
	/**
	 * посылает в CRM запрос о полях ввода для верификации карты
	 * @param string $cardNumber
	 * @return array
	 */
	/*
	function getVerification3ds($cardNumber = '') {
		
		// проверка существования данных:
		if ((strlen($cardNumber) == 0) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) < 10)) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [];
		
		// удаляем спецсимволы:
		// $request = $this->preprocessing($request);
		//$requestJson = json_encode($request);
		
		// непосредственно отправка:
		$url = $this->url . 'PublicApi/GetVarification3dsResult?card=' . $cardNumber;
		$res = $this->curl($url, $headers, $request, "GET");
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('cardReg_');
		$strlog .= "TKLenderAPI.getVerification3ds  METHOD: GetVarification3dsResult "
				. "\nurl = " . $url
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
				$log->write($strlog);
				
				return $response;
	}
	*/
	
	/**
	 * возвращает номер платежной системы пользователя
	 * @param string CustomerAuthToken
	 * @return array
	 */
	public function GetVeryficationSystem($CustomerAuthToken) {
		
		// массив имен платежных систем: 
		$systemNames = [
				'UnKnow',
				'WayForPay',
				'Tranzzo',
		];
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// массив данных запроса:
		$request = [
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetVeryficationSystem', $headers, $request, "GET");
		
		$res = json_decode($res, true);
		
		$response = $res;
		
		if ($res['Success']) {
			$response['Data']['systemName'] = $systemNames[$response['Data']['systemType']];
		}
		
		// подключаем логирование:
		$log = new Log('getSystem_');
		$strlog = "METHOD: GetVeryficationSystem (TKLenderAPI.tranzooGetCardsAll) " .
			// "\nres=" . print_r($res, true) .
			"\nresponse=" . print_r($response, true);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * Вход в систему. Если пользователь существует, возвращает токен в том числе
	 * @param string $login
	 * @param string $password
	 * @param boolean $flagTechnologist (true - если технолог)
	 * @return array
	 */
	public function loginCustomer($login, $password, $flagTechnologist = false) {
		
		// проверка существования данных:
		if (!isset($login) || !isset($password))	{
	
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			
			return $response;
		}
	
		$request = [
				'login' => $login,
				'password' => $password,
		];
	
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
		
		$url = $this->url . 'PublicApi/LoginCustomer';
		/*if ($flagTechnologist) {
			$url = $this->url . 'PublicApi/LoginServiceUser';
		} else {
			$url = $this->url . 'PublicApi/LoginCustomer';
			// $url = $this->url . 'PublicApi/Customers/LoginCustomer';
		}*/
		
		// непосредственно отправка:
		$res = $this->curl($url, $headers, $request);
		$response = json_decode($res, true);
		
		if (($response['Success']) && (!$flagTechnologist)) {
		
			// отправка данных сессии:
			// $this->sendUserInfo();							// первоначальная запись
			$this->sendUserInfo(['DateUpdated' => date('c')]);	// запись обновления
			
			// добавление токен в массив заголовков:
			$headers = [
					"tkLender_ApiKey: {$this->key_api}",
					"tkLender_CustomerAuthToken: {$response['CustomerAuthToken']}",
					];
			
			$requestSession = [];
			$requestSession['sessionId'] = session_id();
			
			// непосредственно отправка номера сессии при логине:
			$resSession = $this->curl($this->url . 'PublicApi/ClientSessions/AssignCustomer/', $headers, $requestSession, "PUT");
			
			// запись Cookie:
			$myCookie = new Cookies($login);
			// var_dump($myCookie->getBody());die;
		}
		
		// подключаем логирование:
		$log = new Log('login_');
		$request['password'] = '******';	// убираем реальный пароль из лога 
		$strlog .= "TKLenderAPI.loginCustomer METHOD: " . (($flagTechnologist) ? "loginTechnologist" : "loginCustomer") .
				"\nrequest = " . print_r($request, true) .
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res) .
				"\nsession_id = " . session_id() .
				"\nresSession = " . ((isJSON($resSession)) ? print_r(json_decode($resSession, true), true) : $resSession);
		$log->write($strlog);

		
		return $response;
	}
	
	/**
	 * Вход в систему под технологом
	 * @return array
	 */
	public function loginTechnologist() {
		
		return $this->loginCustomer($this->technologist['login'], $this->technologist['password'], true);
	}
	
	/**
	 * заказывает реструктуризацию
	 * @param int $loanId
	 * @return array
	 */
	public function makeRestructure($loanId, $restructureId) {
		
		// проверка существования данных:
		if (!$loanId || !$restructureId|| !$_SESSION ['token']) {
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [
				'loanId' => $loanId,
				'restructureId' => $restructureId,
		];
		
		// удаляем спецсимволы:
		// $request = $this->preprocessing($request);
		//$requestJson = json_encode($request);
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/MakeRestructure', $headers, $request);
		$response = json_decode($res, true);
		//if (isJSON($response['Data']))
		//	$response['Data'] = json_decode($response['Data'], true);
			
		// подключаем логирование:
		$log = new Log('restruct_');
		$strlog .= "TKLenderAPI.makeRestructure  METHOD: MakeRestructure"
			. "\nrequest = " . print_r($request, true)
			. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
					
		return $response;
	}
	
	/**
	 * возвращает массив уведомлений для текущего клиента
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function notifyGetList(string $CustomerAuthToken = '') {

	    // проверка существования данных:
	    if (!$CustomerAuthToken) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$CustomerAuthToken}",
	        ];
	    
	    $request = [];
	    
	    // непосредственно отправка:
	    $url = $this->url . 'PublicApi/GetPersonalNotifications';
	    $res = $this->curl($url, $headers, $request, "GET");
	    $response = json_decode($res, true);
	        
        // подключаем логирование:
        $log = new Log('notify_');
        $strlog .= "TKLenderAPI.notifyGetList METHOD: GetPersonalNotifications"
            . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
        $log->write($strlog);
            
        return $response;
	}
	
	/**
	 * уведомляет бек, что сообщение прочитано, и нужно ли показывать позже
	 * @param string $CustomerAuthToken
	 * @param int $notificationId
	 * @param boolean $showLater - если true, сообщение будет показано позже
	 * @return array
	 */
	public function notifyShowLater(string $CustomerAuthToken = '', int $notificationId = 0, bool $showLater = false) {
	    
	    // проверка существования данных:
	    if (!$CustomerAuthToken || !$notificationId) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$CustomerAuthToken}",
	        ];
	    
	    $request = [];
	    
	    $showLaterint = ($showLater) ? 1 : 0;
	    
	    // непосредственно отправка:
	    $url = $this->url . "PublicApi/ShowLater?notificationId={$notificationId}&showLater={$showLaterint}";
	    $res = $this->curl($url, $headers, $request, "GET");
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('notify_');
	    $strlog .= "TKLenderAPI.notifyShown METHOD: NotificationShown"
	        . "\nurl = " . $url
	        . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
	    $log->write($strlog);
	        
	    return $response;
	}
	
	/**
	 * используется для уведомления бека о том, что нотификация была показана пользователю 
	 * @param string $CustomerAuthToken
	 * @param int $notificationId
	 * @return array
	 */
	public function notifyShown(string $CustomerAuthToken = '', int $notificationId = 0 ) {
	    
	    // проверка существования данных:
	    if (!$CustomerAuthToken || !$notificationId) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$CustomerAuthToken}",
	        ];
	    
	    $request = [];
	    
	    // непосредственно отправка:
	    $url = $this->url . 'PublicApi/NotificationShown?notificationId=' . $notificationId;
	    $res = $this->curl($url, $headers, $request, "GET");
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('notify_');
	    $strlog .= "TKLenderAPI.notifyShown METHOD: NotificationShown"
	       . "\nurl = " . $url
	       . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
        $log->write($strlog);
	        
        return $response;
	}
	
/**
	 * Оплата для погашения кредита
	 * @param array $data
	 * @return array
	 */
	public function payCredit($data) {
		
		if (isset($data['id'])) {
			
			// если платить текущей картой, то просто посылаем запрос в CRM:
			if ($data['isCurrentCard'] === 'true') {
				$res = [
						'amount' => $data['amount'],
						'LoanId' => $data['id'],
						'isCurrentCard' => 'true',
				];
				// отправляем запрос оплаты в CRM
				$resCRM = $this->acceptPay($res, $_SESSION['token']);
				$error = ($resCRM['Success']) ? 777 : 303; // Не удалось перечислить средства с карты

			} else {
				// иначе запускаем Платежную систему:
				
				$orderReference = 'FR' . date('YmdHis') . $data['id'];
				
				$data['orderReference'] = $orderReference;
				$data['system_name'] = $this->config['system_name'];
				$payment = Payment::getProvider($this->config);
				$res = $payment->purchase($data);
				$error = $res['error'];
					
				if ($error == 777) {
					$response['form'] = $res['form'];
					$response['widget'] = $res['widget'];
				} else {
					$response['response_payments'] = $res;
				}
			}

			// подключаем логирование:
			$log = new Log('w4p_');
			$strlog .= "Результат работы TKLender.payCredit METHOD: payCredit" .
				"\nres = " . print_r($res, true).
				"\nresCRM=" .	print_r($resCRM, true);
			$log->write($strlog);
			
		} else {
			$error = 100; // Нет входных данных
		}
		
		$response['error'] = $error;
		
		return $response;
	}
	
	/**
	 * удаляет спец.символы и пр. из полей массива
	 * @param array | string $request
	 * @return array
	 */
	public function preprocessing($request) {

   		if (is_array($request)) {
			// удаляем спецсимволы:
			foreach ($request as $key => $value) {
				if (is_string($value)) {
					$request[$key] = $this->updateValue($request[$key]); // удаляем спецсимволы
				}
			}
		} elseif (is_string($request)) {
			$request = $this->updateValue($request); // удаляем спецсимволы
		}
		return $request;
	}
	
	/**
	 * Отправляет заем для автоматической обработки (scoring)
	 * @param int $loanId
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function processLoan($loanId, $CustomerAuthToken) {
		
		// проверка существования данных:
		if (!isset($loanId) || !isset($CustomerAuthToken))	{
	
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			
			return $response;
		}
	
		// массив данных запроса:
		$request = [
				'LoanId' => $loanId,
		];
	
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/ProcessLoan', $headers, $request);
		$response = json_decode($res, true);
		
		return $response;
	}
	
	/**
	 * заказывает пролонгацию кредита (старая версия)
	 * @param string $CustomerAuthToken
	 * @param int $loanId
	 * @param string $desiredPaymentDate
	 * @param string $comments
	 * @return array
	 */
	public function prolongation($CustomerAuthToken, $loanId, $desiredPaymentDate, $comments = '') {
		// проверка существования данных:
		if (!$loanId || !$desiredPaymentDate)	{
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		$request = [
				'loanId' => $loanId,
				// 'term' => $term,
				'desiredPaymentDate' => $desiredPaymentDate,
				'comments' => $comments,
				];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/ApplyRollover', $headers, $request);
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('prolong_');
		$strlog .= "TKLenderAPI.prolongation METHOD: prolongation " . 
			"\nrequest=" . print_r($request, true). 
			//"\nres=" . print_r($res, true). 
			//"\nresponse=" . print_r($response, true);
			"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
		
	}
	
	/**
	 * регистрация пользователя в системе.
	 * @param string $login
	 * @param string $password
	 * @return array
	 */
	public function registerCustomer($login, $password) {
	
			// проверка существования данных:
		if (!isset($login) || !isset($password))	{
	
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		// проверка корректности телефона:
		if (($login !== htmlspecialchars($login)) || (preg_match('/^\+380\d{9}$/', $login) !== 1)) {
			$response = [
					'Success' => false,
					'Message' => "'Некорректный телефон",
					'error' => 105,
			];
			return $response;
		}
		
		$request = [
				'login' => $login,
				'password' => $password,
		];
	
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		
		// массив заголовков:
		$headers = ["tkLender_ApiKey: {$this->key_api}"];
	
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/RegisterCustomer', $headers, $request);
		//$res = $this->curl($this->url . 'PublicApi/Customers/RegisterCustomer', $headers, $request);
		$response = json_decode($res, true);
	
		// запись Cookie:
		$myCookie = new Cookies($login);
		
		// подключаем логирование:
		$log = new Log('reg_');
		$request['password'] = '******';
		$strlog .= "TKLenderAPI.registerCustomer  METHOD: registerCustomer " . 
			"\nrequest = " . print_r($request, true). 
			//"\nresponse = " . print_r($response, true). 
			"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
			"\nREMOTE_ADDR=" .	$_SERVER['REMOTE_ADDR'];
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * результат проплаты с карты в платежной системе
	 * @param array $data
	 * @param boolean $flagWidget
	 * @return array
	 */
	public function resPay($data, $flagWidget = false) {
		
		if (_IS_DEV) {
			$res ['error'] = 0;	// 000 Ошибка  системы
			$res ['response'] = '';	// Ответ
			return $res;
		}
		
		$payment = Payment::getProvider($this->config);
		$res = [];
		$res = $payment->resPay($data, $flagWidget);
		//$error = $res['error'];
				
		return $res;
	}
	
	/**
	 * результат проверки карты в платежной системе
	 * @param array $data 
	 * @return array
	 */
	public function resVerify($data) {
	
		if (_IS_DEV) {
			$res ['error'] = 0;	// 000 Ошибка  системы
			$res ['response'] = '';	// Ответ
			return $res;
		}
		
		$payment = Payment::getProvider($this->config);
		$res = $payment->resVerify($data);
		$error = $res['error'];
				
		if ($error == 777) {

			// здесь записываем данные, полученные от платежной системы:
			if (in_array($res['transactionStatus'], ['Approved', 'WaitingAuthComplete']) && $res['recToken']) {
				// ..
				/*
			        [merchantAccount] => test_merch_n1
				    [orderReference] => 201606231336531
				    [merchantSignature] => 94e527f04898e335f5e73245fc738dc4
				    [amount] => 1
				    [currency] => UAH
				    [authCode] => 
				    [email] => stebaev@mail.ru
				    [phone] => 380675711605
				    [createdDate] => 1466689310
				    [processingDate] => 1466689339
				    [cardPan] => 48****4138
				    [cardType] => Visa
				    [issuerBankCountry] => Ukraine
				    [issuerBankName] => PJSC UKRSOTSBANK
				    [recToken] => 56f57ccd-2b34-4129-9a0e-574638a74599
				    [transactionStatus] => WaitingAuthComplete
				    [reason] => Ok
				    [reasonCode] => 1100
				    [fee] => 0
				    [paymentSystem] => card
				    [clientName] => ROMAN KATERYNCHYK
				    [rc_token] => 56f57ccd-2b34-4129-9a0e-574638a74599
				    [card_id] => 1
				    [error] => 777
				    [response] => {"orderReference":"201606231336531","status":"accept","time":1466689521,"signature":"9488c0162b05e9cc4a817340b83f9e18"}

				*/
				// отправляем результат верификации в CRM
				$resCRM = $this->acceptVerification($res, $_SESSION['token']);	
			} else {
				$resCRM = 'Не отправлено по причине несоответствия условиям.';
			}
				
		}
		
		/*
		$testW4p = file_get_contents(MODX_BASE_PATH. '/DesignAPI/testW4p.txt');
		file_put_contents(MODX_BASE_PATH. '/DesignAPI/testW4p.txt', $testW4p . "\n--------------\n Отправка в  CRM ответа на VERIFY time=" . date('Y-m-d H:i:s') .
				"\nres=" . print_r($res, true).
				//"\nSERVER=" .	print_r($_SERVER, true) .
				"\nresCRM=" .	print_r($resCRM, true)
					
				);
		*/
		
		return $res;
	}
	
	/**
	 * отправляет данные партнеру при успешной транзакции
	 * @param int $loanId
	 * @return boolean
	 */
	public function sendAffiliate($loanId, $method = 'confirmContract') {
		
		// файл отчетности:
		$dir = MODX_BASE_PATH . "/DesignAPI/reports/" . date('Y') . "/" . date('m') . "/";
		@mkdir($dir, 0777, true);
		$outfileName = $dir . 'affiliate_' . date("Ym") . ".csv";
		
		// узнаем, сколько было заявок на кредит:
		// $loans = $this->getCustomerLoans($_SESSION['token']);
		$loans = [];
		$loans ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
		$loans ['Data'] = $_SESSION['api']['credits'];
		if($loans['Success']) {
			$countLoans = count($loans['Data']);
			$amount = '0';
			$isRepeated = ($countLoans > 1) ? '1' : '0';
			foreach ($loans['Data'] as $key => $value) {
				if ($value['Id'] == $loanId) {
					$amount = $value['Amount'];
					break;
				}
			}
		} else {
			$countLoans = 0;
			$amount = '0';
			$isRepeated = '0';
		}
			
		$flagToSend = false;			// признак, что данные нужно отправлять
		$flagToDeleteCookie = false;	// признак, что cookie нужно удалить
		
		if ($_COOKIE['SalesDoubler']) {
			$affiliateName = 'SalesDoubler';
			
			$affiliate = unserialize($_COOKIE[$affiliateName]);
				
			$affiliateTransaction = $affiliate['user'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = (($isRepeated == '0')? $this->config['SalesDoubler_url'] : $this->config['SalesDoubler_url_repeat'])
					. $affiliate['user'] . '?trans_id=' . $loanId . '&token=' . $this->config['SalesDoubler_token'];
				
				// $flagToSend = ($isRepeated) ? false : true;	
				$flagToSend = true;	
				$flagToDeleteCookie = true;

			/* 
			} elseif (in_array($method, ['applyforLoan', 'verify'])) {
				$affiliateUrl = $this->config['SalesDoubler_url_loan']
					. $affiliate['user'] . '?trans_id=' . $loanId . '&token=' . $this->config['SalesDoubler_token'];
				
				// если еще не отправляли инфо о заявке, перезаписывем cookie с этой инфо:
				if(isset($affiliate[$method])) {
					$flagToSend = false;
					$message = ' По данной cookie уже отправлялись данные о создании заявки.';
				} else {
					$affiliate[$method] = true;
					$daysExpires = (int) $this->config['SalesDoubler_daysExpires'];
					setcookie ( "SalesDoubler", serialize ( $affiliate ), time () + 86400 * $daysExpires, '/' );
					if ((($method === 'verify') && ($isRepeated == '0')) || (($method === 'applyforLoan') && ($isRepeated == '1'))) {
						$flagToSend = true;
					}
				}
				$flagToDeleteCookie = false;
			*/
				
			}
				
		} elseif ($_COOKIE['PrimeLead']) {
			$affiliateName = 'PrimeLead';
		
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$affiliateTransaction = $affiliate['transaction_id'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = (($isRepeated == '0')? $this->config['PrimeLead_url'] : $this->config['PrimeLead_url_repeat'])
					. '?adv_sub=' . $loanId . '&transaction_id=' . $affiliate['transaction_id'];
				
				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
				
		} elseif ($_COOKIE['Admitad']) {
			$affiliateName = 'Admitad';
		
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$affiliateTransaction = $affiliate['admitad_uid'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = $this->config['Admitad_url']
					. '&action_code=2&uid=' . $affiliate['admitad_uid'] . '&order_id=' . $loanId
					. '&currency_code=UAH'
					. '&quantity=1&position_id=1&position_count=1&product_id=1&payment_type=sale'
					. '&tariff_code=' . ((int) $isRepeated + 1)	// тариф (1 - новый клиент, 2 - повторник)
					. '&price=' . $amount .'&old_consumer=' . $isRepeated;
							
				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
				
        /*
 		} elseif ($_COOKIE['Loangate']) {
			$affiliateName = 'Loangate';
		
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$affiliateTransaction = $affiliate['afclick'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = $this->config['Loangate_url']
					. '?clickid=' . $affiliateTransaction . '&action_id=' . $loanId
					. '&goal=' . ((int) $isRepeated + 1);	// тариф (1 - новый клиент, 2 - повторник)
	
				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
        */
			
		} elseif ($_COOKIE['Linkprofit']) {
			$affiliateName = 'Linkprofit';
			
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$repeated = ($isRepeated == '0') ? '' : '&ActionCode=secondary';	// признак повторника для URL
			
			$affiliateTransaction = $affiliate['refid'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = $this->config['Linkprofit_url']
					. '&OrderID=' . $loanId . '&ClickHash=' . $affiliate['click_hash'] . '&AffiliateID=' . $affiliate['refid']
					. $repeated;
				
				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
			
		} elseif ($_COOKIE['DoAffiliate']) {
			$affiliateName = 'DoAffiliate';
			
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$repeated = ($isRepeated == '0') ? '' : '2';	// признак повторника для URL
			
			$affiliateTransaction = $affiliate['visitor'];
			
			http://tracker2.doaffiliate.net/api/mycredit-ua?type=CPA
			
			// if (($method === 'confirmContract') && ($isRepeated == '0')) {    // только для первичников
		    if ($method === 'confirmContract') {
		        $affiliateUrl = $this->config['DoAffiliate_url'] . $repeated
					. '&lead=' . $loanId . '&v=' . $affiliate['visitor'] . '&sale=' . $loanId;

				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
			
		} elseif ($_COOKIE['Leads_su']) {
			$affiliateName = 'Leads_su';
			
			$affiliate = unserialize($_COOKIE[$affiliateName]);
			
			$repeated = ($isRepeated == '0') ? '0' : '519';	// признак повторника для URL
			
			$affiliateTransaction = $affiliate['transaction_id'];
			
			if ($method === 'confirmContract') {
				$affiliateUrl = $this->config['Leads_su_url']
					. '&goal_id=' . $repeated
					. '&transaction_id=' . $affiliate['transaction_id'] .  '&adv_sub=' . $loanId . '&status=approved&comment=ok';
				
				$flagToSend = true;
				$flagToDeleteCookie = true;
			}
			
		} elseif ($_COOKIE['Targetme']) {
		    $affiliateName = 'Targetme';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    
		    $repeated = ($isRepeated == '0') ? '2' : '3';	// признак повторника для URL
		    
		    $pid = $affiliate['pid'];
		    $clickid = $affiliate['clickid'];
		    
		    $affiliateTransaction = $clickid;
		    
		    if ($method === 'confirmContract') {
		        $affiliateUrl = $this->config['Targetme_url']
		          . '?clickid=' . $clickid
		          . '&goal=' . $repeated
		          . '&action_id=' . $loanId;
		        
	            $flagToSend = true;
	            $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Finline']) {
		    $affiliateName = 'Finline';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    
		    $repeated = ($isRepeated == '0') ? '1' : '2';	// признак повторника для URL
		    
		    $pid = $affiliate['pid'];
		    $clickid = $affiliate['clickid'];
		    
		    $affiliateTransaction = $clickid;
		    
		    if ($method === 'confirmContract') {
		        $affiliateUrl = $this->config['Finline_url']
		        . '?clickid=' . $clickid
		        . '&action_id=' . $loanId
		        . '&goal=' . $repeated
		        . '&status=1';
		        
		        $flagToSend = true;
		        $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Moneyexpert']) {
		    $affiliateName = 'Moneyexpert';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    $affiliateTransaction = $affiliate['campaign'];
		    
		    if ($method === 'confirmContract') {
		        $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Uacreditbiz']) {
		    $affiliateName = 'Uacreditbiz';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    $affiliateTransaction = $affiliate['campaign'];
		    
		    if ($method === 'confirmContract') {
		        $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Kadam']) {
		    $affiliateName = 'Kadam';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    
		    // $sid = $affiliate['sid'];
		    // $catid = $affiliate['catid'];
		    $cpa = $affiliate['cpa'];

		    $affiliateTransaction = $cpa;
		    
		    if ($method === 'confirmContract') {
		        $affiliateUrl = $this->config['Kadam_url']
		        . '?data=' . $cpa;
		        // . '&goal=' . $isRepeated
		        
		        $flagToSend = true;
		        $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Letmeads']) {
		    $affiliateName = 'Letmeads';
		    
		    $affiliate = unserialize($_COOKIE[$affiliateName]);
		    
		    $letmeads_ref = $affiliate['letmeads_ref'];
		    
		    $affiliateTransaction = $letmeads_ref;
		    
		    if ($method === 'confirmContract') {
		        $affiliateUrl = $this->config['Letmeads_url']
		        . "&ref_id={$loanId}"
                . "&click_id={$letmeads_ref}";
		        
		        $flagToSend = true;
		        $flagToDeleteCookie = true;
		    }
		    
		} elseif ($_COOKIE['Satellite'] || $_SESSION['sat_id']) {
			$sat_id = ($_COOKIE['Satellite']) ? : $_SESSION['sat_id'];
			$affiliateName = 'Satellite_' . $sat_id;
			
			$affiliateTransaction = (isset($this->satellites[$sat_id])) ? $this->satellites[$sat_id] : 'empty';
			
			$affiliateUrl = $affiliateName;	// просто для лога
			
			if ($method === 'confirmContract') {
				// $flagToSend = false;
				$flagToDeleteCookie = true;
			}
			
		    // если нет Cookie,	записываем confirmContract тому, кто сделал заявку:
		} elseif ( $method === 'confirmContract' && existsStringInFile($outfileName, ",applyforLoan,{$loanId},")) {
				
			$content = file_get_contents($outfileName);
			preg_match("/.{25},(\w{1,20}),\w{1,40},applyforLoan,{$loanId}/", $content, $matches, PREG_OFFSET_CAPTURE);
			$affiliateName = $matches[1][0];
			
			$affiliateTransaction = 'empty';
			$affiliateUrl = $affiliateName;	// просто для лога
			
			
		} else {
			
			$log = new Log('affil_');
			$strlog .= "TEST affiliate (кредит без партнера) METHOD: TEST_AFFILIATE_{$method} "
				// . "\ncookie_SalesDoubler = " . print_r($_COOKIE['SalesDoubler'], true)
				// . "\ncookie_PrimeLead = " . print_r($_COOKIE['PrimeLead'], true)
				. "\nmethod = {$method} loanId = {$loanId}" ;
			$log->write($strlog);
			
			return false;
		}
		
		// проверка на попытку повторной отправки при перезагрузке
		$affiliateHash = md5($affiliateName . $_COOKIE[$affiliateName] . $loanId . $method);
		if ($_SESSION['affiliateHash'] === $affiliateHash) {
			return false;
		}
		
		// если вдруг нет $loanId:
		if (!$loanId) 
			$flagToSend = false;
		
		// удаляем спецсимволы:
		$affiliate = $this->preprocessing($affiliate);
		
		// отправляем, если есть признак на отправку:
		if ($flagToSend) {
					
			// если нет строки с LoanId в файле отправленных отчетов:
			if (!existsStringInFile($outfileName, ",{$method},{$loanId},1,")) {
 				
 				// Параметры
				$request = [];
				// массив заголовков:
				$headers = [];
				// непосредственно отправка:
				$res = $this->curl($affiliateUrl, $headers, $request, 'GET');
				// $response = json_decode($res, true);
				$flagPosted = true;
				
			} else {
				$res = 'Не отправлено по причине того, что данный LoanId уже есть в файле отчетов.';
				$flagPosted = false;
			}
			
		} else {
			$res = 'Не отправлено по причине того, что недостаточно условий для отправки.';
			$flagPosted = false;
		}
			
		// записываем отправку в файл для отчетности:
		$fileExist = file_exists($outfileName);
		$outfile = fopen($outfileName, 'a');
		if ($outfile) {
			$fields = [
					date('c'),
					$affiliateName,
					$affiliateTransaction,
					$method,
					$loanId,
					$flagPosted,
					$isRepeated,
			];
			if (!$fileExist) fputcsv($outfile, ['Date', 'Name', 'User', 'Method', 'LoanId', 'FlagPosted', 'isRepeated']);	// первая строка
			fputcsv($outfile, $fields);
			fclose($outfile);
		}
		//
		
		// записываем отправку в файл для отчетности конкретного Affiliate:
		if (in_array($affiliateName, ['Satellite_1', 'Satellite_2', 'Moneyexpert', 'Uacreditbiz'])) {
			$outfileName = $dir . "affil_{$affiliateName}_" . date("Ym") . ".csv";
			$fileExist = file_exists($outfileName);
			$outfile = fopen($outfileName, 'a');
			if ($outfile) {
				$fields = [
						date('c'),
						$affiliateName,
						$affiliateTransaction,
						$method,
						$loanId,
						$flagPosted,
						$isRepeated,
				];
				if (!$fileExist) fputcsv($outfile, ['Date', 'Name', 'User', 'Method', 'LoanId', 'FlagPosted', 'isRepeated']);	// первая строка
				fputcsv($outfile, $fields);
				fclose($outfile);
			}
		}
		//
		
		// удаляем куки, если нужно:
		if($flagToDeleteCookie) {
			setcookie($affiliateName, "", time() - 1, "/", $_SERVER['SERVER_NAME'], $https, true);
			unset($_COOKIE[$affiliateName]);
		}
		
		$_SESSION['affiliateHash'] = $affiliateHash;
		
		// подключаем логирование:
		$log = new Log('affil_');
		$strlog .= "AFFILIATE {$affiliateName} METHOD: SEND_AFFILIATE_{$method} "
				. (($affiliateName == 'SalesDoubler') ? "\naff_sub (SalesDoubler_user) = " . $affiliate['user']
					. "\nloanId (trans_id) = " . $loanId : '')
				. (($affiliateName == 'PrimeLead') ? "\ntransaction_id = " . $affiliate['transaction_id']
					. "\nloanId (SUB_ID) = " . $loanId : '')
				. (($affiliateName == 'Admitad') ? "\nadmitad_uid = " . $affiliate['admitad_uid']
					. "\nloanId (order_id) = " . $loanId : '')
				. (($affiliateName == 'Loangate') ? "\nafclick = " . $affiliate['afclick']
    			    . "\nloanId (action_id) = " . $loanId : '')
    			. (($affiliateName == 'Targetme') ? "\nclickid = " . $affiliate['clickid'] . "\npid = " . $affiliate['pid']
	       	        . "\nloanId (action_id) = " . $loanId : '')
			    . (($affiliateName == 'Satellite_') ? "\nsat_id = " . $sat_id
					. "\nloanId = " . $loanId : '')
								
				// для теста:
				. (($_COOKIE['SalesDoubler']) ? "\nSalesDoubler = " . print_r(unserialize($_COOKIE['SalesDoubler']), true) : '' )
				. (($_COOKIE['PrimeLead']) ? "\nPrimeLead = " . print_r(unserialize($_COOKIE['PrimeLead']), true) : '' )
				. (($_COOKIE['Admitad']) ? "\nAdmitad = " . print_r(unserialize($_COOKIE['Admitad']), true) : '' )
				// . (($_COOKIE['Loangate']) ? "\nLoangate = " . print_r(unserialize($_COOKIE['Loangate']), true) : '' )
				
				. "\nurl = " . $affiliateUrl
				. "\nresult = " . $res  . $message;
		$log->write($strlog);
		
		return true;	// если отправили ответ партнеру
	}
	
	/**
	 * отсылает код регистрации по SMS 
	 * @param string $phone
	 * @return int $code
	 */
	public function sendCodeReg($phone, $lang = 'ru') {
		$phone = preg_replace("/\s+/", "", $phone); // удалить пробелы
		
		$config = $this->config;
		// меняем отправителя:
		if ($_SESSION['sat_id']) {
			// $config['turbo_sender'] = "MICROZAYM";
		}
		
		$sms = Api_SMS::getProvider($this->config['sms_provider']);
		$sms->setup($config);
		
				// подключаем логирование:
				$log = new Log('ajax_');
				$strlog = "function sendCodeReg phone = " . $phone . ' lang = ' . $lang;
				$log->write($strlog);
					
		$code = $sms->sendCode($phone, $lang);

				// подключаем логирование:
				$log = new Log('ajax_');
				$strlog = "code = " . $code;
				$log->write($strlog);
					
		return $code;
	}
	
	/**
	 * отправляет собранные данные от клиента (email, телефон, и пр.)
	 * @param array $data
	 * @return array
	 */
	public function sendClientContactInfo($data) {
		
		if (!is_array($data)) {
			$response['Success'] = false;
			$response['Message'] = 'No data';
			return $response;
		}
			
		// массив заголовков:
		$headers = [
		    "tkLender_ApiKey: {$this->key_api}",
		];
		
		// массив данных запроса:
		$request = $data;
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/ClientContactInfo', $headers, $request, 'POST');
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('info_');
		$strlog .= "TKLenderAPI.ClientContactInfo METHOD: ClientContactInfo "
				. "\nrequest = " . print_r($request, true)
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * отсылает информацию в Google Analytics
	 * @param integer $loanId
	 * @param string $CustomerAuthToken
	 */
	public function sendGoogleAnalytics($loanId, $CustomerAuthToken) {
		
		// получаем ID клиента:
		// $customer = $this->getCustomerDetails($CustomerAuthToken);
		$customer = [];
		$customer['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
		$customer['Data'] = $_SESSION['api']['client'];
		if ($customer['Success']) {
			$customerId = $customer['Data']['Id'];
		} else {
			$customerId = '';
		}
		// получаем сумму и срок кредита:
		// $loans = $this->getCustomerLoans($CustomerAuthToken);
		$loans = [];
		$loans ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
		$loans ['Data'] = $_SESSION['api']['credits'];
		if ($loans['Success']) {
			$term = '';
			$amount = '';
			$countLoans = count($loans['Data']);
			$isRepeated = ($countLoans > 1) ? 'repeat' : 'new';
			foreach ($loans['Data'] as $key => $value) {
				if ($value['Id'] == $loanId) {
					$term = $value['Term'];
					$amount = $value['Amount'];
					break;
				}
			}
		} else {
			$term = '';
			$amount = '';
			$isRepeated = 'new';
		}
		
		// проверка на попытку повторной отправки при перезагрузке
		$analyticsHash = md5($customerId . $loanId . $amount . $term);
		if ($_SESSION['analyticsHash'] === $analyticsHash) {
			return false;
		}
		
		$ga = new GoogleAnalytics();
		$resTransaction = $ga->requestTransaction($customerId, $loanId, $amount);
		$resItem = $ga->requestItem($term, $isRepeated);
		
		$_SESSION['analyticsHash'] = $analyticsHash;

		// подключаем логирование:
		$log = new Log('addLoan_');
		$strlog .= "TKLenderAPI.sendGoogleAnalytics METHOD: sendGoogleAnalytics " .
				"\ncid (customerId) = " . $customerId .
				"\nti  (loanId) = " . $loanId .
				"\ntr (amount) = " . $amount .
				"\nip (amount) = " . $amount .
				"\nin (term) = " . 'Credit' . $term . 'days' .
				"\nresult Transaction = " . print_r($resTransaction, true) .
				"\nresult Item = " . print_r($resItem, true) ;
				
		$log->write($strlog);

		return true;
	}
	
	/**
	 * Отпраляет email 
	 * @param array $data
	 * @return array
	 */
	public function sendEmail($data) {
	
		$response = [];
		$error = 777; // всё ок
	
		$dateSend = str_replace('T', ' ', date('c'));
		
		$subject = "Сообщение от " . ($data['sender'] ? : '') . ": ". $data['fromName'] . ' email: ' . $data['fromEmail'];
		$message = 'Дата: ' . $dateSend . '<br>Получено от: '. $data['fromName'] . '<br>email: ' . $data['fromEmail'] . '<br>Сообщение: '. $data['message'];
		$to = $data['to'];	// адрес кому
		$email_sender = Mail_Sender::getSender($this->config['email_provider']);
		$res = $email_sender->sendPlain($to, $subject, $message);
	
		$response['response_code'] = $error;
		$response['result'] = $res;
		$response['to'] = $to;
		$response['subject'] = $subject;
		$response['message'] = $message;
	
		// подключаем логирование:
		$log = new Log('email_');
		$strlog = "TKLenderAPI.sendEmail  METHOD: sendEmail "
				. "\nresponse=" . print_r($response, true);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 *  посылает запрос в CRM на отправку письма клиенту для активацию почты
	 * @param string $CustomerAuthToken
	 * @param bool $isRegisteredUser - зарегистрирован ли пользователь
	 * @return mixed
	 */
	public function sendEmailConfirmation(string $CustomerAuthToken, bool $isRegisteredUser = false) {
	    
	    // проверка существования данных:
	    if (!$CustomerAuthToken) {
	        $response = [
	            'Success' => false,
	            // 'Message' => "Нет входных данных",
	            'error' => 100,    // Нет входных данных
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: $CustomerAuthToken",
	    ];
	    
	    $request = [
	        'isRegisteredUser' => $isRegisteredUser,
	    ];
	    
	    // непосредственно отправка:
	    $res = $this->curl($this->url . 'PublicApi/SendEmailConfirmation', $headers, $request, 'POST');
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('email_');
	    $strlog .= "TKLenderAPI.sendEmailConfirmation METHOD: SendEmailConfirmation "
	        . "\nCustomerAuthToken = " . $CustomerAuthToken
	        . "\nurl = " . $this->url . 'PublicApi/SendEmailConfirmation'
            . "\nrequest = " . print_r($request, true)
	        . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
	    $log->write($strlog);
	            
	    return $response;
	}
	
	/**
	 * Отпраляет email менеджеру
	 * @param array $data
	 * @return array
	 */
	public function sendEmailToManager($data) {
		
		// проверка существования данных:
		if (!is_array($data)) {
			$response = [
					'response_code' => 100,
			];
			return $response;
		}
		
		$request = $data;
		$request['to'] = (isset($data['MailPartner'])) ? $this->config['email_manager'] : $this->config['email_support'];	// адрес саппорта
		
		// непосредственно отправка:
		$response = $this->sendEmail( $request);
		
		return $response;
	}
	
	/**
	 * Отпраляет email саппорту
	 * @param array $data
	 * @return array
	 */
	public function sendEmailToSupport($data) {
	
		// проверка существования данных:
		if (!is_array($data)) {
			$response = [
					'response_code' => 100,
			];
			return $response;
		}
	
		$request = $data; 
		$request['to'] = (isset($data['MailReviews'])) ? $this->config['email_reviews'] : $this->config['email_support'];	// адрес саппорта
		
		// непосредственно отправка:
		$response = $this->sendEmail( $request);
		
		return $response;
	}
	
	/**
	 * Передает в CRM список адресов email  для последующей отсылки сообщений клиентам
	 * @param array $emails
	 */
	public function sendМailAddresses($emails) {
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        // "tkLender_CustomerAuthToken: $CustomerAuthToken",
	        ];
	    
	    //	массив данных запроса:
	    $request = [
	        'emails' => $emails,
	    ];
	    
	    // непосредственно отправка:
	    $res = $this->curl($this->url . 'PublicApi/SendEmails', $headers, $request, 'POST');
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('emailOnline_');
	    $strlog .= "TKLenderAPI.sendМailAddresses METHOD: sendМailAddresses "
	        . "\nrequest = " . print_r($request, true)
	        . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
	        $log->write($strlog);
	        
	        return $response;
	}
	
	/**
	 * отправляет в CRM собранную информацию о клиенте
	 * @param $data - для дополнительного инфо
	 * @return array
	 */
	public function sendUserInfo($data = []) {
		
		// вдруг передали инфу в параметре, или уже есть инфо:
		$data = array_merge ((($_SESSION['UserInfo']['Data']) ? : []), $data);
		$_SESSION['UserInfo']['Data'] = $data; 
		
		$sliderData = $_SESSION['UserInfo']['sliderData'];
		
		//$this->request['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
		//$this->request['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		//if ($_SERVER['HTTP_X_FORWARDED_FOR']) $this->request['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR']; 
		//$this->request['SessionId'] = session_id();

		if ($_SESSION ['token']) {
			// получаем Id пользователя:
			// $resCustomer = $this->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
			$resCustomer = [];
			$resCustomer['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
			$resCustomer['Data'] = $_SESSION['api']['client'];
			if ( $resCustomer['Success']) {
				$customerId = $resCustomer['Data']['Id'];
			}
		}
		
		// Откуда пришел пользователь на сайт изначально:
		if ($_COOKIE['SalesDoubler']) {
		    $affiliateName = 'SalesDoubler';
		} elseif ($_COOKIE['PrimeLead']) {
		    $affiliateName = 'PrimeLead';
		} elseif ($_COOKIE['Admitad']) {
		    $affiliateName = 'Admitad';
		} elseif ($_COOKIE['Loangate']) {
		    $affiliateName = 'Loangate';
		} elseif ($_COOKIE['Linkprofit']) {
		    $affiliateName = 'Linkprofit';
		} elseif ($_COOKIE['DoAffiliate']) {
		    $affiliateName = 'DoAffiliate';
		} elseif ($_COOKIE['Leads_su']) {
		    $affiliateName = 'Leads_su';
		} elseif ($_COOKIE['Targetme']) {
		    $affiliateName = 'Targetme';
		} elseif ($_COOKIE['Finline']) {
		    $affiliateName = 'Finline';
		} elseif ($_COOKIE['Satellite'] || $_SESSION['sat_id']) {
		    $sat_id = ($_COOKIE['Satellite']) ? : $_SESSION['sat_id'];
		    $affiliateName = 'Satellite_' . $sat_id;
		} else {
		    $affiliateName = '';
		}
		
		// если еще не отправляли данные:
		if (!$_SESSION['UserInfo']['Posted']) {
			
			// дополнительные параметры:
			$customJsonData = [];
			if ($data['Desktop']) $customJsonData['Desktop'] = $data['Desktop'];
			if ($data['Mobile']) $customJsonData['Mobile'] = $data['Mobile'];
			if ($data['Tablet']) $customJsonData['Tablet'] = $data['Tablet'];
			if ($data['iPad']) $customJsonData['iPad'] = $data['iPad'];
			if ($data['iPhone']) $customJsonData['iPhone'] = $data['iPhone'];
			if ($data['iPod']) $customJsonData['iPod'] = $data['iPod'];
			if ($data['Android_Phone']) $customJsonData['Android_Phone'] = $data['Android_Phone'];
			if ($data['Android_Tablet']) $customJsonData['Android_Tablet'] = $data['Android_Tablet'];
			if ($data['BlackBerry_Phone']) $customJsonData['BlackBerry_Phone'] = $data['BlackBerry_Phone'];
			if ($data['BlackBerry_Tablet']) $customJsonData['BlackBerry_Tablet'] = $data['BlackBerry_Tablet'];
			if ($data['Windows_Phone']) $customJsonData['Windows_Phone'] = $data['Windows_Phone'];
			if ($data['Windows_Tablet']) $customJsonData['Windows_Tablet'] = $data['Windows_Tablet'];
			if ($data['Firefox_OS']) $customJsonData['Firefox_OS'] = $data['Firefox_OS'];
			if ($data['Firefox_OS_Phone']) $customJsonData['Firefox_OS_Phone'] = $data['Firefox_OS_Phone'];
			if ($data['Firefox_OS_Tablet']) $customJsonData['Firefox_OS_Tablet'] = $data['Firefox_OS_Tablet'];
			if ($data['os_cpu']) $customJsonData['os_cpu'] = $data['os_cpu'];
			if ($data['navi_vendorSub']) $customJsonData['navi_vendorSub'] = $data['navi_vendorSub'];
			if ($data['navi_productSub']) $customJsonData['navi_productSub'] = $data['navi_productSub'];
			if ($data['navi_buildID']) $customJsonData['navi_buildID'] = $data['navi_buildID'];
			if ($data['appCodeName']) $customJsonData['appCodeName'] = $data['appCodeName'];
			if ($data['appName']) $customJsonData['appName'] = $data['appName'];
			if ($data['appVersion']) $customJsonData['appVersion'] = $data['appVersion'];
			if ($data['user_date']) $customJsonData['user_date'] = $data['user_date'];
			if ($data['user_timeZone']) $customJsonData['user_timeZone'] = $data['user_timeZone'];
			if ($data['user_country']) $customJsonData['user_country'] = $data['user_country'];
			if ($data['user_area']) $customJsonData['user_area'] = $data['user_area'];
			if ($data['user_city']) $customJsonData['user_city'] = $data['user_city'];
			
			if ($data['fingerprint']) $customJsonData['fingerprint'] = $data['fingerprint'];
			if ($data['fingerprintDevice']) $customJsonData['fingerprintDevice'] = $data['fingerprintDevice'];
			if ($data['fingerprintComponents']) $customJsonData['fingerprintComponents'] = $data['fingerprintComponents'];
			
			// Id пользователя в CRM:
			if ($customerId) $customJsonData['customer_id'] = $customerId;
			
			// проверка на наличие старого формата в массиве страниц:
			if ($data['Pages']) {
				foreach ($data['Pages']as $key => $value) {
					if (is_string($value)) {
						$page = ['Page' => $value, 'PageDate' => null];
						$data['Pages'][$key] = $page;
					}
				}
			}
			
			if ($data['Pages']) $customJsonData['Pages'] = $data['Pages'];
			if ($data['DateClosed']) $customJsonData['DateClosed'] = $data['DateClosed'];
			if ($data['DateUpdated']) $customJsonData['DateUpdated'] = $data['DateUpdated'];
			if ($sliderData) $customJsonData['sliderData'] = $this->updateSliderData($sliderData);	// меняем структуру данных
			// запись Cookie:
			$myCookie = new Cookies();
			if ($myCookie->getBody() !== []) $customJsonData['myUsers'] = $myCookie->getBody();
			if ($myCookie->getLoans() !== []) $customJsonData['myLoans'] = $myCookie->getLoans();
			if ($myCookie->getCookieId()) $customJsonData['myCookieId'] = $myCookie->getCookieId();
			
			// Откуда пришел пользователь на сайт:
			if (isset($_SESSION['referer']['url'])) $customJsonData['myReferer'] = $_SESSION['referer']['url'];
			
			if ($affiliateName) $customJsonData['MyCPA'] = $affiliateName;
			
			// платформа:
			if ($data['iOS']) { $platform = 'iOS';} 
			elseif ($data['Android']) {$platform = 'Android';}
			elseif ($data['BlackBerry']) {$platform = 'BlackBerry';}
			elseif ($data['Windows']) {$platform = 'Windows';}
			elseif ($data['Firefox_OS']) {$platform = 'Firefox_OS';}
			
			// браузер:
			$browserInfo = [];
			if ($data['navi_userAgent']) $browserInfo['UserAgent'] = $data['navi_userAgent'];
			if ($data['user_language']) $browserInfo['Language'] = $data['user_language'];
			if ($platform) $browserInfo['Platform'] = $platform;
			if ($data['navi_vendor']) $browserInfo['Vendor'] = $data['navi_vendor'];
			
			// система
			$systemInfo = [];
			if ($data['user_dateTime']) {
				$userDate = DateTime::createFromFormat('Y-n-j G:i:s P', $data['user_dateTime']);
				if ($userDate) {
					$systemInfo['UserTime'] = $userDate->format('c');
				}
			}
			if ($data['screen_width']) $systemInfo['ScreenWidth'] = $data['screen_width'];
			if ($data['screen_height']) $systemInfo['ScreenHeight'] = $data['screen_height'];
			
			// заполняем требуемую структуру запроса:
			$request = [];
			$request['Id'] = session_id();
			
			if ($_SERVER['REMOTE_ADDR']) {
				$request['ClientIP'] = $_SERVER['REMOTE_ADDR'];
				// добавляем данные MaxMind:
				/*
				$maxMind = new MaxMind();
				$dataMaxMind = $maxMind->getLocation($request['ClientIP']);
				$customJsonData['MaxMind'] = $dataMaxMind;
				*/
			}
			if ($_SERVER['HTTP_X_FORWARDED_FOR']) $request['ForwardedFromIP'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	
			if ($customJsonData !== [] ) $request['CustomJsonData'] = $customJsonData;
			if ($browserInfo !== [] ) $request['BrowserInfo'] = $browserInfo;
			if ($systemInfo !== [] ) $request['SystemInfo'] = $systemInfo;
			
			$urlSub = 'PublicApi/ClientSessions/CreateSession/';
			$type = 'POST';
		
		} else {
			
			$customJsonData = [];
			if ($data['Pages']) $customJsonData['Pages'] = $data['Pages'];
			if ($data['DateClosed']) $customJsonData['DateClosed'] = $data['DateClosed'];
			if ($data['DateUpdated']) $customJsonData['DateUpdated'] = $data['DateUpdated'];
			if ($sliderData) $customJsonData['sliderData'] = $this->updateSliderData($sliderData);	// меняем структуру данных
			// Id пользователя в CRM:
			if ($customerId) $customJsonData['customer_id'] = $customerId;
			// запись Cookie:
			$myCookie = new Cookies();
			if ($myCookie->getBody() !== []) $customJsonData['myUsers'] = $myCookie->getBody();
			if ($myCookie->getLoans() !== []) $customJsonData['myLoans'] = $myCookie->getLoans();
			if ($myCookie->getCookieId()) $customJsonData['myCookieId'] = $myCookie->getCookieId();
				
			// Откуда пришел пользователь на сайт:
			if (isset($_SESSION['referer']['url'])) $customJsonData['myReferer'] = $_SESSION['referer']['url'];
			
			if ($affiliateName) $customJsonData['MyCPA'] = $affiliateName;
			
			// заполняем требуемую структуру запроса:
			$request = [];
			$request['sessionId'] = session_id();
			//$request['customerActivityData'] = json_encode($customJsonData, JSON_UNESCAPED_SLASHES);	// Не экранировать "/"
			if ($customJsonData !== [] ) $request['customerActivityData'] = $customJsonData;
					
			$urlSub = 'PublicApi/ClientSessions/LogCustomerActivity/';
			$type = 'PUT';
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				//"tkLender_CustomerAuthToken: $CustomerAuthToken",
				"Content-Type: application/json",
		];
		
		// удаляем спецсимволы:
		//$request = $this->preprocessing($request);
		
		$request = json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	// Не экранировать "/", Не кодировать многобайтные символы Unicode
				
		// непосредственно отправка:
		//$res = $this->curl($this->url . $urlSub, $headers, $request, $type);
		try {
			$res = $this->curl($this->url . $urlSub, $headers, $request, $type);
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
		
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('info_');
		$strlog .= "TKLenderAPI.sendUserInfo  METHOD: sendUserInfo " . $urlSub .
				"\nsession_id = " . session_id() .
				//"\n\nPages = " . print_r($data['Pages'], true) .
				//"\njsonPages = " . json_encode($data['Pages']) .
				//"\njsonPagesSlash = " . json_encode($data['Pages'], JSON_UNESCAPED_SLASHES) .
				//"\ndata=" . print_r($data, true).
				//"\nsliderData=" . print_r($sliderData, true).
				//"\njson request=" . print_r($request, true).
				"\nrequest = " . print_r(json_decode($request, true), true).
				"\nrequestJson = " . $request.
				"\nerror = " . print_r($error, true).
				//"\nres = " . print_r($res, true).
				//"\nresponse = " . print_r($response, true);
				"\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		if ($response['Success']) { 
			
			unset($_SESSION['UserInfo']['Data']['Pages']);
			unset($_SESSION['UserInfo']['sliderData']);
			unset($_SESSION['UserInfo']['DateUpdated']);

			$_SESSION['UserInfo']['Posted'] = true;
				
			// выясняем, передавать ли инфо о закрытии сессии:
			if ($data['DateClosed']) {
				// куда передавать данные:
				//$urlSub = 'PublicApi/ClientSessions/EndSession/?sessionId=' . session_id();
				$urlSub = 'PublicApi/ClientSessions/EndSession/';
				
				$request = [];
				$request['sessionId'] = session_id();
				$request = json_encode($request);
				
				// непосредственно отправка инфо о закрытии:
				$resEnd = $this->curl($this->url . $urlSub, $headers, $request, "PUT");
				$responseEnd = json_decode($resEnd, true);
			
				unset($_SESSION['UserInfo']);
				
				// подключаем логирование:
				$strlog = "TKLenderAPI.sendUserInfo  METHOD: sendEndSession " . $urlSub .
						"\nsession_id = " . session_id() .
						//"\nresponse=" . print_r($responseEnd, true);
						"\nresponse = " . ((isJSON($resEnd)) ? print_r($responseEnd, true) : $resEnd);
				$log->write($strlog);
			}
			
		} elseif ($response['Message'] == 'Session already exists') {
			$_SESSION['UserInfo']['Posted'] = true;
		}

		return $response;
	}
	
	/**
	 * проверка работоспособности CRM
	 * @param integer $timeout - таймаут в секундах
	 * @return boolean
	 */
	function testCRM(int $timeout = self::TIMEOUT_DEFAULT, bool $checkDB = self::NOT_CHECK_CRM_DB) {
	    
	    // массив заголовков:
	    $headers = ["tkLender_ApiKey: {$this->key_api}"];
	    
	    $request = [
	    ];
	    
	    // непосредственно отправка:
	    $url = $this->url . (($checkDB) ?  'PublicApi/IsAlive' : 'IsAlive');
	    // $url = $this->url . (($checkDB) ?  'PublicApi/GetCreditProducts' : 'IsAlive');
	    // $res = $this->curl($this->url . 'PublicApi/GetCreditProducts', $headers, $request, 'GET', $timeout);
	    $res = $this->curl($url, $headers, $request, 'GET', $timeout, self::NOT_CHECK_CRM_WORK);
	    $response = json_decode($res, true);
	    
	    // временно для 508:
// 	    if ($checkDB) {
// 	        if ($response['Success']) {
// 	            return true;
// 	            // return false;
// 	        } else {
// 	            return false;
// 	        }
// 	    } else {
// 	        if ($response['message'] === 'success') {
// 	            return true;
// 	            // return false;
// 	        } else {
// 	            return false;
// 	        }
// 	    }
	    // конец временно для 508
	    
	    
	    
	    if ($response['message'] === 'success') {
	        return true;
	        // return false;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * запрашивает список платежных карт залогиненного пользователя в API CRM (Tranzzo)
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function tranzzoGetCardsAll($CustomerAuthToken) {
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
				];
		
		// массив данных запроса:
		$request = [
		];
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/GetAllCards', $headers, $request, "GET");
		
		$res = json_decode($res, true);
		
		if ($res['Success']) {
			$response = $res['Data'];
		} else {
			$response = [];
		}
		
		// подключаем логирование:
		$log = new Log('getCard_');
		$strlog = "METHOD: getCards (TKLenderAPI.tranzooGetCardsAll) " .
				//"\nres=" . print_r($res, true) .
		"\nresponse=" . print_r($response, true);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * делает дополнительную карту основной
	 * @param string $card
	 * @return boolean[]|string[]|number[]|mixed
	 */
	public function tranzzoMakeCardMain($card) {
	    
	    // проверка существования данных: 516800******4960
	    if (!$card || !preg_match('/^\d{6}\*{6}\d{4}$/', $card) || !$_SESSION ['token']) {
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	            'error' => 100,
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$_SESSION ['token']}",
	        ];
	    
	    $request = [
	        'card' => $card,
	    ];
	    
	    // непосредственно отправка:
	    $res = $this->curl($this->url . 'PublicApi/MakeCardMain', $headers, $request);
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('cardReg_');
	    $strlog .= "TKLenderAPI.tranzzoMakeCardMain METHOD: MakeCardMain"
	        . "\nrequest = " . print_r($request, true)
	        . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
	    $log->write($strlog);
	        
	    return $response;
	}
	
	/**
	 * отправляет данные в CRM для оплаты другой картой
	 * @param array $cardDetails
	 * @return array
	 */
	public function tranzzoPayAnotherCard($cardDetails) {
	    
	    // проверка существования данных:
	    if ((!is_array($cardDetails)) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) == 0 )) {
	        
	        $response = [
	            'Success' => false,
	            'Message' => "Нет входных данных",
	            'error' => 100,
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = [
	        "tkLender_ApiKey: {$this->key_api}",
	        "tkLender_CustomerAuthToken: {$_SESSION ['token']}",
	        ];
	    
	    $request = [
	        'Card' => (string) $cardDetails['cardNumber'],
	        'CardDateMonth' => (int) $cardDetails['cardDateMonth'],	// месяц годности
	        'CardDateYear' => (int) $cardDetails['cardDateYear'],		// год годности
	        'Cvv' => $cardDetails['cardCvv2'],
	        'Amount' => $cardDetails['amount'],
	        'BackUrl' => $cardDetails['backUrl'],
	    ];
	    
	    // удаляем спецсимволы:
	    $request = $this->preprocessing($request);
	    $requestJson = json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	    
	    // непосредственно отправка:
	    $res = $this->curl($this->url . 'PublicApi/MakePaymentAnOtherCard', $headers, $requestJson);
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $request['Cvv'] = '***';
	    
	    $log = new Log('tranzzo_');
	    $strlog .= "TKLenderAPI.tranzzoPayAnotherCard  METHOD: MakePaymentAnOtherCard "
	        . "\nrequest = " . print_r($request, true)
	        // . "\nrequestJson = " . print_r($requestJson, true)
	    . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
	    $log->write($strlog);
	    
	    return $response;
	}
	
	/**
	 * отправляет данные для проплаты в CRM через Tranzzo
	 * @param array $data
	 * @return array
	 */
	public function tranzzoPayCredit($data) {
		
		// проверка существования данных:
		if ((!is_array($data)) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) == 0 )) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [
				'ProtectedCardNumber' => $_SESSION['card']['cards'][(int) $data['card']]['Card'],
				'Amount' => $data['amount'],
				'PaymentURL' => $data['returnUrl'],
		];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		$requestJson = json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/MakePaymentByCard', $headers, $requestJson);
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('tranzzo_');
		$strlog .= "TKLenderAPI.tranzzoPayCredit  METHOD: MakePaymentByCard "
			. "\nrequest = " . print_r($request, true)
			 . "\nrequestJson = " . print_r($requestJson, true)
			// . "\ntoken={$_SESSION ['token']}"
			. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * отправляет данные по карте в CRM для верификации
	 * @param array $cardDetails
	 * @return array
	 */
	public function tranzzoSendCardDetails($cardDetails) {
		
		// проверка существования данных:
		if ((!is_array($cardDetails)) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) == 0 )) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [
				'Card' => (string) $cardDetails['cardNumber'],
				// 'CardDateMonth' => (int) substr($cardDetails['cardTime'], 0, 2),	// месяц годности
				// 'CardDateYear' => (int) substr($cardDetails['cardTime'], 3, 2),	// год годности
		        'CardDateMonth' => (int) $cardDetails['cardDateMonth'],       // месяц годности
		        'CardDateYear' => (int) $cardDetails['cardDateYear'],         // год годности
    		    'Cvv' => $cardDetails['cardCvv2'],
				'BackUrl' => $cardDetails['backUrl'],
		];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		$requestJson = json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/SetCardDetails', $headers, $requestJson);
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$request['Cvv'] = '***';
		
		$log = new Log('cardReg_');
		$strlog .= "TKLenderAPI.tranzooSendCardDetails  METHOD: setCardDetails "
				. "\nrequest = " . print_r($request, true)
				// . "\nrequestJson = " . print_r($requestJson, true)
		. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
		$log->write($strlog);
		
		return $response;
	}
	
	/**
	 * посылает в CRM код проверки верификации карты (Not3Ds)
	 * @param string $cardNumber
	 * @param string $sendCode
	 * @return array
	 */
	function tranzzoSendPayCode($cardNumber = '', $sendCode = '') {
		
		// проверка существования данных:
		if ((strlen($cardNumber) == 0) || (strlen($sendCode) == 0) || (!isset($_SESSION ['token'])) || (strlen($_SESSION ['token']) < 10)) {
			
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
			return $response;
		}
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: {$_SESSION ['token']}",
				];
		
		$request = [
				'card' => $cardNumber,
				'cod' => $sendCode,
		];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		//$requestJson = json_encode($request);
		
		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/CheckTranzzoLookUp', $headers, $request);
		$response = json_decode($res, true);
		
		// подключаем логирование:
		$log = new Log('cardReg_');
		$strlog .= "TKLenderAPI.tranzzoSendPayCode  METHOD: CheckTranzzoLookUp "
				. "\nrequest = " .  print_r($request, true)
				. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
				$log->write($strlog);
				
				return $response;
	}
	
	/**
	 * изменяет информацию о залогиненном пользователе
	 * @param array $data
	 * @param string $CustomerAuthToken
	 * @return array
	 */
	public function updateCustomerDetails($data, $CustomerAuthToken = '') {
	
		// проверка существования данных:
		if ((!is_array($data)) || (strlen($CustomerAuthToken) == 0 )) {
	
			$response = [
					'Success' => false,
					'Message' => "Нет входных данных",
					'error' => 100,
			];
				
			return $response;
		}
	
		$request = $data;
		
		// корректируем массив по надобности:
		
		// устанавливаем дату/время изменения:
		$dt = new DateTime();
		$jsonDate = $dt->format('c');
		$request['CreationDate'] = $jsonDate;
		
		if ($data['BirthDate']) {
			$timestamp = strtotime($data['BirthDate']);
			$dt = new DateTime();
			$dt->setTimestamp($timestamp);
			$jsonDate = $dt->format('c');
			$request['BirthDate'] = $jsonDate;
		}
		if ($data['PassportRegistration']) {
			$timestamp = strtotime($data['PassportRegistration']);
			$dt = new DateTime();
			$dt->setTimestamp($timestamp);
			$jsonDate = $dt->format('c');
			$request['PassportRegistration'] = $jsonDate;
		}
		if (isset($data['IsFirstEducation'])) $request['IsFirstEducation'] = ($data['IsFirstEducation'] == '1') ? 'true' : 'false';  
		if (isset($data['IsBudget'])) $request['IsBudget'] = ($data['IsBudget'] == '1') ? 'true' : 'false';
		if ($data['BeginLearn']) {
			$timestamp = strtotime($data['BeginLearn']);
			$dt = new DateTime();
			$dt->setTimestamp($timestamp);
			$jsonDate = $dt->format('c');
			$request['BeginLearn'] = $jsonDate;
		}
		if (isset($data['IsBudget'])) $request['IsBudget'] = ($data['IsBudget'] == '1') ? 'true' : 'false';
		
		if ($data['AdditionalJsonData']) 
			$request['AdditionalJsonData'] = json_encode($data['AdditionalJsonData'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);	// Дополнительная информация

		if ($data['AdditionalData']) {
			$arrAdditionalData = [];
			foreach ($data['AdditionalData'] as $key => $value) {
				$arrAdditionalData[] = ['Key' => $key, 'Value' => $value,];
			}
			$request['AdditionalData'] = $arrAdditionalData;
		}

		if (!isset($data['Occupation']) || ($data['Occupation'] == '0')) $request['Occupation'] = 5;	// обязательное поле, приходится временно поставить 5
		
		// ставим некоторые поля принудительно, для CRM:
		$request['Citizenship'] = '1';	// гражданин Украины
		$request['Gender'] = '2';	// пол женский
		$request['CarOwner'] = '1';	// есть автомобиль
		$request['Bank']['RoutingNumber'] = '351834';	// МФО
		$request['Bank']['BankName'] = 'Невидимый банк';	// название банка
		if (!$request['Bank']['AccountNumber']) $request['Bank']['AccountNumber'] = '26201111111111111111';	// счет в банке
		$request['PreviousExperience'] = 100;	// общий стаж
		
		// массив заголовков:
		$headers = [
				"tkLender_ApiKey: {$this->key_api}",
				"tkLender_CustomerAuthToken: $CustomerAuthToken",
		];
		
		// удаляем спецсимволы:
		$request = $this->preprocessing($request);
		$requestToSession = $request;
		
		$request = json_encode($request);

		// непосредственно отправка:
		$res = $this->curl($this->url . 'PublicApi/UpdateCustomerDetails', $headers, $request);
		$response = json_decode($res, true);
	
		if ($response['Success']) {
			$_SESSION['api']['client'] = $requestToSession;	// обновляем данные клиента в сессии
		}
		
		// подключаем логирование:
		$log = new Log('updUser_');
		$strlog .= "TKLenderAPI.updateCustomerDetails  METHOD: updateCustomerDetails " 
			. "\nrequest=" . print_r(json_decode($request, true), true) 
			. "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res)
			. "\nREMOTE_ADDR=" .	$_SERVER['REMOTE_ADDR'];
		$log->write($strlog);

		return $response;
	}
	
	/**
	 * посылает запрос в CRM на активацию почты по заданному коду
	 * @param string $verificationCode
	 * @return array
	 */
	public function verifyCustomerEmail(string $verificationCode) {
	    
	    // проверка существования данных:
	    if (!$verificationCode) {
	        $response = [
	            'Success' => false,
	            // 'Message' => "Нет входных данных",
	            'error' => 100,    // Нет входных данных
	        ];
	        return $response;
	    }
	    
	    // массив заголовков:
	    $headers = ["tkLender_ApiKey: {$this->key_api}"];
	    
	    $request = [
	        'verificationCode' => $verificationCode,
	    ];
	    
	    // непосредственно отправка:
	    $res = $this->curl($this->url . 'PublicApi/VerifyCustomerEmail', $headers, $request, 'POST');
	    $response = json_decode($res, true);
	    
	    // подключаем логирование:
	    $log = new Log('email_');
	    $strlog .= "TKLenderAPI.VerifyCustomerEmail METHOD: VerifyCustomerEmail "
	        . "\nverificationCode = " . $verificationCode
	        . "\nurl = " . $this->url . 'PublicApi/VerifyCustomerEmail'
            . "\nrequest = " . print_r($request, true)
            . "\nresponse = " . ((isJSON($res)) ? print_r($response, true) : $res);
        $log->write($strlog);
	            
	            return $response;
	}
	
}