<?php

$websiteActive = true;	// активный сайт
// $websiteActive = false;	// неактивный сайт

$modx->setPlaceholder ( 'hash', '20180809' );	// для хеша скриптов

$config = parse_ini_file(MODX_BASE_PATH. '/DesignAPI/config.ini');
$status = parse_ini_file(MODX_BASE_PATH. '/DesignAPI/status.ini');
$config = (is_array($status)) ? array_merge($config, $status) : $config;

$https = ($config['https'] == 1) ? true : false;

//if (!$websiteActive || $config['websiteActive'] != 1) {
//	echo "<h1 style='font-size: 30px;padding-top: 100px; text-align: center;'>Извините, сайт находится на профилактике. Будет доступен в ближайшее время.</h1>";die;
//}

require MODX_BASE_PATH . '/DesignAPI/promocodes.php';
require_once MODX_BASE_PATH . '/DesignAPI/classes/bonuses.php';
//require_once MODX_BASE_PATH . '/DesignAPI/promocodes.php';
require_once MODX_BASE_PATH . '/DesignAPI/classes/Log.php';
require_once MODX_BASE_PATH . '/DesignAPI/classes/captcha/Captcha.php';
require_once MODX_BASE_PATH . '/DesignAPI/classes/ReCaptcha/ReCaptcha.php';
require_once MODX_BASE_PATH . '/DesignAPI/classes/TKLenderAPI.php';

$api = new TKLenderAPI ();
switch ($modx->event->name) {
	
/*      // тест
    case "OnWebPagePrerender":
        // подключаем логирование:
        $log = new Log ( 'aaa_' );
        $strlog = "METHOD: test documentOutput " . $modx->documentOutput;
        $log->write ( $strlog );
        break;
    // тест
 */
    
    case "OnSiteSettingsRender" :
		$modx->event->output ( $modx->mergeSettingsContent ( $modx->getChunk ( "settings" ) ) );
		break;
		
	case "OnWebPageInit" : // выполняется перед загрузкой данных о странице раньше всех других событий единоразово
		
	    // считываем общие данные:
		if ($_SESSION ['token']) {
			
		    // getCustomerDetails:
		    if ((!$_SESSION['api']['client']) || ($_SESSION['api']['client'] === [])) {
		        $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
		        if ($res ['Success']) {
		            $_SESSION['api']['client'] = $res['Data'];
		        } else {
		            $_SESSION['api']['client'] = [];
		        }
		    }
		    
 		    // Нотификации notifyGetList:
		    if ((!isset($_SESSION['notify'])) || (in_array($modx->documentIdentifier, [4, 5, 22, 26,]))) {
		        $res = $api->notifyGetList ( $_SESSION ['token'] ); // прочитать данные пользователя
		        
		        // Для теста:
		        // dd($res);
		        // die;
		        // $res = $api->notifyShowLater ( $_SESSION ['token'], 12, true ); // прочитать данные пользователя
		        // dd($res);
		        // $res = $api->notifyShown ( $_SESSION ['token'], 12 ); // прочитать данные пользователя
		        // dd($res);
		        // $res = $api->notifyShowLater ( $_SESSION ['token'], 12, false ); // прочитать данные пользователя
		        // dd($res);
		        // die;
		        
		        if ($res ['Success']) {
		            $_SESSION['notify'] = $res['Data'];
		        } else {
		            $_SESSION['notify'] = [];
		        }
		    }
		    
		    // GetVeryficationSystem:
			if (!$_SESSION['api']['paySystem']) {
				$res = $api->GetVeryficationSystem( $_SESSION ['token'] ); // прочитать данные платежной системы
				if ($res ['Success']) {
					$_SESSION['api']['paySystem'] = $res['Data']['systemName'];
				}
			}
			$modx->setPlaceholder ( 'paySystem', $_SESSION['api']['paySystem']);

			// getCustomerLoans:
			$res = $api->getCustomerLoans ( $_SESSION ['token'] );
			if ($res ['Success']) {
				// заполняем массив api данными о кредите:
				$_SESSION['api']['credits'] = $res['Data'];
			} else {
			    $_SESSION['api']['credits'] = (isset($_SESSION['api']['credits'])) ? $_SESSION['api']['credits'] : [];
			}
			
			// getCreditProducts:
			$promoCode = ($_SESSION['PromoCode']) ? : (($_SESSION['hiddenPromoCode']) ? : null);
			// получаем кредитный продукт:
			// $res = $api->getCreditProducts($promoCode, $_SESSION ['token']); // получаем список продуктов
			// временно каждый раз:
			// if(false) {
			if(($_SESSION['api']['creditProducts']['Promocode'] === $promoCode) && ($_SESSION['api']['creditProducts']['isToken'] == true)
					&& (!in_array($modx->documentIdentifier, [4, 40, 330, 331,]))) {
				$res = $_SESSION['api']['creditProducts'];
			} else {
				$res = $api->getCreditProducts($promoCode, $_SESSION ['token']); // получаем список продуктов
				if ($res ['Success']) {
					$_SESSION['api']['creditProducts'] = $res;
					$_SESSION['api']['creditProducts']['Promocode'] = $promoCode;
					$_SESSION['api']['creditProducts']['isToken'] = true;
				} else {
				    $_SESSION['api']['creditProducts'] = (isset($_SESSION['api']['creditProducts'])) ? $_SESSION['api']['creditProducts'] : [];
				}
			}
			
			// getCards:
			if (($_SESSION['api']['paySystem'] === 'WayForPay') && ((!$_SESSION['api']['cards']) || (in_array($modx->documentIdentifier, [22, 87, 157, 321, 332,])))) {
				$res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
				// $_SESSION['api']['cards'] = $res;
				$_SESSION['api']['cards'] = (($res === []) && (isset($_SESSION['api']['cards']))) ? $_SESSION['api']['cards'] : $res;
                                $_SESSION['card']['cards'] = $_SESSION['api']['cards'];
			}
			
			// tranzzoGetCardsAll:
			if (($_SESSION['api']['paySystem'] === 'Tranzzo') && ((!$_SESSION['api']['tranzzoCards']) || (in_array($modx->documentIdentifier, [26, 87, 181, 335, 332,])))) {
				$res = $api->tranzzoGetCardsAll( $_SESSION ['token'] ); // получить список карт клиента
				if (count($res) > 0) {
                    // проверяем, есть ли "главная" карта:
				    $flagIsMain = false;
				    foreach ($res as $key => $card) {
				        if ($card['IsMain'] == true) {
				            $flagIsMain = true;
				            break;
				        }
                    }
                    if (!$flagIsMain)
				        $res[count($res)-1]['IsMain'] = true;   // делаем последнюю карту "главной"
				}
				// $_SESSION['api']['tranzzoCards'] = $res;
				$_SESSION['api']['tranzzoCards'] = (($res === []) && (isset($_SESSION['api']['tranzzoCards']))) ? $_SESSION['api']['tranzzoCards'] : $res;
                                $_SESSION['card']['cards'] = $_SESSION['api']['tranzzoCards'];
			}

		} else {
			
			$promoCode = ($_SESSION['PromoCode']) ? : (($_SESSION['hiddenPromoCode']) ? : null);
			$filename = MODX_BASE_PATH . 'DesignAPI/tmp/getCreditProducts.tmp';
			// Чтение:
			if (file_exists($filename)) {
				$data = file_get_contents($filename);
				$res = json_decode($data, true);
				// $res = unserialize($data);
			} else {
				$res = $api->getCreditProducts($promoCode, $_SESSION ['token']); // получаем список продуктов
			}
			if ($res ['Success']) {
				$_SESSION['api']['creditProducts'] = $res;
				$_SESSION['api']['creditProducts']['Promocode'] = $promoCode;
				$_SESSION['api']['creditProducts']['isToken'] = false;
			} else {
				// $_SESSION['api']['creditProducts'] = [];
				$_SESSION['api']['creditProducts'] = (isset($_SESSION['api']['creditProducts'])) ? $_SESSION['api']['creditProducts'] : [];
			}
			
		}
		
		if (!$websiteActive || $config['websiteActive'] != 1) 
			break;
		
		$res = $api->getInitCRM ();
		if (! isset ( $res ['error'] )) {
			$_SESSION ['initCRM'] = $res;
		}
		
		if ($_GET ['a'] == 'test')
			header ( "Location: " . $modx->config ['lang'] . $modx->makeUrl ( 2 ) );
			
			if(isset($_SESSION['res']['error']) && in_array($_SESSION['res']['error'], [8, 9])) {	// csrf отсутствует, или неверный
			break;
		}
		
		// Мои карты. Ввод карты:
		if (isset($_POST ['addCard']) && (0 < count ( $_POST ['addCard'] ))) {
			
			// добавление новой карты:
			/*
			 * if (isset($_POST['addCard']['card-number'])) {
			 *
			 * $number = $_POST['addCard']['card-number'];
			 * $number = str_replace ( "-", "", $number );
			 * $number = str_replace ( " ", "", $number );
			 *
			 * if ((strlen($number) == 16) && preg_match("/\d{16}/", $number)) {
			 * $res = $api->getCards(); // получить список всех карт клиента
			 *
			 * if (isset($res['error'])) {
			 * $res = $api->addCard($number); // добавить карту клиента
			 * $_SESSION['card'] = $res;
			 * } else {
			 * $flagExist = false;
			 * foreach ($res['cards'] as $key => $value) {
			 * if ($value['number'] === $number) {
			 * $flagExist = true;
			 * break;
			 * }
			 * }
			 * if (!$flagExist) {
			 * $res = $api->addCard($number); // добавить карту клиента
			 * $_SESSION['card'] = $res;
			 * } else {
			 * $_SESSION['card']['error'] = 161; // Такая карта уже зарегистрирована
			 * }
			 * }
			 * } else {
			 * $_SESSION['card']['error'] = 162; // Карта введена некорректно
			 * }
			 * }
			 */

			// если нажали кнопку "Далее" после верификации:
			if (isset ( $_POST ['addCard'] ['verifyOK'] ) && ($_POST ['addCard'] ['verifyOK'] !== '')) {
				// отправляем данные партнеру о создании заявки:
				if ($_POST ['addCard'] ['loanId']) {
					$resAffiliate = Affiliate::send($_POST ['addCard'] ['loanId'], 'verify');
				}
				if ($_SESSION['sat_id']) {
					header ( "Location: " . $modx->makeUrl ( 333 ) );
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl ( 331 ) );
				} else {
					header ( "Location: " . $modx->makeUrl ( 4 ) );
				}
				die;
			}
					
			// верификация карты:
			if (isset ( $_POST ['addCard'] ['verify-id'] ) && ($_POST ['addCard'] ['verify-id'] !== '')) {

				if (! $_SESSION ['token']) {
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				} else {
					
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					
					if ($res ['Success']) {
						$phone = $res ['Data'] ['Phone'];
						$email = $res ['Data'] ['Email'];
					} else {
						$phone = '';
						$email = '';
					}
					
					$data = [ 
							'id' => ( int ) $_POST ['addCard'] ['verify-id'],
							'phone' => $phone,
							'email' => $email,
							'lang' => $modx->config ['lang'] 
					];
					// куда возвращаться после верификации:
					//$returnUrl = ($_POST['addCard']['isRegistration']) ? 'registraciya-karty' : 'lichnyj-kabinet/pay_data';
					if ($_POST['addCard']['isRegistration']) {
						if ($_SESSION['sat_id']) {
							$returnUrl = 'satellites/registraciya-karty';
						} elseif ($_SESSION['mob_id']) {
							$returnUrl = "mob/registraciya-karty/";
						} else {
							$returnUrl = 'registraciya-karty';
						}
					} else {
						if ($_SESSION['sat_id']) {
							$returnUrl = 'satellites/registraciya-karty';
						} elseif ($_SESSION['mob_id']) {
							$returnUrl = 'mob/registraciya-karty';
						} else {
							$returnUrl = 'lichnyj-kabinet/pay_data';
						}
					}
					$res = $api->checkCard ( $data, $returnUrl ); // проверить карту клиента
					
					$_SESSION ['card'] = $res;
					
					// если есть виджет, запускаем, иначе пробуем открыть страницу:
					if ($res ['widget'] && ($config['pay_widget'] == 1) && ( !$_SESSION['sat_id'] ) && ( !$_SESSION['mob_id'] )) {
						$js .= $res ['widget'];
					} elseif ($res ['form']) {
						echo $res ['form'];
						die ();
					}
				}
			}
			
			break;
		}
		
		// добавить кредит:
		if (isset($_POST['addCredit']) && (0 < count ( $_POST ['addCredit'] ))) { // пришли данные с формы оформления кредита
			$post = $_POST ['addCredit'];
			
			if (! $_SESSION ['token']) {

				if ($_SESSION['sat_id']) {
					// header ( "Location: " . $modx->makeUrl ( 333 ) );
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl ( 323) );
				} else {
					header ( "Location: " . $modx->makeUrl ( 1 ) );
				}
				die;
				
			} else {
				
				// проверку промокода перенесли в CRM. Ошибка будет возвращаться в методе applyforLoan
				/*
				 * // проверка существования введенного промокода:
				 * if ($post['PromoCode']) {
				 * $res = $api->checkPromoCode($post['PromoCode']);
				 * if (!$res['Success']) {
				 * $_SESSION['loan'] = $res; //
				 * break;
				 * }
				 * }
				 */

				// если при заказе кредита установлена галочка "согласен":
				if ($post ['isAgreedUseMyData'] && isset($_SESSION ['client']['Data'])) {
						
					$data = $_SESSION ['client']['Data'];
					$data['IsAgreedUseMyData'] = ($post ['isAgreedUseMyData'] == 'on') ? 1 : 0;
					
					$res = $api->updateCustomerDetails ( $data, $_SESSION ['token'] ); // изменить информацию о пользователе
					
					if (!$res ['Success']) {
						break;					
					}
				}

				// отправляем заявку на кредит
				if ($post ['days'] && $post ['amount']) {
					
					// проверка возможности сделать новую заявку:
					if ( !$api->checkToAddLoan ( $_SESSION ['token'] )) {
						$_SESSION ['loan'] = [ 
								'error' => 194, 
						]; // У Вас уже существует заявка, или активный кредит
						break;
					}

					// $promoCode = ($post ['PromoCode']) ? : null;
					$promoCode = ($post ['PromoCode']) ? : (($_SESSION['hiddenPromoCode']) ? : null);
					$res = $api->getCreditProducts ($promoCode, $_SESSION ['token']); // получаем список продуктов
					if ($res ['Success']) {
						
						// проверка заполнения данных пользователя:
						$resCheck = $api->checkCompleteUserData($_SESSION ['token']);
						if ($resCheck ['Success']) {
						
							//обработка дочерних продуктов: 
							if ($res['Data']['ChildProducts']) {
								$resChild = getChildProduct($post ['amount'], $post ['days'], $res['Data']);
								if ($resChild ['Success']) {
									$res = $resChild; // заменяем на дочерний
								}
							}
							
							$lang = $modx->config ['lang'];
							$culture = ($lang == 'ua') ? 'uk-UA' : 'ru-RU';
							unset ( $_SESSION ['loan'] );
							// делаем заявку на кредит:
							$res = $api->applyforLoan ( $post ['amount'], $post ['days'], $res['Data']['Name'], $res['Data']['MaxAmount'], $_SESSION ['token'], $promoCode, $culture );
							// если заявка передана успешно:
							if ($res ['Success']) {
								$_SESSION ['loan'] = $res;
								unset ( $_SESSION ['PromoCode'] );

								if ($_SESSION['sat_id']) {
									// header ( "Location: " . $modx->makeUrl ( 333 ) );
								} elseif ($_SESSION['mob_id']) {
									header ( "Location: " . $modx->makeUrl ( 331) );
								} else {
									header ( "Location: " . $modx->makeUrl ( 4 ) );
								}
								die;
								
							}
						} else {
							$res = $resCheck;
						}
					}
					$_SESSION ['loan'] = $res;
					
					/*
					 * if (isset($res['error'])) {
					 * //$js = '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post['login'] . '")';
					 * } else {
					 * $js .= '$("#div_select").addClass("hidden");';
					 * $js .= '$("#div_code").removeClass("hidden");';
					 * }
					 */
					
				} else {
					if ($post ['PromoCode']) {
						$res = $api->checkPromoCode ( $post ['PromoCode'], $_SESSION ['token'] );
						if ($res ['Success']) {
							$_SESSION ['PromoCode'] = trim($post ['PromoCode']);
						} else {
							$_SESSION ['loan'] = $res; //
							unset ( $_SESSION ['PromoCode'] );
							break;
						}
					} else {
						unset ( $_SESSION ['PromoCode'] );
					}
					// $res['error'] = 100; // нет входных данных
					// $_SESSION['res'] = $res;
					unset ( $_SESSION ['loan'] );
				}
			}
			break;
		}
		
		// аутентификация:
		if (isset($_POST['auth']) && (0 < count ( $_POST ['auth'] ))) { // пришли данные с формы аутентификации
		                                 
			// чистим данные для востановления пароля:
			unset($_SESSION['forgot']);
			unset($_SESSION['Success']);
			unset($_SESSION['res']);
			
			if (isset ( $_SESSION ['token'] ))
				unset ( $_SESSION ['token'] );
			
			$post = $_POST ['auth'];
			
			if (isset ( $post ['login'] ) && isset ( $post ['password'] )) {
			    
			    $post ['login'] = preg_replace ( "/[^+\d]+/", "", $post ['login'] ); // удаляет все символы, кроме + и цифр
// 			    $post ['login'] = str_replace ( " ", "", $post ['login'] );
// 				$post ['login'] = str_replace ( "(", "", $post ['login'] );
// 				$post ['login'] = str_replace ( ")", "", $post ['login'] );
// 				$post ['login'] = str_replace ( "-", "", $post ['login'] );
			    
				if (isset($post['captcha'])) {
				    
				    if ($config['ReCaptcha_enabled'] == 1) {
				        $recaptcha = new Recaptcha ();
				        if (!$recaptcha->check($post['captcha'])) {
				            $_SESSION ['res_auth']['error'] = 156;	// Каптча введена неверно
				            // $_SESSION ['res_auth']['error'] = 157;	    // Символы введены неверно
				            // $js .= '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post ['login'] . '")';
				            $js .= 'showModalAuth("' . $post ['login'] . '");';
				            break;
				        }
				        
				    } elseif ($config['ReCaptcha_enabled'] == 2) {
				        if (($_SESSION['captcha_keystring'] !== trim($post['captcha'])) && (!isModerator('ReCaptchaModerators', $post ['login']))) {
				            // $_SESSION ['res_auth']['error'] = 156;	// Каптча введена неверно
				            $_SESSION ['res_auth']['error'] = 157;	    // Символы введены неверно
				            // $js .= '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post ['login'] . '")';
				            $js .= 'showModalAuth("' . $post ['login'] . '");';
				            break;
				        }
				    }
				}
				
				$res = $api->loginCustomer ( $post ['login'], $post ['password'] );
				
				if ($res ['Success']) {
					if (isset ( $_SESSION ['res'] )) {
						unset ( $_SESSION ['res'] );
						unset ( $_SESSION ['res_auth'] );
					}
					$_SESSION ['token'] = $res ['CustomerAuthToken'];
					$_SESSION ['tokenTime'] = time ();
					
					// если стояла галочка "Запомнить меня":
					if ($post ['remember'] === 'on') {
						setcookie ( "rememberLogin", $post ['login'], time () + 60 * 60 * 24 * 300 ); // запоминаем на 300 дней
					} else {
						setcookie ( "rememberLogin", "", 1 ); // сброс куки
					}
					
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 333 ) );
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 331) );
					} else {
						header ( "Location: " . $modx->makeUrl ( 4 ) );
					}
					die;
				} else {
					// $_SESSION ['res'] = $res;
					$_SESSION ['res_auth'] = $res;
					$_SESSION ['to_login'] = true;
					//$js .= '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post ['login'] . '")';
					$js .= 'console.log("showModalAuth"); showModalAuth("' . $post ['login'] . '");';
					
					// выполняем накопленные js-скрипты:
					// $js .= '$("#myModal").modal("show"); $("[name=\'auth[login]\']").val("aaaaaaaaa")';
					//if (isset ( $js ))
					//	$modx->regClientScript ( '<script>$(document).ready(function(){' . $js . '});</script>' );
				}
				
				$modx->setPlaceholder ( 'e', 'ok' );
			}
			break;
		}
		
		// запрос Captcha
		/*
		 * if (isset($_GET['captcha'])) {
		 *
		 * $captcha = new Captcha();
		 * $_SESSION['captcha_keystring'] = $captcha->getKeyString();
		 * die;
		 * }
		 */
		
		if (isset($_GET['captcha'])) {
		 
            $captcha = new Captcha();
            if ($_GET['captcha'] === 'reg') {
                $_SESSION['captcha_keystring_reg'] = $captcha->getKeyString();
            } else {
                $_SESSION['captcha_keystring'] = $captcha->getKeyString();
            }
			//echo $captcha;
			//echo "3333";
			// $x = $captcha->getKeyString();
            die;
		 }
		
		// смена пароля:
		if (isset($_POST['change']) && (0 < count ( $_POST ['change'] ))) { // пришли данные с формы смены пароля
			
			if (! $_SESSION ['token']) {
				header ( "Location: " . $modx->makeUrl ( 1 ) );
			}
			
			$post = $_POST ['change'];
			if ($post ['password'] && $post ['password-1'] && $post ['password-2']) {
				
				if (($post ['password-1'] === $post ['password-2']) && (strlen ( 'password-1' ) > 0)) {
					$res = $api->changeCustomerPassword ( $post ['password'], $post ['password-2'], $_SESSION ['token'] );
					$_SESSION ['change'] = $res;
					
					if (! $res ['Success']) {
						$js .= '$("#js-modal-password").modal("show");';
					} else {
						if (isset ( $_SESSION ['change'] ))
							unset ( $_SESSION ['change'] );
					}
				} else {
					$_SESSION ['change'] ['error'] = 103; // Пароли не совпадают
					$js .= '$("#js-modal-password").modal("show");';
				}
			}
			break;
		}
		
		// проверка кода регистрации:
		if (isset($_POST['codeReg']) && (0 < count ( $_POST ['codeReg'] ))) { // пришли данные с формы подтверждения кода регистрации
		    $post = $_POST ['codeReg'];
			if (( int ) $post ['code'] === $_SESSION ['codeReg']) {
				$_SESSION ['codeRegConfirm'] = true;
				$_SESSION ['res'] = null;
			} else {
				$_SESSION ['codeRegConfirm'] = false;
				$_SESSION ['res'] ['error'] = 111;
			}
			break;

		}
		
		// подтверждение договора:
		if (isset($_POST['confirmDog']) && (0 < count ( $_POST ['confirmDog'] ))) { // пришли данные с формы подтверждения договора
			$post = $_POST ['confirmDog'];
			
			if ($post ['credit_id']) {
				$res = $api->confirmContract ( $post ['credit_id'], $_SESSION ['token'] );
				$_SESSION ['res'] = $res;
				if (isset ( $res ['Success'] )) {
					unlink ( $_SESSION ['file'] ); // удаляем временный файл
					$_SESSION ['sendCode'] = null;
					$_SESSION ['file'] = null;
					if ($_SESSION['sat_id']) {

					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 331 ) ); // Мои кредиты (mob)
					} else {
						header ( "Location: " . $modx->makeUrl ( 4 ) ); // Мои кредиты
					}
				} else {
					// $_SESSION['sendCode'] = $res;
				}
			}
			break;
		}
		
		// Продление кредита:
		if (isset ( $_POST ['continueLoan'] )) {
			$post = $_POST ['continueLoan'];
			
			if ($post ['credit_id']) {
				$_SESSION ['cred_id'] = $post ['credit_id'];
			} else {
				unset ( $_SESSION ['cred_id'] );
			}
			break;
		}
		
		// Сбор контактных данных клиента:
		if (isset ( $_POST ['clientContactInfo'] )) {
			$post = $api->preprocessing ($_POST ['clientContactInfo']);

			if ($post['Name'] && $post['Email'] && $post['Phone']) {
				
				$post['Phone'] = trim(str_replace(' ', '', $post['Phone']));
				
				$resCli = $api->sendClientContactInfo($post);
			} else {
				$resCli['Success'] = false;
				$resCli['Message'] = 'No data';
			}
			
			if ($resCli['Success']) {
				$modx->setPlaceholder('resultContactInfo', 'success');
			} else {
				$_SESSION ['clientContactInfo'] = $resCli;
			}
			
			break;
		}
		
		// выбор действия по кредиту:
		if (isset($_POST['credits']) && (0 < count ( $_POST ['credits'] ))) { // пришли данные с формы Мои кредиты
			$post = $_POST ['credits'];

			// устаревающий if с credit_id (будет заменен на $_SESSION ['credit'] ) (осторожно, используется в Истории кредитов!!!)
			if ($post ['cred_id'] && $post ['href']) {
				$_SESSION ['cred_id'] = $post ['cred_id'];
				if ($post ['href'] == 4) {
					$_SESSION ['selected_cred_id'] = $post ['cred_id'];
				} else {
					$_SESSION ['cred_id'] = $post ['cred_id'];
					header ( "Location: " . $modx->makeUrl ( (int) $post ['href'] ) );
					die;
				}
			}
			
			// если пришел номер из массива кредитов 
			if (isset($post ['cred_number']) && $post ['href']) {
				$_SESSION ['cred_id'] = $_SESSION['res']['credits'][(int) $post ['cred_number']]['id'];
				if ($post ['href'] == 4) {
					$_SESSION ['selected_cred_id'] = $_SESSION ['cred_id'];
				} else {
					header ( "Location: " . $modx->makeUrl ( (int) $post ['href'] ) );
					die;
				}
			}
			
			// новый if с $_SESSION ['credit'] )
			if ($_SESSION ['credit'] && $post ['href']) {
				$_SESSION ['cred_id'] = $_SESSION ['credit']['Id'];
				if ($post ['href'] == 4) {
					$_SESSION ['selected_cred_id'] = $_SESSION ['credit']['Id'];
				} else {
					if ($post['amount']) 
						$_SESSION ['credit']['amountToPay'] = $post['amount'];
					header ( "Location: " . $modx->makeUrl ( (int) $post ['href'] ) );
					die;
				}
			}
			break;
		}
		
		// просмотр доп.соглашения:
		if (isset($_POST['dopdogovor']) && (0 < count ( $_POST ['dopdogovor'] ))) { // пришли данные с формы для просмотра доп.соглашения
		    $post = $_POST ['dopdogovor'];
		    
		    $_SESSION['dopdogovor']['refId'] = $post['refId'];   // куда возвращаться после просмотра (якорь)
		    
		    // получить файл договора
		    if (isset($_SESSION ['credit']['Id'])) {
		        $credit_id = $_SESSION ['credit']['Id'];
		        
		        switch ($post['type']) {

		            case 'prolongation':
		                $_SESSION['dopdogovor']['anchorId'] = 'prolongation-anchor';   // куда возвращаться после просмотра (якорь)
		                $_SESSION['prolongation']['selectedId'] = $post['elementId'];
		                $res = $api->getAdditionalContract ( $_SESSION ['token'], (int) $credit_id, 'prolongation', (int) $post['elementId'] ); // получить договор
		                // $res = $api->getContract ( $credit_id, $_SESSION ['token'] ); // получить договор (для теста)
		                break;
		            case 'restructuring':
		                $_SESSION['dopdogovor']['anchorId'] = 'restructuring-anchor';   // куда возвращаться после просмотра (якорь)
		                // $_SESSION['prolongation']['selectedId'] = $post['elementId'];
		                $res = $api->getAdditionalContract ( $_SESSION ['token'], (int) $credit_id,  'restructuring', (int) $post['elementId'] ); // получить договор
		                break;
		                
		            default:
		                $res ['Success'] = false;
		                break;
		        }

                $outFile = MODX_BASE_PATH . 'DesignAPI/tmp/additionalContract_' . $credit_id . '.pdf';
                $_SESSION ['file'] = $outFile;
                if ($res ['Success']) {
		            file_put_contents ( $outFile, $res ['Data'] ); // файл договора
		        } else {
		            file_put_contents ( $outFile, '' ); // пустой файл
		        }
		    }
		    
		    break;
		}
		
		// мечты - запись:
		if (isset($_POST['dreams']) && (0 < count ( $_POST ['dreams'] ))) { // пришли данные с формы "Мечты"
			$post = $api->preprocessing ($_POST ['dreams']);
			
			//die(print_r($post, true));
			
			// это модератор:
			$isModerator = isModerator('DreamsModerators');
			
			$dreamsTable = new DreamsTable();
			
			// если уже есть id:
			if (($post['id']) && ($post['id'] !== 0) && $isModerator) {
				
				// поиск записи:
				if ($dreamsTable->findById((int) $post['id'])) {
					
					// удалить запись:
					if($post['forDelete']) {
						if (!$dreamsTable->delete()) $_SESSION['res']['error'] = 323;	// Изменить мечту не удалось
						break;
					}
					
					$fields = [];
					if ($post['name']) $fields['name'] = $post['name'];
					if ($post['dream']) $fields['dream'] = $post['dream'];
					if ($post['dream_details']) $fields['dream_details'] = $post['dream_details'];
					if ($post['rating']) $fields['rating'] = $post['rating'];
					if ($post['answer']) $fields['answer'] = $post['answer'];
					// if ($post['login']) $fields['login'] = $post['login'];
					$fields['status'] = ($post['status'])? 1 : 0;
					if ($post['image_delete']) $fields['file'] = '';	// убрать картинку
					// if ($post['email']) $fields['email'] = $post['email'];
					if ($post['moderator'])	$fields['moderator'] = $post['moderator'];
					if ($login) $fields['moder_login']= $login;
					//if ($post['date_created']) $fields['date_created']= $post['name'];
					$fields['date_updated']= date('Y-m-d H:i:s');
					
					if (!$dreamsTable->update($fields)) $_SESSION['res']['error'] = 323;	// Изменить мечту не удалось
					
				} else {
					$_SESSION['res']['error'] = 3;	// 003 ошибка при работе с БД
				}
				
			} elseif (!$post['id']) { // функционал добавления мечты реализован в pluginAjax.php
				
				$fields = [];
				if ($post['name']) $dreamsTable->name = $post['name'];
				if ($post['dream']) $dreamsTable->dream = $post['dream'];
				if ($post['dream_details']) $dreamsTable->dream_details = $post['dream_details'];
				if ($post['rating']) $dreamsTable->rating = $post['rating'];
				// if ($post['answer']) $dreamsTable->answer = $post['answer'];
				if ($login) $dreamsTable->login = $login;
				$dreamsTable->status = 0;
				// if ($post['email']) $reviewTable->email = $post['email'];
				// if ($post['moderator']) $reviewTable->moderator = $post['moderator'];
				$dreamsTable->moderator = $config["ReviewsModeratorName"];
				//if ($login) $fields['moder_login']= $login;
				$dreamsTable->date_created = date('Y-m-d H:i:s');
				$dreamsTable->date_updated = date('Y-m-d H:i:s');
				
				if (!$dreamsTable->insert()) $_SESSION['res']['error'] = 322;	// Отправить отзыв не удалось
				
				// отправка почты:
				if ($post ['name'] && $post ['email'] && $post ['review']) {
					
					$toEmail = [
							'sender' => 'страницы мечты',
							'fromName' => $post ['name'],
							'fromEmail' => $post ['email'],
							'message' => "<br>Выбранный рейтинг: " . $post ['rating'] . "<br>" . $post ['review'],
							'MailReviews' => true,
					];
					$res = $api->sendEmailToSupport ( $toEmail );
					$_SESSION ['email'] = $res;
				} else {
					unset ( $_SESSION ['email'] );
				}
			}
			
			break;
		}
		
		// Пришел запрос на активацию email:
		if (isset($_GET['email_code'])) {
		    $emailActivationCode = $_GET['email_code'];
		    $resEmailCode = $api->verifyCustomerEmail($emailActivationCode);
		    if ($resEmailCode['Success']) {
		        $messageType = 'message';
		        $messageCode = 501;  // Ваш email подтвержден
		        // ставим метку что емаил подтвержден для страницы мои данные:
		        $_SESSION ['api']['client']['IsEmailVerifiedByCustomer'] = true;
		    } else {
		        $messageType = 'error';
		        $messageCode = 1; // URL запроса неверный
		    }
		    $_SESSION['resShowMessage'] = [
		        'Success' => false,
		        'error' => $messageCode,
		    ];
		    // модалка для сообщений:
		    $modx->setPlaceholder('showMessage', $messageType);    // тип сообщения
		    $modx->setPlaceholder('showMessageTitle', '[#1702#]');   // Заголовок "Результат активации почты"
		}
		    
		// Личный кабинет. Загрузили файлы документов:
		if (isset($_POST['files']) && (0 < count ( $_POST ['files'] ))) { // пришли данные с формы Мои документы
			
			if (isset ( $_FILES ['files'] ['name'] ['scan1'] ) && ! empty ( $_FILES ['files'] ['name'] ['scan1'] )) {
				$scan = base64_encode ( file_get_contents ( $_FILES ['files'] ['tmp_name'] ['scan1'] ) );
				$data ['scan1'] = $scan;
				// move_uploaded_file($_FILES['scan1']['tmp_name'], 'data/' . $this->user->id . '_scan1.jpg');
			}
			
			if (isset ( $_FILES ['files'] ['name'] ['scan2'] ) && ! empty ( $_FILES ['files'] ['name'] ['scan2'] )) {
				$scan = base64_encode ( file_get_contents ( $_FILES ['files'] ['tmp_name'] ['scan2'] ) );
				$data ['scan2'] = $scan;
			}
			if (isset ( $_FILES ['files'] ['name'] ['scan3'] ) && ! empty ( $_FILES ['files'] ['name'] ['scan3'] )) {
				$scan = base64_encode ( file_get_contents ( $_FILES ['files'] ['tmp_name'] ['scan3'] ) );
				$data ['scan3'] = $scan;
			}
			
			if (is_array ( $data ) && (count ( $data ) > 0)) {
				$res = $api->sendUserprofile ( $data );
				$_SESSION ['res'] = $res;
				
				if (isset ( $res ['error'] )) {
					$_SESSION ['send'] = $res;
				} else {
					$_SESSION ['send'] = null;
					header ( "Location: " . $modx->makeUrl ( 6 ) );
				}
			}
			break;
		}
		
		// восстановление пароля:
		if (isset($_POST['forgot']) && (0 < count ( $_POST ['forgot'] ))) { // пришли данные с формы восстановления пароля
			$post = $_POST ['forgot'];
			$post = $api->preprocessing ( $post ); // удаляем спецсимволы
			// проверяем на уникальность запроса (добавляем время до минуты):
			$hash = md5 ( serialize ( $post ) . date ( 'YmdHi' ) );
			if ($hash === $_SESSION ['hash']) {
				break;
			} else {
				$_SESSION ['hash'] = $hash;
			}
			
			$lang = $modx->config ['lang'];
			
			// если восстановление по СМС:
			if ($post ['phone']) {
				$post ['phone'] = str_replace ( " ", "", $post ['phone'] );
				$post ['phone'] = str_replace ( "(", "", $post ['phone'] );
				$post ['phone'] = str_replace ( ")", "", $post ['phone'] );
				$post ['phone'] = str_replace ( "-", "", $post ['phone'] );
				
				if (! isset ( $post ['code'] ) || ! isset ( $post ['password'] )) {
					$res = $api->forgotPass ( $post ['phone'], 'phone', $lang ); // запрос на сброс пароля по СМС
					                                                         
					// $res['Success'] = true; // для теста
					
					if (isset ( $res ['error'] ) || ! $res ['Success']) {
						if (! $res ['error'] && ! $res ['Message']) {
							$res ['error'] = 320; // Отправить код не удалось
						}
						$_SESSION ['forgot'] = $res;
					} else {
						$js .= '$("#recoveryPassword").attr("disabled", true);';
						$js .= '$("#buttonSendPhone").addClass("hidden");';
						$js .= '$(".hidden-form").removeClass("hidden");';
						unset ( $_SESSION ['forgot'] );
						$_SESSION ['forgot'] = $post;
						$_SESSION ['forgot'] ['js'] = $js;
					}
				} else {
					if ($post ['password'] === $post ['password2']) {
						
						$res = $api->forgotPass ( $post ['phone'], 'phone', $lang, $post ['code'], $post ['password'] );
						
						// $res['Success'] = true; // для теста
						
						if (isset ( $res ['error'] ) || ! $res ['Success']) {
							if (! $res ['error'] && ! $res ['Message']) {
								$res ['error'] = 321; // Подтвердить код не удалось
							}
							$_SESSION ['forgot'] = array_merge ( (($_SESSION ['forgot']) ? : []), $res );
							unset ( $_SESSION ['Success'] );
						} else {
							unset ( $_SESSION ['forgot'] );
							$_SESSION ['Success'] ['Flag'] = true;
							$_SESSION ['Success'] ['Method'] = 'phone';
							$_SESSION ['Success'] ['recoveryPassword'] = $post ['phone'];
							
							// пытаемся залогиниться:
							$resLogin = $api->loginCustomer($post['phone'], $post['password']);
							if ($resLogin ['Success'] && !$_SESSION['sat_id'] && !$_SESSION['mob_id']) {
							    $_SESSION ['token'] = $resLogin ['CustomerAuthToken'];
							    $_SESSION ['tokenTime'] = time ();
							}
							
							if ($_SESSION['sat_id']) {
								// header ( "Location: " . $modx->makeUrl ( 333 ) );
							} elseif ($_SESSION['mob_id']) {
								header ( "Location: " . $modx->makeUrl ( 323) );
							} else {
								// header ( "Location: " . $modx->makeUrl ( 5 ) );
							}
							// die;
						}
					} else {
						$_SESSION ['forgot'] ['error'] = 103; // Пароли не совпадают
					}
				}
			}
			// если восстановление по email:
			if ($post ['email']) {
				
				if (! isset ( $post ['code'] ) || ! isset ( $post ['password'] )) {
					$res = $api->forgotPass ( $post ['email'], 'email', $lang ); // запрос на сброс пароля по email
					                                                         
					// $res['Success'] = true; // для теста
					
					if (isset ( $res ['error'] ) || ! $res ['Success']) {
						$_SESSION ['forgot'] = $res;
					} else {
						unset ( $_SESSION ['forgot'] );
						$_SESSION ['forgot'] ['email'] = $post ['email'];
						$_SESSION ['Success'] ['Flag'] = true;
						$_SESSION ['Success'] ['Method'] = 'sendEmail';
						$_SESSION ['Success'] ['recoveryPassword'] = $post ['email'];
					}
				} else {
					if ($post ['password'] === $post ['password2']) {
						
						$res = $api->forgotPass ( $post ['email'], 'email', $lang, $post ['code'], $post ['password'] );
						
						// $res['Success'] = true; // для теста
						
						if (isset ( $res ['error'] ) || ! $res ['Success']) {
							$res ['Message'] = 'The link is incorrect or expired';
							$_SESSION ['forgot'] = $res;
							unset ( $_SESSION ['Success'] );
						} else {
							unset ( $_SESSION ['forgot'] );
							$_SESSION ['Success'] ['Flag'] = true;
							$_SESSION ['Success'] ['Method'] = 'email';
							$_SESSION ['Success'] ['recoveryPassword'] = $post ['email'];
							
							// пытаемся залогиниться:
							$resLogin = $api->loginCustomer($post['phone'], $post['password']);
							if ($resLogin ['Success'] && !$_SESSION['sat_id'] && !$_SESSION['mob_id']) {
							    $_SESSION ['token'] = $resLogin ['CustomerAuthToken'];
							    $_SESSION ['tokenTime'] = time ();
							}
							
						}
					} else {
						$_SESSION ['forgot'] ['error'] = 103; // Пароли не совпадают
					}
				}
			}
			break;
		}
		
		// восстановление пароля:
		if (isset ( $_GET ['forgot'] )) { // пришли данные при переходе по ссылке восстановления пароля
		                              
			// чистим данные для востановления пароля:
			unset ( $_SESSION ['forgot'] );
			unset ( $_SESSION ['Success'] );
			
			$_SESSION ['forgot'] ['token'] = $_GET ['token'];
			$_SESSION ['forgot'] ['login'] = $_GET ['login'];
			
			// подключаем логирование:
			$log = new Log ( 'forgot_' );
			$strlog = "METHOD: GETforgotPass (GET forgotPass) " . "\nlogin=" . $_GET ['login'] . "\ntoken=" . $_GET ['token'];
			$log->write ( $strlog );
			
			break;
		}
		
		// Заказ кредита в калькуляторе:
		if (isset($_POST['orderCredit']) && (0 < count ( $_POST ['orderCredit'] ))) { // пришли данные с формы аутентификации
			
			$_SESSION ['orderCredit'] = $api->preprocessing ($_POST ['orderCredit']);
			// если пришли со страницы студентов:
			if ($_POST ['orderCredit']['pageId'] == '173') {
				header ( "Location: " . $modx->makeUrl ( 269 ) );
				die;
			}

			// строки ниже не срабатывают, см. case 1:
			if (isset ( $_SESSION ['token'] )) {
				if ($_SESSION['sat_id']) {
					// header ( "Location: " . $modx->makeUrl ( 333 ) );
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl ( 488) );
				} else {
					header ( "Location: " . $modx->makeUrl ( 2 ) );
				}
				die;

			} else {
				if ($_SESSION['sat_id']) {
					// header ( "Location: " . $modx->makeUrl ( 333 ) );
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl ( 327) );
				} else {
					header ( "Location: " . $modx->makeUrl ( 17 ) );
				}
				die;
			}
			break;
		}
		
		// Дополнительные данные о клиенте:
		if (isset($_POST['otherData']) && (0 < count ( $_POST ['otherData'] ))) { // пришли данные с формы доп.данных
			
			$_SESSION ['otherData'] = [
					// 'AdditionalJsonData' => $_POST ['otherData'],
					'AdditionalData' => $_POST ['otherData'],
			];

			// сохраним доп. данные:
			if ($_SESSION ['token']) {
				// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
				$res = [];
				$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
				$res ['Data'] = $_SESSION['api']['client'];
				if ($res ['Success']) {
					$data = array_merge ( $res ['Data'], $_SESSION ['otherData'] ); // добавляем к ранее введенным данным новые
					$res = $api->updateCustomerDetails ( $data, $_SESSION ['token'] ); // изменить информацию о пользователе
				}
			}
				
			break;
		}
		
		// Оплата кредита
		if (isset ( $_POST ['pay'] )) {
			$post = $_POST ['pay'];
			
			if ($post ['amount']) {
				
				$post = $api->preprocessing ( $post ); // удаляем спецсимволы
				
				// проверяем на уникальность запроса (добавляем время до минуты):
				$hash = md5 ( serialize ( $post ) . date ( 'YmdHi' ) );
				if ($hash === $_SESSION ['hash']) {
					break;
				} else {
					$_SESSION ['hash'] = $hash;
				}
				
				// $_SESSION ['cred_id'] = $post ['credid'];
				
				// если ввели большую сумму, чем нужно, или меньше 1.00:
				if ((( float ) $post ['amount'] > ( float ) $post ['sumToPay']) || (( float ) $post ['amount'] < 1.00)) {
					$_SESSION ['pay'] = [ 
							'error' => 182 
					]; // Сумма введена некорректно
					break;
				}
				
				// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
				$res = [];
				$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
				$res ['Data'] = $_SESSION['api']['client'];
				if ($res ['Success']) {
					$phone = $res ['Data'] ['Phone'];
					$email = $res ['Data'] ['Email'];
					$firstName = $res ['Data'] ['FirstName'];
					$lastName = $res ['Data'] ['LastName'];
				} else {
					$phone = '';
					$email = '';
					$firstName = '';
					$lastName = '';
				}
				
				// если платежная система WayForPay:
				if ($_SESSION['api']['paySystem'] == 'WayForPay') {
					
					// временно. Здесь нужно получать recToken (если решится, что он нужен)
					$recToken = '';
					// $recToken = '577b5174-ba48-4a1b-a247-49f738a74599';
					
					$data = [ 
							'id' => ( int ) $post ['credid'],
							'amount' => ( float ) number_format ( $post ['amount'], 2, '.', '' ),
							'long' => ($post ['long']) ?: 0,
							'cardid' => ($post ['card_id']) ? : $post ['credit_card_id'],
							'phone' => $phone,
							'email' => $email,
							'lang' => $modx->config ['lang'],
							'recToken' => $recToken,
							'clientFirstName' => $firstName,
							'clientLastName' => $lastName,
							'clientPhone' => $phone,
							'clientEmail' => $email,
							'returnUrl' => ($post ['isProlongation'] === '1') ? 'prodlenie-kredita' : 'lichnyj-kabinet/moi-kredity',
							
							'isCurrentCard' => ($post ['isCurrentCard'] == '1') ? 'true' : 'false' 
					];
					
					$res = $api->payCredit ( $data ); // оплата кредита, или подготовка виджета
					
					if ($res ['error'] != 777) {
						$_SESSION ['pay'] = $res;
					} else {
						unset ( $_SESSION ['pay'] );
						unset ( $_SESSION ['credit']['amountToPay'] );
						
						if ($post ['isProlongation'] === '1') $_SESSION ['isPayForProlongation'] = '1';
						
						// если есть виджет, запускаем, иначе пробуем открыть страницу:
						if ($res['widget'] && ($config['pay_widget'] == 1)) {
							$js .= $res ['widget'];
						} elseif ($res ['form']) {
							echo $res ['form'];
							die ();
						} else {
							if ($post ['isProlongation'] === '1') {
								// $_SESSION ['isPayForProlongation'] = '1';
								header("Location: " . $modx->makeUrl(87));
								die;
							} else {
								if ($_SESSION['sat_id']) {
									
								} elseif ($_SESSION['mob_id']) {
									header ( "Location: " . $modx->makeUrl(331));
								} else {
									header ( "Location: " . $modx->makeUrl(4));
								}
								die();	// для оптимизации возврата в Мои кредиты
							}
						}
						/*
						 * if ((int)$post['amount'] !== (int)$post['sumToPay']) {
						 * header("Location: " . $modx->makeUrl(36));
						 * } else {
						 * header("Location: " . $modx->makeUrl(37));
						 * }
						 */
					
					}

				// если платежная система Tranzzo:
				} elseif ($_SESSION['api']['paySystem'] == 'Tranzzo') {
				
					$startLink = ($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://'; 
					$data = [
						// 'id' => ( int ) $_SESSION ['cred_id'],
						'amount' => ( float ) number_format ( $post ['amount'], 2, '.', '' ),
						'card' => $post ['card'],
						// 'returnUrl' => $startLink . $_SERVER['SERVER_NAME'] . "/{$modx->config ['lang']}/lichnyj-kabinet/" . (($post ['isProlongation'] === '1') ? 'prodlenie-kredita' : 'moi-kredity') . '/',
						// 'returnUrl' => $startLink . $_SERVER['SERVER_NAME'] . "/{$modx->config ['lang']}/lichnyj-kabinet/moi-kredity/",
						'returnUrl' => ($_SESSION['mob_id']) ? $modx->makeUrl(331) : $modx->makeUrl(4),
							];
					$res = $api->tranzzoPayCredit ( $data ); // оплата кредита
					if ($res ['Success'] && $res ['Data']['Url']) {
						$_SESSION['toPay']['data'] = $data;	// записываем данные оплаты для последующей проверки
						$_SESSION['toPay']['RepaymentId'] = $res ['Data']['RepaymentId'];	// записываем ID транзакции для последующей проверки
						header("Location: " . $res ['Data']['Url']);
						die;
					} else {
						$_SESSION ['pay'] = $res;
					}
				}
			}
			break;
		}
		
		// Оплата кредита: данные, пришедшие от платежной системы
		if (isset ( $_GET ['pay'] )) {
			
			$res = $api->resPay ( $_POST ); // обработка ответа платежной системы
			$error = $res ['error'];
			if ($error == 777) {
				// здесь записываем данные, полученные от платежной системы:
				if ($res ['transactionStatus'] == 'Approved') {
					
					$result = [ ];
					$id = substr ( $res ['orderReference'], 16 );
					$dt = new DateTime ();
					// $jsonDate = substr($dt->format('c'), 0, 19); // время без timezone
					$jsonDate = $dt->format ( 'c' ); // время
					
					$result ['LoanId'] = $id;
					$result ['Date'] = $jsonDate; // дата-время платежа
					$result ['ServiceName'] = "WayForPay front-end";
					$result ['RefNum'] = $res ['orderReference']; // номер платежа в WayForPay
					$result ['Amount'] = $res ['amount']; // сумма платежа (если он успешный)
					$result ['AmountLeft'] = $res ['amount']; // сумма которая ушла на погашения кредита (сейчас равна Amount)
					$result ['FailedAmount'] = 0; // сумма платежа (если он не успешный)
					$result ['Status'] = 'Successful'; // Successful/Failed
					$result ['Error'] = ''; // текст ошибки
					
					$resLogin = $api->loginTechnologist ();
					if ($resLogin ['Success']) {
						
						// отправляем результат оплаты в CRM
						$resCRM = $api->acceptChargedPay ( $result, $resLogin ['CustomerAuthToken'] );
						// $resCRM = json_decode($resCRM);
						
						if (! $resCRM ['Success']) {
							$error = 303; // Не удалось перечислить средства с карты
							$res ['response'] = json_encode ( [ ] );
						}
					} else {
						$error = 112; // Пользователь не найден
						$res ['response'] = json_encode ( [ ] );
					}
				} else {
					$resCRM = 'Не отправлено по причине несоответствия условиям.';
					$error = 303; // Не удалось перечислить средства с карты
				}
			}
			
			echo $res ['response'];
			die ();
		}
		
		// запрос PDF
		if (isset ( $_GET ['pdf'] )) {
			if ($_SESSION ['file'])
				$file = $_SESSION ['file'];
			else
				die ( 'NO PDF FOUND' );
			
			if ($_GET ['download'])
				header('Content-Disposition: attachment; filename="dogovor.pdf"');

			header ( "Content-type: application/pdf" );
			$content = base64_decode ( file_get_contents ( $file ) );
			
			echo $content;
			die ();
		}
		
		// личный кабинет. Мои данные:
		if (isset($_POST['profile']) && (0 < count ( $_POST ['profile'] ))) { // пришли данные с формы "Мои данные"
			
			// подключаем логирование:
			/*
			$log = new Log ( 'aaa_' );
			$strlog = "AFFILIATE TEST METHOD: TEST "
					. "\npost = " . print_r($post, true);
			$log->write ( $strlog );
			*/
			
			if (! $_SESSION ['token']) {
				
				if ($_SESSION['sat_id']) {
					
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl(323));
				} else {
					header ( "Location: " . $modx->makeUrl(4));
				}
				die;
				
			} else {
				
				$post = $_POST ['profile'];
				$post = $api->preprocessing ( $post ); // удаляем спецсимволы
				$data = [ ];
				
				$data = postToData ( $post ); // функция заполнения массива data данными из формы (POST)
				// дополнительные данные:
				if ($_SESSION ['otherData']) {
					$data = array_merge ( $_SESSION ['otherData'], $data );
				}
				
				if (($post['form_myData2']) && (! $data ['IsAgreedWithMailSubscription']))
					$data ['IsAgreedWithMailSubscription'] = 0;
					
				if (($post['form_myData2']) && (! $data ['IsAgreedUseMyData']))
					$data ['IsAgreedUseMyData'] = 0;
					
				// заполним счет маской карты:
				// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
				$res = $_SESSION['api']['cards'];	// получить список карт клиента
				// если еще нет карты у клиента, возвращается '', поэтому модернизируем ответ:
				if (trim ( ( string ) $res [0] ['number'] ) !== '') {
					$data ['Bank'] ['AccountNumber'] = ( string ) $res [0] ['number']; // счет в банке
				}
				
				// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); 
				// прочитать данные пользователя
				$res = [];
				$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
				$res ['Data'] = ($_SESSION['api']['client']) ? : [];
				
				// флаг о том дать или нет команду TKLender послать письмо с подтверждением email
				$isSendEmailConfirmation = false;
				// если email изменился, после проверки на не существующий домен
				// прерываем сохранение данных либо сбрасываем флаг подтверждения email
				// и ставим флаг $sendEmailConfirmation в истину
				if ( isset($data['Email']) && ($data['Email'] !== $res['Data']['Email'])) {
				    require_once MODX_BASE_PATH . 'DesignAPI/classes/CheckMail/checkMail.php';
				    $checkMail = new checkMail($data['Email']);
				    $result = $checkMail->check();
				    if ($result === 'bad domain') {
				        $res ['Success'] = false;
				        $res ['error'] = 106;   // Некорректный email
				        $_SESSION ['profile'] = $res;
				        break;
				    } else {
				        // updateCustomerDetails сохраняет поле IsEmailVerifiedByCustomer со всеми другими данными
				        // в TKLender сбрасываем флаг подтверждения email
				        $data ['IsEmailVerifiedByCustomer'] = false;
				        // ставим метку что после сохранения новых данных updateCustomerDetails
				        // и измененном email нужно даем команду TKLender послать письмо с подтверждением email
				        $isSendEmailConfirmation = true;
				    }
				}
				
				// запрет на редактирования конкретных полей:
				$fieldsList = [
					'SocialSecurityNumber', 'FirstName', 'LastName', 'MiddleName', 'BirthDate',
					'Phone', 'PassportType', 'Passport', 'PassportIssuedBy', 'PassportRegistration',
					'PassportReestr', 'PassportNumberDoc',
				];
				$checkForUpdates = checkForUpdates($fieldsList, $data, $res['Data']);
				//$checkForUpdates['Success'] = true;
				// запрет на редактирования конкретных полей внутри Address:
				$fieldsList = ['State', 'City', 'Street', 'House', 'Building', 'Apartment'];
				$checkForUpdatesAddress = checkForUpdates($fieldsList, $data['Address'], $res['Data']['Address']);
				//$checkForUpdatesAddress['Success'] = true;
				// запрет на редактирования конкретных полей внутри SecondAddress:
				$checkForUpdatesSecondAddress = checkForUpdates($fieldsList, $data['SecondAddress'], $res['Data']['SecondAddress']);
				
				//$checkForUpdatesSecondAddress['Success'] = true;
				if (!$checkForUpdates['Success'] || !$checkForUpdatesAddress['Success'] || !$checkForUpdatesSecondAddress['Success']) {
					$res ['Success'] = false;
					$res ['Message'] = ($checkForUpdates['Message']) ? : (($checkForUpdatesAddress['Message']) ? : $checkForUpdatesSecondAddress['Message']);
				
				} else {
					$data = array_merge ( $res ['Data'], $data ); // добавляем к ранее введенным данным новые
					$_SESSION ['userData'] = $data;
					
					$res = $api->updateCustomerDetails ( $data, $_SESSION ['token'] ); // изменить информацию о пользователе
				}
				
				if ($res ['Success']) {
					
				    // если email изменился даем команду TKLender послать письмо с подтверждением email
				    if($isSendEmailConfirmation){
				        $resSendConfirm = $api->sendEmailConfirmation($_SESSION['token'], true);
				    }
				    
				    $_SESSION['api']['client'] = $data;
					
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					unset ( $_SESSION ['profile'] );
				} else {
					$_SESSION ['profile'] = $res;
				}
				
				// header ( "Location: " . $modx->makeUrl ( 5 ) );
			}
			break;
		}
		
		// личный кабинет. Пролонгация:
		if (isset($_POST['prolong']) && (0 < count ( $_POST ['prolong'] ))) { // пришли данные с формы "Пролонгация"
		    
		    if (!$_SESSION ['token'] || !$_SESSION ['credit']) {
		        if ($_SESSION['sat_id']) {
		            
		        } elseif ($_SESSION['mob_id']) {
		            header ( "Location: " . $modx->makeUrl(323));
		        } else {
		            header ( "Location: " . $modx->makeUrl(1));
		        }
		        die;
		    }
		    
		    $post = $_POST ['prolong'];
		    
		    // запрос на выбранный тип пролонгации:
		    if ($post ['href'] && $post ['prolong_id']) {
		        $res = $api->applyProlongation ((int) $_SESSION ['credit']['Id'], (int) $post['prolong_id']);
		        // переход на оплату проллонгации:
		        if ($res['Success'] && $post['amount']) {
		            $_SESSION ['cred_id'] = $_SESSION ['credit']['Id'];
		            $_SESSION ['credit']['amountToPay'] = $post['amount'];
		            header ( "Location: " . $modx->makeUrl ( (int) $post ['href'] ) );
		            die;
		        }
		    }
		    
		    break;
		}
		
		// личный кабинет. Пролонгация (старая):
		if (isset($_POST['prolongation']) && (0 < count ( $_POST ['prolongation'] ))) { // пришли данные с формы "Пролонгация"
			
			if (! $_SESSION ['token']) {
				if ($_SESSION['sat_id']) {
					
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl(323));
				} else {
					header ( "Location: " . $modx->makeUrl(1));
				}
				die;
			}
			
			$post = $_POST ['prolongation'];
			
			$dateProl = $post ['date'];
			$dateBegin = $_SESSION ['prolongation'] ['dateBegin'];
			$dateEnd = $_SESSION ['prolongation'] ['dateEnd'];
			$credit_id = $_SESSION ['prolongation'] ['credit_id'];
			
			// если удовлетворяет начальным условиям - пролонгируем:
			if (($credit_id = $post ['credit_id']) && (strtotime ( $dateProl ) >= strtotime ( $dateBegin )) && (strtotime ( $dateProl ) <= strtotime ( $dateEnd )) && $api->isLoanCanBeRolloved($_SESSION ['token'], $credit_id, $dateProl)) {
				// количество дней пролонгации:
				// $term = (int) ((strtotime($dateProl) - strtotime($dateBegin)) / 86400) + 1;
				
				// пролонгация:
				// echo "Пролонгация $credit_id срок: $term <br>";die;
				// $res = $api->prolongation($_SESSION['token'], $credit_id, $term, 'MyCredit frontend');
				$res = $api->prolongation ( $_SESSION ['token'], $credit_id, $dateProl, 'MyCredit frontend' );
				if ($res ['Success']) {
					unset ( $_SESSION ['prolongation'] );
					if ($_SESSION['sat_id']) {
						
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl(331));
					} else {
						header ( "Location: " . $modx->makeUrl(4));
					}
					die;
				} else {
					$_SESSION ['prolongation'] = $res;
				}
			} else {
				$_SESSION ['prolongation'] ['error'] = 113; // Данные не найдены
			}
			break;
		}
		
		// Работа с дополнительными картами:
		if (isset($_POST['radiocard']) && (0 < count ( $_POST ['radiocard'] ))) { // пришли данные с формы "Дополнительные карты"
		    $post = $api->preprocessing ($_POST ['radiocard']);
		    
		    $cards = ($_SESSION['card']['cards']) ? : [];
		    $card = (isset($cards[$post['id']]['Card'])) ? $cards[$post['id']]['Card'] : '';
		    $cardId = (isset($cards[$post['id']]['CardId'])) ? $cards[$post['id']]['CardId'] : 0;
		    if ($card) {
    		    $resCard['Success'] = false;
		        if ($post['action'] === 'main') { 
    		        $resCard = $api->tranzzoMakeCardMain($card);
    		    } elseif ($post['action'] === 'delete') {
    		        $resCard = $api->tranzzoDeleteCard( (int) $cardId);
    		    }
    		    if ($resCard['Success']) {
		            $res = $api->tranzzoGetCardsAll( $_SESSION ['token'] ); // получить список карт клиента
		            if (count($res) > 0)
	                    $_SESSION['api']['tranzzoCards'] = $res;
    		    } else {
    		        $_SESSION['card']['error'] = 169; // Вы можете изменить основную карту только после погашения кредита
    		    }
 		    }
		}

		// Регистрация:
		if (isset($_POST['reg']) && (0 < count ( $_POST ['reg'] ))) { // пришли данные с формы "Регистрация"
		    $post = $api->preprocessing ($_POST ['reg']);

			if ($post ['phone']) {
				$post ['phone'] = str_replace ( " ", "", $post ['phone'] );
				$post ['phone'] = str_replace ( "(", "", $post ['phone'] );
				$post ['phone'] = str_replace ( ")", "", $post ['phone'] );
				$post ['phone'] = str_replace ( "-", "", $post ['phone'] );
				$post ['phone'] = trim($post ['phone']);
			}
			
			// если еще не было регистрации:
			if ($post ['flagForm'] == '0') {
				if ($post ['password'] !== $post ['password2']) {
					$_SESSION ['res'] ['error'] = 103; // Пароли не совпадают
					// для возврата данных на форму:
					$js .= '$("[name=\'reg[phone]\']").val("' . $post ['phone'] . '");';
					$js .= '$("[name=\'reg[email]\']").val("' . $post ['email'] . '");';
					$js .= '$("[name=\'reg[password]\']").val("' . $post ['password'] . '");';
					$js .= '$("[name=\'reg[password2]\']").val("' . $post ['password2'] . '");';
					break;
				}
				if (strlen ( $post ['password'] ) < 6) {
					$_SESSION ['res'] ['error'] = 104; // Пароль не удовлетворяет требованиям
					// для возврата данных на форму:
					$js .= '$("[name=\'reg[phone]\']").val("' . $post ['phone'] . '");';
					$js .= '$("[name=\'reg[email]\']").val("' . $post ['email'] . '");';
					$js .= '$("[name=\'reg[password]\']").val("' . $post ['password'] . '");';
					$js .= '$("[name=\'reg[password2]\']").val("' . $post ['password2'] . '");';
					break;
				}
				
				if ($post ['email']) {
				    require_once MODX_BASE_PATH . '/DesignAPI/classes/CheckMail/checkMail.php';
				    $post ['email'] = trim($post ['email']);
				    $checkMail = new checkMail($post ['email']);
				    $res = $checkMail->check();
				    if($res === 'bad domain'){
			            //$_SESSION ['res'] ['error'] = 106; // Некорректный email
				        // для возврата данных на форму:
				        $js .= '$("[name=\'reg[phone]\']").val("' . $post ['phone'] . '");';
				        $js .= '$("[name=\'reg[email]\']").val("' . $post ['email'] . '");';
				        $js .= '$("[name=\'reg[password]\']").val("' . $post ['password'] . '");';
				        $js .= '$("[name=\'reg[password2]\']").val("' . $post ['password2'] . '");';
				        $js .= '$("#email").next().css("display","block");';
				        break;
				    }
				}
				// проверка существования введенного промокода:
				if ($post ['PromoCode']) {
					$res = $api->checkPromoCode ( $post ['PromoCode'] );
					if ($res ['Success']) {
						$_SESSION ['PromoCode'] = trim($post ['PromoCode']);
					} else {
						$_SESSION ['res'] = $res;
						
						// модалка для сообщений:
						// $modx->setPlaceholder('showMessage', 'error');
						// $modx->setPlaceholder('showMessageTitle', '[#993#]');	// Ошибка регистрации
						
						// для возврата данных на форму:
						$js .= '$("[name=\'reg[phone]\']").val("' . $post ['phone'] . '");';
						$js .= '$("[name=\'reg[email]\']").val("' . $post ['email'] . '");';
						$js .= '$("[name=\'reg[password]\']").val("' . $post ['password'] . '");';
						$js .= '$("[name=\'reg[password2]\']").val("' . $post ['password2'] . '");';
						$js .= '$("[name=\'reg[PromoCode]\']").val("' . $post ['PromoCode'] . '");';
						break;
					}
				}

				// если есть код регистрации, можно регистрировать:
				if ($_SESSION ['codeReg'] && $_SESSION ['codeRegConfirm'] && ($_SESSION ['phoneReg'] === $post ['phone'])) {
					$res = $api->registerCustomer ( $post ['phone'], $post ['password'] ); // регистрация
					// $res = $api->loginCustomer ( $post ['phone'], $post ['password'] ); // регистрация для теста

					if (! $res ['Success']) {
						unset ( $_SESSION ['codeReg'] );
						unset ( $_SESSION ['codeRegConfirm']);
						unset ( $_SESSION ['phoneReg']);
						unset ( $_SESSION ['token'] );
						// попробуем залогинется, вдруг пользователь уже есть:
						$resLogin = $api->loginCustomer ( $post ['phone'], $post ['password'] ); // вход
						if ($resLogin ['Success'] && !$_SESSION['sat_id'] && !$_SESSION['mob_id']) {
							$res = $resLogin;
							$_SESSION ['token'] = $resLogin ['CustomerAuthToken'];
							$_SESSION ['tokenTime'] = time ();
							header ( "Location: " . $modx->makeUrl ( 5 ) );
							die;
						}
						// попытка понять, что клиент уже существует. Если да - уходим на логин:
						if (strpos($res ['Message'], 'Клієнт з таким email вже існує.')) {
							$_SESSION ['res'] = $res;
							$modx->setPlaceholder('isCustomerExists', 1);
							// $_SESSION ['to_login'] = true;
							// header ( "Location: " . $modx->makeUrl ( 1 ) );
							// die();
						}
						
						// модалка для сообщений:
						// $modx->setPlaceholder('showMessage', 'error');
						// $modx->setPlaceholder('showMessageTitle', '[#993#]');	// Ошибка регистрации
					} else {
						$_SESSION['api']['client'] = [];
					}
				} else {
					unset($_SESSION ['codeReg']);
					unset($_SESSION ['codeRegConfirm']);
					// unset($_SESSION ['phoneReg']);
					/*
					unset($_SESSION ['codeReg']);
					unset($_SESSION ['codeRegConfirm']);
					unset($_SESSION ['phoneReg']);
					*/
				}
			}
			
			if (isset($res ['Success']) && $res ['Success'] && $res ['CustomerAuthToken']) {
				$_SESSION ['token'] = $res ['CustomerAuthToken'];
				$_SESSION ['tokenTime'] = time ();
				if (isset ( $_SESSION ['res'] ))
					unset ( $_SESSION ['res'] );
			}
			
			if ($_SESSION ['token']) {
				
				// если зарегистрировались, пытаемся записать данные пользователя:
				$data = [ ];
				
				if ($post ['phone']) {
					$data ['Phone'] = $post ['phone'];
				}
				if ($post ['email']) {
				    $data ['Email'] = $post ['email'];
				}
				if ($post ['facebook']) {
				    $data ['FacebookUrl'] = trim ( $post ['facebook'] );
				}
				// дополнительные данные:
				if ($_SESSION ['otherData']) {
					$data = array_merge ( $_SESSION ['otherData'], $data );
				}
				
				// заполняем дефолтовые значения:
				$data ['IsAgreedWithMailSubscription'] = 1; // Получать информацию о новостях и акциях
				$data ['IsAgreedUseMyData'] = 1; 			// Согласен на обработку данных операторами
				$data ['Address']['ResidentialMatchesRegistration'] = 1; // Адрес регистрации совпадает с адресом проживания
				
				// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); 
				// прочитать данные пользователя
				$res = [];
				// $res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
				$res ['Success'] = true;
				$res ['Data'] = $_SESSION['api']['client'];
				$data = array_merge ( $res ['Data'], $data ); // добавляем к ранее введенным данным новые
				
				if (isset ( $_SESSION ['userData'] )) {
					$data = array_merge ( $_SESSION ['userData'], $data ); // добавляем к ранее введенным данным новые
				}
				$_SESSION ['userData'] = $data;
				
				$res = $api->updateCustomerDetails ( $data, $_SESSION ['token'] ); // изменить информацию о пользователе
				
				if ($res ['Success']) {
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					$modx->setPlaceholder ( 'flagForm', '2' ); // открыть форму 2
					$_SESSION['api']['client'] = $data;

					// послать код для перехода по ссылке из email для подтверждения email на стороне TK Lender
					$resSendConfirm = $api->sendEmailConfirmation($_SESSION ['token']);
				
				} else {
					// если ошибка с email, то в отдельный error:
				    // if (isset($res ['Message']) && (strpos($res ['Message'], 'User with the same Email already exists') !== false)) {
    		        if (isset($res ['Message']) && (strpos($res ['Message'], 'Email') !== false)) {
			            $_SESSION ['res_email'] = $res;
				    } else {
				        $_SESSION ['res1'] = $res;
				    }
					$modx->setPlaceholder ( 'flagForm', '1' ); // открыть форму 1
					$modx->setPlaceholder ( 'flagErrorReg', '1' ); // 
					// для возврата данных на форму:
					$js .= '$("[name=\'reg[phone]\']").val("' . $post ['phone'] . '");';
					$js .= '$("[name=\'reg[email]\']").val("' . $post ['email'] . '");';
					$js .= '$("[name=\'reg[password]\']").val("' . $post ['password'] . '");';
					$js .= '$("[name=\'reg[password2]\']").val("' . $post ['password2'] . '");';
					$js .= '$("[name=\'reg[PromoCode]\']").val("' . $post ['PromoCode'] . '");';
				}
				
				// header("Location: " . $modx->makeUrl(17));
			} else {
				$_SESSION ['res'] = $res;
				if (isset ( $_SESSION ['token'] ))
					unset ( $_SESSION ['token'] );
				
				$modx->setPlaceholder ( 'flagForm', '0' ); // открыть форму 0 (редактирование логина)
					                                        
				// для возврата данных на форму:
				// $js .= '$("[name=\'reg[phone]\']").val("' . $post['phone'] . '");';
				// $js .= '$("[name=\'reg[email]\']").val("' . $post['email'] . '");';
				// $js .= '$("[name=\'reg[password]\']").val("' . $post['password'] . '");';
				// $js .= '$("[name=\'reg[password2]\']").val("' . $post['password2'] . '");';
			}
		}
		
		// Регистрация форма 2 и 3:
		if (isset($_POST['reg2']) && (0 < count ( $_POST ['reg2'] ))) { // пришли данные с второй, или третьей формы "Регистрация"
			$post = $api->preprocessing ($_POST ['reg2']);
			if (($post ['formID'] == '2') && ((( int ) $post ['bdate_year'] < ( int ) date ( 'Y' ) - 75) || (( int ) $post ['bdate_year'] > ( int ) date ( 'Y' ) - 18))) {
				$_SESSION ['res'] ['error'] = 107; // Некорректна дата рождения
				break;
			}
			
			if ($_SESSION ['token']) {
				if (isset ( $_SESSION ['res'] ))
					unset ( $_SESSION ['res'] );
					
				// если зарегистрировались, пытаемся записать данные пользователя:
				
				$data = postToData ( $post ); // функция заполнения массива data данными из формы (POST)
				if ((! $data ['IsAgreedWithMailSubscription']) && ($post ['formID'] == '3'))
					$data ['IsAgreedWithMailSubscription'] = 0;
				if ((! $data ['IsAgreedUseMyData']) && ($post ['formID'] == '3'))
					$data ['IsAgreedUseMyData'] = 0;
						
				// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
				$res = [];
				$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
				$res ['Data'] = $_SESSION['api']['client'];
				$data = array_merge ( $res ['Data'], $data ); // добавляем к ранее введенным данным новые
				
				if (isset ( $_SESSION ['userData'] )) {
					$data = array_merge ( $_SESSION ['userData'], $data ); // добавляем к ранее введенным данным новые
				}
				$_SESSION ['userData'] = $data;
				
				$res = $api->updateCustomerDetails ( $data, $_SESSION ['token'] ); // изменить информацию о пользователе
				
				if ($res ['Success']) {
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					unset ( $_SESSION ['reg2'] );
						
					if ($post ['formID'] == '2')
						$modx->setPlaceholder ( 'flagForm', '3' ); // открыть форму 3
					if ($post ['formID'] == '3') {
						unset ( $_SESSION ['codeReg'] );
						
						// добавить кредит:

						// отправляем заявку на кредит
						if ($post ['days'] && $post ['amount']) {
							
							// проверка возможности сделать новую заявку:
						    if ( !$api->checkToAddLoan ( $_SESSION ['token'] )) {
 							// if ($api->checkToAddLoan ( $_SESSION ['token'] )) { // для теста!!!
								$_SESSION ['res'] = [ 
									'error' => 194, 
								];	// У Вас уже существует заявка, или активный кредит
								if ($_SESSION['sat_id']) {
									
								} elseif ($_SESSION['mob_id']) {
									header ( "Location: " . $modx->makeUrl(331));
								} else {
									header ( "Location: " . $modx->makeUrl(4));
								}
								die;
								
							} else {

								// $promoCode = ($_SESSION ['PromoCode']) ?: null;
								$promoCode = ($_SESSION['PromoCode']) ? : (($_SESSION['hiddenPromoCode']) ? : null);
								$res = $api->getCreditProducts($promoCode, $_SESSION ['token']); // получаем список продуктов
								if ($res ['Success']) {
									
									// проверка заполнения данных пользователя:
									$resCheck = $api->checkCompleteUserData($_SESSION ['token']);
									if ($resCheck ['Success']) {
										
										// обработка дочерних продуктов: 
										if ($res['Data']['ChildProducts']) {
											$resChild = getChildProduct($post ['amount'], $post ['days'], $res['Data']);
											if ($resChild ['Success']) {
												$res = $resChild; // заменяем на дочерний
											}
										}
							
										$lang = $modx->config ['lang'];
										$culture = ($lang == 'ua') ? 'uk-UA' : 'ru-RU';
										// делаем заявку на кредит:
										$res = $api->applyforLoan ( $post ['amount'], $post ['days'], $res['Data']['Name'], $res['Data']['MaxAmount'], $_SESSION ['token'], $promoCode, $culture );
										// если заявка передана успешно:
										if ($res ['Success']) {
											$_SESSION ['loan'] = $res;
											unset ( $_SESSION ['PromoCode'] );
											if ($_SESSION['sat_id']) {
												if ($_SESSION['api']['paySystem'] == 'WayForPay') {
													header ( "Location: " . $modx->makeUrl ( 321 ) );
												} elseif ($_SESSION['api']['paySystem'] == 'Tranzzo') {
													header ( "Location: " . $modx->makeUrl ( 335 ) );
												}
											} elseif ($_SESSION['mob_id']) {
												if ($_SESSION['api']['paySystem'] == 'WayForPay') {
													header ( "Location: " . $modx->makeUrl ( 332) );
												} elseif ($_SESSION['api']['paySystem'] == 'Tranzzo') {
													header ( "Location: " . $modx->makeUrl ( 653 ) );
												}
											} else {
												if ($_SESSION['api']['paySystem'] == 'WayForPay') {
													header ( "Location: " . $modx->makeUrl ( 157 ) );
												} elseif ($_SESSION['api']['paySystem'] == 'Tranzzo') {
													header ( "Location: " . $modx->makeUrl ( 181 ) );
												}
											}
											die;
										}
	
									} else {
										$res = $resCheck;
										// модалка для сообщений:
										// $modx->setPlaceholder('showMessage', 'error');
										// $modx->setPlaceholder('showMessageTitle', '[#993#]');	// Ошибка регистрации
									}
								}
								$_SESSION ['res'] = $res;
							}
							
						} else {
							if ($_SESSION['sat_id']) {
								
							} elseif ($_SESSION['mob_id']) {
								header ( "Location: " . $modx->makeUrl ( 328 ) );
							} else {
								header ( "Location: " . $modx->makeUrl ( 5 ) );
								// $res['error'] = 100; // нет входных данных
								// $_SESSION['res'] = $res;
							}
							die;
						}
						
						$modx->setPlaceholder ( 'flagForm', '3' ); // открыть форму 3
						// header("Location: " . $modx->makeUrl(2));
					}
				} else {
					$_SESSION ['reg2'] = $res;
					
					if ($post ['formID'] == '2')
						$modx->setPlaceholder ( 'flagForm', '2' ); // открыть форму 2
					if ($post ['formID'] == '3')
						$modx->setPlaceholder ( 'flagForm', '3' ); // открыть форму 3
				}
			} else {
				$_SESSION ['res'] ['error'] = 5; // 005 token отсутствует
				$modx->setPlaceholder ( 'flagForm', '0' ); // открыть форму 0 (редактирование логина)
				// для возврата данных на форму:
				// $js .= '$("[name=\'reg[phone]\']").val("' . $post['phone'] . '");';
			}
			break;
		}
		
		// Реструктуризация заказ (старая):
		if (isset($_POST['restruct']) && (0 < count ( $_POST ['restruct'] ))) { // пришли данные с формы "Мой кредит Реструктуризация
			
			if (!$_SESSION ['token'] || !$_SESSION ['credit']) {
				if ($_SESSION['sat_id']) {
					
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl(323));
				} else {
					header ( "Location: " . $modx->makeUrl(1));
				}
				die;
			}
			
			$post = $api->preprocessing ($_POST ['restruct']);

			if ($post['id'] && ((int) $_SESSION ['credit']['DaysPastDue'] > 0)) {
				$res = $api->makeRestructure($_SESSION ['credit']['Id'], $post['id']);	// заказ реструктуризации
				if (!$res['Success']) {
					$_SESSION ['res'] = $res;
				}
			}
			break;
		}
		
		// личный кабинет. Реструктуризация заказ:
		if (isset($_POST['restructuring']) && (0 < count ( $_POST ['restructuring'] ))) { // пришли данные с формы "Реструктуризация"
		    
		    if (!$_SESSION ['token'] || !$_SESSION ['credit']) {
		        if ($_SESSION['sat_id']) {
		            
		        } elseif ($_SESSION['mob_id']) {
		            header ( "Location: " . $modx->makeUrl(323));
		        } else {
		            header ( "Location: " . $modx->makeUrl(1));
		        }
		        die;
		    }
		    
		    $post = $_POST ['restructuring'];
		    
		    // запрос на выбранный тип реструктуризации:
		    if ($post ['href'] && $post ['restruct_id']) {
		        $res = $api->makeRestructure($_SESSION ['credit']['Id'], (int) $post['restruct_id']);	// заказ реструктуризации
		        // переход на оплату реструктуризации:
		        if ($res['Success'] && $post['amount']) {
		            $_SESSION ['cred_id'] = $_SESSION ['credit']['Id'];
		            $_SESSION ['credit']['amountToPay'] = $post['amount'];
		            header ( "Location: " . $modx->makeUrl ( (int) $post ['href'] ) );
		            die;
		        }
		    }
		    
		    break;
		}
		
		// Отзывы - запись:
		if (isset($_POST['reviews']) && (0 < count ( $_POST ['reviews'] ))) { // пришли данные с формы "Контакты"
			$post = $api->preprocessing ($_POST ['reviews']);

			//die(print_r($post, true));
			
			// это модератор:
			$isModerator = isModerator('ReviewsModerators');
			
			$reviewTable = new ReviewTable();
			
			// если уже есть id:
			if (($post['id']) && ($post['id'] !== 0) && $isModerator) {
				
				// поиск записи:
				if ($reviewTable->findById((int) $post['id'])) {

					// удалить запись:
					if($post['forDelete']) {
						if (!$reviewTable->delete()) $_SESSION['res']['error'] = 323;	// Изменить отзыв не удалось
						break;
					}
					
					$fields = [];
					if ($post['name']) $fields['name'] = $post['name'];
					if ($post['review']) $fields['review'] = $post['review'];
					if ($post['rating']) $fields['rating'] = $post['rating'];
					if ($post['answer']) $fields['answer'] = $post['answer'];
					// if ($post['login']) $fields['login'] = $post['login'];
					$fields['status'] = ($post['status'])? 1 : 0;
					// if ($post['email']) $fields['email'] = $post['email'];
					if ($post['moderator'])	$fields['moderator'] = $post['moderator'];
					if ($login) $fields['moder_login']= $login;
					//if ($post['date_created']) $fields['date_created']= $post['name'];
					$fields['date_updated']= date('Y-m-d H:i:s');

					if (!$reviewTable->update($fields)) $_SESSION['res']['error'] = 323;	// Изменить отзыв не удалось
					
				} else {
					$_SESSION['res']['error'] = 3;	// 003 ошибка при работе с БД
				}

			} elseif (!$post['id']) {
				
				$fields = [];
				if ($post['name']) $reviewTable->name = $post['name'];
				if ($post['review']) $reviewTable->review = $post['review'];
				if ($post['rating']) $reviewTable->rating = $post['rating'];
				// if ($post['answer']) $reviewTable->answer = $post['answer'];
				if ($login) $reviewTable->login = $login;
				$reviewTable->status = 0;
				// if ($post['email']) $reviewTable->email = $post['email'];
				// if ($post['moderator']) $reviewTable->moderator = $post['moderator'];
				$reviewTable->moderator = $config["ReviewsModeratorName"];
				//if ($login) $fields['moder_login']= $login;
				$reviewTable->date_created = date('Y-m-d H:i:s');
				$reviewTable->date_updated = date('Y-m-d H:i:s');
				
				if (!$reviewTable->insert()) $_SESSION['res']['error'] = 322;	// Отправить отзыв не удалось
				
				// отправка почты:
				if ($post ['name'] && $post ['email'] && $post ['review']) {
					
					$toEmail = [
							'sender' => 'страницы отзывов',
							'fromName' => $post ['name'],
							'fromEmail' => $post ['email'],
							'message' => "<br>Выбранный рейтинг: " . $post ['rating'] . "<br>" . $post ['review'],
							'MailReviews' => true,
					];
					$res = $api->sendEmailToSupport ( $toEmail );
					$_SESSION ['email'] = $res;
				} else {
					unset ( $_SESSION ['email'] );
				}
			}
			
			break;
		}

		// Отправка email в MyCredit, например, саппорту:
		if (isset($_POST['sendMail']) && (0 < count ( $_POST ['sendMail'] ))) {
		    $post = $_POST ['sendMail'];
		    if ($post ['sername'] && $post ['email'] && $post ['message']) {
		        $toEmail = [
		            'fromName' => $post ['sername'],
		            'fromEmail' => $post ['email'],
		            'message' => $post ['message']
		        ];
		        // пришли данные с формы "Контакты"
		        if (isset($post ['to'] )){
		            switch ($post ['to']) {
		                case 'support':
		                    $recipient = $config['email_support']; // email для клиентской поддержки
		                    break;
		                case 'marketing':
		                    $recipient = $config['email_manager']; // email для менеджера
		                    break;
		                case 'hr':
		                    $recipient = $config['email_hr']; // email для HR
		                    break;
		                default :
		                    $recipient = $config['email_dispatch']; // email  для корреспонденции
		            }
		            $res = $api->sendEmailToMaintenance($toEmail, $recipient);
		        } else {// с других страниц
		          $res = $api->sendEmailToSupport($toEmail);
		        }
		        $_SESSION ['email'] = $res;
		    } else {
		        unset ( $_SESSION ['email'] );
		    }
		    header ( "Location: " . $modx->makeUrl ( 9 ) );
		    die ();
		    break;
		}
		
		// Отправка email с заглушки :) :
		$resFlug = 0;
		if (isset($_POST['sendMailPartner']) && (0 < count ( $_POST ['sendMailPartner'] ))) { // пришли данные с формы "Партнеры"
			$post = $_POST ['sendMailPartner'];
			if ($post ['sername'] && $post ['phone']  && $post ['email']) {
				
				$toEmail = [
						'sender' => 'партнера',
						'fromName' => $post ['sername'],
						'fromEmail' => $post ['email'],
						'message' => "<br>Телефон: " . $post ['phone'],
						'MailPartner' => true,
				];
				$res = $api->sendEmailToManager ( $toEmail );
				$_SESSION ['email'] = $res;
				$resFlug = 1;
			} else {
				unset ( $_SESSION ['email'] );
			}
			if ($resFlug == 1) {
				$modx->setPlaceholder('formResult', 'success'); //результат проверки формы
				break;
			} else {
				$modx->setPlaceholder('formResult', ' no success'); //результат проверки формы
			}
			header ( "Location: " . $modx->makeUrl ( 334 ) );
			die ();
		}
		
		// Отправка email отзыв:
		if (isset($_POST['sendMailReviews']) && (0 < count ( $_POST ['sendMailReviews'] ))) { // пришли данные с формы "Отзывы"
			$post = $_POST ['sendMailReviews'];
			if ($post ['sername'] && $post ['email'] && $post ['message']) {
				
				$toEmail = [
						'sender' => 'страницы отзывов',
						'fromName' => $post ['sername'],
						'fromEmail' => $post ['email'],
						'message' => "<br>Выбранный рейтинг: " . $post ['rating'] . "<br>" . $post ['message'],
						'MailReviews' => true,
				];
				$res = $api->sendEmailToSupport ( $toEmail );
				$_SESSION ['email'] = $res;
			} else {
				unset ( $_SESSION ['email'] );
			}
			header ( "Location: " . $modx->makeUrl ( 228 ) );
			die ();
			break;
		}
		
		// Верификация карты: данные, пришедшие от платежной системы
		if (isset ( $_GET ['verify'] )) {
			
			$res = $api->resVerify ( $_POST ); // обработка ответа платежной системы
			$error = $res ['error'];
			if ($error == 777) {
				// данные, полученные от платежной системы, записываются в методе $api->resVerify
			}
			
			echo $res ['response'];
			die ();
		}
		
		break;
	
	case "OnLoadWebDocument" :
		
	    // файл инициализации параметров для вывода на странице: 
		require MODX_BASE_PATH . '/DesignAPI/classes/loadWebInit.php';
		require_once MODX_BASE_PATH . '/DesignAPI/errors.php';
		
		// if (count($_POST))
		// foreach($_POST as $key => $value)
		// $modx->documentObject['post_'.$key] = strip_tags($value);
		
		// если сайт остановлен:
		if ((!$websiteActive || ($config['websiteActive'] != 1)) && ($modx->documentIdentifier != 225)) { 
			if ($_SESSION['sat_id']) {
				header ( "Location: " . $modx->makeUrl(320));
			} elseif ($_SESSION['mob_id']) {
				header ( "Location: " . $modx->makeUrl(323));
			} else {
				header ( "Location: " . $modx->makeUrl(225));
			}
			break;
		}
		
		// Активность TKLemder-а:
		$modx->documentObject['TKLenderActive'] = $config['TKLenderActive']; 
		// если попытка войти на страницы, требующие авторизации, при отключенном TKLemder:
		if (!$config['TKLenderActive']) {
			unset ( $_SESSION ['token'] );
			if (in_array($modx->documentIdentifier, [2, 3, 4, 5, 22, 33, 40, 87, 17, 23, 35, 130, 157, 181, ])) {
				if ($_SESSION['sat_id']) {
					header ( "Location: " . $modx->makeUrl(320));
				} elseif ($_SESSION['mob_id']) {
					header ( "Location: " . $modx->makeUrl(323));
				} else {
					header ( "Location: " . $modx->makeUrl(1));
				}
				break;
			}
		}
				
		if (count ( $_GET )) {
			foreach ( $_GET as $key => $value )
				$modx->documentObject ['get_' . $key] = (is_string ( $value )) ? strip_tags ( $value ) : $value;
			
			// получаем строку GET-параметров:
			$url = $_SERVER['REQUEST_URI'];
			preg_match('/^([^?]+)(\?.*?)?(#.*)?$/', $url, $matches);
			if (isset($matches[2])) {
				$_SESSION['urlParam'] = $matches[2];
			} else {
				$_SESSION['urlParam'] = '';
			}
		}
		$modx->setPlaceholder('urlParam', ($_SESSION['urlParam']) ? : '');
		$modx->setPlaceholder('urlParamForHref', '');
		
		// обрабатываем параметры сателитов:
		if ($_GET['sat_id']) {
			$_SESSION['sat_id'] = $_GET['sat_id'];
			
			if ($_GET['money'])
				$_SESSION ['orderCredit'] ['money-value'] = $_GET['money'];
			if ($_GET['days'])
				$_SESSION ['orderCredit'] ['day-value'] = $_GET['days'];
			
			$modx->setPlaceholder ( 'flagForm', '0' );
			unset($_SESSION ['codeReg']);
			unset($_SESSION ['token']);
			unset($_SESSION ['res']);
			unset($_SESSION ['res1']);
			unset($_SESSION ['res_email']);
			
			// удаляем куки других партнеров (еще нужно посмотреть текст вначале case "OnLoadWebDocument"):
			Affiliate::deleteCookiesExcept("Satellite", $https);
			
			setcookie ( "Satellite", $_GET['sat_id'], time () + 86400 * $daysExpires, '/', $_SERVER['SERVER_NAME'], $https, true);
			// переходим по ссылке, чтобы обновились Cookie:
			header ( "Location: " . $modx->makeUrl ( $modx->documentIdentifier) );
			die;
		}
		if ($_SESSION['sat_id']) $modx->documentObject['sat_id'] = $_SESSION['sat_id'];
		// перенесено из affiliate по причине того, что нужно заходить личный кабинет:
		if ($_GET['utm_source']) {
			if (substr($_GET['utm_source'], 0, 4) === 'sat_') {
			
				// http://dev.mycredit.com.ua/ru/?utm_source=sat_1
				
				$sat_id = substr($_GET['utm_source'], 4);
				$daysExpires = $_SESSION ['initCRM'] ['affiliate'] ['Satellite'] ['daysExpires'];
				// удаляем куки других партнеров (еще нужно посмотреть текст вначале case "OnLoadWebDocument"):
				Affiliate::deleteCookiesExcept("Satellite", $https);
				
				unset($_SESSION['sat_id']);
				// $_SESSION['sat_id'] = $sat_id;

				setcookie ( "Satellite", $sat_id, time () + 86400 * $daysExpires, '/', $_SERVER['SERVER_NAME'], $https, true);
				// переходим по ссылке, чтобы обновились Cookie:
				header ( "Location: " . $modx->makeUrl ( $modx->documentIdentifier) );
				die;
				
				// подключаем логирование:
				$log = new Log ( 'affil_' );
				$strlog = "AFFILIATE Satellite METHOD: AFFILIATE "
						. "\nsat_id = " . $sat_id;
				$log->write ( $strlog );
			}
		}
		// номер сателита (используется для лендинга):
		$modx->documentObject ['ConfSatelliteId'] = ($config['Satellite_id']) ? : 0;
		
		// обрабатываем параметры мобильной версии:
		if ($_GET['mob_id']) {
			$_SESSION['mob_id'] = $_GET['mob_id'];
		}
		if ($_SESSION['mob_id']) $modx->documentObject['mob_id'] = $_SESSION['mob_id'];
		
		// обработка информации "Откуда пришел пользователь на сайт":
		$referer = $_SERVER['HTTP_REFERER'];
		//if (!$referer) {
			// $_SESSION['referer'] = [];
		//} else
		if (($referer || $_GET ['utm_source'] || $_GET ['admitad_uid'] || $_GET ['afclick'] || $_GET ['gclid'] || $_GET ['sat_id']) 
				&& (!strpos($referer, $_SERVER['HTTP_HOST'])) && (!strpos($referer, 'wayforpay.com'))) {
					
			$_SESSION['referer'] = [];
			$_SESSION['referer']['url'] = ($referer) ? : (($_GET ['utm_source']) ? : (($_GET ['admitad_uid']) ? 'admitad' : 'loangate'));
			
			if ($_GET ['utm_source']) {
				$_SESSION['referer']['utm_source'] = $_GET ['utm_source'];
			}
			if ($_GET ['utm_campaign']) {
				$_SESSION['referer']['utm_campaign'] = $_GET ['utm_campaign'];
			}
			if ($_GET ['utm_medium']) {
				$_SESSION['referer']['utm_medium'] = $_GET ['utm_medium'];
			} else {
				if (($_GET ['utm_source'] == 'salesdoubler') || ($_GET ['utm_source'] == 'linkprofit')){
					$_SESSION['referer']['utm_medium'] = 'cpa';
				}
			}
			if ($_GET ['admitad_uid']) {
				$_SESSION['referer']['utm_source'] = 'admitad';
				$_SESSION['referer']['utm_medium'] = 'cpa';
			}
			if ($_GET ['gclid']) {
				$_SESSION['referer']['utm_source'] = 'google';
				$_SESSION['referer']['utm_medium'] = 'cpc';
			}
			if ($_GET ['sat_id']) {
				$_SESSION['referer']['utm_source'] = 'sat_' . $_GET['sat_id'];
				$_SESSION['referer']['utm_medium'] = 'cpa';
			}
		}
				
		// удаляем куки "старых" партнеров, если пришли от нового партнера:
		if ($_GET ['utm_source'] || $_GET ['admitad_uid']) {
			
		    Affiliate::deleteOldCookies($modx);   // удаляет старые куки
		    
			// подключаем логирование:
			$log = new Log ( 'affil_' );
			$strlog = "UTM_SOURCE удаляем куки партнеров. METHOD: DELETE_AFFILIATE " 
					. (($modx->documentObject ['get_utm_source']) ? "\nutm_source = " . $modx->documentObject ['get_utm_source'] : "")
					. (($modx->documentObject ['get_admitad_uid']) ? "\nadmitad_uid = " . $modx->documentObject ['get_admitad_uid'] : "")
					. (($modx->documentObject ['get_afclick']) ? "\nloangate afclick = " . $modx->documentObject ['get_afclick'] : "");

			$strlog .= "\nREQUEST_URI = " . $_SERVER['REQUEST_URI'];
			$log->write ( $strlog );
		}

		// выход из системы:
		if ($_GET ['logout']) {
			
			$res = $api->sendUserInfo ( [ 
					'DateClosed' => date ( 'c' ), 
			] ); // отсылаем инфо
			
			$session_mob_id = $_SESSION['mob_id'];
			
			unset ( $_SESSION ['token'] );
			unset ( $_SESSION ['orderCredit'] );
			unset ( $_SESSION ['countCodeSend'] );
			unset ( $_SESSION ['sendCardDetails'] );
			unset ( $_SESSION ['codeRegConfirm'] );
			unset ( $_SESSION ['forgot'] );
			unset ( $_SESSION ['PromoCode'] );
			unset ( $_SESSION ['hiddenPromoCode'] );
			unset ( $_SESSION ['api'] );
			
			$modx->documentObject ['auth'] = null;
			// header("Cache-Control: no-store, no-cache, must-revalidate");
			// header('ETag: "10c24bc-4ab-457e1c1f"');
			if ($_SESSION['sat_id']) {
				header ( "Location: " . $modx->makeUrl(320));
			} elseif ($_SESSION['mob_id']) {
				header ( "Location: " . $modx->makeUrl(323) . "?mob_id={$_SESSION['mob_id']}");
			} else {
				header ( "Location: " . $modx->makeUrl(1));
			}
			
			// уничтожаем сессию
			session_unset ();
			session_destroy ();
			session_write_close ();
			setcookie ( session_name (), '', 0, '/' );
			// session_regenerate_id ( true );
			
			// $js .= 'console.log("window.location.reload(true)");';
			// $js .= 'window.location.reload(true);';
			die;
		}
		
		// передаем данные настроек кредита в формы:
		if (isset ( $_SESSION ['initCRM'] )) {
			$modx->documentObject ['minSum'] = $_SESSION ['initCRM'] ['credit_amount_min'];
			$modx->documentObject ['maxSum'] = $_SESSION ['initCRM'] ['credit_amount_max'];
			$modx->documentObject ['minDay'] = $_SESSION ['initCRM'] ['credit_days_min'];
			$modx->documentObject ['maxDay'] = $_SESSION ['initCRM'] ['credit_days_max'];
			$modx->documentObject ['step'] = $_SESSION ['initCRM'] ['credit_amount_step'];
			$modx->documentObject ['percent'] = $_SESSION ['initCRM'] ['credit_percent_main'];
			$modx->documentObject ['resolution'] = $_SESSION ['initCRM'] ['credit_time_resolution'];
			$modx->documentObject ['selectSum'] = $_SESSION ['initCRM'] ['credit_amount_select'];
			$modx->documentObject ['selectDay'] = $_SESSION ['initCRM'] ['credit_days_select'];
			$modx->documentObject ['maxlong'] = $_SESSION ['initCRM'] ['credit_long_max'];
			$modx->documentObject ['stepLong'] = $_SESSION ['initCRM'] ['credit_long_step'];
			$modx->documentObject ['intervalStopSlider'] = $_SESSION ['initCRM'] ['intervalStopSlider'];
			
			$lifeTimeToken = ( int ) $_SESSION ['initCRM'] ['lifeTimeToken'] * 60; // Время жизни токен в секундах
			
			$modx->documentObject ['ReCaptcha_sitekey'] = $_SESSION ['initCRM'] ['ReCaptcha_sitekey'];
			
			$modx->documentObject ['JivositeWidgetId'] = $_SESSION ['initCRM'] ['JivositeWidgetId'];
			
			$modx->documentObject ['GoogleAnalyticsId'] = $_SESSION ['initCRM'] ['GoogleAnalytics_id'];
			$modx->documentObject ['GoogleKey'] = $_SESSION ['initCRM'] ['GoogleKey'];
			$modx->documentObject ['GoogleGTM'] = $_SESSION ['initCRM'] ['GoogleGTM'];
			
			$modx->documentObject ['YandexMetrikaId'] = $_SESSION ['initCRM'] ['YandexMetrikaId'];
			$modx->documentObject ['FacebookPixelId'] = $_SESSION ['initCRM'] ['FacebookPixelId'];
			$modx->documentObject ['VK_id'] = $_SESSION ['initCRM'] ['VK_id'];
			$modx->documentObject ['VK_r'] = $_SESSION ['initCRM'] ['VK_r'];
			$modx->documentObject ['ismatlab_sys'] = $_SESSION ['initCRM'] ['ismatlab_sys'];
			$modx->documentObject ['HotjarId'] = $_SESSION ['initCRM'] ['HotjarId'];
			$modx->documentObject ['PW_websiteId'] = $_SESSION ['initCRM'] ['PW_websiteId'];
			
			// для нестандартных значений калькулятора:
			$modx->documentObject ['maxSum_main1'] = '1500';
			$modx->documentObject ['maxSum_landing'] = '3000';
			$modx->documentObject ['minDay_landing'] = '14';
			$modx->documentObject ['maxDay_landing'] = '90';
				
			/*
			 * $modx->documentObject['maxSum_main'] = '800';
			 * $modx->documentObject['minDay_superMain'] = 45;
			 * $modx->documentObject['maxDay_superMain'] = 50;
			 */
		} else {
			$modx->documentObject ['minSum'] = 100;
			$modx->documentObject ['maxSum'] = 3000;
			$modx->documentObject ['minDay'] = 1;
			$modx->documentObject ['maxDay'] = 30;
			$modx->documentObject ['step'] = 50;
			$modx->documentObject ['percent'] = 1.62;
			$modx->documentObject ['resolution'] = 15;
			$modx->documentObject ['selectSum'] = 100;
			$modx->documentObject ['selectDay'] = 1;
			$modx->documentObject ['maxlong'] = 10;
			$modx->documentObject ['stepLong'] = 1;
			$modx->documentObject ['intervalStopSlider'] = 5;
				
			$lifeTimeToken = 0;
		}
		// Проверяем время жизни token:
		if (isset ( $_SESSION ['token'] ) && ((time () - $_SESSION ['tokenTime']) >= $lifeTimeToken)) {
			unset ( $_SESSION ['token'] );
		}
		
		if(in_array($modx->documentIdentifier, [1,])) unset($_SESSION['hiddenPromoCode']);
		
		// если была аутентификация:
		if (isset ( $_SESSION ['token'] )) {
			// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
			$res = [];
			$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
			$res ['Data'] = $_SESSION['api']['client'];
			$_SESSION ['client'] = $res;
			if ($res ['Success']) {
				$modx->documentObject ['firstName'] = '';
				$modx->documentObject ['lastName'] = '';
				$modx->documentObject ['thirdName'] = '';
                                
                                if ($res['Data']['LastName']) {
                                    $modx->documentObject ['lastName'] = $res['Data']['LastName'];
                                    $modx->documentObject ['fullName'] = $modx->documentObject['lastName'];
                                }
                                
                                if ($res['Data']['FirstName']) {
                                    $modx->documentObject ['firstName'] = $res['Data']['FirstName'];
                                    $modx->documentObject ['fullName'] .= ' ' . $modx->documentObject['firstName'];
                                }
                                
                                if ($res['Data']['MiddleName']) {
                                    $modx->documentObject ['thirdName'] = $res['Data']['FirstName'];
                                    $modx->documentObject ['fullName'] .= ' ' . $modx->documentObject['firstName'];
                                }
                                
				$modx->documentObject ['firstNameRepl'] = str_replace('`', "′", $modx->documentObject ['firstName']); // с заменой символов
				$modx->documentObject ['fullNameRepl'] = str_replace('`', "′", $modx->documentObject ['fullName']); // с заменой символов
                                
				$modx->documentObject ['clientId'] = ($res ['Data'] ['Id']) ? : '';
				// сегодня ДР:
				if(date( 'dm', strtotime( $res ['Data'] ['BirthDate'])) === date('dm')) {
				    $modx->documentObject ['isBirthDate'] = 1;  // удалить
				}
				$client = $res['Data'];
			} else {
				$client = [];
			}
			// узнаем, есть ли непогашеные кредиты:
			
			// Получаем список кредитов:
			// условие для принудительной загрузки списка кредитов:
			if($_SESSION ['isPayForProlongation_test']) { // пока отключено "_test"
				$res = $api->getCustomerLoans ( $_SESSION ['token'] );
				if($res ['Success']) {
					$_SESSION['api']['credits'] = $res ['Data'];
				}
			} else {
				// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
				$res = [];
				$res ['Success'] = (is_array($_SESSION['api']['credits']) && $_SESSION['api']['credits'] !== []) ? true : false;
				$res ['Data'] = $_SESSION['api']['credits'];
				$_SESSION ['res'] = $res;
			}
			
			$bonuses = [];   // изначально бонусы
			
			if ($res ['Success']) {
				if (isset ( $_SESSION ['res'] ['Data'] )) {
					$credits = (is_array ( $_SESSION ['res'] ['Data'] )) ? $_SESSION ['res'] ['Data'] : [ ];
					// проверим статусы кредитов
					$modx->documentObject ['isCreditNotClosed'] = '0';
					foreach ( $credits as $key => $value ) {
						if ($value ['Status'] == 'Active') {
							$modx->documentObject ['is_payment_sent'] = 'enabled';
						}
						if (in_array($value['Status'], ['Active', 'PastDue', 'Restructured'])) {
							$modx->documentObject ['isCreditNotClosed'] = '1';
						}
					}
					// Запишем, повторник, или нет ()
					$modx->documentObject ['isRepeated'] = (count($credits) > 1) ? '1' : '0';
					
					// заполняем массив api данными о кредите:
					$_SESSION['api']['credits'] = $credits;
					
					// получаем бонусы по кредитам:
					$bonuses = getBonusesForLoans($credits, $client);
					foreach ($bonuses as $key => $value) {
						$modx->documentObject[$key] = $value;
					}
				}
			} else {
				$modx->documentObject ['isRepeated'] = '0';
				$modx->documentObject ['isCreditNotClosed'] = '0';
			}
			
			// дополнительный расчет нотификаций для шапки калькулятора:
			if (in_array($modx->documentIdentifier, [1,])) {
			    $oldNotifyCalculatorType = $modx->documentObject ['notifyCalculatorType'];
			    $resNotify = Notify::getCalculatorType($_SESSION['notify'], $oldNotifyCalculatorType, $bonuses);
			    $modx->documentObject ['notifyCalculatorType'] = $resNotify['notifyCalculatorType'];
			    // оправим уведомление о показе:
			    if ($resNotify['notifyId']) {
			        $res = $api->notifyShown ( $_SESSION ['token'], $resNotify['notifyId'] ); // прочитать данные пользователя
			    }
			}
			
			// дополнительный расчет нотификаций для всплывающих уведомлений popup:
			$notifiesClosed = (isset($_SESSION['notifiesClosed'])) ? $_SESSION['notifiesClosed'] : [];   // список закрытых юзером нотификаций
			$notifies = (isset($_SESSION['notify'])) ? $_SESSION['notify'] : [];  // список нотификаций
			foreach ($notifies as $key => $notify) {
			    // если нотификация в списке закрытых, то удаляем ее из массива
			    if (in_array($notify['Id'], $notifiesClosed))
			        unset($notifies[$key]);
			}
			$oldNotifyPopupType = $modx->documentObject ['notifyPopupType'];
			$resNotify = Notify::getPopupType($notifies, $oldNotifyPopupType);
			$modx->documentObject ['notifyPopupType'] = $resNotify['notifyPopupType'];
			$modx->documentObject ['notifyPopupId'] = $resNotify['notifyId'];
			// отправим уведомление о показе:
		    if ($resNotify['notifyId']) {
		        $res = $api->notifyShown ( $_SESSION ['token'], $resNotify['notifyId'] ); // показали нотификацию
		    }
		    
		    // дополнительный расчет нотификаций для модалок в ЛК:
		    if (in_array($modx->documentIdentifier, [4, 5, 7, 40])) {
		        $oldNotifyThematicByLoanType = $modx->documentObject ['notifyModalType'];
		        $resNotify = Notify::getModalTypeForLK($_SESSION['notify'], $oldNotifyThematicByLoanType);
		        $modx->documentObject ['notifyModalType'] = $resNotify['notifyModalType'];
		        $modx->documentObject ['notifyModalId'] = $resNotify['notifyId'];
		        // отправим уведомление о показе:
		        if ($resNotify['notifyId']) {
		            $res = $api->notifyShown ( $_SESSION ['token'], $resNotify['notifyId'] ); // прочитать данные пользователя
		        }
		    }
		    
		    // дополнительный расчет нотификаций для модалок в Мои Карты:
		    if (in_array($modx->documentIdentifier, [22, 26, 181, 157])) {
		        $notifiesClosed = (isset($_SESSION['notifiesClosed'])) ? $_SESSION['notifiesClosed'] : [];   // список закрытых юзером нотификаций
		        $notifies = (isset($_SESSION['notify'])) ? $_SESSION['notify'] : [];  // список нотификаций
		        
		        // костыль для акции Visa; изначально предполагалось, что будем получать признак с бэка; решили, что нет.
		        $notifies[] = [
		            'Id' => 27,
		            'Notification' => 27,
		            'Handler' => Notify::HANDLER_MODAL
		        ];
		        
		        foreach ($notifies as $key => $notify) {
		            // если нотификация в списке закрытых, то удаляем ее из массива
		            if (in_array($notify['Id'], $notifiesClosed))
		                unset($notifies[$key]);
		        }
		        
		        $oldNotifyThematicByLoanType = $modx->documentObject['notifyModalType'];
		        $resNotify = Notify::getModalTypeForMyCards($notifies, $oldNotifyThematicByLoanType);
		        $modx->documentObject['notifyModalTypeMyCards'] = $resNotify['notifyModalTypeMyCards'];
		        $modx->documentObject['notifyModalIdMyCards'] = $resNotify['notifyId'];
		        // отправим уведомление о показе:
		        if ($resNotify['notifyId']) {
		            $res = $api->notifyShown($_SESSION ['token'], $resNotify['notifyId']); // прочитать данные пользователя
		        }
		    }
		    
		    if($_SESSION['api']['paySystem'] == 'Tranzzo') {
				$modx->documentObject ['excludeDocs'] = ',22';	// не показывать документы в меню
				$modx->documentObject ['credits_href'] = '130';	// страница оплаты
			} elseif($_SESSION['api']['paySystem'] == 'WayForPay') {
				$modx->documentObject ['excludeDocs'] = ',26';	// не показывать документы в меню
				$modx->documentObject ['credits_href'] = '35';	// страница оплаты
			} else {
				$modx->documentObject ['excludeDocs'] = '';		// не показывать документы в меню
				$modx->documentObject ['credits_href'] = '1';	// страница оплаты (заглушка)
			}
			$modx->documentObject ['paySystem'] = ($_SESSION['api']['paySystem']) ? : '';	// имя платежной системы

			// проверка заполнения данных, и карт:
			if(in_array($modx->documentIdentifier, [2, 4, 35, 130,])) {
            	
            	// проверка заполнения данных пользователя:
            	$resCheck = $api->checkCompleteUserData($_SESSION ['token'], $modx->config ['lang']);
                if ($resCheck ['Success']) {
            	    $modx->documentObject ['checkCompleteUserData'] = "1";
			
            	    unset($_SESSION ['res']);
            	} else {
            	    $modx->documentObject ['checkCompleteUserData'] = "0";
            	    $_SESSION ['res'] = $resCheck;
            	}
            	
            	// проверка верифицированной карты:
            	if ($_SESSION['api']['paySystem'] == 'Tranzzo') {
            	    $cards = ($_SESSION['api']['tranzzoCards']) ? : [];
            	    $flagVerify = false;
            	    foreach ($cards as $card) {
            	        if ($card['Verified']) {
            	            $flagVerify = true;
            	            break;
            	        }
            	    }
            	    if ($flagVerify) {
            	        $modx->documentObject ['checkCompleteUserCard'] = "1";
            	    } else {
            	        $modx->documentObject ['checkCompleteUserCard'] = "0";
            	        if ($resCheck ['Success']) {
            	            $_SESSION ['res']['error'] = 166;   // 'Вам необходимо обновить информацию в разделе ‘Мои карты‘,
            	        }
            	    }
            	} elseif ($_SESSION['api']['paySystem'] == 'WayForPay') {
            	    $cards = $_SESSION['api']['cards'];
            	    if ($cards[0]['verify']) {
            	        $modx->documentObject ['checkCompleteUserCard'] = "1";
            	    } else {
            	        $modx->documentObject ['checkCompleteUserCard'] = "0";
            	        if ($resCheck ['Success']) {
            	            $_SESSION ['res']['error'] = 166;   // 'Вам необходимо обновить информацию в разделе ‘Мои карты‘,
            	        }
            	    }
            	}
			}
			
			// запишем телефон, если с моб.приложения (возможно, в дальнейшем отключим эту запись):
			if ($_SESSION['mob_id']) {
				$phoneMob = ($_SESSION['api']['client']['Phone'])? : '';
				if ($phoneMob) {
					$dir = MODX_BASE_PATH . "/DesignAPI/logs/" . date('Y') . "/" . date('m') . "/";
					$fileMob = $dir . "mobPhone_" . date("Ymd") . ".log";
					// если нет строки с телефоном в файле:
					if (!existsStringInFile($fileMob, $phoneMob)) {
						$reportLogFile = fopen ( $fileMob, 'a' );
						if($reportLogFile) {
							fwrite ( $reportLogFile, "$phoneMob\r\n" );
							fclose ( $reportLogFile );
						}
					}
				}
			}
	
		} else {
			unset($_SESSION ['client']);
		}
		
		$promoCode = ($_SESSION['PromoCode']) ? : (($_SESSION['hiddenPromoCode']) ? : null);
		if (!$promoCode) {
			// проверяем, нужен ли скрытый промокод:
			switch ($modx->documentIdentifier) {
				// Студенты:
				case 173 :
					$promoCode = $promocodes['Students'];
					break;
			}
		}
		
		// Корректируем значения калькулятора:
		
		// получаем кредитный продукт:
		// $res = $api->getCreditProducts ($promoCode, $_SESSION ['token']); // получаем список продуктов
		if($_SESSION['api']['creditProducts']['Promocode'] === $promoCode) {
			$res = $_SESSION['api']['creditProducts'];
			$resNormal = ($_SESSION['api']['creditProductsNormal']) ? : [];  // кред.продукт без промокода
		} else {
			$res = $api->getCreditProducts($promoCode, $_SESSION ['token']); // получаем список продуктов
			if ($res ['Success']) {
				$_SESSION['api']['creditProducts'] = $res;
				$_SESSION['api']['creditProducts']['Promocode'] = $promoCode;
				$_SESSION['api']['creditProducts']['isToken'] = false;
			} else {
				$_SESSION['api']['creditProducts'] = [];
			}
		}
		if ($res ['Success']) {
		    $modx->documentObject ['minSum'] = $res['Data']['MinAmount'];
		    $modx->documentObject ['maxSum'] = $res['Data']['MaxAmount'];
		    $modx->documentObject ['minDay'] = $res['Data']['MinTerm'];
		    $modx->documentObject ['maxDay'] = $res['Data']['MaxTerm'];
		    $modx->documentObject ['percent'] = $res['Data']['InterestRate'];
		    // подготовим массив дочерних продуктов для фронта:
		    if ($res['Data']['ChildProducts']) {
		        $childProducts = [];
		        foreach ($res['Data']['ChildProducts'] as $value) {
		            $product['MinAmount'] = $value['MinAmount'];
		            $product['MaxAmount'] = $value['MaxAmount'];
		            $product['MinTerm'] = $value['MinTerm'];
		            $product['MaxTerm'] = $value['MaxTerm'];
		            $product['InterestRate'] = $value['InterestRate'];
		            $childProducts[] = $product;
		        }
		        $modx->documentObject ['ChildProducts'] = json_encode($childProducts);
		    }
		}
		
		// "старый" продукт без промокода:
		if ($resNormal ['Success']) {
		    $modx->documentObject ['percentNormal'] = $resNormal['Data']['InterestRate'];
		    // подготовим массив дочерних продуктов для фронта:
		    if ($resNormal['Data']['ChildProducts']) {
		        $childProducts = [];
		        foreach ($resNormal['Data']['ChildProducts'] as $value) {
		            $product['MinAmount'] = $value['MinAmount'];
		            $product['MaxAmount'] = $value['MaxAmount'];
		            $product['MinTerm'] = $value['MinTerm'];
		            $product['MaxTerm'] = $value['MaxTerm'];
		            $product['InterestRate'] = $value['InterestRate'];
		            $childProducts[] = $product;
		        }
		        $modx->documentObject ['ChildProductsNormal'] = json_encode($childProducts);
		    }
		} else {
		    $modx->documentObject ['percentNormal'] = -1;
		}
		
		// Получаем средний балл google stars:
		$star = new GoogleStars();
		$res = $star->getScore();
		$modx->documentObject ['score'] = $res->score; 
		$modx->documentObject ['countScore'] = $res->countScore;
		
		$modx->documentObject ['auth'] = ($_SESSION ['token']) ? : '';
		// $modx->documentObject['test_aaa'] = $modx->getPlaceholder('get_aaa');
		
		// min и max год рождения пользователя
		$modx->documentObject ['minYear'] = ( int ) date ( 'Y' ) - 75;
		$modx->documentObject ['maxYear'] = ( int ) date ( 'Y' ) - 18;
		
		// min и max год начала обучения пользователя в ВУЗе
		// $modx->documentObject ['minYearBeginLearn'] = ( int ) date ( 'Y' ) - 10;
		// $modx->documentObject ['maxYearBeginLearn'] = ( int ) date ( 'Y' );
		
		// если уже были выбраны сумма/дни:
		if (isset ( $_SESSION ['orderCredit'] )) {
			$modx->documentObject ['selectSum'] = $_SESSION ['orderCredit'] ['money-value'];
			$modx->documentObject ['selectDay'] = $_SESSION ['orderCredit'] ['day-value'];
		}
		
		// присутствует ли ReCaptcha в принципе:
		$modx->documentObject ['ReCaptcha_enabled'] = ($config['ReCaptcha_enabled'])?  : '0';
		// отключаем, если мобильное приложение:
		if ($_SESSION['mob_id'])
			$modx->documentObject ['ReCaptcha_enabled'] =  '0';
			
		// пишем в сессию массив посещенных страниц:
		$url = $modx->makeUrl ( $modx->documentIdentifier );
		$url = str_replace ( $modx->config ["site_url"], '', $url ); // удаляем Url сайта
		if (! isset ( $_SESSION ['UserInfo'] ['Data'] ['Pages'] ))
			$_SESSION ['UserInfo'] ['Data'] ['Pages'] = [ ];
		if (! stripos ( $url, '/404/' )) {
			$page = [
					'Page' => $url,
					'PageDate' => date('c'),
			];
			array_push ( $_SESSION ['UserInfo'] ['Data'] ['Pages'], $page);
		}
		
		// запись Cookie:
		$myCookie = new Cookies();
		$modx->documentObject ['cookieId'] = $myCookie->getId();
		
		// читаем рейтинг страницы:
		$pagesRatingTable = new PagesRatingTable();
		$res = $pagesRatingTable->findByPageId((int) $modx->documentIdentifier);
		if ($res) {
			$modx->documentObject ['pageRatingPositive'] = $res->positive_reviews;
			$modx->documentObject ['pageRatingNegative'] = $res->negative_reviews;
		} else {
			$modx->documentObject ['pageRatingPositive'] = 0;
			$modx->documentObject ['pageRatingNegative'] = 0;
		}
		// если рейтинг уже существует:
		if (isset($_SESSION['pageRating'][$modx->documentIdentifier])) {
			$modx->documentObject ['pageRatingExists'] = '1';
		} else {
			$modx->documentObject ['pageRatingExists'] = '0';
		}
		
		// если email не активирован, делаем признак в меню:
		if(isset($_SESSION['api']['client']['IsEmailVerifiedByCustomer']) && !$_SESSION['api']['client']['IsEmailVerifiedByCustomer'] && $modx->documentObject['parent'] == 3) {
		    $modx->setPlaceholder('warning_menu_5', 1);
		}
		
		// проверка на рабочее время:
		$timezone = new DateTimeZone('Europe/Kiev');
		$datetime = new DateTime('now', $timezone);
		$hour = (int) $datetime->format('H');
		$modx->documentObject ['isWorkTime'] = (($hour >= 22) || ($hour < 8)) ? '0' : '1';	// 1 - рабочее время

		// проверка наличия будущего обновления сайта:
		if (isset($config['isFutureUpdate']) && $config['isFutureUpdate'] == 1) {
		    $timeStartShowStr = date('Y-m-d ') . $config['futureUpdate_timeStartShow'];
		    $timeStartShow = DateTime::createFromFormat('Y-m-d H:i', $timeStartShowStr, $timezone);
		    $modx->setPlaceholder('isFutureUpdate', ($datetime > $timeStartShow) ? '1' : '0');
		    $modx->setPlaceholder('futureUpdate_start', $config['futureUpdate_start']);
		    $modx->setPlaceholder('futureUpdate_end', $config['futureUpdate_end']);
		} else {
		    $modx->setPlaceholder('isFutureUpdate', '0');
		}
		
		switch ($modx->documentIdentifier) {
			
			// Главная страница:
			case 1 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mob_id']);
				
			// Главная страница Mobil:
			case 323 :
				
			    if (isset($_POST ['auth']) && (0 < count ( $_POST ['auth'] ))) { // пришли данные с формы аутентификации
				
			    } else if ((isset($_POST ['orderCredit']) && (0 < count ( $_POST ['orderCredit'] ))) && (isset ( $_SESSION ['token'] ))) { // пришли данные с калькулятора
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					if (isset ( $_SESSION ['loan'] ))
						unset ( $_SESSION ['loan'] );
					
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl(320));
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl(327));
					} else {
						header ( "Location: " . $modx->makeUrl(2));
					}
					die;

				} else if (isset ( $_SESSION ['to_login'] )) {

				} else if (isset($_POST ['clientContactInfo']) && (0 < count ( $_POST ['clientContactInfo'] ))) {	// пришли данные с формы Сбора контактных данных клиента
					if (!$_SESSION ['clientContactInfo']['Success']) {
						$modx->documentObject ['Name'] = $_POST ['clientContactInfo']['Name'];
						$modx->documentObject ['Email'] = $_POST ['clientContactInfo']['Email'];
						$modx->documentObject ['Phone'] = $_POST ['clientContactInfo']['Phone'];
					}
					
				} else {
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					unset ( $_SESSION ['clientContactInfo']);
						// если токен есть, идти в Мои кредиты:
					// if (isset($_SESSION['token'])) header("Location: " . $modx->makeUrl(4));
				}
				
				if (isset ( $_SESSION ['orderCredit'] ) && ! isset ( $_SESSION ['token'] )) {
					// $js .= '$("#modal_auth").modal("show");';
				}
				// если кто-то не попал по назначению, так как не было аутентификации:
				if (isset ( $_SESSION ['to_login'] )) {
					unset ( $_SESSION ['to_login'] );
					// $js .= '$("#modal_auth").modal("show");';
					$js .= 'showModalAuth("");';
				}
				
				// если пришли данные по ссылке восстановления пароля:
				if (isset ( $_SESSION ['forgot'] ['token'] )) {
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl(320));
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl(326));
					} else {
						header ( "Location: " . $modx->makeUrl(23));
					}
					die;
				}
				
				// если запомнили логин:
				if ($_COOKIE ["rememberLogin"]) {
					$modx->documentObject ['phone'] = $_COOKIE ["rememberLogin"];
					$modx->documentObject ['checked'] = 'checked';
				} else {
					$modx->documentObject ['phone'] = '+380';
					$modx->documentObject ['checked'] = '';
				}
				
				// чистим код регистрации:
				unset ( $_SESSION ['codeReg'] );
				unset ( $_SESSION ['codeRegConfirm'] );
				// чистим ранее введенные временные личные данные пользователя:
				unset ( $_SESSION ['userData'] );
				// чистим данные для востановления пароля:
				unset ( $_SESSION ['forgot'] );
				unset ( $_SESSION ['Success'] );
				
				break;
			
			// оформить кредит:
			case 2 :
			// оформить кредит (моб.версия):
			case 488 :
				
				// echo "Форма Оформить кредит"; die;
				
				if (! $_SESSION ['token']) {
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				// проверка возможности сделать новую заявку:
				if ( !$api->checkToAddLoan ( $_SESSION ['token'])) {
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нельзя делать заявку - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 331 ) ); // если нельзя делать заявку - на Мои кредиты
					} else {
						header ( "Location: " . $modx->makeUrl ( 4 ) ); // если нельзя делать заявку - на Мой кредит
					}
				}
				
				$modx->documentObject ['PromoCode'] = ($_SESSION ['PromoCode']) ?: '';
				$modx->documentObject ['isAgreedUseMyData'] = ($client['IsAgreedUseMyData']) ? : '';
				
				if ((isset($_POST ['addCredit']) && (0 < count ( $_POST ['addCredit'] ))) && (! $_SESSION ['loan'] ['Success'])) {
					if ($_POST ['addCredit'] ['credit_id']) {
						$modx->documentObject ['credit_id'] = $_POST ['addCredit'] ['credit_id'];
					}
				} else {
				    if (isset($_POST ['addCredit']) && (0 < count ( $_POST ['addCredit'] ))) {
						$modx->documentObject ['credit_id'] = $_SESSION ['loan'] ['LoanId'];
					} else {
						unset ( $_SESSION ['loan'] );
					}
					// if (isset($_SESSION['res'])) unset($_SESSION['res']);
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					if (! $res ['Success']) {
					} else {
						// $_SESSION['cards'] = [['id' => 1, 'number' => '4111111111111111']]; //$res['cards'];
						// $_SESSION['tels'] = [['id' => 1, 'value' => '380671111111']]; //$res['tels'];
					}
				}
				
				break;
			
			// личный кабинет:
			case 3 :
				
				header ( "Location: " . $modx->makeUrl ( 5 ) );
				break;
			
			// личный кабинет. Мои кредиты:
			case 4 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mod_id']);
			// личный кабинет. Мои кредиты. Моб.версия
			case 331 :
			// личный кабинет. Мои бонусы. Моб.версия
			case 427 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
 					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				// интервал обновления страницы:
				$modx->documentObject ['interval_refresh_page'] = $_SESSION ['initCRM']['interval_refresh_page'];
				
				// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
				// $_SESSION ['res'] = $res;
				$res = [];
				// $res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
				$res ['Success'] = true;	// !!!
				$res ['Data'] = $_SESSION['api']['credits'];
				$_SESSION ['res'] = $res;
				
				if (! $res ['Success']) {
					$modx->documentObject ['error'] = 'Кредиты не найдены';
					
					$modx->documentObject ['credit_status'] = 'zero';
					$modx->documentObject ['amount'] = 0;
					$modx->documentObject ['days'] = 0;
					$modx->documentObject ['repay'] = 0;
					$modx->documentObject ['sumToPay'] = 0;
				} else {
					
					if (isset ( $_SESSION ['res'] ['Data'] )) {
						$credits = (is_array ( $_SESSION ['res'] ['Data'] )) ? $_SESSION ['res'] ['Data'] : [ ];
						
						// берем последний кредит из массива:
						if (count($credits) > 0) {
							$credit = end($credits);
							$credit_id = $credit ['Id'];
							$key_id = count($credits) - 1;

							// подставляем вместо последнего кредита более приоритетный:
							foreach ( $credits as $key => $value ) {
								
								if (in_array($value['Status'], ['Active', 'PastDue', 'Restructured'])) {
									$credit = $value;
									$credit_id = $credit ['Id'];
									$key_id = $key;
									break;
								}
								elseif (in_array($value['Status'], ['Approved', 'DisbursementInProgress', 'WaitingForAgreement',])) {
									$credit = $value;
									$credit_id = $credit ['Id'];
									$key_id = $key;
									break;
								}
							}			
						
						} else {
							$credit_id = 0;
							$key_id = null;
						}
						
					} else {
						$credit_id = 0;
						$credits = [ ];
						$modx->documentObject ['credit_status'] = 'zero';
						$modx->documentObject ['amount'] = 0;
						$modx->documentObject ['days'] = 0;
						$modx->documentObject ['repay'] = 0;
						$modx->documentObject ['sumToPay'] = 0;
					}
					
					$modx->documentObject ['credit_id'] = $credit_id;
					
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					$_SESSION ['client'] = $res;
					if ($res ['Success']) {
						$modx->documentObject ['firstName'] = ($res ['Data'] ['FirstName']) ? : '';
						// (с удалением апострофа):
						// $modx->documentObject ['firstName'] = str_replace('`', "′", $modx->documentObject ['firstName']);
						$modx->documentObject ['lastName'] = ($res ['Data'] ['LastName']) ? : '';
						$modx->documentObject ['thirdName'] = ($res ['Data'] ['MiddleName']) ? : '';

						$modx->documentObject ['StudentId'] = ($res ['Data'] ['AdditionalData'] ['StudentId']) ? : '';	// номер студенческого билета
						
					}
					
					// получить данные кредита
					if (isset ( $key_id )) {
						$credit = $credits [$key_id];
						$_SESSION ['credit'] = $credit;
						
						$modx->documentObject ['credit_status'] = $credit ['Status'];
						$modx->documentObject ['credit_status_original'] = $credit ['Status'];
						
//						$modx->documentObject['credit_status'] = 'PastDue'; // Для теста !!!
//						$credit['DaysPastDue'] = 40;							// Для теста !!!
//						$flagWarningRed = true;
                                                 
						$modx->documentObject ['PublicId'] = $credit ['PublicId']; // номер заявки/кредита
						$modx->documentObject ['amount'] = $credit ['Amount'];
						$modx->documentObject ['amountStr'] = number_format ( $credit ['Amount'], 2, '.', ' ' );
						$dateCreate = ($credit['DisbursementDate']) ? : $credit['CreationDate'];
						// срок:
						if ($credit ['NextPaymentDate']) {
						    $term = ( int ) ((strtotime ( substr ( $credit ['NextPaymentDate'], 0, 10 ) ) - strtotime ( substr ( $dateCreate, 0, 10 ) )) / 86400);
						    $termEndReal = ( int ) ((strtotime ( substr ( $credit ['NextPaymentDate'], 0, 10 ) ) - strtotime ( date ( 'Y-m-d' ) )) / 86400) - 0;
						} else {
						    $term = $credit['Term'];
						    $termEndReal = ( int ) ((strtotime ( substr ( $dateCreate, 0, 10 ) . " + {$term} days" ) - strtotime ( date ( 'Y-m-d' ) )) / 86400) - 0;
						}
						$modx->documentObject ['days'] = $term;
						// $modx->documentObject ['days'] = $credit ['Term'];
						$modx->documentObject ['daysTermStr'] = getDayLang ( $term, $modx->config ['lang'] );
						$modx->documentObject ['dateCreateStr'] = getDateLang ( date ( 'd.m.Y', strtotime ( $dateCreate) ), $modx->config ['lang'] );
						$modx->documentObject ['daysStr'] = getDayLang ( $term, $modx->config ['lang'] );
						// $modx->documentObject['dateEnd'] = getDateLang(date('d.m.Y', strtotime($credit['CreationDate']) + ((int) $credit['Term'] * 86400)), $modx->config['lang']);
						$modx->documentObject ['dateEnd'] = getDateLang ( date ( 'd.m.Y', strtotime ( $credit ['NextPaymentDate'] ) ), $modx->config ['lang'] );
						// количество дней до конца срока (с обнулением времени выдачи):
						// $termEndReal = (int) ((strtotime(substr($credit['CreationDate'], 0, 10)) + (int) $credit['Term'] * 86400 - strtotime(date('Y-m-d')))/86400) - 0;
						// $termEndReal = ( int ) ((strtotime ( substr ( $credit ['NextPaymentDate'], 0, 10 ) ) - strtotime ( date ( 'Y-m-d' ) )) / 86400) - 0;
						
						$termEnd = ($termEndReal > 0) ? $termEndReal : 0; // количество дней до конца срока >= 0
						$modx->documentObject ['termEnd'] = ( string ) $termEnd . " " . getDayLang ( $termEnd, $modx->config ['lang'] ); // количество дней до конца срока >= 0 со словом "дней"
						$modx->documentObject ['termEndReal'] = ( string ) $termEndReal; // реальное количество дней до конца срока
						// реальное количество дней до конца срока со словом "дней":
						$modx->documentObject ['termEndRealStr'] = ( string ) abs ( $termEndReal ) . " " . getDayLang ( $termEndReal, $modx->config ['lang'] );
						
						$modx->documentObject ['HasVerifiedCard'] = ($credit ['HasVerifiedCard']) ? '1' : '0'; // признак наличия верифицированной карты
						if ($modx->documentObject ['checkCompleteUserCard'] == '0') $modx->documentObject ['HasVerifiedCard'] = '0';
						    
						$modx->documentObject ['LastStatusChangeComment'] = ($credit ['LastStatusChangeComment']) ? : ''; // последний комментарий менеджера при смене статуса
						$modx->documentObject ['HasValidCustomerInfo'] = ($credit ['HasValidCustomerInfo']) ? '1' : '0'; // признак наличия всех заполненных обязательныъ полей
						
						$modx->documentObject ['OriginalAmount'] = $credit ['OriginalAmount']; // предыдущее значение суммы
						$modx->documentObject ['OriginalAmountStr'] = number_format ( $credit ['OriginalAmount'], 2, '.', ' ' ); // предыдущее значение суммы
						$modx->documentObject ['OriginalTerm'] = $credit ['OriginalTerm']; // предыдущее значение срока
						// предыдущее значение срока со словом "дней":
						$modx->documentObject ['OriginalTermStr'] = ( string ) ($credit ['OriginalTerm']) . " " . getDayLang ( $credit ['OriginalTerm'], $modx->config ['lang'] );
						
						if (($modx->documentObject ['HasVerifiedCard'] !== '1') || ! $credit ['HasValidCustomerInfo']) 
							$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту

						if (($credit ['Status'] === 'WaitingForApproval')
								|| (( in_array($credit ['Status'], ['Origination', 'WaitingForApproval', 'WaitingForAgreement']))
								&& (($credit ['OriginalAmount'] != $credit ['Amount']) || ($credit ['OriginalTerm'] != $credit ['Term']))))
							$modx->documentObject ['flagWarningGreen'] = "1"; // флаг предупреждения клиенту
							
						// обрабатываем информацию об оплате для сообщения:
						if ($_SESSION['toPay']['data']) {
							
							// статусы транзакции:
							define("PAY_SUCCESSFUL", 0);
							define("PAY_FAILED", 1);
							define("PAY_INPROGRESS", 2);
							define("PAY_DUPLICARED", 3);
							
							$flagWarningGreen = false;
							$flagWarningRed = false;
							
							if ($_SESSION['toPay']['credit']) {
								// проверка на разницу кредитов:
								if (array_diff_assoc($credit, $_SESSION['toPay']['credit']) != []) {
									unset($_SESSION['toPay']);
								}
								if ($_SESSION['toPay']['RepaymentId']) {
									// получаем транзакцию по кредиту
									// $resPay = $api->getPayment($_SESSION ['token'], $credit['Id'], $_SESSION['toPay']['RepaymentId']); // запрос конкретной транзакции
									$resPay = $api->getPayment($_SESSION ['token'], $credit['Id']); // запрос последней транзакции
									// возможно, нужно убирать признак процесса оплаты, или выставлять флаг:
									if ($resPay !== []) {
										// если транзакция успешно прошла
										if ($resPay['IsSuccessful']) { 
											unset($_SESSION['toPay']);
										}
										// если разница во времени:
										// [Date] => 2018-02-22T15:21:51.183
										elseif (((time() - strtotime($resPay['Date'])) > (60 * 60 * 4)) && ($resPay['Status'] !== PAY_FAILED)) {
											unset($_SESSION['toPay']);
										}
										// если ошибка:
										elseif ($resPay['Status'] === PAY_FAILED) {
											$flagWarningRed = true;
										}
										// если дубликат:
										elseif ($resPay['Status'] === PAY_DUPLICARED) {
											$flagWarningRed = true;
										}
										else {
											$flagWarningGreen = true;
										}
									}
								}
							} else {
								$_SESSION['toPay']['credit'] = $credit;
								$flagWarningGreen = true;
							}
							
							if($flagWarningRed) {
								$lang = $modx->config ['lang'];
								$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту
								$modx->documentObject ['flagPayRed'] = "1";
								$modx->documentObject ['payAmount'] = number_format ( $_SESSION['toPay']['data']['amount'], 2, '.', '' );
								$modx->documentObject ['payCard'] = $_SESSION['card']['cards'][(int) $_SESSION['toPay']['data']['card']]['Card'];
								$modx->documentObject ['payError'] = (isset($errorsStr[$lang][$resPay['Error']])) ? $errorsStr[$lang][$resPay['Error']] : $resPay['Error'];
							}
							elseif($flagWarningGreen) {
								$modx->documentObject ['flagWarningGreen'] = "1"; // флаг предупреждения клиенту
								$modx->documentObject ['flagPayGreen'] = "1";
								$modx->documentObject ['payAmount'] = number_format ( $_SESSION['toPay']['data']['amount'], 2, '.', '' );
								$modx->documentObject ['payCard'] = $_SESSION['card']['cards'][(int) $_SESSION['toPay']['data']['card']]['Card'];
							}
						}
							
							
						// считаем всего начислений:
						/*
						$totally = 0;
						foreach ( $credit ['Schedule'] as $key => $value ) {
							$totally += $value ['Total'];
						}
						*/
						$totally = ($credit ['NextPaymentAmount']) ? : $credit ['OutstandingBalance'];
						$modx->documentObject ['repay'] = number_format ( $totally, 2, '.', ' ' );
						// $modx->documentObject['repay'] = $credit['Amount'] + $credit['Schedule'][0]['Interest'] + $credit['Schedule'][0]['Fees'];
						
						$modx->documentObject ['sumToPay'] = number_format ( $totally, 2, '.', ' ' );
						// $modx->documentObject['sumToPay'] = + $credit['Amount'] - $credit['amount_payed'] + $credit['Schedule'][0]['Interest'] + $credit['Schedule'][0]['Fees'];
						
						$modx->documentObject ['CurrentDebt'] = ($credit ['CurrentDebt']) ? number_format( $credit ['CurrentDebt'], 2, '.', ' ' ) : 0;
						
						// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
						$res = $_SESSION['api']['cards'];	// получить список карт клиента
						$modx->documentObject ['credit_card'] = $res [0] ['number'];
						$reasonCode = $res [0] ['reasonCode'];
						if ($reasonCode && $reasonCode !== '1100') {
							$modx->documentObject ['cardReasonCode'] = $reasonCode; // есть проблемы
							$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту
						} else {
							$modx->documentObject ['cardReasonCode'] = '1100'; // всё ОК
						}
						
						// если была попытка оплаты перед пролонгацией:
						if ($_SESSION ['isPayForProlongation']) {
							$modx->documentObject ['isPayForProlongation'] = '1'; // есть проблемы
							$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту
							unset ( $_SESSION ['isPayForProlongation'] );
						}
						
						// проверка возможности сделать новую заявку:
						if ( $api->checkToAddLoan ( $_SESSION ['token'] )) {
							$modx->documentObject ['checkToAddLoan'] = "1";
						} else {
							$modx->documentObject ['checkToAddLoan'] = "0";
						}

						// расчитать, когда можно подавать повторную заявку:
						if (in_array($credit['Status'], ['Rejected',])) {
							$days = $_SESSION ['initCRM']['change_status_rejected'];	// Дней до смены статуса "Отклонен"
							$dateCreated = new DateTime($credit['CreationDate']);
							$dateCreated->modify('+' . $days . ' days');
							$dateToday = new DateTime();
							if ($dateCreated < $dateToday) {
								$modx->documentObject['credit_status'] = 'zero';
							} else {
								$modx->documentObject['date_resending'] = getDateLang($dateCreated->format('d.m.Y'), $modx->config ['lang']);
							}
						}
						
						// изменение псевдо-статуса в зависимости от даты:
						// истек срок согласования:
						if (in_array($credit['Status'], ['AgreementExpired',])) {
							$days = $_SESSION ['initCRM']['change_status_agrExpired'];	// Дней до смены статуса "Истек срок согласования"
							$dateCreated = new DateTime($credit['CreationDate']);
							$dateCreated->modify('+' . $days . ' days');
							$dateToday = new DateTime();
							if ($dateCreated < $dateToday) {
								$modx->documentObject['credit_status'] = 'zero';
							}
						}
						// закрыт:
						if (in_array($credit['Status'], ['Closed_Repaid', 'Closed_WrittenOff',])) {
							$days = $_SESSION ['initCRM']['change_status_closed'];	// Дней до смены статуса "Закрыт"					
							$dateCreated = new DateTime($credit['CreationDate']);
							$dateCreated->modify('+' . $credit['Term'] . ' days');
							$dateCreated->modify('+' . $days . ' days');
							$dateToday = new DateTime();
							if ($dateCreated < $dateToday) {
								$modx->documentObject['credit_status'] = 'zero';
							}
						}
						//
						
						// признак возможности пролонгации:
//						$modx->documentObject ['IsLoanCanBeRollovered'] =  ($credit['IsLoanCanBeRollovered']) ? '1' : '0';
                        $modx->documentObject['IsLoanCanBeRollovered'] = $api->isLoanCanBeRolloved($_SESSION ['token'], $credit_id);
						
						// пролонгация:
						
						$prolongations = [];
						
						if (in_array($credit['Status'], ['Active', 'PastDue', 'Rollover'])) {
						    $res = $api->getListProlongations($credit_id);
						    if ($res['Success'] && $res['Data']) {
					            $prolongations = $res['Data'];	// массив возможных пролонгаций
						    }
						}
						$modx->setPlaceholder('prolongationsIsEmpty', (count($prolongations) === 0) ? '1' : '0');   // признак пустого массива пролонгации
						$modx->setPlaceholder('prolongations', $prolongations);
						// $_SESSION['prolongations'] = $prolongations;
						
						// end пролонгация
						
						// реструктуризация:
						
						$modx->documentObject['DaysPastDue'] = ($credit['DaysPastDue']) ? : '0';		// дней просрочки
						$modx->documentObject['IsRestructured'] = ($credit['IsRestructured']) ? : '0';	// Признак реструктуризации
						
						$restructs = [];
						$partAmountNeedToPay = 0;	// сумма к оплате для возможности реструктурировать
						if ((int) $credit['DaysPastDue'] > 0) {
							$res = $api->getListRestructurings($credit_id);
							if ($res['Success'] && $res['Data']) {
								// если есть сумма на оплату для возможности реструктуризировать:
								if ($res['Data']['requiredPaymentAmountForRestructure']) {
									$partAmountNeedToPay = ($res['Data']['requiredPaymentAmountForRestructure']) ? : -1;	// сумма к оплате для возможности реструктурировать
									if ($partAmountNeedToPay > 0)
										$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту
								} else {
									$restructs = $res['Data'];	// массив возможных реструктуризаций
								}
							}
						}
						$modx->setPlaceholder('restructsIsEmpty', (count($restructs) === 0) ? '1' : '0');   // признак пустого массива реструктуризации
						$modx->setPlaceholder('restructs', $restructs);
						$modx->setPlaceholder('partAmountNeedToPay', number_format($partAmountNeedToPay, 2, '.', '' ));
						// $modx->setPlaceholder('credit', $credit);

						// end реструктуризация
                                                
                                                // передан в оценку для продажи
                                                $modx->documentObject['isGivenToEstimateForSale'] = false;
                                                
                                                if ($credit['Status'] == 'PastDue' && (int)$credit['DaysPastDue'] >= $api::DAYS_PAST_DUE_TO_ESTIMATE_FOR_SALE) {
                                                    $modx->documentObject ['flagWarningRed'] = '1';
                                                    $modx->documentObject['isGivenToEstimateForSale'] = true;
                                                }
                                                // end передан в оценку для продажи
						
						// продан:
                                                $modx->documentObject['IsSoldLoan'] = false;
                                                
						if ($credit['Status'] === 'Sold') {
                                                        $modx->documentObject['IsSoldLoan'] = true;
							$modx->documentObject['MessageForSoldLoan'] = ($credit['MessageForSoldLoan']) ? : '';
						}
                                                // end продан
						
						// - $credit['percent_payed'] + $credit['penalty'] - $credit['penalty_payed'] ;
						$card_id = 0; // $credit['credit_card'];
						              
						// ------ для теста
						//if ($credit_id == 10) { $modx->documentObject['credit_status'] = 'Active';}
						//if ($credit_id == 10) { $modx->documentObject['credit_status'] = 'WaitingForAgreement';}
						//if ($credit_id == 10) { $modx->documentObject['credit_status'] = 'NoContact';}
						// ------ для теста конец
						              
						// Ниже для совместимости дизайна и снипета credits:
						$_SESSION ['res'] ['credits'] = [ ];
						foreach ( $credits as $key => $value ) {
							$_SESSION ['res'] ['credits'] [$key] ['status'] = $value ['Status'];
							$_SESSION ['res'] ['credits'] [$key] ['PublicId'] = $value ['PublicId'];
							// считаем всего начислений:
							/*
							$totally = 0;
							$totallyInterest = 0;
							$totallyFees = 0;
							foreach ( $value ['Schedule'] as $keySchedule => $valueSchedule ) {
								$totally += $valueSchedule ['Total'];
								$totallyInterest += $valueSchedule ['Interest'];
								$totallyFees += $valueSchedule ['Fees'];
							}
							*/
							$_SESSION ['res'] ['credits'] [$key] ['amount'] = $value ['Amount'];
							// $_SESSION ['res'] ['credits'] [$key] ['amount_payed'] = $value ['Amount'] + $totallyInterest + $totallyFees - $totally;
							$_SESSION ['res'] ['credits'] [$key] ['amount_payed'] = 0;
							$_SESSION ['res'] ['credits'] [$key] ['percent'] = $value ['CurrentInterest'];
							$_SESSION ['res'] ['credits'] [$key] ['percent_payed'] = 0;
							$_SESSION ['res'] ['credits'] [$key] ['penalty'] = 0;
							$_SESSION ['res'] ['credits'] [$key] ['penalty_payed'] = 0;
							$_SESSION ['res'] ['credits'] [$key] ['CurrentDebt'] = $value ['CurrentDebt'];
							$_SESSION ['res'] ['credits'] [$key] ['NextPaymentAmount'] = ($value ['NextPaymentAmount']) ? : $value ['OutstandingBalance'];
							$dateCreate = ($value['DisbursementDate']) ? : $value['CreationDate'];
							$_SESSION ['res'] ['credits'] [$key] ['createDate'] = date ( 'd.m.Y', strtotime ( $dateCreate) );
							// срок кредита:
							// $_SESSION['res']['credits'][$key]['days'] = $value['Term'];
							if ($value ['NextPaymentDate']) {
								$_SESSION ['res'] ['credits'] [$key] ['days'] = ( int ) ((strtotime ( substr ( $value ['NextPaymentDate'], 0, 10 ) ) - strtotime ( substr ( $dateCreate, 0, 10 ) )) / 86400);
							} else {
								$_SESSION ['res'] ['credits'] [$key] ['days'] = $value ['Term'];
							}
							$_SESSION ['res'] ['credits'] [$key] ['longDate'] = '';
							$_SESSION ['res'] ['credits'] [$key] ['id'] = $value ['Id'];
							$_SESSION ['res'] ['credits'] [$key] ['IsLoanCanBeRollovered'] = $value ['IsLoanCanBeRollovered'];
						}
						
					} else {
						$modx->documentObject ['credit_status'] = 'zero';
						$modx->documentObject ['checkToAddLoan'] = '1';	// флаг - можно брать кредит
						$modx->documentObject ['HasVerifiedCard'] = "1"; // флаг предупреждения клиенту карта неверифицирована
						$modx->documentObject ['cardReasonCode'] = "1100"; // флаг предупреждения клиенту карта -ок
						
						$modx->setPlaceholder('restructs', []);
						$modx->setPlaceholder('partAmountNeedToPay', '0.00');
						
					}
					
					// проверка заполнения данных пользователя:
					$resCheck = $api->checkCompleteUserData($_SESSION ['token']);
					if (!$resCheck ['Success']) {
						$modx->documentObject ['flagWarningRed'] = "1"; // флаг предупреждения клиенту
						$modx->documentObject ['HasValidCustomerInfo'] = "0";	// // флаг заполнения данных
					}

					// расчет нотификации для Мой Кредит:
					$notifiesClosed = (isset($_SESSION['notifiesClosed'])) ? $_SESSION['notifiesClosed'] : [];   // список закрытых юзером нотификаций
					$notifies = (isset($_SESSION['notify'])) ? $_SESSION['notify'] : [];  // список нотификаций
					foreach ($notifies as $key => $notify) {
					    // если нотификация в списке закрытых, то удаляем ее из массива
					    if (in_array($notify['Id'], $notifiesClosed))
					        unset($notifies[$key]);
					}
					$oldNotifyMyCreditType = $modx->documentObject ['notifyMyCreditType'];
					$resNotify = Notify::getMyCreditType($notifies, $oldNotifyMyCreditType);
					$modx->documentObject ['notifyMyCreditType'] = $resNotify['notifyMyCreditType'];
					$modx->documentObject ['notifyId'] = $resNotify['notifyId'];
					// оправим уведомление о показе:
					if ($resNotify['notifyId']) {
					    $res = $api->notifyShown ( $_SESSION ['token'], $resNotify['notifyId'] ); // прочитать данные пользователя
					}

					// получить файл договора
					if ($credit_id) {
						$res = $api->getContract ( $credit_id, $_SESSION ['token'] ); // получить договор
						
						$outFile = MODX_BASE_PATH . 'DesignAPI/tmp/contract_' . $credit_id . '.pdf';
						$_SESSION ['file'] = $outFile;
						if (! $res ['Success']) {
							file_put_contents ( $outFile, '' ); // пустой файл
						} else {
							file_put_contents ( $outFile, $res ['Data'] ); // файл договора
						}
					}
				}
				
				break;
			
			// личный кабинет. Мои данные:
			case 5 :
			// личный кабинет. Мои данные моб. версия:
			case 328 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				//$res = $api->getCustomerDetails ( $_SESSION ['token'] ); 
				// прочитать данные пользователя
				$res = [];
				$res ['Success'] = (is_array($_SESSION['api']['client']) 
					&& ($_SESSION['api']['client'] !== [])) ? true : false;
				$res ['Data'] = $_SESSION['api']['client'];
				
				if ($res ['Success']) {
					// если была ошибка при записи в CRM:
					if (isset ( $_SESSION ['profile'] )) {
						if (isset ( $_SESSION ['userData'] )) {
							// удаляем неправильный ИНН из массива временных данных:
							if ($_SESSION ['profile']['Message'] === 'User with same SSN exist') {
								unset($_SESSION ['userData']['SocialSecurityNumber']);
							}
							$res ['Data'] = array_merge ( $res ['Data'], (($_SESSION ['userData']) ? : [])); // если была ошибка, добавляем к ранее введенным данным новые
						}
						$_SESSION ['res'] = $_SESSION ['profile']; 	// для отображения ошибки
						unset($_SESSION ['profile']);
					} else {
						unset ( $_SESSION ['res'] );
					}
					
					$modx->documentObject ['tel1'] = $res ['Data'] ['Phone'];
					$modx->documentObject ['tel2'] = $res ['Data'] ['AlternativePhone'];
					$modx->documentObject ['email'] = ($res ['Data'] ['Email']) ? : 'не указан';
					
					// метка - подтвержден ли email
					$modx->documentObject ['confirmEmailFlag'] = ($res ['Data'] ['IsEmailVerifiedByCustomer']) ? true : false;
					
					$modx->documentObject ['FacebookUrl'] = ($res ['Data'] ['FacebookUrl']) ? : '';
					$modx->documentObject ['firstName'] = ($res ['Data'] ['FirstName']) ? : '';
					$modx->documentObject ['lastName'] = ($res ['Data'] ['LastName']) ? : '';
					$modx->documentObject ['thirdName'] = ($res ['Data'] ['MiddleName']) ? : '';
					
					$isBirthDate = ($res ['Data'] ['BirthDate']) ? true : false;
					$modx->documentObject ['bdate'] = ($isBirthDate) ? date ( 'd.m.Y', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_day'] = ($isBirthDate) ? date ( 'd', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_month'] = ($isBirthDate) ? date ( 'm', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_year'] = ($isBirthDate) ? date ( 'Y', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					
					/*
					$modx->documentObject ['bdate'] = date ( 'd.m.Y', strtotime ( $res ['Data'] ['BirthDate'] ) );
					$modx->documentObject ['bdate_day'] = date ( 'd', strtotime ( $res ['Data'] ['BirthDate'] ) );
					$modx->documentObject ['bdate_month'] = date ( 'm', strtotime ( $res ['Data'] ['BirthDate'] ) );
					$modx->documentObject ['bdate_year'] = date ( 'Y', strtotime ( $res ['Data'] ['BirthDate'] ) );
					*/
					
					$modx->documentObject ['passport'] = ($res ['Data'] ['Passport']) ? : '';
					$modx->documentObject ['passportSeries'] = substr ( trim ( $res ['Data'] ['Passport'] ), 0, 4 ); // русские буквы - по два символа
					$modx->documentObject ['passportNumber'] = substr ( trim ( $res ['Data'] ['Passport'] ), 4 );
					$modx->documentObject ['PassportIssuedBy'] = ($res ['Data'] ['PassportIssuedBy']) ? : '';
					$modx->documentObject ['passportType'] = ($res ['Data'] ['PassportType']) ? : '0'; // Тип документа (паспорта)
					// $modx->documentObject ['passportReestr'] = $res ['Data'] ['PassportReestr']; // номер в реестре
					$modx->documentObject ['passportReestr'] = substr ( trim ( $res ['Data'] ['Passport'] ), 0, 14 ); // номер в реестре
					// $modx->documentObject['passportReestr'] = ''; // номер в реестре
					//$modx->documentObject ['passportNumberDoc'] = $res ['Data'] ['PassportNumberDoc']; // номер документа
					$modx->documentObject ['passportNumberDoc'] = substr ( trim ( $res ['Data'] ['Passport'] ), 14, 9 ); // номер документа
					// $modx->documentObject['passportNumberDoc'] = ''; // номер документа
					
					$isPassportRegistration = ($res ['Data'] ['PassportRegistration']) ? true : false;
					$modx->documentObject ['PassportRegistration'] = ($isPassportRegistration) ? date ( 'd.m.Y', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationDay'] = ($isPassportRegistration) ? date ( 'd', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationMonth'] = ($isPassportRegistration) ? date ( 'm', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationYear'] = ($isPassportRegistration) ? date ( 'Y', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';

					$minAgeForPassport = ( $modx->documentObject ['passportType'] == 2) ? 14 : 16;
					$modx->documentObject ['minYearPassport'] = ($modx->documentObject ['bdate_year'] && (( int ) $modx->documentObject ['bdate_year'] > 1979)) ? ( string ) (( int ) $modx->documentObject ['bdate_year'] + $minAgeForPassport) : '1994';
					// если новый паспорт - меняем минимальный год:
					if ((( int ) $modx->documentObject ['passportType'] == 2) && (( int ) $modx->documentObject ['minYearPassport'] < 2015))
						$modx->documentObject ['minYearPassport'] = '2015';
					$modx->documentObject ['maxYearPassport'] = date ( 'Y' );
					
					$modx->documentObject ['inn'] = ($res ['Data'] ['SocialSecurityNumber']) ? : '';
					$modx->documentObject ['MaritalStatus'] = ($res ['Data'] ['MaritalStatus']) ? : '';
					$modx->documentObject ['Education'] = ($res ['Data'] ['Education']) ? : '';
					$modx->documentObject ['RealEstate'] = ($res ['Data'] ['RealEstate']) ? : '';
					
					$modx->documentObject ['index'] = $res ['Data'] ['Address'] ['ZipCode'];
					$modx->documentObject ['oblast'] = ($res ['Data'] ['Address'] ['State']) ? : '';
					$modx->documentObject ['city'] = ($res ['Data'] ['Address'] ['City']) ? : '';
					$modx->documentObject ['street'] = ($res ['Data'] ['Address'] ['Street']) ? : '';
					$modx->documentObject ['House'] = ($res ['Data'] ['Address'] ['House']) ? : '';
					$modx->documentObject ['Building'] = ($res ['Data'] ['Address'] ['Building']) ? : '';
					$modx->documentObject ['Apartment'] = ($res ['Data'] ['Address'] ['Apartment']) ? : '';
					
					$modx->documentObject ['address_same'] = ($res ['Data'] ['Address'] ['ResidentialMatchesRegistration']) ? : '0';
					$modx->documentObject ['fact_index'] = ($res ['Data'] ['SecondAddress'] ['ZipCode']) ? : '';
					$modx->documentObject ['fact_oblast'] = ($res ['Data'] ['SecondAddress'] ['State']) ? : '';
					$modx->documentObject ['fact_city'] = ($res ['Data'] ['SecondAddress'] ['City']) ? : '';
					$modx->documentObject ['fact_street'] = ($res ['Data'] ['SecondAddress'] ['Street']) ? : '';
					$modx->documentObject ['fact_House'] = ($res ['Data'] ['SecondAddress'] ['House']) ? : '';
					$modx->documentObject ['fact_Building'] = ($res ['Data'] ['SecondAddress'] ['Building']) ? : '';
					$modx->documentObject ['fact_Apartment'] = ($res ['Data'] ['SecondAddress'] ['Apartment']) ? : '';
					
					$modx->documentObject ['BusynessType'] = ($res ['Data'] ['BusynessType']) ? : '';
					$modx->documentObject ['workType'] = ($res ['Data'] ['Occupation']) ? : '';
					$modx->documentObject ['org'] = ($res ['Data'] ['CompanyName']) ? : '';
					$modx->documentObject ['work_index'] = $res ['Data'] ['WorkAddress'] ['ZipCode'];
					$modx->documentObject ['work_oblast'] = ($res ['Data'] ['WorkAddress'] ['State']) ? : '';
					$modx->documentObject ['work_city'] = ($res ['Data'] ['WorkAddress'] ['City']) ? : '';
					$modx->documentObject ['work_street'] = ($res ['Data'] ['WorkAddress'] ['Street']) ? : '';
					$modx->documentObject ['work_House'] = ($res ['Data'] ['WorkAddress'] ['House']) ? : '';
					$modx->documentObject ['work_Building'] = ($res ['Data'] ['WorkAddress'] ['Building']) ? : '';
					$modx->documentObject ['work_Apartment'] = ($res ['Data'] ['WorkAddress'] ['Apartment']) ? : '';
					
					$modx->documentObject ['dolj'] = ($res ['Data'] ['Position']) ? : '';
					$modx->documentObject ['work_tel'] = ($res ['Data'] ['WorkPhone']) ? : '';
					$modx->documentObject ['work_tel_kadr'] = ($res ['Data'] ['WorkPhoneExt']) ? : '';
					// $modx->documentObject ['vremyaorg'] = ($res ['Data'] ['CurrentExperience']) ? : '';
					$modx->documentObject ['costFamily'] = ($res ['Data'] ['CostFamily']) ? : ''; // Расходы на семью
					$modx->documentObject ['GrossMonthlyIncome'] = ($res ['Data'] ['GrossMonthlyIncome']) ? : ''; // Месячный доход
					
					// $modx->documentObject ['nextPay'] = ($res ['Data'] ['NextPay']) ? : ''; // следующее получение зарплаты
					// $modx->documentObject ['oftenPay'] = ($res ['Data'] ['OftenPay']) ? : ''; // как часто платят
					$modx->documentObject ['purposeLoan'] = ($res ['Data'] ['PurposeLoan']) ? : ''; // цель получения кредита
					$modx->documentObject ['sumPayLoans'] = ($res ['Data'] ['SumPayLoans']) ? : ''; // Сумма платежей по кредитам
					$modx->documentObject ['sourceIncome'] = ($res ['Data'] ['sourceIncome']) ? : ''; // Есть доход? (0/1)
					$modx->documentObject ['nameUniversity'] = ($res ['Data'] ['NameUniversity']) ? : ''; // Название учебного заведения
					$modx->documentObject ['Specializationfaculty'] = ($res ['Data'] ['SpecializationFaculty']) ? : ''; // Специализация факультета
					$modx->documentObject ['qualification'] = ($res ['Data'] ['QualificationAfterGraduation']) ? : ''; // Степень/квалификация после выпуска
					// $modx->documentObject ['isBudget'] = ($res ['Data'] ['IsBudget']) ? '1' : '0'; // Бюджет или контракт? (1/0)
					// $modx->documentObject ['formTraining'] = ($res ['Data'] ['FormTraining']) ? : ''; // Форма обучения
					// $modx->documentObject ['isFirstEducation'] = ($res ['Data'] ['IsFirstEducation']) ? '1' : '0'; // первое высшее образование (1/0)
					
					// $modx->documentObject ['beginLearn'] = ($res ['Data'] ['BeginLearn']) ? : ''; // Когда Вы начали учиться
					// $modx->documentObject['beginLearn_month'] = date('m', strtotime($res['Data']['BeginLearn']));
					// $modx->documentObject ['beginLearn_year'] = date ( 'Y', strtotime ( $res ['Data'] ['BeginLearn'] ) );
					$modx->documentObject ['StudentId'] = ($res ['Data'] ['AdditionalData'] ['StudentId']) ? : '';	// номер студенческого билета
						
					$modx->documentObject ['mainSource'] = ($res ['Data'] ['MainSource']) ? : ''; // Основной источник дохода
					$modx->documentObject ['reasonDismissal'] = ($res ['Data'] ['ReasonDismissal']) ? : ''; // Причина увольнения
					// $modx->documentObject ['planNewJob'] = ($res ['Data'] ['PlanNewJob']) ? : ''; // Планируете ли искать новую работу
					$modx->documentObject ['periodResidence'] = ($res ['Data'] ['PeriodResidence']) ? : ''; // Проживаю по данному адресу
					$modx->documentObject ['groupDisability'] = ($res ['Data'] ['GroupDisability']) ? : ''; // Группа инвалидности
					
					$modx->documentObject ['isRecInfo'] = ($res ['Data'] ['IsAgreedWithMailSubscription']) ? : '';	// Получать информацию о новостях и акциях
					$modx->documentObject ['isAgreedUseMyData'] = ($res ['Data'] ['IsAgreedUseMyData']) ? : '';		// Согласен на использование телекоммуникационных данных
					
					$modx->documentObject ['url'] = $api->getUrl ();
					
				} else {
					$modx->documentObject ['error'] = $res ['error'];
					$_SESSION ['res'] = $res;
				}
				
				break;
			
			// личный кабинет. Мои документы:
			case 6 :
				
				// заглушка на Мои документы
				/*
				 * // модалка для сообщений:
				 * $modx->setPlaceholder('showMessage', 'error');
				 * $_SESSION['res']['error'] = 1;
				 * break;
				 * header("Location: " . $modx->makeUrl(5));
				 *
				 * $res = $api->getUserProfile();
				 * if (isset($res['error'])) {
				 * $modx->documentObject['error'] = $res['error'];
				 * } else {
				 * $modx->documentObject['scan1'] = ($res['client']['scan1']) ? 'файл загружен &nbsp;' : '';
				 * $modx->documentObject['scan2'] = ($res['client']['scan2']) ? 'файл загружен &nbsp;' : '';
				 * $modx->documentObject['scan3'] = ($res['client']['scan3']) ? 'файл загружен &nbsp;' : '';
				 * }
				 */
				break;
	
			// Контакты:
			case 9 :
			    if (isset($_POST ['sendMail']) && (0 < count ( $_POST ['sendMail'] ))) { // пришли данные с формы "Контакты"
					if ($_SESSION ['email'] ['message']) {
						$modx->documentObject ['emailOK'] = 1; // признак отправки сообщения
						// unset($res);
					} else {
						// $modx->documentObject['emailOK'] = 0; // признак отправки сообщения
						// unset($_SESSION['email']);
					}
				}
				
				if ($_SESSION ['email'] ['message']) {
					$modx->documentObject ['emailOK'] = 1; // признак отправки сообщения
					unset ( $_SESSION ['email'] );
				}
				
				break;
			
			// Регистрация:
			case 17 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mob_id']);
				Affiliate::partnerProcessing($modx, $https);	// обрабатывает вход через партнеров (functions.php)
			// Регистрация сателиты:
			case 320 :
			// Регистрация мобильное приложение:
			case 327 :

				// $modx->setPlaceholder ( 'flagForm', '3' );
				// unset($_SESSION ['codeReg']);
				// unset($_SESSION ['token']);
				
				// начальное положение первой страницы:
				if (! $modx->getPlaceholder ( 'flagForm' )) {
					if ($_SESSION ['token']) {
						$modx->setPlaceholder ( 'flagForm', '1' );
					} else {
						$modx->setPlaceholder ( 'flagForm', '0' );
					}
					
					// unset($_SESSION['codeReg']);
				}
				
				if (isset($_POST ['reg']) && (0 < count ( $_POST ['reg'] ))) { // пришли данные с формы "Регистрация"
					$modx->documentObject ['phone'] = $_POST ['reg'] ['phone'];
					$modx->documentObject ['email'] = $_POST ['reg'] ['email'];
					$modx->documentObject ['password'] = $_POST ['reg'] ['password'];
					$modx->documentObject ['password2'] = $_POST ['reg'] ['password2'];
				} elseif (isset($_POST ['codeReg']) && (0 < count ( $_POST ['codeReg'] ))) { // пришли данные с формы подтверждения кода регистрации
					$modx->documentObject ['phone'] = $_POST ['codeReg'] ['phone'];

					if ($_SESSION ['res'] == null) {
						// $js = '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post['login'] . '")';
					} else {
						// $js = '$("#code-modal").modal("show");';
					}
				} elseif (isset($_POST ['reg2']) && (0 < count ( $_POST ['reg2'] ))) {
					
				} else {
						
					$modx->setPlaceholder('isCustomerExists', 0);
					
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
				}
				
				if ($_SESSION ['codeReg'] && $_SESSION ['codeRegConfirm']) { // код регистрации, полученный по СМС
					$modx->documentObject ['codeReg'] = 'true';
				}
				
				if ($_SESSION ['token']) {
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
				} else {
					$res = [ ];
				}
				
				if ($_SESSION ['PromoCode'])
					$modx->documentObject ['PromoCode'] = $_SESSION ['PromoCode'];
					
				// расчет счетчика заявок:
				$RequestCounter = getRequestCounter();
				$modx->documentObject ['requestCounter_1'] = substr($RequestCounter, 3, 1);
				$modx->documentObject ['requestCounter_2'] = substr($RequestCounter, 2, 1);
				$modx->documentObject ['requestCounter_3'] = substr($RequestCounter, 1, 1);
				$modx->documentObject ['requestCounter_4'] = substr($RequestCounter, 0, 1);
				
				if ($res ['Success']) {
					// если была ошибка при записи в CRM:
					if (isset ( $_SESSION ['reg2'] )) {
						if (isset ( $_SESSION ['userData'] )) {
							// удаляем неправильный ИНН из массива временных данных:
							if ($_SESSION ['reg2']['Message'] === 'User with the same SSN already exists') {
								unset($_SESSION ['userData']['SocialSecurityNumber']);
								$modx->documentObject ['errorInnExist'] = '1';
							}
							$res ['Data'] = array_merge ( $res ['Data'], $_SESSION ['userData'] ); // если была ошибка, добавляем к ранее введенным данным новые
						}
						$_SESSION ['res'] = $_SESSION ['reg2']; 	// для отображения ошибки
						unset($_SESSION ['reg2']);
					} else {
						if ( $modx->getPlaceholder ( 'flagErrorReg') !== '1')
							unset ( $_SESSION ['res'] );
					}
					
					$modx->documentObject ['phone'] = $res ['Data'] ['Phone'];
					$modx->documentObject ['tel2'] = $res ['Data'] ['AlternativePhone'];
					$modx->documentObject ['email'] = ($res ['Data'] ['Email']) ?: 'не указан';
					$modx->documentObject ['FacebookUrl'] = ($res ['Data'] ['FacebookUrl']) ? : '';
					$modx->documentObject ['firstName'] = ($res ['Data'] ['FirstName']) ? : '';
					$modx->documentObject ['lastName'] = ($res ['Data'] ['LastName']) ? : '';
					$modx->documentObject ['thirdName'] = ($res ['Data'] ['MiddleName']) ? : '';
					
					$isBirthDate = ($res ['Data'] ['BirthDate']) ? true : false;
					$modx->documentObject ['bdate'] = ($isBirthDate) ? date ( 'd.m.Y', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_day'] = ($isBirthDate) ? date ( 'd', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_month'] = ($isBirthDate) ? date ( 'm', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					$modx->documentObject ['bdate_year'] = ($isBirthDate) ? date ( 'Y', strtotime ( $res ['Data'] ['BirthDate'] ) ) : '';
					
					$modx->documentObject ['passport'] = ($res ['Data'] ['Passport']) ? : '';
					$modx->documentObject ['passportSeries'] = substr ( trim ( $modx->documentObject ['passport']), 0, 4 ); // русские буквы - по два символа
					$modx->documentObject ['passportNumber'] = substr ( trim ( $modx->documentObject ['passport']), 4 );
					$modx->documentObject ['PassportIssuedBy'] = ($res ['Data'] ['PassportIssuedBy']) ? : '';
					$modx->documentObject['passportType'] = ($res['Data']['PassportType']) ? : 1; // Тип документа (паспорта)
					// $modx->documentObject ['passportType'] = '1'; // Тип документа (паспорта) 1 - старый, 2 - новый
					// $modx->documentObject['passportReestr'] = $res['Data']['PassportReestr']; // номер в реестре
					//$modx->documentObject ['passportReestr'] = ''; // номер в реестре
					$modx->documentObject ['passportReestr'] = substr ( trim ( $modx->documentObject ['passport']), 0, 14 ); // номер в реестре
					// $modx->documentObject['passportNumberDoc'] = $res['Data']['PassportNumberDoc']; // номер документа
					// $modx->documentObject ['passportNumberDoc'] = ''; // номер документа
					$modx->documentObject ['passportNumberDoc'] = substr ( trim ( $modx->documentObject ['passport']), 14, 9 ); // номер документа
					
					$isPassportRegistration = ($res ['Data'] ['PassportRegistration']) ? true : false;
					$modx->documentObject ['PassportRegistration'] = ($isPassportRegistration) ? date ( 'd.m.Y', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationDay'] = ($isPassportRegistration) ? date ( 'd', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationMonth'] = ($isPassportRegistration) ? date ( 'm', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					$modx->documentObject ['PassportRegistrationYear'] = ($isPassportRegistration) ? date ( 'Y', strtotime ( $res ['Data'] ['PassportRegistration'] ) ) : '';
					
					$minAgeForPassport = ( $modx->documentObject ['passportType'] == 2) ? 14 : 16;
					$modx->documentObject ['minYearPassport'] = ($modx->documentObject ['bdate_year'] && (( int ) $modx->documentObject ['bdate_year'] > 1979)) ? ( string ) (( int ) $modx->documentObject ['bdate_year'] + $minAgeForPassport) : '1994';
					
					$modx->documentObject ['minYearPassport'] = ($modx->documentObject ['bdate_year'] && (( int ) $modx->documentObject ['bdate_year'] > 1979)) ? (( string ) (( int ) $modx->documentObject ['bdate_year'] + 16)) : '1994';
					$modx->documentObject ['maxYearPassport'] = date ( 'Y' );
					
					$modx->documentObject ['inn'] = ($res ['Data'] ['SocialSecurityNumber']) ? : '';
					$modx->documentObject ['MaritalStatus'] = ($res ['Data'] ['MaritalStatus']) ? : '';
					$modx->documentObject ['Education'] = ($res ['Data'] ['Education']) ? : '';
					$modx->documentObject ['RealEstate'] = ($res ['Data'] ['RealEstate']) ? : '';
					
					$modx->documentObject ['index'] = $res ['Data'] ['Address'] ['ZipCode'];
					$modx->documentObject ['oblast'] = ($res ['Data'] ['Address'] ['State']) ? : '';
					$modx->documentObject ['city'] = ($res ['Data'] ['Address'] ['City']) ? : '';
					$modx->documentObject ['street'] = ($res ['Data'] ['Address'] ['Street']) ? : '';
					$modx->documentObject ['House'] = ($res ['Data'] ['Address'] ['House']) ? : '';
					$modx->documentObject ['Building'] = ($res ['Data'] ['Address'] ['Building']) ? : '';
					$modx->documentObject ['Apartment'] = ($res ['Data'] ['Address'] ['Apartment']) ? : '';
					
					$modx->documentObject ['address_same'] = ($res ['Data'] ['Address'] ['ResidentialMatchesRegistration'] == 0) ? 0 : 1;
					$modx->documentObject ['fact_index'] = ($res ['Data'] ['SecondAddress'] ['ZipCode']) ? : '';
					$modx->documentObject ['fact_oblast'] = ($res ['Data'] ['SecondAddress'] ['State']) ? : '';
					$modx->documentObject ['fact_city'] = ($res ['Data'] ['SecondAddress'] ['City']) ? : '';
					$modx->documentObject ['fact_street'] = ($res ['Data'] ['SecondAddress'] ['Street']) ? : '';
					$modx->documentObject ['fact_House'] = ($res ['Data'] ['SecondAddress'] ['House']) ? : '';
					$modx->documentObject ['fact_Building'] = ($res ['Data'] ['SecondAddress'] ['Building']) ? : '';
					$modx->documentObject ['fact_Apartment'] = ($res ['Data'] ['SecondAddress'] ['Apartment']) ? : '';
					
					$modx->documentObject ['BusynessType'] = ($res ['Data'] ['BusynessType']) ? : '';
					$modx->documentObject ['workType'] = ($res ['Data'] ['Occupation']) ? : '';
					$modx->documentObject ['org'] = ($res ['Data'] ['CompanyName']) ? : '';
					$modx->documentObject ['work_index'] = ($res ['Data'] ['WorkAddress'] ['ZipCode']) ? : '';
					$modx->documentObject ['work_oblast'] = ($res ['Data'] ['WorkAddress'] ['State']) ? : '';
					$modx->documentObject ['work_city'] = ($res ['Data'] ['WorkAddress'] ['City']) ? : '';
					$modx->documentObject ['work_street'] = ($res ['Data'] ['WorkAddress'] ['Street']) ? : '';
					$modx->documentObject ['work_House'] = ($res ['Data'] ['WorkAddress'] ['House']) ? : '';
					$modx->documentObject ['work_Building'] = ($res ['Data'] ['WorkAddress'] ['Building']) ? : '';
					$modx->documentObject ['work_Apartment'] = ($res ['Data'] ['WorkAddress'] ['Apartment']) ? : '';
					
					$modx->documentObject ['dolj'] = ($res ['Data'] ['Position']) ? : '';
					$modx->documentObject ['work_tel'] = ($res ['Data'] ['WorkPhone']) ? : '';
					$modx->documentObject ['work_tel_kadr'] = ($res ['Data'] ['WorkPhoneExt']) ? : '';
					// $modx->documentObject ['vremyaorg'] = ($res ['Data'] ['CurrentExperience']) ? : '';
					$modx->documentObject['costFamily'] = ($res['Data']['CostFamily']) ? : '0'; // Расходы на семью
					$modx->documentObject ['GrossMonthlyIncome'] = ($res ['Data'] ['GrossMonthlyIncome']) ? : ''; // Месячный доход
					                                                                                  
					// $modx->documentObject['nextPay'] = $res['Data']['nextPay']; // следующее получение зарплаты
					// $modx->documentObject ['nextPay'] = ''; // следующее получение зарплаты
					// $modx->documentObject['oftenPay'] = $res['Data']['OftenPay']; // как часто платят
					// $modx->documentObject ['oftenPay'] = ''; // как часто платят
					// $modx->documentObject['purposeLoan'] = $res['Data']['PurposeLoan']; // цель получения кредита
					$modx->documentObject ['purposeLoan'] = ''; // цель получения кредита
					// $modx->documentObject['sumPayLoans'] = $res['Data']['SumPayLoans']; // Сумма платежей по кредитам
					$modx->documentObject ['sumPayLoans'] = ''; // Сумма платежей по кредитам
					// $modx->documentObject['sourceIncome'] = $res['Data']['sourceIncome']; // Есть доход?
					$modx->documentObject ['sourceIncome'] = '1'; // Есть доход? (0/1)
					// $modx->documentObject['nameUniversity'] = $res['Data']['NameUniversity']; // Название учебного заведения
					$modx->documentObject ['nameUniversity'] = ''; // Название учебного заведения
					// $modx->documentObject['Specializationfaculty'] = $res['Data']['SpecializationFaculty']; // Специализация факультета
					$modx->documentObject ['Specializationfaculty'] = ''; // Специализация факультета
					// $modx->documentObject['qualification'] = $res['Data']['QualificationAfterGraduation']; // Степень/квалификация после выпуска
					$modx->documentObject ['qualification'] = ''; // Степень/квалификация после выпуска
					// $modx->documentObject['isBudget'] = $res['Data']['IsBudget']; // Бюджет или контракт? (1/0)
					// $modx->documentObject ['isBudget'] = ''; // Бюджет или контракт? (1/0)
					// $modx->documentObject['formTraining'] = $res['Data']['FormTraining']; // Форма обучения
					// $modx->documentObject ['formTraining'] = ''; // Форма обучения
					// $modx->documentObject['isFirstEducation'] = $res['Data']['IsFirstEducation']; // первое высшее образование (1/0)
					// $modx->documentObject ['isFirstEducation'] = '1'; // первое высшее образование (1/0)
					                                                
					// $modx->documentObject['beginLearn'] = $res['Data']['BeginLearn']; // Когда Вы начали учиться
					// $modx->documentObject ['beginLearn'] = ''; // Когда Вы начали учиться
					// $modx->documentObject['beginLearn_month'] = '';
					// $modx->documentObject ['beginLearn_year'] = '';
					
					// $modx->documentObject['mainSource'] = $res['Data']['MainSource']; // Основной источник дохода
					$modx->documentObject ['mainSource'] = ($res['Data']['MainSource']) ? :''; // Основной источник дохода: 8 - зарплата
					// $modx->documentObject['reasonDismissal'] = $res['Data']['ReasonDismissal']; // Причина увольнения
					$modx->documentObject ['reasonDismissal'] = ''; // Причина увольнения
					// $modx->documentObject['planNewJob'] = $res['Data']['PlanNewJob']; // Планируете ли искать новую работу
					// $modx->documentObject ['planNewJob'] = ''; // Планируете ли искать новую работу
					$modx->documentObject ['periodResidence'] = ($res ['Data'] ['PeriodResidence']) ? : ''; // Проживаю по данному адресу
					// $modx->documentObject['periodResidence'] = ''; // Проживаю по данному адресу
					// $modx->documentObject['groupDisability'] = $res['Data']['GroupDisability']; // Группа инвалидности
					$modx->documentObject ['groupDisability'] = '1'; // Группа инвалидности
					
					$modx->documentObject ['isRecInfo'] = $res ['Data'] ['IsAgreedWithMailSubscription']; // Получать информацию о новостях и акциях
					// $modx->documentObject['isRecInfo'] = '1'; // Получать информацию о новостях и акциях
					$modx->documentObject ['isAgreedUseMyData'] = $res ['Data'] ['IsAgreedUseMyData'];		// Согласен на использование телекоммуникационных данных
				} else {
					$modx->documentObject ['passportType'] = '1'; // Тип документа (паспорта)
					$modx->documentObject ['passportReestr'] = ''; // номер в реестре
					$modx->documentObject ['passportNumberDoc'] = ''; // номер документа
					$modx->documentObject ['minYearPassport'] = '1995';
					$modx->documentObject ['maxYearPassport'] = date ( 'Y' );
					$modx->documentObject ['address_same'] = '1';
					if ($_POST ['reg'] ['phone']) {
						$modx->documentObject ['phone'] = $_POST ['reg'] ['phone'];
					} elseif ($_POST ['codeReg'] ['phone']) {
						$modx->documentObject ['phone'] = $_POST ['codeReg'] ['phone'];
					} else {
						$modx->documentObject ['phone'] = '+380 ';
					}
					
					// добавляем данные из BankID, если таковые есть:
					/*
					if (isset ( $_SESSION ['userData'] ['BankId'] )) {
						
						if ($_SESSION ['userData'] ['BankId'] ['phone'] && ($modx->documentObject ['phone'] == '+380 '))
							$modx->documentObject ['phone'] = $_SESSION ['userData'] ['BankId'] ['phone'];
						if ($_SESSION ['userData'] ['BankId'] ['email'])
							$modx->documentObject ['email'] = $_SESSION ['userData'] ['BankId'] ['email'];
						
						if ($_SESSION ['userData'] ['BankId'] ['firstName'])
							$modx->documentObject ['firstName'] = $_SESSION ['userData'] ['BankId'] ['firstName'];
						if ($_SESSION ['userData'] ['BankId'] ['middleName'])
							$modx->documentObject ['thirdName'] = $_SESSION ['userData'] ['BankId'] ['middleName'];
						if ($_SESSION ['userData'] ['BankId'] ['lastName'])
							$modx->documentObject ['lastName'] = $_SESSION ['userData'] ['BankId'] ['lastName'];
						if ($_SESSION ['userData'] ['BankId'] ['inn'])
							$modx->documentObject ['inn'] = $_SESSION ['userData'] ['BankId'] ['inn'];
						
						if ($_SESSION ['userData'] ['BankId'] ['birthDay']) {
							$modx->documentObject ['bdate'] = $_SESSION ['userData'] ['BankId'] ['birthDay'];
							$modx->documentObject ['bdate_day'] = date ( 'd', strtotime ( $_SESSION ['userData'] ['BankId'] ['birthDay'] ) );
							$modx->documentObject ['bdate_month'] = date ( 'm', strtotime ( $_SESSION ['userData'] ['BankId'] ['birthDay'] ) );
							$modx->documentObject ['bdate_year'] = date ( 'Y', strtotime ( $_SESSION ['userData'] ['BankId'] ['birthDay'] ) );
						}
						
						// адрес регистрации:
						if ($_SESSION ['userData'] ['BankId'] ['addresses']) {
							foreach ( $_SESSION ['userData'] ['BankId'] ['addresses'] as $key => $value ) {
								if ($value ['type'] == 'factual') {
									$modx->documentObject ['oblast'] = $value ['state'];
									$modx->documentObject ['city'] = $value ['city'];
									$modx->documentObject ['street'] = $value ['street'];
									$modx->documentObject ['House'] = $value ['houseNo'];
									$modx->documentObject ['Apartment'] = $value ['flatNo'];
								}
							}
						}
						
						// гражданский паспорт:
						if ($_SESSION ['userData'] ['BankId'] ['documents']) {
							foreach ( $_SESSION ['userData'] ['BankId'] ['documents'] as $key => $value ) {
								if ($value ['type'] == 'passport') {
									$modx->documentObject ['passportSeries'] = $value ['series'];
									$modx->documentObject ['passportNumber'] = $value ['number'];
									$modx->documentObject ['PassportIssuedBy'] = $value ['issue'];
									$modx->documentObject ['PassportRegistration'] = date ( 'd.m.Y', strtotime ( $value ['dateIssue'] ) );
									$modx->documentObject ['PassportRegistrationDay'] = date ( 'd', strtotime ( $value ['dateIssue'] ) );
									$modx->documentObject ['PassportRegistrationMonth'] = date ( 'm', strtotime ( $value ['dateIssue'] ) );
									$modx->documentObject ['PassportRegistrationYear'] = date ( 'Y', strtotime ( $value ['dateIssue'] ) );
								}
							}
						}
					}
					*/
				}
				
				break;
			
			// Мои карты:
			case 22 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mod_id']);
				
			// Мои карты (РЕГИСИРАЦИЯ КАРТЫ мобильная версия):
			// case 332 :
				
				//----------------------------------------------------------------------
				// Для теста, удалить
				// echo 'string=' . $api->preprocessing('<ScRiPt >CDck(9091)</ScRiPt>');die; 

				/*
				// файл отчетности:
				$dir = MODX_BASE_PATH . "DesignAPI/logs/" . date('Y') . "/" . date('m') . "/";
				//$dir = "/DesignAPI/logs/" . date('Y') . "/" . date('m') . "/";
				$outfileName = $dir . 'affiliate_' . date("Ym") . ".csv";
				
				$loanId = '139006';
				$content = file_get_contents($outfileName);
				preg_match("/.{25},(\w{1,20}),\w{1,40},applyforLoan,{$loanId}/", $content, $matches, PREG_OFFSET_CAPTURE);
				//$affiliateName = $matches[1][0];
				//if (file_exists($outfileName)) {echo $outfileName;} else {echo 'нет файла ' . $outfileName;}die;
				var_dump($matches[1][0]);die;
				*/
				
				/*
					
					$lang = $modx->config ['lang'];
					$culture = ($lang == 'ua') ? 'uk-UA' : 'ru-RU';
					// делаем заявку на кредит:
					// $res = $api->applyforLoan ( $post ['amount'], $post ['days'], $res['Data']['Name'], $res['Data']['MaxAmount'], $_SESSION ['token'], $promoCode, $culture );
					// если заявка передана успешно:
					if ($res ['Success']) {
					}
				*/
				//----------------------------------------------------------------------
				
				// заглушка на Мои карты
				// header("Location: " . $modx->makeUrl(5));
				
				if (! $_SESSION ['token']) {
					
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
					die;
				}
				
				// проверка заполнения данных пользователя:
				$resCheck = $api->checkCompleteUserData($_SESSION ['token']);
				if ($resCheck ['Success']) {
					$modx->documentObject ['checkCompleteUserData'] = "1";
				} else {
					$modx->documentObject ['checkCompleteUserData'] = "0";
				}
				
				if (isset ( $_SESSION ['loan'] ))
					$modx->documentObject ['loanId'] = $_SESSION ['loan'] ['LoanId'];
				
				unset ( $_SESSION ['card'] );
				
				// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
				$res = $_SESSION['api']['cards'];	// получить список карт клиента
				
				// если еще нет карты у клиента, возвращается '', поэтому модернизируем ответ:
				if (trim ( ( string ) $res [0] ['number'] ) !== '') {
					$_SESSION ['card'] ['cards'] = $res;
				} else {
					$_SESSION ['card'] ['cards'] = [ ];
				}
				if ($res [0] ['Success'])
					$modx->documentObject ['loanId'] = $res [0] ['id'];
				
				// если есть карта, запишем номер карты в номер счета данных пользователя:
				if ($res[0]['number']) {
					// $resCustomer = $api->getCustomerDetails ( $_SESSION ['token'] ); 
					// прочитать данные пользователя
					$resCustomer = [];
					$resCustomer ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$resCustomer ['Data'] = $_SESSION['api']['client'];
					if ($resCustomer['Success']) {
						if ($resCustomer['Data']['Bank']['AccountNumber'] !== $res[0]['number']) {
							$resCustomer['Data']['Bank']['AccountNumber'] = $res[0]['number'];
							$resCustomerUpd = $api->updateCustomerDetails ( $resCustomer['Data'], $_SESSION ['token'] ); // изменить информацию о пользователе
						}
					}
				}
						
				/*
				 * if (((0 < count($_POST['addCard'])) || (0 < count($_POST['verify']))) && (isset($_SESSION['card']['error']))) {
				 * // $modx->documentObject['card-number'] = $_POST['setCard']);
				 * $res = $api->getCards($_SESSION['token']); // получить список карт клиента
				 * $_SESSION['card']['cards'] = $res['cards'];
				 * } else {
				 * $res = $api->getCards($_SESSION['token']); // получить список карт клиента
				 * $_SESSION['card']['cards'] = $res;
				 * }
				 */
					
				// верификация карты:
					/*
				 * далее скрипт, если вводятся параметры карты на нашем сайте
				 * if (isset($_POST['addCard']['verify-id']) && ($_POST['addCard']['verify-id'] !== '')) {
				 * foreach ($res['cards'] as $key => $value) {
				 * if ($value['id'] === $_POST['addCard']['verify-id']) {
				 * $modx->documentObject['id'] = $value['id'];
				 * $number = substr($value['number'], 0, 4) . '-' . substr($value['number'], 4, 4) .
				 * '-' .substr($value['number'], 8, 4) . '-' .substr($value['number'], 12, 4);
				 * $modx->documentObject['number'] = $number;
				 * //$js .= '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post['login'] . '")';
				 * $js .= '$("#modal_card_verify").modal("show")';
				 * break;
				 * }
				 * }
				 * }
				 */

				break;
			
			// Восстановление пароля:
			case 23:
			// Восстановление пароля моб. версия
			case 326:
				
				/*
				 if (0 < count($_SESSION['forgot']))	{
				 $modx->documentObject['phone'] = $_SESSION['forgot']['phone'];
				 $modx->documentObject['email'] = $_SESSION['forgot']['email'];
				 if ($_SESSION['forgot']['phone']) $modx->documentObject['recoveryPassword'] = $_SESSION['forgot']['phone'];
				 if ($_SESSION['forgot']['email']) $modx->documentObject['recoveryPassword'] = $_SESSION['forgot']['email'];
				 if ($_SESSION['forgot']['code']) $modx->documentObject['code'] = $_SESSION['forgot']['code'];
				 //if ($_SESSION['Success']) $modx->documentObject['Success'] = 'Success';
				 if ($_SESSION['forgot']['token']) {
				 $modx->documentObject['code'] = $_SESSION['forgot']['token'];
				 $modx->documentObject['recoveryPassword'] = $_SESSION['forgot']['login'];
				 $modx->documentObject['email'] = $_SESSION['forgot']['login'];
				 $modx->documentObject['Success'] = '';
				 unset($_SESSION['forgot']);
				 unset($_SESSION['Success']);
				 }
				 if ($_SESSION['forgot']['js']) {
				 $js .= $_SESSION['forgot']['js'];
				 }
				 //
				 //if (!(0 < count($_POST['forgot'])))	{
				 //	unset($_SESSION['forgot']);
				 //	unset($_SESSION['Success']);
				 //}
				 } else {
				 //
				 //if ($_SESSION['Success']['Flag']) {
				 //	$modx->documentObject['Success'] = $_SESSION['Success']['Method'];
				 //	$modx->documentObject['recoveryPassword'] = $_SESSION['Success']['recoveryPassword'];
				 //}
				 //
				 //unset($_SESSION['forgot']);
				 //unset($_SESSION['Success']);
				 }

				 // подключаем логирование:
				 $log = new Log('forgot_');
				 $strlog = "METHOD: GETforgotPass23 (GET forgotPass) " .
				 "\nPOST=" . print_r($_POST['forgot'], true) .
				 "\nforgot=" . print_r($_SESSION['forgot'], true) .
				 "\nSuccess=" . print_r($_SESSION['Success'], true) .
				 "\nmodx->documentObject['Success']=" . $modx->documentObject['Success'];

				 $modx->documentObject['hash'] = time();

				 if ($_SESSION['Success']['Flag']) {
				 $modx->documentObject['Success'] = $_SESSION['Success']['Method'];
				 $modx->documentObject['recoveryPassword'] = $_SESSION['Success']['recoveryPassword'];
				 if (0 >= count($_POST['forgot'])) {
				 unset($_SESSION['forgot']);
				 unset($_SESSION['Success']);
				 unset($_SESSION['Success']['Method']);
				 }
				 } else {
				 $modx->documentObject['Success'] = '';
				 }
				 	
				 $strlog .=	"\nmodx->documentObject['Success']=" . $modx->documentObject['Success'];
				 $log->write($strlog);
				 */
				
			    if (isset($_POST ['forgot']) && (0 < count ( $_POST ['forgot'] ))) {
					$modx->documentObject ['phone'] = $_POST ['forgot'] ['phone'];
					$modx->documentObject ['email'] = $_POST ['forgot'] ['email'];
					if ($_POST ['forgot'] ['phone'])
						$modx->documentObject ['recoveryPassword'] = $_POST ['forgot'] ['phone'];
					if ($_POST ['forgot'] ['email'])
						$modx->documentObject ['recoveryPassword'] = $_POST ['forgot'] ['email'];
					if ($_POST ['forgot'] ['code'])
						$modx->documentObject ['code'] = $_POST ['forgot'] ['code'];
					// if ($_SESSION['Success']) $modx->documentObject['Success'] = 'Success';
				} else {
					if ($_SESSION ['forgot'] ['token']) {
						$modx->documentObject ['code'] = $_SESSION ['forgot'] ['token'];
						$modx->documentObject ['recoveryPassword'] = $_SESSION ['forgot'] ['login'];
						$modx->documentObject ['email'] = $_SESSION ['forgot'] ['login'];
					}
					unset ( $_SESSION ['forgot'] );
					unset ( $_SESSION ['Success'] );
				}
				
				if ($_SESSION ['Success'] ['Flag']) {
					$modx->documentObject ['Success'] = ($_SESSION ['Success'] ['Method']) ? : '';
					$modx->documentObject ['recoveryPassword'] = ($_SESSION ['Success'] ['recoveryPassword']) ? : '';
				} else {
					$modx->documentObject ['Success'] = '';
				}
				$modx->documentObject ['hash'] = ($_SESSION ['hash']) ? : '';
				break;
			
			// О нас:
			case 25 :
			case 716 :   // АВ - тест
			    
			    
			    // для теста, удалить:

			    // ?utm_source=source1&utm_campaign=campaign1
                /* 			    
                $isRepeated = (true) ? 'repeat' : 'new';
			    $ga = new GoogleAnalytics();
			    $resTransaction = $ga->requestTransaction('123123123', 5555, 1111);
			    $resItem = $ga->requestItem(22, $isRepeated);
			    dd($resTransaction);dd($resItem);die;
                 */
			    
			    /*
			    $dateCreate = '2017-10-03T10:36:32.493';
				$dateClose = '2017-10-06T10:36:32.493';
				$maxlong = (new DateTime ($dateCreate))->diff(new DateTime ($dateClose))->days; 	// разница в днях от даты выдачи до сегодня
				echo "interval = " . print_r($maxlong, true);
				die;
				*/
			    
                /*
  			    // Получаем содержимое файла в виде массива
			    $logContent = file(MODX_BASE_PATH . '/tmp/2018-07-04/ajax_20180703.log');
			    $logOut = [];
			    //Перебираем все элементы массива в цикле
			    foreach ($logContent as $string) {
			        $outString = '';
			        $result = preg_match('/\d{4}.+\+03\:00/', $string, $found);        // Производим поиск
			        $outString .= ($result) ? $found[0] : '';
			        $result = preg_match('/\+380\d{9}/', $string, $found);        // Производим поиск
			        if ($result) {
			            $outString .= ",{$found[0]};\r\n";
			            $logOut[] = $outString;
			            echo "$outString<br>";
			        }
			    }
			    file_put_contents(MODX_BASE_PATH . '/tmp/2018-07-04/phoneToReg.csv', $logOut);
                */
			    
				// header ( "Location: " . $modx->makeUrl ( 19 ) );
				break;
							
			// Мои карты (Новые):
			case 26 :
		    // Мои карты (Новые) Регистрация:
			case 181 :

			    unset($_SESSION['sat_id']);
			    unset($_SESSION['mod_id']);
			    
		    // Мои карты (Новые) Регистрация Сателиты:
			case 335 :
			    // Мои карты (Новые) Мобильная версия:
			case 653 :
				
				if (! $_SESSION ['token']) {
					
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
					die;
				}
				// проверка заполнения данных пользователя:
				$resCheck = $api->checkCompleteUserData($_SESSION ['token']);
				if ($resCheck ['Success']) {
					$modx->documentObject ['checkCompleteUserData'] = "1";
				} else {
					$modx->documentObject ['checkCompleteUserData'] = "0";
				}
				
				if (isset ( $_SESSION ['loan'] ))
					$modx->documentObject ['loanId'] = $_SESSION ['loan'] ['LoanId'];
				
				if (!$_POST)
        			unset ( $_SESSION ['card'] );
				
        		//$res = $api->tranzzoGetCardsAll ( $_SESSION ['token'] ); // получить список карт клиента
				$res = $_SESSION['api']['tranzzoCards'];	// получить список карт клиента
				
				// если еще нет карты у клиента, возвращается '', поэтому модернизируем ответ:
				if (count($res) > 0) {
					$_SESSION ['card'] ['cards'] = $res;
					$modx->documentObject ['cardsCount'] = count($res);	// количество карт
				} else {
					$_SESSION ['card'] ['cards'] = [ ];
					$modx->documentObject ['cardsCount'] = 0;	// количество карт
				}
					
				// вычисляем url, куда возвращаться:
			    $modx->documentObject ['returnUrl'] = $modx->makeUrl($modx->documentIdentifier);
				
				/*
				// если есть карта, запишем номер карты в номер счета данных пользователя:
				if ($res[0]['number']) {
					// $resCustomer = $api->getCustomerDetails ( $_SESSION ['token'] );
					// прочитать данные пользователя
					$resCustomer = [];
					$resCustomer ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$resCustomer ['Data'] = $_SESSION['api']['client'];
					if ($resCustomer['Success']) {
						if ($resCustomer['Data']['Bank']['AccountNumber'] !== $res[0]['number']) {
							$resCustomer['Data']['Bank']['AccountNumber'] = $res[0]['number'];
							$resCustomerUpd = $api->updateCustomerDetails ( $resCustomer['Data'], $_SESSION ['token'] ); // изменить информацию о пользователе
						}
					}
				}
				*/
			    
				break;
						
			// Договор кредита:
			case 33 :
			// Договор кредита (моб. версия):
			case 486 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				if ($_SESSION ['cred_id']) {
					$credit_id = $_SESSION ['cred_id'];
					// unset($_SESSION['cred_id']);
					$res = $api->getContract ( $credit_id, $_SESSION ['token'] ); // получить договор

					$_SESSION ['res'] = $res;
					
					if (! $res ['Success']) {
						if ($_SESSION['sat_id']) {

						} elseif ($_SESSION['mob_id']) {
							header ( "Location: " . $modx->makeUrl ( 331 ) ); // если не удалось получить договор - на Мой кредит
						} else {
							header ( "Location: " . $modx->makeUrl ( 4 ) ); // если не удалось получить договор - на Мой кредит
						}
						// $modx->documentObject['error'] = $res['error'];
					} else {
						$outFile = MODX_BASE_PATH . 'DesignAPI/tmp/contract_' . $_SESSION ['cred_id'] . '.pdf';
						file_put_contents ( $outFile, $res ['Data'] );
						$_SESSION ['file'] = $outFile;
						
						// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); 
						// получить данные клиента
						$res = [];
						$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
						$res ['Data'] = $_SESSION['api']['client'];
						if ($res ['Success']) {
							$modx->documentObject ['firstName'] = $res ['Data'] ['FirstName'];
							$modx->documentObject ['lastName'] = $res ['Data'] ['LastName'];
							$modx->documentObject ['thirdName'] = $res ['Data'] ['MiddleName'];
						}

						// находим нужные параметры кредита:
						// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
						$res = [];
						$res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
						$res ['Data'] = $_SESSION['api']['credits'];
						if ( $res ['Success']) {
							if (isset ( $res['Data'] )) {
								$credits = (is_array($res['Data'])) ? $res['Data'] : [ ];
								foreach ($credits as $key => $credit) {
									if ($credit['Id'] == $credit_id) {
										$modx->documentObject ['amount'] = $credit['Amount'];
										break;
									}
								}
							}
						}
					}
					
					$modx->documentObject ['credit_id'] = $credit_id;
				} else {
					if ($_SESSION['sat_id']) {
						
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // // если нет cred_id - на главную mob
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет cred_id - на главную
					}
				}
				
				break;
			
			// Доп.договор:
			case 34 :
			    
			    $modx->setPlaceholder('refId', ($_SESSION['dopdogovor']['refId']) ? : 4);        // куда возвращаться после просмотра
			    $modx->setPlaceholder('anchorId', ($_SESSION['dopdogovor']['anchorId']) ? : '');    // куда возвращаться после просмотра (якорь)
			    
			    break;
			    
			// Оплата кредита:
			case 35 :
		    // Оплата кредита моб. версия:
			case 329 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				if (! isset ( $_POST ['pay'] )) {
					unset ( $_SESSION ['pay'] );
				}
				
				if ($_SESSION ['cred_id']) {
					$credit_id = $_SESSION ['cred_id'];
					// unset($_SESSION['cred_id']);
					
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					$_SESSION ['client'] = $res;
					if ($res ['Success']) {
						$modx->documentObject ['firstName'] = $res ['Data'] ['FirstName'];
						$modx->documentObject ['lastName'] = $res ['Data'] ['LastName'];
						$modx->documentObject ['thirdName'] = $res ['Data'] ['MiddleName'];
						
						// получаем список кредитов
						// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
						// $_SESSION ['res'] = $res;
						$res = [];
						$res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
						$res ['Data'] = $_SESSION['api']['credits'];
						
						if (! $res ['Success']) {
							$modx->documentObject ['error'] = 'Кредиты не найдены';
							
							$modx->documentObject ['credit_status'] = 'zero';
							$modx->documentObject ['amount'] = 0;
							$modx->documentObject ['days'] = 0;
							$modx->documentObject ['repay'] = 0;
							$modx->documentObject ['sumToPay'] = 0;
						} else {
							
						    if (isset ( $res ['Data'] )) {
								$credits = (is_array ( $_SESSION ['api'] ['credits'] )) ?  $_SESSION ['api'] ['credits'] : [ ];
								
								// найдем нужный кредит:
								$credit = [ ];
								foreach ( $credits as $key => $value ) {
									if ($value ['Id'] === ( int ) $credit_id) {
										$credit = $value;
										break;
									}
								}
							}
							
							// узнаем, сколько нужно оплатить (тело + проценты и пеню):
							// $totally = $principal + $credit ['CurrentFees'] + $credit ['CurrentInterest'];
							// $totally = $credit ['CurrentDebt'];
							$totally = ($credit ['IsRestructured']) ? $credit ['OutstandingBalance'] : $credit ['CurrentDebt'];
							$totally = number_format ( $totally, 2, '.', '' );
							// $modx->documentObject['sumToPay'] = + $totally;
							$modx->documentObject ['sumToPay'] = $totally;
							// выбранная сумма проплаты:
							$modx->documentObject ['amount'] = ($_SESSION ['credit']['amountToPay']) ? number_format ( $_SESSION ['credit']['amountToPay'], 2, '.', '' ) : $totally;

							// $modx->documentObject['sumToPay'] = + $credit['Amount'] - $credit['amount_payed'] +
							// $credit['Schedule'][0]['Interest'] + $credit['Schedule'][0]['Fees'] ;
							
							$modx->documentObject ['credit_card_id'] = $credit ['credit_card'];
							$modx->documentObject ['docName'] = $credit ['PublicId'];
							$dateCreate = ($value['DisbursementDate']) ? : $value['CreationDate'];
							$modx->documentObject ['docDate'] = date ( 'd.m.Y', strtotime($dateCreate));
							// $modx->documentObject ['docDate'] = substr ( $credit ['CreationDate'], 8, 2 ) . '.' . substr ( $credit ['CreationDate'], 5, 2 ) . '.' . substr ( $credit ['CreationDate'], 0, 4 );
						}
						
						// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
						$res = $_SESSION['api']['cards'];	// получить список карт клиента
						$modx->documentObject ['cardName'] = $res [0] ['number'];
						
						// $res = $api->getContract($credit_id, $_SESSION['token']); // получить данные договора
						// $_SESSION['doc'] = $res;
						
						/*
						 * if (!isset($res['error'])) {
						 * $modx->documentObject['docName'] = $res['docName'];
						 * $modx->documentObject['docDate'] = substr($res['creationDateTime'], 8, 2) . '.' .
						 * substr($res['creationDateTime'], 5, 2)
						 * . '.' . substr($res['creationDateTime'], 0, 4);
						 * }
						 */
					}
					
					$modx->documentObject ['credit_id'] = $credit_id;
				}
				
				break;
			
			// Возврат пользователя из BankID:
			case 39 :
				
				// var_dump($_GET);die;
				
				if (isset ( $_GET ['code'] )) {
					$api_bankId = new BankidAPI ();
					$res = $api_bankId->getAccessToken ( $_GET ['code'] ); // запрос токена
					if (! isset ( $res ['error'] ) && isset ( $res ['access_token'] )) {
						$res = $api_bankId->getData ( $res ['access_token'] ); // запрос данных о клиенте
						if ($res ['state'] === 'ok') {
							// echo print_r($res['customer']);die;
							
							// добавляем данные, полученные из BankID:
							$data ['BankId'] = $res ['customer'];
							
							if (isset ( $_SESSION ['userData'] )) {
								$data = array_merge ( $_SESSION ['userData'], $data ); // добавляем к ранее введенным данным новые
							}
							$_SESSION ['userData'] = $data;
						}
					}
				}
				
				header ( "Location: " . $modx->makeUrl ( 17 ) );
				
				break;
			
			// История кредитов:
			case 40 :
			// История кредитов моб. версия:
			case 330 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				// получить список кредитов:
				// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
				// $_SESSION ['res'] = $res;
				$res = [];
				$res ['Success'] = (is_array($_SESSION['api']['credits'])) ? true : false;
				$res ['Data'] = $_SESSION['api']['credits'];
				
				if ($res ['Success']) {
					
					if (isset ( $_SESSION ['res'] ['Data'] )) {
						$credits = (is_array ( $_SESSION ['res'] ['Data'] )) ? $_SESSION ['res'] ['Data'] : [ ];
					} else {
						$credits = [];
					}
					
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); 
					// получить данные клиента
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					$_SESSION ['client'] = $res;
					if ($res ['Success']) {
						$modx->documentObject ['firstName'] = $res ['Data'] ['FirstName'];
						$modx->documentObject ['lastName'] = $res ['Data'] ['LastName'];
						$modx->documentObject ['thirdName'] = $res ['Data'] ['MiddleName'];
					}
					
					krsort($credits);	// сортировка в обратном порядке
					$credits = array_values($credits);	// сброс ключей массива
					
					// секция для пагинации:
					
					// для теста, удалить:
					// if (isset($credits[0])) for($i=1; $i<10; $i++) $credits[] = $credits[0];
					
					// первый показываемый кредит:
					$loansStart = ($modx->documentObject ['get_loans_start']) ? : 0;
					
					// количество кредитов на странице:
					$loansCount = ($config['loansCount']) ? : 4;
					
					// количество запиcей всего:
					$allCount = count($credits) ;
					
					// подготовим массив для вывода:
					$creditsOut = [];
					$creditsCount = 0;
					foreach ($credits as $key => $value) {
						if ($key < $loansStart) continue;
						if ($creditsCount >= $loansCount) break;
						$creditsOut[] = $value;
						$creditsCount ++;
					}
					
					$modx->setPlaceholder ( 'loansStart', $loansStart);
					$modx->setPlaceholder ( 'loansCount', $loansCount);
					$modx->setPlaceholder ( 'allCount', $allCount);
					
					if ($modx->documentObject ['get_loans_start'] === '0')
						header ( "Location: " . $modx->makeUrl ( $modx->documentIdentifier) ); // переход на  страницу без параметра
						
					// секция для пагинации - конец
						
					// Ниже для совместимости дизайна и снипета credits:
					$_SESSION ['res'] ['credits'] = [ ];
					foreach ( $creditsOut as $key => $value ) {
						$_SESSION ['res'] ['credits'] [$key] ['status'] = $value ['Status'];
						// ------ для теста
						// if ($value ['Id'] == 10) $_SESSION ['res'] ['credits'] [$key] ['status'] = 'Active';
						// ------ для теста конец
						$_SESSION ['res'] ['credits'] [$key] ['PublicId'] = $value ['PublicId'];
						
						$_SESSION ['res'] ['credits'] [$key] ['amount'] = $value ['Amount'];
						$_SESSION ['res'] ['credits'] [$key] ['amount_payed'] = 0;
						$_SESSION ['res'] ['credits'] [$key] ['percent'] = $value ['CurrentInterest'];
						$_SESSION ['res'] ['credits'] [$key] ['percent_payed'] = 0;
						$_SESSION ['res'] ['credits'] [$key] ['penalty'] = 0;
						$_SESSION ['res'] ['credits'] [$key] ['penalty_payed'] = 0;
						$_SESSION ['res'] ['credits'] [$key] ['CurrentDebt'] = $value ['CurrentDebt'];
						$_SESSION ['res'] ['credits'] [$key] ['NextPaymentAmount'] = ($value ['NextPaymentAmount']) ? : $value ['OutstandingBalance'];
						$dateCreate = ($value['DisbursementDate']) ? : $value['CreationDate'];
						$_SESSION ['res'] ['credits'] [$key] ['createDate'] = date ( 'd.m.Y', strtotime ( $dateCreate) );
						// срок кредита:
						if ($value ['NextPaymentDate']) {
							$_SESSION ['res'] ['credits'] [$key] ['days'] = ( int ) ((strtotime ( substr ( $value ['NextPaymentDate'], 0, 10 ) ) - strtotime ( substr ( $dateCreate, 0, 10 ) )) / 86400);
						} else {
							$_SESSION ['res'] ['credits'] [$key] ['days'] = $value ['Term'];
						}
						$_SESSION ['res'] ['credits'] [$key] ['longDate'] = '';
						$_SESSION ['res'] ['credits'] [$key] ['id'] = $value ['Id'];
						$_SESSION ['res'] ['credits'] [$key] ['IsLoanCanBeRollovered'] = $value ['IsLoanCanBeRollovered'];
					}
				}
				
				break;
			
			// Продление кредита:
			case 87 :
				
				if (! $_SESSION ['token']) {
					header ( "Location: " . $modx->makeUrl ( 1 ) );
					break;
				}

				// if ($_SESSION ['cred_id']) {
				// 	$credit_id = $_SESSION ['cred_id'];
				if ($_SESSION ['credit']) {
					$credit_id = $_SESSION ['credit']['Id'];
				} else {
					header ( "Location: " . $modx->makeUrl ( 4 ) );
					break;
				}
				
				// Получаем список кредитов:
				$res = [];
				$res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
				$res ['Data'] = $_SESSION['api']['credits'];
				
				$credits = (is_array ( $res ['Data'] )) ? $res ['Data'] : [ ];

				// найдем наш кредит:
				$credit = [ ];
				foreach ( $credits as $key => $value ) {
					if ($value ['Id'] == $credit_id) {
						$credit = $value;
						break;
					}
				}
				
				if ($credit !== [ ]) {
				    
				    // если пролонгировать нельзя:
//				    if (!$credit['IsLoanCanBeRollovered']) {
////				        header ( "Location: " . $modx->makeUrl ( 4 ) );
//				        break;
//				    }
				    
				    // считаем всего начислений:
					$pay = $credit ['CurrentTotalInterest'];
					
					// проверяем, если сумма задолженности мала, ставим ноль:
					if ($pay < 0.02)
						$pay = 0;
						// $pay = 7.77; // для теста !!!!!!!
						// $pay = 0; // для теста !!!!!!!
					
					$pay = number_format ( $pay, 2, '.', '' );
					
					$modx->documentObject ['pay'] = $pay;
					$modx->documentObject ['credit_id'] = $credit ['Id'];
					// $modx->documentObject ['sumToPay'] = $principal + $credit ['CurrentFees'] + $credit ['CurrentInterest'];
					$modx->documentObject ['sumToPay'] = number_format ( $credit ['CurrentTotalInterest'], 2, '.', '' );
					//$modx->documentObject ['Amount'] = number_format ( $credit ['Amount'], 2, '.', ' ' );
					$modx->documentObject ['Amount'] = number_format ( $credit ['OutstandingBalance'], 2, '.', '' );

					$modx->documentObject ['credit_card_id'] = $credit ['credit_card'];
					$modx->documentObject ['docName'] = $credit ['PublicId'];
					$dateCreate = ($value['DisbursementDate']) ? : $value['CreationDate'];
					// $modx->documentObject ['docDate'] = substr ( $credit ['CreationDate'], 8, 2 ) . '.' . substr ( $credit ['CreationDate'], 5, 2 ) . '.' . substr ( $credit ['CreationDate'], 0, 4 );
					$modx->documentObject ['docDate'] = date ( 'd.m.Y', strtotime($dateCreate));
					
					// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
					$res = $_SESSION['api']['cards'];	// получить список карт клиента
					$modx->documentObject ['cardName'] = $res [0] ['number'];
					
					// разница в днях от даты выдачи до сегодня:
					$maxlong = (new DateTime ($dateCreate))->diff(new DateTime ())->days; 	
					if ($maxlong > $credit ['Term'])
						$maxlong = $credit ['Term'];
						
					// получаем дату окончания срока:
					// $dateTmp = new DateTime($credit['CreationDate']);
					$dateTimeEnd = new DateTime ( $credit ['NextPaymentDate'] );
					// получаем дату максимальной пролонгации:
					$dateTimeEnd->add ( new DateInterval ( 'P' . $maxlong. 'D' ) ); // + дней максимальной пролонгации
					
					// проверим, дата максимальной пролонгации  больше, чем сегодня + Term
					$dateTimeTerm = new DateTime ();
					$dateTimeTerm->add ( new DateInterval ( 'P' . $credit ['Term']. 'D' ) ); // + дней срока кредита
					if ($dateTimeTerm < $dateTimeEnd)
						$dateTimeEnd = $dateTimeTerm;
					
					$dateEnd = $dateTimeEnd->format ( 'Y-m-d' ); // конечная дата возможного продления
					
					// начальная дата возможного продления (+1 день)
					$dateTimeBegin = new DateTime ( $credit ['NextPaymentDate'] );
					$dateTimeBegin->add(new DateInterval('P1D'));
					
					// если конечная дата меньше начальной (когда пролонгировать нельзя):
					if ($dateTimeEnd < $dateTimeBegin) {
						// ставим начальную дату на последний день кредита:
						$dateBegin = $dateEnd;
					} else {
						$dateBegin = $dateTimeBegin->format ( 'Y-m-d' );
					}
					
					if (! $_POST ['prolongation'])
						unset ( $_SESSION ['prolongation'] );
						
					// если была попытка оплатить проценты для последующей пролонгации:
					if ($_SESSION ['isPayForProlongation'] === '1') {
						if ($pay === '0.00') {
							unset ( $_SESSION ['isPayForProlongation'] );
						} else {
							if (!$_POST ['pay']['isProlongation']) header ( "Location: " . $modx->makeUrl ( 4 ) );
						}
					}
					
					$_SESSION ['prolongation'] ['dateBegin'] = $dateBegin;
					$_SESSION ['prolongation'] ['dateEnd'] = $dateEnd;
					$_SESSION ['prolongation'] ['credit_id'] = $credit_id;
				} else {
					unset ( $_SESSION ['prolongation'] );
				}
				
				$js .= 'setDatepicker("#datepicker", "' . $dateBegin . '", "' . $dateEnd . '");';
				
				break;
			
			// Новости:
			case 106 :

			    if ($modx->documentObject ['get_news_start'] === '0') {
					header ( "Location: " . $modx->makeUrl ( 106 ) ); // переход на  страницу без параметра
				} else {
				    $modx->setPlaceholder('urlParamForHref', '?news_start=' . $modx->documentObject ['get_news_start']);
				}
				break;
				
			// Оплата кредита (tranzzo):
			case 130 :
			// Оплата кредита (mob tranzzo):
			case 662 :
				
				if (! $_SESSION ['token']) {
					$_SESSION ['to_login'] = true;
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
				}
				
				if (! isset ( $_POST ['pay'] )) {
					unset ( $_SESSION ['pay'] );
				}
				
				if ($_SESSION ['cred_id']) {
					$credit_id = $_SESSION ['cred_id'];
					// unset($_SESSION['cred_id']);
					
					// $res = $api->getCustomerDetails ( $_SESSION ['token'] ); // получить данные клиента
					$res = [];
					$res ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$res ['Data'] = $_SESSION['api']['client'];
					$_SESSION ['client'] = $res;
					if ($res ['Success']) {
						$modx->documentObject ['firstName'] = $res ['Data'] ['FirstName'];
						$modx->documentObject ['lastName'] = $res ['Data'] ['LastName'];
						$modx->documentObject ['thirdName'] = $res ['Data'] ['MiddleName'];
						
						if ($modx->documentObject ['checkCompleteUserCard'] == '0') 
						    $_SESSION ['client']['error'] = 166;  // Вам необходимо обновить информацию в разделе ‘Мои карты‘
						
						// получаем список кредитов
						// $res = $api->getCustomerLoans ( $_SESSION ['token'] );
						// $_SESSION ['res'] = $res;
						$res = [];
						$res ['Success'] = ($_SESSION['api']['credits'] !== []) ? true : false;
						$res ['Data'] = $_SESSION['api']['credits'];
						
						if (! $res ['Success']) {
							$modx->documentObject ['error'] = 'Кредиты не найдены';
							
							$modx->documentObject ['credit_status'] = 'zero';
							$modx->documentObject ['amount'] = 0;
							$modx->documentObject ['days'] = 0;
							$modx->documentObject ['repay'] = 0;
							$modx->documentObject ['sumToPay'] = 0;
						} else {
							
						    if (isset ( $res ['Data'] )) {
								$credits = (is_array ( $_SESSION ['api'] ['credits'] )) ?  $_SESSION ['api'] ['credits'] : [ ];
								
								// найдем нужный кредит:
								$credit = [ ];
								foreach ( $credits as $key => $value ) {
									if ($value ['Id'] === ( int ) $credit_id) {
										$credit = $value;
										break;
									}
								}
							}
							
							$totally = ($credit ['IsRestructured']) ? $credit ['OutstandingBalance'] : $credit ['CurrentDebt'];
							$totally = number_format ( $totally, 2, '.', '' );
							// $modx->documentObject['sumToPay'] = + $totally;
							$modx->documentObject ['sumToPay'] = $totally;
							// выбранная сумма проплаты:
							$modx->documentObject ['amount'] = ($_SESSION ['credit']['amountToPay']) ? number_format ( $_SESSION ['credit']['amountToPay'], 2, '.', '' ) : $totally;
							
							// $modx->documentObject['sumToPay'] = + $credit['Amount'] - $credit['amount_payed'] +
							// $credit['Schedule'][0]['Interest'] + $credit['Schedule'][0]['Fees'] ;
							
							$modx->documentObject ['credit_card_id'] = $credit ['credit_card'];
							$modx->documentObject ['docName'] = $credit ['PublicId'];
							$dateCreate = ($value['DisbursementDate']) ? : $value['CreationDate'];
							$modx->documentObject ['docDate'] = date ( 'd.m.Y', strtotime($dateCreate));
							// $modx->documentObject ['docDate'] = substr ( $credit ['CreationDate'], 8, 2 ) . '.' . substr ( $credit ['CreationDate'], 5, 2 ) . '.' . substr ( $credit ['CreationDate'], 0, 4 );
						}
						
						// $res = $api->tranzzoGetCardsAll ( $_SESSION ['token'] ); // получить список карт клиента
						$res = $_SESSION['api']['tranzzoCards'];	// получить список карт клиента
						$cards = [];
						$_SESSION['card']['cards'] = is_array($res) ? $res : [];
						$modx->documentObject ['cardName'] = $res [0] ['number'];
						
						// $res = $api->getContract($credit_id, $_SESSION['token']); // получить данные договора
						// $_SESSION['doc'] = $res;
						
						/*
						 * if (!isset($res['error'])) {
						 * $modx->documentObject['docName'] = $res['docName'];
						 * $modx->documentObject['docDate'] = substr($res['creationDateTime'], 8, 2) . '.' .
						 * substr($res['creationDateTime'], 5, 2)
						 * . '.' . substr($res['creationDateTime'], 0, 4);
						 * }
						 */
					}
					
					$modx->documentObject ['credit_id'] = $credit_id;
				}
				
				// вычисляем url, куда возвращаться:
				$startLink = ($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
				$returnUrl = $startLink . $_SERVER['SERVER_NAME'] . "/{$modx->config ['lang']}/";
				if ($_SESSION['sat_id']) {
				    
				} elseif ($_SESSION['mob_id']) {
				    $returnUrl .= 'mob/moi-kredity';
				} else {
				    $returnUrl .= "lichnyj-kabinet/" . (($post ['isProlongation'] === '1') ? 'prodlenie-kredita' : 'moi-kredity') . '/';
				}
				$modx->documentObject ['returnUrl'] = $returnUrl;
				
				break;
				
			// Вход через партнера:
			case 132 :
				
				Affiliate::partnerProcessing($modx, $https);	// обрабатывает вход через партнеров (functions.php)
				
				header ( "Location: " . $modx->makeUrl ( 1 ) );
				
				break;

			// Главная страница (HOME1):
			case 149 :
				
			    if (isset($_POST ['auth']) && (0 < count ( $_POST ['auth'] ))) { // пришли данные с формы аутентификации
				
			    } else if (isset($_POST ['orderCredit']) && ((0 < count ( $_POST ['orderCredit'] ))) && (isset ( $_SESSION ['token'] ))) { // пришли данные с калькулятора
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					if (isset ( $_SESSION ['loan'] ))
						unset ( $_SESSION ['loan'] );
					header ( "Location: " . $modx->makeUrl ( 2 ) );
				} else if (isset ( $_SESSION ['to_login'] )) {
					
				} else {
					if (isset ( $_SESSION ['res'] ))
						unset ( $_SESSION ['res'] );
					// если токен есть, идти в Мои кредиты:
					// if (isset($_SESSION['token'])) header("Location: " . $modx->makeUrl(4));
				}
				
				if (isset ( $_SESSION ['orderCredit'] ) && ! isset ( $_SESSION ['token'] )) {
					// $js .= '$("#modal_auth").modal("show");';
				}
				// если кто-то не попал по назначению, так как не было аутентификации:
				if (isset ( $_SESSION ['to_login'] )) {
					unset ( $_SESSION ['to_login'] );
					$js .= '$("#modal_auth").modal("show");';
				}
				
				// если пришли данные по ссылке восстановления пароля:
				if (isset ( $_SESSION ['forgot'] ['token'] )) {
					header ( "Location: " . $modx->makeUrl ( 23 ) );
					break;
				}
				
				// если запомнили логин:
				if ($_COOKIE ["rememberLogin"]) {
					$modx->documentObject ['phone'] = $_COOKIE ["rememberLogin"];
					$modx->documentObject ['checked'] = 'checked';
				} else {
					$modx->documentObject ['phone'] = '';
					$modx->documentObject ['checked'] = '';
				}
				
				// чистим код регистрации:
				unset ( $_SESSION ['codeReg'] );
				unset ( $_SESSION ['codeRegConfirm'] );
				// чистим ранее введенные временные личные данные пользователя:
				unset ( $_SESSION ['userData'] );
				// чистим данные для востановления пароля:
				unset ( $_SESSION ['forgot'] );
				unset ( $_SESSION ['Success'] );
				
				break;
			
			// Регистрация карты:
			case 157 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mod_id']);
			// Регистрация карты (сателиты):
			case 321 :
			// Регистрация карты (моб.версия): 
			case 332 :
				
				if (! $_SESSION ['token']) {
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
					die;
				}
				
				if (isset ( $_SESSION ['loan'] ))
					$modx->documentObject ['loanId'] = $_SESSION ['loan'] ['LoanId'];
			
				unset ( $_SESSION ['card'] );
			
				// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
				$res = $_SESSION['api']['cards'];	// получить список карт клиента
				
				// если еще нет карты у клиента, возвращается '', поэтому модернизируем ответ:
				if (trim ( ( string ) $res [0] ['number'] ) !== '') {
					$_SESSION ['card'] ['cards'] = $res;
					$modx->documentObject ['cardsCount'] = 1;	// количество карт
				} else {
					$_SESSION ['card'] ['cards'] = [ ];
					$modx->documentObject ['cardsCount'] = 0;	// количество карт
				}
				if ($res [0] ['Success'])
					$modx->documentObject ['loanId'] = $res [0] ['id'];
								
				// если есть карта, запишем номер карты в номер счета данных пользователя:
				if ($res[0]['number']) {
					// $resCustomer = $api->getCustomerDetails ( $_SESSION ['token'] ); // прочитать данные пользователя
					$resCustomer = [];
					$resCustomer ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$resCustomer ['Data'] = $_SESSION['api']['client'];
					if ($resCustomer['Success']) {
						$resCustomer['Data']['Bank']['AccountNumber'] = $res[0]['number'];
						$resCustomerUpd = $api->updateCustomerDetails ( $resCustomer['Data'], $_SESSION ['token'] ); // изменить информацию о пользователе
					}
				}
					
				break;
				
			// Студенты:
			case 173 :
				
				$_SESSION ['hiddenPromoCode'] = $promocodes['Students'];
				// var_dump($_SESSION ['hiddenPromoCode']);die;
				
				break;
				
			// Блог:
			case 179 :
				
				if ($modx->documentObject ['get_news_start'] === '0') {
					header ( "Location: " . $modx->makeUrl ( 179 ) ); // переход на  страницу без параметра
				} else {
					$modx->setPlaceholder('urlParamForHref', '?news_start=' . $modx->documentObject ['get_news_start']);
				}
					
				break;
				
			// Сайт offline:
			case 225 :
			
				// если сайт запустили:
				if ($websiteActive && ($config['websiteActive'] == 1)) {
					
					// отсылаем список email в CRM, переименовывем файл:
					$dir = MODX_BASE_PATH . "/DesignAPI/logs/" . date('Y') . "/" . date('m') . "/";
					@mkdir($dir, 0777, true);
					$file = $dir . 'emailOnline_' . date("Ymd") . ".txt";
					$fileNew = $dir . 'emailOnline_' . date("Ymd") . '_' . date("His") . ".txt";
						
					if (file_exists($file)) {
						if(rename($file, $fileNew)) {

							$emails = [];
							$handle = @fopen($fileNew, "r");
							if ($handle) {
							    while (($buffer = fgets($handle, 4096)) !== false) {
							        $emails[] = preg_replace("/\s+/", "", $buffer); // удалить пробелы
							    }
							    fclose($handle);
							}
							$api->sendМailAddresses($emails);	// отправляем список email в CRM
						}
					}
					header ( "Location: " . $modx->makeUrl ( 1 ) );
				}
							
				break;
			
			// Студенты для редиректа:
			case 267 :
			
				header ( "Location: " . $modx->makeUrl ( 173 ) );
				
				break;
				
			// Личные данные для студентов:
			case 269 :

				if ($_POST['otherData']['StudentId']) {
					if ($_SESSION ['token']) {
						header ( "Location: " . $modx->makeUrl ( 2 ) );
					} else {
						header ( "Location: " . $modx->makeUrl ( 17 ) );
					}
				}
				
				break;
				
			// Отзывы:
			case 514 :
				
				// первый показываемый отзыв:
				$reviewsStart = ($modx->documentObject ['get_reviews_start']) ? : 0;
				
				// количество отзывов на странице:
				$reviewsCount = ($config['ReviewsCount']) ? : 6;

				// это модератор:
				$isModerator = isModerator('ReviewsModerators');

				$where = ($isModerator) ? '' : 'status=1';

				$reviewTable = new ReviewTable();
				
				// количество запиcей всего:
				$allCount = count(($reviewTable->findAll(false, $where)) ? : []) ;

				$reviewTable->setLimit("{$reviewsStart},{$reviewsCount}");
				$reviews = ($reviewTable->findAll('date_created desc', $where)) ? : [] ;
				
				$arrReviews = [];
				foreach ($reviews as $key => $review) {
					$arrReviews[] = $review->jsonSerialize();
				}

				$modx->setPlaceholder ( 'arrReviews', $arrReviews);
				$modx->setPlaceholder ( 'reviewsStart', $reviewsStart);
				$modx->setPlaceholder ( 'reviewsCount', $reviewsCount);
				$modx->setPlaceholder ( 'allCount', $allCount);
				$modx->setPlaceholder ( 'isModerator', $isModerator);
				
				/*
				for ($i = 0; $i < 10; $i++) {
					$reviewTable = new ReviewTable();
					$reviewTable->name = "Писатель_1$i";
					$reviewTable->review = "Написал отзыв № 1$i";
					$reviewTable->rating = rand(1, 5);
					//$reviewTable->answer = "Ответ № $i";
					$reviewTable->status = 0;
					$reviewTable->date_created = date('Y-m-d');
					$reviewTable->date_updated = date('Y-m-d');
					$reviewTable->insert();
				}
				*/
				if ($modx->documentObject ['get_reviews_start'] === '0')
					header ( "Location: " . $modx->makeUrl ( 514) ); // переход на  страницу без параметра
					
				break;

			// Мои карты:
			case 516 :
				unset($_SESSION['sat_id']);
				unset($_SESSION['mod_id']);
				
				// заглушка на Мои карты
				// header("Location: " . $modx->makeUrl(5));
				
				if (! $_SESSION ['token']) {
					
					if ($_SESSION['sat_id']) {
						header ( "Location: " . $modx->makeUrl ( 320 ) ); // если нет токена - на регистрацию
					} elseif ($_SESSION['mob_id']) {
						header ( "Location: " . $modx->makeUrl ( 323 ) ); // если нет токена - на главную
					} else {
						header ( "Location: " . $modx->makeUrl ( 1 ) ); // если нет токена - на главную
					}
					die;
				}
				
				if (isset ( $_SESSION ['loan'] ))
					$modx->documentObject ['loanId'] = $_SESSION ['loan'] ['LoanId'];
					
				unset ( $_SESSION ['card'] );
				
				// $res = $api->getCards ( $_SESSION ['token'] ); // получить список карт клиента
				$res = $_SESSION['api']['cards'];	// получить список карт клиента
				
				// если еще нет карты у клиента, возвращается '', поэтому модернизируем ответ:
				if (trim ( ( string ) $res [0] ['number'] ) !== '') {
					$_SESSION ['card'] ['cards'] = $res;
				} else {
					$_SESSION ['card'] ['cards'] = [ ];
				}
				if ($res [0] ['Success'])
					$modx->documentObject ['loanId'] = $res [0] ['id'];
					
				// если есть карта, запишем номер карты в номер счета данных пользователя:
				if ($res[0]['number']) {
					// $resCustomer = $api->getCustomerDetails ( $_SESSION ['token'] );
					// прочитать данные пользователя
					$resCustomer = [];
					$resCustomer ['Success'] = ($_SESSION['api']['client'] !== []) ? true : false;
					$resCustomer ['Data'] = $_SESSION['api']['client'];
					if ($resCustomer['Success']) {
						if ($resCustomer['Data']['Bank']['AccountNumber'] !== $res[0]['number']) {
							$resCustomer['Data']['Bank']['AccountNumber'] = $res[0]['number'];
							$resCustomerUpd = $api->updateCustomerDetails ( $resCustomer['Data'], $_SESSION ['token'] ); // изменить информацию о пользователе
						}
					}
				}
				
				// верификация карты:
				/*
				 * далее скрипт, если вводятся параметры карты на нашем сайте
				 * if (isset($_POST['addCard']['verify-id']) && ($_POST['addCard']['verify-id'] !== '')) {
				 * foreach ($res['cards'] as $key => $value) {
				 * if ($value['id'] === $_POST['addCard']['verify-id']) {
				 * $modx->documentObject['id'] = $value['id'];
				 * $number = substr($value['number'], 0, 4) . '-' . substr($value['number'], 4, 4) .
				 * '-' .substr($value['number'], 8, 4) . '-' .substr($value['number'], 12, 4);
				 * $modx->documentObject['number'] = $number;
				 * //$js .= '$("#modal_auth").modal("show"); $("[name=\'auth[login]\']").val("' . $post['login'] . '")';
				 * $js .= '$("#modal_card_verify").modal("show")';
				 * break;
				 * }
				 * }
				 * }
				 */
				
				break;
						
			// Мечты. Список:
			case 607 :
				
				// первый показываемый отзыв:
				$dreamsStart = ($modx->documentObject ['get_dreams_start']) ? : 0;
				
				// количество отзывов на странице:
				$dreamsCount = ($config['DreamsCount']) ? : 4;
				
				// это модератор:
				$isModerator = isModerator('DreamsModerators');
				
				$where = ($isModerator) ? '' : 'status=1';
				
				$dreamsTable = new DreamsTable();
				
				// количество запиcей всего:
				$allCount = count(($dreamsTable->findAll(false, $where)) ? : []) ;
				
				$dreamsTable->setLimit("{$dreamsStart},{$dreamsCount}");
				$dreams = ($dreamsTable->findAll('date_created desc', $where)) ? : [] ;
				
				$arrDreams = [];
				foreach ($dreams as $key => $dream) {
					$dreamTmp = $dream->jsonSerialize();
					if ($dreamTmp['file']) {
						$dreamTmp['file'] = '/' . $config['DreamsFilesPath'] . $dreamTmp['file'];	// добавляем полный путь
					} else {
						$dreamTmp['file'] = '/assets/images/wishes/gift.png';	// файл по умолчанию
					}
					$dreamTmp['liked'] = ($_SESSION['actionValentine']['myLikes'][$dreamTmp['id']]) ? : false;
					$arrDreams[] = $dreamTmp;
				}
				
				$modx->setPlaceholder ( 'arrDreams', $arrDreams);
				$modx->setPlaceholder ( 'dreamsStart', $dreamsStart);
				$modx->setPlaceholder ( 'dreamsCount', $dreamsCount);
				$modx->setPlaceholder ( 'allCount', $allCount);
				$modx->setPlaceholder ( 'isModerator', $isModerator);
				
				/*
				for ($i = 0; $i < 10; $i++) {
					$dreamsTable = new DreamsTable();
					$dreamsTable->findById(66);
					$dreamsTable->id = '';
					$dreamsTable->insert();
				}
				*/
				
				if ($modx->documentObject ['get_dreams_start'] === '0') {
					header ( "Location: " . $modx->makeUrl ( 607) ); // переход на  страницу без параметра
					die;
				}
				break;
					
			// Отправка уведомлений FireBase:
			case 666 :
			    
			    // это модератор:
			    if (!isModerator('FirebaseModerators')) {
			        header ( "Location: " . $modx->makeUrl (1) ); // переход на гл. страницу
			        die;
			    }
			    break;
			    
			// Пасхальная акция 2018г.:
			case 678 :
			    
			    if (isset($_SESSION['tryCount']) && ((int) $_SESSION['tryCount'] <= 3)) {
			        $modx->documentObject ['tryCount'] = $_SESSION['tryCount'];
			    } else {
			        $modx->documentObject ['tryCount'] = 3;
			    }
			    break;
			    
			    


		}
		
		break;
}

// выполняем накопленные js-скрипты:
// $js .= '$("#myModal").modal("show"); $("[name=\'auth[login]\']").val("aaaaaaaaa")';
if (isset ( $js )) {
    // включаем задержку:
    if ($modx->documentIdentifier == 1) {
        $modx->regClientScript ( '<script >setTimeout(function() { $(document).ready(function(){' . $js . '}); }, 500);</script>' );
    } else {
	   $modx->regClientScript ( '<script>$(document).ready(function(){' . $js . '});</script>' );
    }
}
