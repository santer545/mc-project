/**
 * Файл с набором функций на JavaScript для работы с MyCredit,
 * используется для взаимодействия фронтенда с API CRM MyCredit
 * 
 * @author Игорь Стебаев, Обраменко Александр, <Stebaev@mail.ru>
 * @copyright Copyright (c) 2018- Mycredit Company
 * @version 2.0
 * @package DesignAPI
 * @link http://www
 */

var flagRunQuery = false,
		flagPlayCred = false, // флаг анимации калькулятора
		flagReg = {phone: false, reCaptcha: false}, // флаги для регистрации
		pageInputType = {input: false, paste: false, lastTime: false}, // данные о способе ввода
		pageInputKeys = {Backspace: 0, Delete: 0, CtrlC: 0, CtrlV: 0, KeysCount: 0, startTime: false}, // данные о вводимых символах
		globalMoney = '0', // сумма после reloadCred()
		globalDay = '0', // дней после reloadCred()
		globalTimeStopSlider = 0, // дата/время после reloadCred(). До первого изменения равен 0
		//globalTimeStopSlider = new Date().getTime(),	// дата/время после reloadCred()
		// интервал простоя в милисекундах (сек. / 1000) калькулятора, после которого требуется запись состояния:
		intervalStopSlider,
		refreshTimerId,
		timerId,
		// массив префиксов мобильных телефонов:
		arrPrefix = ['39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'],
		// массив префиксов городских телефонов (для рабочего телефона)
		arrFixPrefix = [
			'31', '32', '33', '34', '35', '36', '37', '38', '41', '43', '44', '45',
			'46', '47', '48', '51', '52', '53', '54', '55', '56', '57', '61', '62', '64'
		],
		pwdInputCount = 0, // количество введенных символов
		userLocation = {}; // геолокация пользователя



/**
 * анализирует положение калькулятора при остановке, при необходимости фиксирует это
 * @param money
 * @param day
 * @param prefix
 * @param string changed {money | day}
 */
function analysisSlider(money, day, prefix, changed) {

	var intervalStopSlider = parseInt(document.getElementById("intervalStopSlider_" + prefix).value) * 1000;
	var timeStopSlider = new Date().getTime(); // дата/время после reloadCred()

	if ((timeStopSlider >= (globalTimeStopSlider + intervalStopSlider)) && (!flagPlayCred)) {

		// console.log('Простой калькулятора ' + (Math.round((timeStopSlider - globalTimeStopSlider) / 1000)) + " money=" + money + " day=" + day);
		// console.log('intervalStopSlider=' + intervalStopSlider);

		var sliderData = '{';

		var downTimeSlider = Math.round((timeStopSlider - globalTimeStopSlider) / 1000); // простой слайдера в секундах

		var dateSlider = new Date(globalTimeStopSlider);
		var zone = dateSlider.getTimezoneOffset() / 60 * (-1);
		sliderData += '"timeStopSlider":"' + dateSlider.getFullYear() + '-' + (dateSlider.getMonth() + 1) +
				'-' + dateSlider.getDate() + ' ' + dateSlider.getHours() +
				':' + ((dateSlider.getMinutes() < 10) ? '0' : '') + dateSlider.getMinutes() +
				':' + ((dateSlider.getSeconds() < 10) ? '0' : '') + dateSlider.getSeconds() +
				' ' + ((zone < 0) ? '-' : '+') + ((Math.abs(zone) < 10) ? '0' : '') + dateSlider.getTimezoneOffset() / 60 * (-1) + ':00", ';
		sliderData += '"downTimeSlider":' + downTimeSlider + ', ';
		sliderData += '"money":' + money + ', ';
		sliderData += '"days":' + day + ', ';
		sliderData += '"changed":"' + changed + '", ';
		sliderData += '"page":"' + window.location.pathname + '"}';

		// console.log(sliderData);

		// готовим данные для отправки:
		var data = {
			typeData: 'userInfo',
			sliderData: sliderData
		};

		// отправить массив на сервер
		//console.log("Передаем запрос ajax 'userInfo'");
		sendAjax(data);
	}
	globalTimeStopSlider = timeStopSlider; // обновляем временную метку
}

/**
 * проверяет, совпадает ли количество введенных символов в пароле с длиной пароля
 * @returns
 */
function checkPwdInputCount() {

	pwdInputCount++; // количество введенных символов 
	var pwdLength = $("#password-1-auth").val().length; // длина введенного пароля

	if (pwdInputCount > pwdLength) {
		pwdInputCount = pwdLength; // удаляли символы
	}
	if (pwdInputCount < pwdLength) {
		$("#password-1-auth").val(''); // введено больше одного символа за раз
		pwdInputCount = 0;
	}
	// console.log('pwdInputCount = '+pwdInputCount);

	return false;
}

/**
 * Возвращает символьное значение дня в зависимости от языка
 * @param string day 01 02 ... 31
 * @return string
 */
function getDayLang(day) {

	// получаем язык сайта 
	var lang = document.getElementById('lang').innerHTML;
	var dayStr = '';

	switch (lang) {

		case "ru":
			if (day.substring(day.length - 1) == '1' && day !== '11') {
				dayStr = 'день';
			} else if ((day.substring(day.length - 1) == '2' || day.substring(day.length - 1) == '3' || day.substring(day.length - 1) == '4') &&
					(day !== '11') && (day !== '12') && (day !== '13') && (day !== '14')) {
				dayStr = 'дня';
			} else {
				dayStr = 'дней';
			}
			break;

		case "ua":
			if (day.substring(day.length - 1) == '1' && day !== '11') {
				dayStr = 'день';
			} else if ((day.substring(day.length - 1) == '2' || day.substring(day.length - 1) == '3' || day.substring(day.length - 1) == '4') &&
					(day !== '11') && (day !== '12') && (day !== '13') && (day !== '14')) {
				dayStr = 'дні';
			} else {
				dayStr = 'днів';
			}
			break;

	}
	return dayStr;
}

/**
 * возвращает объект дня указанной даты
 * @param myDateStr
 * @returns
 */
function getDayOfWeek(myDateStr) {

	// массив праздников (формат: MM-DD):
	var holidays = ['01-01', '01-07', '03-08', '04-29', '05-01', '05-09', '06-17', '06-28', '08-24', '08-26', '10-14', '10-15', '12-25' ];
	
	// массив принудительно рабочих дней на выходных (формат: MM-DD):
	var notHolidays = ['00-23', ];

	// массив переводов:
	var daysLang = {};
	daysLang['ru'] = ['воскресенье', 'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота'];
	daysLang['ua'] = ['неділя', 'понеділок', 'вівторок', 'середа', 'четвер', 'п’ятниця', 'субота'];
	// массив коротких переводов:
	var daysLangShort = {};
	daysLangShort['ru'] = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
	daysLangShort['ua'] = ['нд', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
	
	// получаем язык сайта 
	var lang = document.getElementById('lang').innerHTML;

	var myDate = new Date(myDateStr);
	var dayNumber = myDate.getDay();
	
	var flagHoliday = 0;
	
	// если выходной:
	if (((dayNumber === 0) || (dayNumber === 6)) && (notHolidays.indexOf(myDateStr.substring(5)) == -1)) {
		flagHoliday = 1;	// признак выходного 
	}

	// если праздник:
	if (holidays.indexOf(myDateStr.substring(5)) !== -1) {
		flagHoliday = 2;	// признак праздника
	}
	
	var dayOfWeek = {
		dayNumber: dayNumber,
		dayString: daysLang[lang][dayNumber],
		dayStringShort: daysLangShort[lang][dayNumber],
		holiday: flagHoliday
	};
	
	return dayOfWeek; 
}

/**
 * возвращает информацию об устройстве (СPU, GPU -видеокарты)
 * @param 'CPU' | 'GPU'
 * @returns string
 */
function getDeviceInfo(param) {

	var result = '';

	switch (param) {

		case "CPU":
			var client = new ClientJS(); // Create A New Client Object
			var CPU = client.getCPU(); // Get CPU Architecture
			if (CPU != undefined)
				result = CPU;
			if (navigator.hardwareConcurrency != undefined)
				result += ' ' + navigator.hardwareConcurrency;
			break;

		case "GPU":
			var canvas = document.createElement('canvas');
			var gl;
			var debugInfo;
			var vendor;
			var renderer;

			try {
				gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
			} catch (e) {
			}

			if (gl) {
				try {
					debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
					if (debugInfo != null) {
						vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
						renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
					}
				} catch (e) {
				}
			}
			if (renderer != undefined)
				result = renderer;
			break;

	}

	return result;
}

/**
 * Возвращает символьное значение месяца в зависимости от языка
 * @param string Month 01 02 ... 12
 * @return string
 */
function getMonthLang(month) {

	// получаем язык сайта 
	var lang = document.getElementById('lang').innerHTML;
	var monthStr = '';

	switch (lang) {

		case "ru":
			switch (month) {
				case "01":
					monthStr = 'января';
					break;
				case "02":
					monthStr = 'февраля';
					break;
				case "03":
					monthStr = 'марта';
					break;
				case "04":
					monthStr = 'апреля';
					break;
				case "05":
					monthStr = 'мая';
					break;
				case "06":
					monthStr = 'июня';
					break;
				case "07":
					monthStr = 'июля';
					break;
				case "08":
					monthStr = 'августа';
					break;
				case "09":
					monthStr = 'сентября';
					break;
				case "10":
					monthStr = 'октября';
					break;
				case "11":
					monthStr = 'ноября';
					break;
				case "12":
					monthStr = 'декабря';
					break;
			}
			break;

		case "ua":
			switch (month) {
				case "01":
					monthStr = 'січня';
					break;
				case "02":
					monthStr = 'лютого';
					break;
				case "03":
					monthStr = 'березня';
					break;
				case "04":
					monthStr = 'квітня';
					break;
				case "05":
					monthStr = 'травня';
					break;
				case "06":
					monthStr = 'червня';
					break;
				case "07":
					monthStr = 'липня';
					break;
				case "08":
					monthStr = 'серпня';
					break;
				case "09":
					monthStr = 'вересня';
					break;
				case "10":
					monthStr = 'жовтня';
					break;
				case "11":
					monthStr = 'листопада';
					break;
				case "12":
					monthStr = 'грудня';
					break;
			}
			break;
	}
	return monthStr;
}

/**
 * Возвращает процент на основе анализа дочерних продуктов
 * @param money
 * @param day
 * @param maxDay
 * @param minDay
 * @param maxSum
 * @param minSum
 * @param percent
 * @param ChildProducts
 * 
 */
function getPercent(money, day, maxDay, minDay, maxSum, minSum, percent, ChildProducts) {

	if (ChildProducts !== '') {

		ChildProducts = JSON.parse(ChildProducts);
		// console.log(ChildProducts);

		var len = ChildProducts.length;
		var product;
		var maxSumTmp = maxSum + 1;
		var maxDayTmp = maxDay + 1;
		for (var i = 0; i < len; i++) {
			product = ChildProducts[i];
			// console.log('product = ' + product.MaxAmount + ' money = '+money+' day = '+ day);
			// стоит ли указатель в зоне действия продукта:
			if ((product.MaxAmount >= money) && (product.MinAmount <= money) &&
					(product.MaxTerm >= day) && (product.MinTerm <= day)) {
				// ищем минимальный возможный продукт:
				if (product.MaxAmount < maxSumTmp) {
					maxSumTmp = product.MaxAmount;
					percent = product.InterestRate; // перезаписываем процентную ставку
					// console.log('percent = ' + percent);
				}
			}
		}
	}

	return percent;
}

/**
 * взвращает случайное число от min до max
 * @param int min
 * @param int max
 * @return int
 */
function getRandomInt(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
}


/**
 * собирает данные сессии
 */
function getSessionData() {
	
	
	var geocoder = new google.maps.Geocoder,
		sessionData = {
			user_country: '#404',
			user_area: '#404',
			user_city: '#404',
			user_formatted_address: '#404',
			user_geometry: '#404'
		};

	
	
	sessionData = getDeviceData(sessionData);

	// отпечаток системы Fingerprint (асинхронный режим!!!):
	// var options = {excludeUserAgent: true};
	var options = {};
	new Fingerprint2(options).get(function(result, components){
		sessionData.fingerprint = result;
		var cpu = getDeviceInfo('CPU');	// получаем тип процессора с параллельными потоками
		if (cpu !== '') {
			components[components.length] = {key:'cpu', value:cpu};
		}
		var gpu = getDeviceInfo('GPU');	// получаем тип видеокарты
		if (gpu !== '') {
			components[components.length] = {key:'gpu', value:gpu};
		}

		var dataComponents = {
			fingerprintComponents: components
		}; 

		var options = {excludeUserAgent: true};	// отключаем использование UserAgent в формировании
		new Fingerprint2(options).get(function(result, components){
			sessionData.fingerprintDevice = result;

			// sessionData += '"fingerprintComponents":"' + components + '", ';
			// console.log(result); //a hash, representing your device fingerprint
			// console.log(components); // an array of FP components
			// console.log(sessionData);

			sessionData.end = 'ok';
			// console.log(sessionData);

			// готовим данные для отправки:
			var data = {
				typeData: 'userInfo',
				sessionData: JSON.stringify(sessionData),
				dataComponents: dataComponents
				// iovation: JSON.stringify(blackbox_info)
			};

			// отправить массив на сервер
			//console.log("Передаем запрос ajax 'userInfo'");
			sendAjax(data);
		});
	});
}


/**
 * высылает на сервер способ введения информации
 */
function sendPageInputType() {

	// pageInputType = {input: false, paste: false, lastTime : false},	// данные о способе ввода

	// готовим данные для отправки:
	var data = {
		typeData: "userInfo",
		pageInputType: pageInputType,
		pageInputKeys: pageInputKeys
	};

	// отправить массив на сервер
	// console.log("Передаем запрос ajax 'userInfo'");
	//console.log(data);
	sendAjax(data);

}


/**
 * отсылает data через ajax на локальный сервер
 * @param url
 */
function sendAjax(data, callback) {

	var url = "/ru/?ajax";

	// отправить массив на сервер
	// console.log("Передаем запрос ajax " + url);
	// console.log("Передаем запрос ajax " + data.typeData);

	$.ajax({
		url: url,
		type: 'POST',
		data: {data: data},
		dataType: 'json',
		//dataType: 'html',
		success: function (json) {
			if (json) {
				// var js = JSON.parse(json);
				var js = json;
				// console.log(js);
				if (typeof callback === 'function') {
					callback(js);
				}
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль
		}
	});
}

function getDeviceData(sessionData) {
	var
		//Ориентация устройства
		Landscape =	device.landscape(),
		Portrait = device.portrait(),

		// Определение устройства
		Mobile = device.mobile(),
		Tablet = device.tablet(),
		Desktop = !(Mobile || Tablet),
		iOS = device.ios(),
		iPad = device.ipad(),
		iPhone = device.iphone(),
		iPod = device.ipod(),
		Android = device.android(),
		Android_Phone = device.androidPhone(),
		Android_Tablet = device.androidTablet(),
		BlackBerry = device.blackberry(),
		BlackBerry_Phone = device.blackberryPhone(),
		BlackBerry_Tablet =	device.blackberryTablet(),
		Windows = device.windows(),
		Windows_Phone =	device.windowsPhone(),
		Windows_Tablet = device.windowsTablet(),
		Firefox_OS = device.fxos(),
		Firefox_OS_Phone = device.fxosPhone(),
		Firefox_OS_Tablet = device.fxosTablet();

	// корректировка в связи с некорректным определением Windows_Phone:
	// {"Mobile":true,"Tablet":true,"iPhone":true,"Android_Phone":true,"Windows_Phone":true,"Windows_Tablet":true}
	if (Mobile && Tablet && iPhone && Android_Phone && Windows_Phone && Windows_Tablet) {
		Tablet = false;
		iPhone = false;
		Android_Phone = false;
		Windows_Tablet = false;
	}

	if (Mobile) sessionData.Mobile = true;
	if (Tablet) sessionData.Tablet = true;
	if (Desktop) sessionData.Desktop = true;
	if (iOS) sessionData.iOS = true;
	if (iPad) sessionData.iPad = true;
	if (iPhone) sessionData.iPhone = true;
	if (iPod) sessionData.iPod = true;
	if (Android) sessionData.Android = true;
	if (Android_Phone) sessionData.Android_Phone = true;
	if (Android_Tablet) sessionData.Android_Tablet = true;
	if (BlackBerry) sessionData.BlackBerry = true;
	if (BlackBerry_Phone) sessionData.BlackBerry_Phone = true;
	if (BlackBerry_Tablet) sessionData.BlackBerry_Tablet = true;
	if (Windows) sessionData.Windows = true;
	if (Windows_Phone) sessionData.Windows_Phone = true;
	if (Windows_Tablet) sessionData.Windows_Tablet = true;
	if (Firefox_OS) sessionData.Firefox_OS = true;
	if (Firefox_OS_Phone) sessionData.Firefox_OS_Phone = true;
	if (Firefox_OS_Tablet) sessionData.Firefox_OS_Tablet = true;

	// console.log(navigator);
	if (navigator != undefined) {
		if (navigator.oscpu != undefined && navigator.oscpu.length > 0) sessionData.os_cpu = navigator.oscpu;
		if (navigator.vendor != undefined && navigator.vendor.length > 0) sessionData.navi_vendor = navigator.vendor;
		if (navigator.vendorSub != undefined && navigator.vendorSub.length > 0) sessionData.navi_vendorSub = navigator.vendorSub;
		if (navigator.productSub != undefined && navigator.productSub.length > 0) sessionData.navi_productSub = navigator.productSub;
		if (navigator.buildID != undefined && navigator.buildID.length > 0) sessionData.navi_buildID = navigator.buildID;
		if (navigator.userAgent != undefined && navigator.userAgent.length > 0) sessionData.navi_userAgent = navigator.userAgent;
		if (navigator.language != undefined && navigator.language.length > 0) sessionData.user_language = navigator.language;
		if (navigator.appCodeName != undefined && navigator.appCodeName.length > 0) sessionData.appCodeName = navigator.appCodeName;
		if (navigator.appName != undefined && navigator.appName.length > 0) sessionData.appName= navigator.appName;
		if (navigator.appVersion != undefined && navigator.appVersion.length > 0) sessionData.appVersion = navigator.appVersion;
		//if (navigator.geolocation != undefined && navigator.geolocation.getCurrentPosition().length > 0) sessionData.geolocation = navigator.geolocation.getCurrentPosition();

		//sessionData.Geolocation = navigator.Geolocation.GeolocationPrototype.getCurrentPosition();
	}

	// console.log(screen);
	if (screen != undefined) {
		if (screen.width != undefined) sessionData.screen_width = screen.width;
		if (screen.height != undefined) sessionData.screen_height = screen.height;
	}

	// Дата, время
	var userData = new Date();
	//console.log(userData);
	//sessionData.user_date = userData.getDate() + '.' + userData.getMonth() + '.' + userData.getFullYear();
	sessionData.user_date = userData.getFullYear() + '-' + (userData.getMonth() + 1) + '-' + userData.getDate();
	sessionData.user_time = userData.getHours() + ':' + userData.getMinutes();
	sessionData.user_timeZone = userData.getTimezoneOffset() / 60 * (-1);
	//sessionData.user_dateTime = userData.toString();
	var zone = userData.getTimezoneOffset()/60*(-1);
	sessionData.user_dateTime = userData.getFullYear() + '-' + (userData.getMonth() + 1) + '-' + userData.getDate() +
		' ' + userData.getHours() + ':' + ((userData.getMinutes() < 10)? '0' : '') + userData.getMinutes() + ':' +
		((userData.getSeconds() < 10)? '0' : '') + userData.getSeconds() + ' ' + 
		((zone < 0)? '-' : '+') + ((Math.abs(zone) < 10)? '0' : '') + userData.getTimezoneOffset()/60*(-1) + ':00';

	// console.log(navigator);
	// console.log(screen);
		
	return sessionData;
}

/**
 * динамически подключает скрипт 
 * @param string url
 * @returns
 */
function includeScript(url) {

	var script = document.createElement('script');
	script.src = url;

	// console.log(script);

	document.getElementsByTagName('head')[0].appendChild(script);
}

/**
 * Меняет язык сайта
 */
function onChangeLanguage(pageId) {

	if (pageId === '/')
		pageId = ''; // чтобы не было двойной "/" на главной странице

	var language = $("#language").val(); // значение выбранного селекта

	switch (language) {

		case "ru":
			location.href = '/ru/' + pageId;
			break;

		case "ua":
			location.href = '/ua/' + pageId;
			break;

		default:
			location.href = '/ru/' + pageId;
			break;
	}
}

/**
 * Обрабатывает Click для ввода промокода на калькуляторе
 * @returns
 */
function onClickGetPromocode(typeSlider) {

	// console.log("onClickGetPromocode");

	$('#promo-modal-calc').modal('hide');

	var promocode = $("#calc_promocode").val();

	var prefix = '_' + typeSlider;

	var url = "/ru/?ajax";

	var data = {
		typeData: 'setPromocode',
		promocode: promocode
	};

	// отправить массив на сервер
	// console.log("Передаем запрос ajax " + url);
	// console.log(data);

	$.ajax({
		url: url,
		type: 'POST',
		data: {data: data},
		dataType: 'json',
		success: function (json) {
			if (json) {
				//var js = JSON.parse(json);
				var js = json;

				console.log(js);

				if (js.message == 'OK') {

					// заполняем калькулятор:

					// если есть новый продукт:
					if (js.dataNew !== undefined) {

						$("#maxDay" + prefix).val(js.dataNew.maxDay);
						$("#minDay" + prefix).val(js.dataNew.minDay);
						$("#maxSum" + prefix).val(js.dataNew.maxSum);
						$("#minSum" + prefix).val(js.dataNew.minSum);

						$("#js-money" + prefix).slider({min: js.dataNew.minSum, max: js.dataNew.maxSum});
						$("#js-days" + prefix).slider({min: js.dataNew.minDay, max: js.dataNew.maxDay});

						$("#minSumLabel" + prefix).text(js.dataNew.minSum);
						$("#minDayLabel" + prefix).text(js.dataNew.minDay);

						// получаем процентную ставку на основании дочерних кредитных продуктов:
						$("#ChildProducts" + prefix).text((js.dataNew.ChildProducts !== undefined) ? js.dataNew.ChildProducts : '');
						$("#percent-value" + prefix).val(js.dataNew.percent);
					}

					// если есть "старый" продукт:
					if (js.dataNormal !== undefined) {

						// получаем процентную ставку на основании дочерних кредитных продуктов:
						$("#ChildProductsNormal" + prefix).text((js.dataNormal.ChildProducts !== undefined) ? js.dataNormal.ChildProducts : '');
						$("#percent-valueNormal" + prefix).val(js.dataNormal.percent);
					}

					reloadCred(typeSlider);

				} else if (js.errorCode === 1) {
					$('#promo-modal-calc-sign').modal('show'); // Вам необходимо войти в личный кабинет
				} else if (js.errorCode === 2) {
					$('#promo-modal-calc-error').modal('show'); // Промокод не действителен или время его действия истекло
				} else {
					$("#span_error").text(js.message_details);
					$('#data-error').modal('show'); // Error через modal
				}
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль

			$('#data-error').modal('show'); // Error через modal
		}
	});

	return false;


}

/**
 * обрабатывает Click на "Выгодных тарифах" на главной
 */
function onClickGreatRates(prefix, money, day, action) {

	ga('send', 'event', action, 'Click'); // аналитика Google
	// eval("yaCounter" + YandexMetrikaId + ".reachGoal('click" + action + "');");	// аналитика Yandex

	$("#GreatRates_money_" + prefix).val(money);
	$("#GreatRates_days_" + prefix).val(day);
	window.document.forms['form_GreatRates_' + prefix].submit();
}

/**
 * обрабатывает Click на форме слайдера
 */
function onClickFormSendMail() {
	if ($("span").is("#span_emailOK"))
		document.getElementById("span_emailOK").innerHTML = '';
}

/**
 * обрабатывает Click на форме слайдера
 */
function onClickFormSlider(prefix) {
	document.getElementById("flagClickFromPlay_" + prefix).value = '1'; // флаг для остановки анимации
	flagPlayCred = false; // флаг анимации калькулятора
}

/**
 * обрабатывает Click кнопки "Войти" в момент входа в ЛК
 * @returns
 */
function onClickLogin() {

	$('#buttonLogin1').attr('disabled', true);

	sendPageInputType(); // высылает на сервер способ введения информации
	setTimeout(commitForm, 500); // задержка, без нее возвращается ошибка (ответ от ajax)

	function commitForm() {

		ga('send', 'event', 'Kabinet', 'Click'); // аналитика Google

		if (window.document.forms['auth'] != null)
			window.document.forms['auth'].submit();
	}
}

/**
 * обрабатывает кнопку "Получить кредит" слайдера
 */
function onClickSubmitSlider(prefix) {

	// анализ переключений калькулятора:
	// analysisSlider(globalMoney, globalDay, prefix);

	ga('send', 'event', 'calculator', 'Click'); // аналитика Google

	window.document.forms['form_slider_' + prefix].submit();
}

/**
 * проверяет вводимый телефон
 * @param idPhone id поля input
 * @returns {Boolean}
 */
function onKeyUpPhone(idPhone, mob) {

	var str = $("#" + idPhone).val();
	var strNum = str.replace(/\D+/g, ""); // оставляем только цифры

	// console.log('str='+str);

	if (strNum.length > 4) {
		var flag = false; // признак недопустимости номера

		if (str.substring(0, 4) !== '+380') {
			flag = true;
		} else {
			var prefix = strNum.substring(3, 5); // префикс оператора
			flag = true;
			
			if (mob) {
				var arrPhonePrefix = arrPrefix;
			} else {
				var arrPhonePrefix = arrPrefix.concat(arrFixPrefix);
			}
			
			// если префикс невалидный
			if (arrPhonePrefix.indexOf(prefix) !== -1) {
				flag = false;
			}
		}

		// если недопустимый номер:
		if (flag) {
			$("#" + idPhone).val("+380"); // начальное значение
			strNum = '380';
			$("#" + idPhone).mask("+38999 999 9999", {
				autoclear: false
			});
			$("#" + idPhone).focus(); // установить фокус
			$("#" + idPhone).selectionStart = 4; // позиция курсора 
		}

		// делаем активными/неактивными элементы формы:
		// if ((strNum.length == 12) && (document.getElementById("regCodereg").value.length == 0)) {
		if ((strNum.length == 12) && (($('#regCodereg').val() == '') || ($("#regCodereg").length = 0))) {
			$('#buttonGetCode').removeAttr('disabled');

			if (flagReg.reCaptcha) {
				$('#buttonGetCode').removeAttr('disabled');
			}
			flagReg.phone = true;
		} else {
			$('#buttonGetCode').attr('disabled', true);
			flagReg.phone = false;
		}
		// console.log(flagReg);

	} else {
		$('#buttonGetCode').attr('disabled', true);
		if (strNum.length < 4) {
			$("#" + idPhone).val("+380"); // начальное значение
			$("#" + idPhone).mask("+38999 999 9999", {
				autoclear: false
			});
			$("#" + idPhone).focus(); // установить фокус
			$("#" + idPhone).selectionStart = 4; // позиция курсора 
		}
	}

	return true;
}

/**
 * обрабатывает onkeyUp на поле ввода промокода
 */
function onkeyUpPromoCode() {

	$('#buttonRefreshProduct').removeAttr('disabled');
	return false;
}

// Obramenko на главную
/**
 * обрабатывает onkeyUp на money и day в калькуляторе
 * @param event
 * @param typeSlider
 */
function onkeyUpSlider(event, typeSlider) {

	// typeSlider = 'large' - большой слайдер

	/*
	 if (typeSlider == 'large') {
	 var prefix = '_large';
	 } else {
	 var prefix = '';
	 }
	 */

	var prefix = '_' + typeSlider;

	document.getElementById("flagClickFromPlay" + prefix).value = '1'; // флаг для остановки анимации

	if (event.keyCode == 13 || event.keyCode == 9)
		reloadCred(typeSlider);

	// console.log(event.keyCode);

	var maxDay = parseInt(document.getElementById("maxDay" + prefix).value);
	var minDay = parseInt(document.getElementById("minDay" + prefix).value);
	var maxSum = parseInt(document.getElementById("maxSum" + prefix).value);
	var minSum = parseInt(document.getElementById("minSum" + prefix).value);

	var money = parseInt(document.getElementById("money-value" + prefix).value);
	var day = parseInt(document.getElementById("day-value" + prefix).value);

	// console.log('money='+money+' day='+day);

	// проверка валидности данных
	//if (isNaN(money) || (money < minSum) || (money > maxSum) || isNaN(day) || (day < minDay) || (day > maxDay)) {
	if ((money > maxSum) || (day > maxDay)) {
		reloadCred(typeSlider);
	}

}

// Obramenko, забираю на главную
/**
 * запускает обновление всех калькуляторов на  странице, при надобности - анимацию.
 */
function onLoadSlider() {

	// обрабатываем все калькуляторы:
	$("[class^=nameSlider]").each(function (i, elem) {

		var nameSlider = $(elem).text();
		var isPlay = document.getElementById("isPlay_" + nameSlider).innerHTML;

		reloadCred(nameSlider);

		if (isPlay === 'yes') {
			playCred(nameSlider);
		}
	});
}

// Obramenko, на главную
/**
 * запускает анимацию калькулятора
 */
function playCred(prefix) {

	var maxDay = parseInt(document.getElementById("maxDay_" + prefix).value);
	var minDay = parseInt(document.getElementById("minDay_" + prefix).value);
	var maxSum = parseInt(document.getElementById("maxSum_" + prefix).value);
	var minSum = parseInt(document.getElementById("minSum_" + prefix).value);

	var step = parseInt(document.getElementById("step_" + prefix).value);

	var money = parseInt(document.getElementById("money-value_" + prefix).value);
	var day = parseInt(document.getElementById("day-value_" + prefix).value);

	var timeAll = 3; // время прохода в один конец в секундах
	var timePlay = timeAll * 1000 / ((maxSum - minSum) / step); // задержка между двумя шагами в money
	timePlay = Math.round(timePlay); // округляем
	var timePlayDay = timeAll * 1000 / ((maxDay - minDay)); // задержка между двумя шагами в day
	timePlayDay = Math.round(timePlayDay); // округляем
	// var moneyEnd = (maxSum - minSum) * 2 / 3;	// значение для остановки money (2/3 от размера)
	// var dayEnd = (maxDay - minDay) * 2 / 3;		// значение для остановки day (2/3 от размера)
	var moneyEnd = (maxSum) * 1 / 3; // значение для остановки money (1/3 от максимального)
	var dayEnd = (maxDay) * 2 / 5; // значение для остановки day (2/5 от максимального)

	// передвигает ползунок суммы вправо до конца (пошагово рекурсивно)
	function toRightMoney(maxSumPlay) {

		var dateReal = new Date();
		// время задержки, расчитываемое с учетом потраченного времени на прорисовку:
		var timePlayReal = dateReal.getTime();

		// если есть флаг клика по форме, останавливаем процесс:
		if (document.getElementById("flagClickFromPlay_" + prefix).value == '1')
			return;

		money += step;
		document.getElementById("money-value_" + prefix).value = money.toString();
		reloadCred(prefix);
		$("#js-money_" + prefix).slider('setValue', money);
		if (money < maxSumPlay) {
			dateReal = new Date();
			timePlayReal = dateReal.getTime() - timePlayReal; // сколько потрачено времени
			if (timePlayReal >= timePlay) {
				timePlayReal = 1; // если потрачена вся задержка
			} else {
				timePlayReal = timePlay - timePlayReal; // остаток задержки
			}
			setTimeout(toRightMoney, timePlayReal, maxSumPlay); //
		} else {
			toLeftMoney(moneyEnd);
		}
	}

	// передвигает ползунок суммы влево до 2/3 (пошагово рекурсивно)
	function toLeftMoney(moneyEndPlay) {

		var dateReal = new Date();
		// время задержки, расчитываемое с учетом потраченного времени на прорисовку:
		var timePlayReal = dateReal.getTime();

		// если есть флаг клика по форме, останавливаем процесс:
		if (document.getElementById("flagClickFromPlay_" + prefix).value == '1')
			return;

		money -= step;
		document.getElementById("money-value_" + prefix).value = money.toString();
		reloadCred(prefix);
		$("#js-money_" + prefix).slider('setValue', money);
		if (money > moneyEndPlay) {
			dateReal = new Date();
			timePlayReal = dateReal.getTime() - timePlayReal; // сколько потрачено времени
			if (timePlayReal >= timePlay) {
				timePlayReal = 1; // если потрачена вся задержка
			} else {
				timePlayReal = timePlay - timePlayReal; // остаток задержки
			}
			setTimeout(toLeftMoney, timePlayReal, moneyEndPlay); //
		} else {
			toRightDay(maxDay); // пробег до конца
			// toRightDay(dayEnd);
		}
	}

	// передвигает ползунок дней вправо до конца (пошагово рекурсивно)
	function toRightDay(maxDayPlay) {

		var dateReal = new Date();
		// время задержки, расчитываемое с учетом потраченного времени на прорисовку:
		var timePlayReal = dateReal.getTime();

		// если есть флаг клика по форме, останавливаем процесс:
		if (document.getElementById("flagClickFromPlay_" + prefix).value == '1')
			return;

		day += 1;
		document.getElementById("day-value_" + prefix).value = day.toString();
		reloadCred(prefix);
		$("#js-days_" + prefix).slider('setValue', day);
		if (day < maxDayPlay) {
			dateReal = new Date();
			timePlayReal = dateReal.getTime() - timePlayReal; // сколько потрачено времени
			if (timePlayReal >= timePlayDay) {
				timePlayReal = 1; // если потрачена вся задержка
			} else {
				timePlayReal = timePlayDay - timePlayReal; // остаток задержки
			}
			setTimeout(toRightDay, timePlayReal, maxDayPlay); //
		} else {
			toLeftDay(dayEnd);
		}
	}

	// передвигает ползунок дней влево до 2/3 (пошагово рекурсивно)
	function toLeftDay(dayEndPlay) {

		var dateReal = new Date();
		// время задержки, расчитываемое с учетом потраченного времени на прорисовку:
		var timePlayReal = dateReal.getTime();

		// если есть флаг клика по форме, останавливаем процесс:
		if (document.getElementById("flagClickFromPlay_" + prefix).value == '1')
			return;

		day -= 1;
		document.getElementById("day-value_" + prefix).value = day.toString();
		reloadCred(prefix);
		$("#js-days_" + prefix).slider('setValue', day);
		if (day > dayEndPlay) {
			dateReal = new Date();
			timePlayReal = dateReal.getTime() - timePlayReal; // сколько потрачено времени
			if (timePlayReal >= timePlayDay) {
				timePlayReal = 1; // если потрачена вся задержка
			} else {
				timePlayReal = timePlayDay - timePlayReal; // остаток задержки
			}
			setTimeout(toLeftDay, timePlayReal, dayEndPlay); //
		} else {
			flagPlayCred = false; // флаг анимации калькулятора
		}
	}

	// запускаем анимацию
	flagPlayCred = true; // флаг анимации калькулятора
	toRightMoney(maxSum); // пробег до конца 
	// toRightMoney(moneyEnd);
}

/**
 * перезаписывает данные в калькуляторе кредита
 * 
 * @returns {Boolean}
 */
function reloadCred(typeSlider) {

	

	var prefix = '_' + typeSlider;
	// если еще не было перезаписи, устанавливаем время:
	if (globalTimeStopSlider == 0)
		globalTimeStopSlider = new Date().getTime(); // дата/время после reloadCred()

	var maxDay = parseInt(document.getElementById("maxDay" + prefix).value);
	var minDay = parseInt(document.getElementById("minDay" + prefix).value);
	var maxSum = parseInt(document.getElementById("maxSum" + prefix).value);
	var minSum = parseInt(document.getElementById("minSum" + prefix).value);

	var money = parseInt(document.getElementById("money-value" + prefix).value);
	var day = parseInt(document.getElementById("day-value" + prefix).value);

	// проверка валидности данных
	if (isNaN(money)) {
		money = minSum;
		document.getElementById("money-value" + prefix).value = money.toString();
	}
	if (money < minSum) {
		money = minSum;
		document.getElementById("money-value" + prefix).value = money.toString();
	}
	if (money > maxSum) {
		money = maxSum;
		document.getElementById("money-value" + prefix).value = money.toString();
	}
	if (isNaN(day)) {
		day = minDay;
		document.getElementById("day-value" + prefix).value = day.toString();
	}
	if (day < minDay) {
		day = minDay;
		document.getElementById("day-value" + prefix).value = day.toString();
	}
	if (day > maxDay) {
		day = maxDay;
		document.getElementById("day-value" + prefix).value = day.toString();
	}

	// получаем процентную ставку на основании дочерних кредитных продуктов:
	var ChildProducts = $("#ChildProducts" + prefix).text();
	var percent = $("#percent-value" + prefix).val();
	percent = getPercent(money, day, maxDay, minDay, maxSum, minSum, percent, ChildProducts);

	// "старый" продукт:
	var ChildProductsNormal = $("#ChildProductsNormal" + prefix).text();
	var percentNormal = $("#percent-valueNormal" + prefix).val();
	// если нет "старых" процентов, то не показываем
	if (percentNormal == -1) {
		$(".calculator-formula").addClass("hidden");
	} else {
		$(".calculator-formula").removeClass("hidden");
	}

	percentNormal = getPercent(money, day, maxDay, minDay, maxSum, minSum, percentNormal, ChildProductsNormal);

	if (percent <= 0.04) {
		$(".slider-selection").css('background-color', '#fc7f2b');
		$(".slider-handle").css('background-color', '#fc7f2b');
		$(".slider-money-share").addClass("active"); // подпись 0% до 500
	} else {
		$(".slider-selection").css('background', '#0056b8');
		$(".slider-handle").css('background-color', '#0056b8');
		$(".slider-money-share").removeClass("active"); // подпись 0% до 500
	}

	var resolution = document.getElementById("resolution" + prefix).value;

	money = money.toString();
	day = day.toString();

	var today = new Date(),
			lastDate = new Date();

	lastDate.setDate(today.getDate() + parseInt(day));
	var lastDay = (lastDate.getDate() > 9) ? lastDate.getDate() : '0' + lastDate.getDate();
	var lastMonth = (lastDate.getMonth() > 8) ? '' + (lastDate.getMonth() + 1) : '0' + (lastDate.getMonth() + 1);
	var lastYear = lastDate.getFullYear();

	today.setMinutes(today.getMinutes() + parseInt(resolution));
	var hour = (today.getHours() > 9) ? '' + today.getHours() : '0' + today.getHours();
	var minute = (today.getMinutes() > 9) ? '' + today.getMinutes() : '0' + today.getMinutes();

	var dayStr = getDayLang(day);
	
	var comission = (credCalculation(money, day, percent) - money) + '',
		comissionOld = (credCalculation(money, day, percentNormal) - money) + '';

	// заполняем слайдер:
	if ($("span").is("#span_amount" + prefix))
		document.getElementById("span_amount" + prefix).innerHTML = '' + money;
	if ($("span").is("#span_amount_day" + prefix))
		document.getElementById("span_amount_day" + prefix).innerHTML = '' + day;
	if ($("span").is("#span_amount_all" + prefix))
		document.getElementById("span_amount_all" + prefix).innerHTML = credCalculation(money, day, percent) + '';
	if ($("span").is("#span_amount_all_end" + prefix))
		document.getElementById("span_amount_all_end" + prefix).innerHTML = ' ' + lastDay + '.' + lastMonth + '.' + lastYear;
	if ($("span").is("#span_get_money" + prefix))
		document.getElementById("span_get_money" + prefix).innerHTML = ' <strong>' + hour + ':' + minute + '</strong>';
	// заполняем комиссию:
	if ($("span").is("#span_commission" + prefix))
		document.getElementById("span_commission" + prefix).innerHTML = comission;
	if ($("span").is("#span_commission_old" + prefix))
		document.getElementById("span_commission_old" + prefix).innerHTML = comissionOld;
	// заполняем дня - день - дней:
	if ($("span").is("#dayMinDay" + prefix))
		document.getElementById("dayMinDay" + prefix).innerHTML = getDayLang(minDay.toString()); // "дней" в minDay
	if ($("span").is("#dayMaxDay" + prefix))
		document.getElementById("dayMaxDay" + prefix).innerHTML = getDayLang(maxDay.toString()); // "дней" в maxDay
	if ($("span").is("#dayMaxDayManual" + prefix))
		document.getElementById("dayMaxDayManual" + prefix).innerHTML = getDayLang(document.getElementById("span_dayMaxDayManual" + prefix).innerHTML); // "дней" в maxDayManual (ручная верстка)

	if ($("span").is("#daySelectDay" + prefix))
		document.getElementById("daySelectDay" + prefix).innerHTML = dayStr; // "дней" выбрано
	if ($("span").is("#dayLargeDay" + prefix))
		document.getElementById("dayLargeDay" + prefix).innerHTML = dayStr; // "дней" всего

	

	// заполняем минимальную форму слайдера:
	if ($("span").is("#span_amount_minForm"))
		document.getElementById("span_amount_minForm").innerHTML = '' + money;
	if ($("span").is("#span_amount_day_minForm"))
		document.getElementById("span_amount_day_minForm").innerHTML = '' + day;
	if ($("span").is("#span_amount_all_minForm"))
		document.getElementById("span_amount_all_minForm").innerHTML = credCalculation(money, day, percent) + '';
	if ($("span").is("#span_amount_all_end_minForm"))
		document.getElementById("span_amount_all_end_minForm").innerHTML = ' ' + lastDay + '.' + lastMonth + '.' + lastYear;
	// заполняем дня - день - дней:
	if ($("span").is("#dayMinForm"))
		document.getElementById("dayMinForm").innerHTML = dayStr; // "дней" всего

	// заполняем значения всех слайдеров:
	$("input[id^='money-value']").val('' + money); // все input, у которых id начинается с money-value
	$("input[id^='day-value']").val('' + day); // все input, у которых id начинается с day-value

	$("input[id^='day-value']").val('' + day); // все input, у которых id начинается с day-value
	

	// заполняем данные в шапке сайта:
	if ($("span").is("#span_amount_header"))
		document.getElementById("span_amount_header").innerHTML = '' + money;
	if ($("span").is("#span_day_header"))
		document.getElementById("span_day_header").innerHTML = '' + day;

	// заполняем данные в теле гл.страницы:
	$(".strongMaxSum").html(maxSum.toString().replace(/(\d)(?=(\d\d\d)+([^\d]|$))/g, '$1 ')); // проставляем пробел между разделами
	$(".strongTimeResolution").html(hour + ':' + minute);

	// обновить слайдеры выбранного калькулятора:
	//$("#js-money_" + prefix).slider('setValue', parseInt(money));
	//$("#js-days_" + prefix).slider('setValue', parseInt(day));

	// обновить все слайдеры на странице:
	$("[id^=js-money_]").slider('setValue', parseInt(money));
	$("[id^=js-days_]").slider('setValue', parseInt(day));

	// обновить все второстепенные значения на странице:
	$("[id^=span_amount]:not([id*='day']):not([id*='all'])").text('' + money);
	$("[id^=span_amount_day]").text('' + day);
	$("[id^=span_amount_all]:not([id*='end'])").text(credCalculation(money, day, percent) + '');
	$("[id^=span_amount_all_end]").text(' ' + lastDay + '.' + lastMonth + '.' + lastYear);
	$("[id^=span_get_money]").html(' <strong>' + hour + ':' + minute + '</strong>');
	$("[id^=span_comission]").text(comission);
	$("[id^=span_comission_old]").text(comissionOld);
	// заполняем дня - день - дней:
	$("[id^=dayMinDay]").text(getDayLang(minDay.toString()));
	$("[id^=dayMaxDay_]").text(getDayLang(maxDay.toString()));
	$("[id^=daySelectDay]").text(dayStr);
	$("[id^=dayLargeDay]").text(dayStr);

	// Работа с календарем (выходные, праздники):
	var dayOfWeek = getDayOfWeek(lastYear + '-' + lastMonth + '-' + lastDay);
	// console.log('holiday = ' + dayOfWeek.holiday + ' ' + dayOfWeek.dayString + ' ' + dayOfWeek.dayStringShort);
	if (dayOfWeek.holiday > 0) {
		$(".js-holiday-day").text('(' + dayOfWeek.dayStringShort + ')');
		$(".js-holiday-parent").addClass('data--attention');
		$(".js-holiday-message").removeClass("hidden");
	} else {
		$(".js-holiday-day").text('');
		$(".js-holiday-parent").removeClass('data--attention');
		$(".js-holiday-message").addClass("hidden");
	}
	

	// анализ переключений калькулятора:
	if ((money !== globalMoney) || (day !== globalDay)) {
		// analysisSlider(globalMoney, globalDay, typeSlider);
		globalMoney = money;
		globalDay = day;
	}

	//console.log('reload '+typeSlider);
	return false;
}

/**
 * открывает модальное окно авторизации
 */
function showModalAuth(login) {
	$("[name=\'auth[login]\']").val(login);
	$("#modal_auth").modal("show");

	// console.log('login = ', login);
}

// Obramenko , забираю на главную
/**
 * открывает модальное окно, если есть ошибка при регистрации
 */
function showModalRegistrationError() {
	var textErr = $("#span-error-text").text();
	var textErr1 = $("#span-error-text1").text();
	if ((textErr.length != 0) || (textErr1.length != 0)) {
		$("#registration-error").modal("show");
	}
}

// Obramenko , забираю на главную
/**
 * запускает submit формы оформления кредита
 * @param string prefix
 * @returns {Boolean}
 */
function submitCredit(prefix) {

	// анализ переключений калькулятора:
	// analysisSlider(globalMoney, globalDay, prefix);

	var money = document.getElementById("money-value_" + prefix).value;
	var days = document.getElementById("day-value_" + prefix).value;

	document.getElementById("money-value-submit").value = money;
	document.getElementById("day-value-submit").value = days;

	if (window.document.forms['form_credit'] != null) {

		ga('send', 'event', 'Application', 'Click'); // аналитика Google
		// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickApplication');");	// аналитика Yandex

		window.document.forms['form_credit'].submit();
	}
	if (window.document.forms['js-form-3'] != null)
		window.document.forms['js-form-3'].submit();

	// console.log('money='+money+' days='+days+'');

	return false;
}

/**
 * Расчитывает сумму кредита, которую необходимо вернуть
 * 
 * @param string amount
 * @param string day
 * @param string percent
 */
function credCalculation(amount, day, percent) {

	var amountOut = parseInt(amount) + (parseInt(amount) * parseFloat(percent) / 100 * parseInt(day));

	amountOut = Math.floor(amountOut).toString();

	return amountOut;
}


//возвращает cookie с именем name, если есть, если нет, то undefined
function getCookie(name) {
	var matches = document.cookie.match(new RegExp(
			"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
			));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options) {
	options = options || {};

	var expires = options.expires;

	if (typeof expires == "number" && expires) {
		var d = new Date();
		d.setTime(d.getTime() + expires * 1000);
		expires = options.expires = d;
	}
	if (expires && expires.toUTCString) {
		options.expires = expires.toUTCString();
	}

	value = encodeURIComponent(value);

	var updatedCookie = name + "=" + value;

	for (var propName in options) {
		updatedCookie += "; " + propName;
		var propValue = options[propName];
		if (propValue !== true) {
			updatedCookie += "=" + propValue;
		}
	}
	document.cookie = updatedCookie;
}

function deleteCookie(name) {
	setCookie(name, "", {
		expires: -1
	})
}

function ajaxError(jqXHR, textStatus, errorThrown) {
	// console.log(jqXHR); // вывод JSON в консоль
	console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
	// console.log(errorThrown); // вывод JSON в консоль
}

$(document).ready(function () {
	downloadJS(0);
	onLoadSlider();

	$('.js-show-promocode-modal').on('click', function() {
		$($(this).data('target')).find('.js-calc-promocode').data('type', $(this).data('type'));
		$($(this).data('target')).find('.js-calc-promocode').data('prefix', $(this).data('prefix'));
	});

	// событие при нажатии на кнопку ввода промокода на калькуляторе:
	$(".js-calc-promocode").on('click', function (event) {
		onClickGetPromocode($(this).data('type') + '_' + $(this).data('prefix'));
	});

	// если есть поле для ввода пароля при входе:
	if ($("#password-1-auth").length > 0) {
		$("#password-1-auth").on('input', function (event) {
			checkPwdInputCount();
		});

		// удаление атрибута readonly:
		$("#password-1-auth, #login_auth").on('focus click', function (event) {
			$("#password-1-auth").removeAttr('readonly');
		});
	}
})