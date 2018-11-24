/**
 * Файл с набором функций на JavaScript для работы с MyCredit,
 * используется для взаимодействия фронтенда с API CRM MyCredit
 * 
 * @author Игорь Стебаев <Stebaev@mail.ru>
 * @copyright Copyright (c) 2016- Artjoker Company
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
 * отправляет подтверждение проплаты в CRM
 * @param response
 * @param id
 */
function acceptPay(response) {

	var data = {
		typeData: 'acceptPay',
		// amount: amount,
		// LoanId: id,
		isCurrentCard: 'false',
		response: response
	};

	// отправить массив на сервер
	console.log("Передаем запрос ajax 'acceptPay' amount=" + amount + ' id=' + id);
	sendAjax(data);
}

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
 * тестовая пока. отправка данных по адресу
 * @param url
 */
/*
 function ajax(url) {
 
 //url = url + "?ajax";	
 url = "/ru/?ajax";	
 
 var response = {
 ajax: true,
 operation: 'GetEncryptedSessionkey',
 skid: null
 };
 
 // отправить массив на сервер
 //var str = JSON.stringify(response); // парсим массив
 
 // отправить массив на сервер
 console.log("Передаем запрос ajax " + url);
 
 $.ajax({
 url: url,
 type: 'POST',
 //data: {data: str},
 data: {data: 'testing',
 arr: response
 },
 dataType: 'json',
 //dataType: 'html',
 success: function(json){
 if(json) {
 //var js = JSON.parse(json);
 var js = json;
 
 console.log(js);
 };
 },
 
 error: function(jqXHR, textStatus, errorThrown){
 // console.log(jqXHR); // вывод JSON в консоль
 console.log('Сообщение об ошибке от сервера: '+textStatus); // вывод JSON в консоль
 // console.log(errorThrown); // вывод JSON в консоль
 }
 });
 }
 */

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
 * Проверяет необходимость перезагрузки страницы, при необходимости - перегружает
 * @param int credit_id
 * @param string credit_status
 * @param int interval_refresh_page
 */
function checkRefreshPage(credit_id, credit_status, interval_refresh_page) {

	var url = "/ru/?ajax";

	var data = {
		typeData: 'checkRefreshPage',
		credit_id: credit_id,
		credit_status: credit_status,
	};

	refreshTimerId = setInterval(function () {
		// отправить массив на сервер
		//console.log("Передаем запрос checkRefreshPage на " + url);
		$.ajax({
			url: url,
			type: 'POST',
			data: {data: data},
			dataType: 'json',
			success: function (json) {
				if (json) {
					var js = json;
					// console.log(js);
					if ((js.message == 'OK') && (js.toRefresh == 'yes')) {
						location = location.href;
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

	}, interval_refresh_page * 1000);

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

/**
 * открывает поле для ввода кода активации
 */
function enterCode(textExistCode, textNotExistCode) {

	if ($("#div_code:hidden").length > 0) {
		$("#div_code").removeClass("hidden");
		$("#btn_code").html(textNotExistCode);
	} else {
		$("#div_code").addClass("hidden");
		$("#btn_code").html(textExistCode);
	}
}

/**
 * проставляет флаг запроса кода и form_dog submit
 * 
 * @returns {Boolean}
 */
function getCode() {

	document.getElementById("get_code").value = 'yes';
	console.log('Запрос кода и form_dog submit');
	window.document.forms['form_dog'].submit();

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

/*
 * парсит строку адреса при помощи Яндекса, и раскладывает по полям
 * @param address
 * @param prefix 'fact' | ''
 *
 function getParserAddress(address, prefix) {
 
 var url = "https://geocode-maps.yandex.ru/1.x/?format=json&geocode=";	
 
 if (prefix === 'fact') {
 prefix = 'fact_';
 } else {
 prefix = '';
 }
 
 // получаем язык сайта 
 var lang = document.getElementById('lang').innerHTML;
 
 switch (lang) {
 case "ru":
 lang = '&lang=ru_RU';
 break;
 case "ua":
 lang = '&lang=uk_UA';
 break;
 default:
 lang = '&lang=ru_RU';
 break;
 }
 
 // данные по ограничению поиска:
 var rspn = "&rspn=1"; // ограничить поиск
 var ll = "&ll=31.000000,48.500000";	// координаты центра
 var spn = "&spn=20.000000,8.000000"	// высота и ширина прямоугольника
 //var spn = "&spn=1.000000,1.000000"	// высота и ширина прямоугольника
 
 //address = address.replace(/(^\s*)|(\s*)$/g, '').replace(/ /g,"+");
 
 // отправить массив на сервер
 console.log("Передаем запрос адреса ", address);
 //console.log("строка url=", url + address + lang + rspn + ll + spn);
 
 $.ajax({
 url: url + address + lang + rspn + ll + spn,
 type: 'GET',
 dataType: 'json',
 //dataType: 'html',
 success: function(json){
 if(json) {
 //var js = JSON.parse(json);
 var js = json;
 // decode base64:
 //console.log(js.response.GeoObjectCollection);
 
 var parseAddress = js.response.GeoObjectCollection.featureMember[0].GeoObject.metaDataProperty.GeocoderMetaData.AddressDetails.Country; 
 console.log('Сообщение от сервера: '+parseAddress); // вывод JSON в консоль
 //console.log(parseAddress); // вывод JSON в консоль
 console.log("страна=" + parseAddress.CountryName); // вывод JSON в консоль
 if (parseAddress.AdministrativeArea !== undefined) 
 parseAddress = parseAddress.AdministrativeArea;
 
 if (parseAddress.AdministrativeAreaName !== undefined)
 console.log("область=" + parseAddress.AdministrativeAreaName); // вывод JSON в консоль
 $("#" + prefix + "obl").val(parseAddress.AdministrativeAreaName);
 //$("#suggest").val(parseAddress.AdministrativeAreaName);
 
 if (parseAddress.SubAdministrativeArea !== undefined)
 parseAddress = parseAddress.SubAdministrativeArea;
 
 if (parseAddress.SubAdministrativeAreaName !== undefined)
 console.log("район=" + parseAddress.SubAdministrativeAreaName); // вывод JSON в консоль
 
 if (parseAddress.Locality !== undefined)
 parseAddress = parseAddress.Locality;
 
 if (parseAddress.LocalityName)	
 console.log("город=" + parseAddress.LocalityName); // вывод JSON в консоль
 $("#" + prefix + "obl_city").val(parseAddress.LocalityName);
 
 if (parseAddress.Thoroughfare !== undefined) 
 parseAddress = parseAddress.Thoroughfare;
 
 if (parseAddress.ThoroughfareName !== undefined)
 console.log("улица=" + parseAddress.ThoroughfareName); // вывод JSON в консоль
 $("#" + prefix + "adress").val(parseAddress.ThoroughfareName);
 
 if (parseAddress.Premise !== undefined) {
 if (parseAddress.Premise.PremiseNumber !== undefined)
 console.log("дом=" + parseAddress.Premise.PremiseNumber); // вывод JSON в консоль
 $("#" + prefix + "House").val(parseAddress.Premise.PremiseNumber);
 }
 };
 },
 
 error: function(jqXHR, textStatus, errorThrown){
 console.log(jqXHR); // вывод JSON в консоль
 console.log('Сообщение об ошибке от сервера: '+textStatus); // вывод JSON в консоль
 console.log(errorThrown); // вывод JSON в консоль
 }
 });
 
 }
 */

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
	
	// Получение blackbox от iovation:
	// console.log("getSessionData start blackbox_info");
	// var blackbox_info = window.IGLOO.getBlackbox();
	// console.log("getSessionData blackbox_info:");
	// console.log(blackbox_info);
	
	var geocoder = new google.maps.Geocoder,
		sessionData = {
			user_country: '#404',
			user_area: '#404',
			user_city: '#404',
			user_formatted_address: '#404',
			user_geometry: '#404'
		};

	// закомментированно, ибо сожрало уйму денег
	/*
	geocoder.geocode({'location': geolocation}, function(results, status) {
		if (status === 'OK') {
			// console.log(results[0]);
			var result = results[0].address_components;
			var lenght = result.length;
			
			for (var i = 0; i < lenght; i++) {
				// проверяем все типы данного элемента:
				result[i].types.forEach(function(item, j, arr) {
					if (item == 'country') userLocation.country = result[i].long_name;
					if (item == 'administrative_area_level_1') userLocation.area = result[i].long_name;
					if (item == 'locality') userLocation.city = result[i].long_name;
				});
			}
			// console.log(userLocation);

			// готовим к отправке:
			sessionData = {
				user_country: userLocation.country,
				user_area: userLocation.area,
				user_city: userLocation.city,
				user_formatted_address: results[0].formatted_address,
				user_geometry: results[0].geometry
			};
		} else {
			// обработка ошибки
			sessionData.geo_status = status;
		}
		
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
	});*/
	
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

/*
 function logout(href) {
 
 console.log('logout');
 
 var cookieOut = getCookie('reload');
 if (cookieOut == 'on') {
 window.location.reload(true);	
 deleteCookie('reload');
 console.log('reload');
 } 
 if (href != '') {
 console.log('setCookie');
 location.href = href;
 setCookie('reload', 'on');
 } 
 }
 */

/**
 * Получает данные геолокации пользователя, и передает на сервер
 */
function getUserLocation_old() {

	return; // временно выключаем

	/*
	 ymaps.ready(initYmaps);
	 
	 function initYmaps() {
	 var geolocation = ymaps.geolocation;
	 
	 // получаем локацию по IP:
	 geolocation.get({
	 provider: 'yandex',
	 mapStateAutoApply: false
	 }).then(
	 function (result) {
	 
	 // получаем адреса:
	 var myGeocoder = ymaps.geocode(result.geoObjects.position);
	 // var myGeocoder = ymaps.geocode("Харьков");
	 myGeocoder.then(
	 function (res) {
	 var result = res.geoObjects.get(0).properties.get('metaDataProperty').GeocoderMetaData;
	 userLocation.country = result.AddressDetails.Country.CountryName;
	 userLocation.area = result.AddressDetails.Country.AdministrativeArea.AdministrativeAreaName;
	 userLocation.city = result.AddressDetails.Country.AdministrativeArea.SubAdministrativeArea.Locality.LocalityName;
	 // console.log(userLocation);
	 
	 // готовим к отправке:
	 var sessionData = '{"user_country":"' + userLocation.country + '", "user_area":"' + userLocation.area + '", "user_city":"' + userLocation.city +'"}';
	 var data = {
	 typeData: 'userInfo',
	 sessionData: sessionData
	 };
	 // отправить массив на сервер
	 sendAjax(data);
	 },
	 function (err) {
	 // обработка ошибки
	 
	 // готовим к отправке:
	 var sessionData = '{"user_country":"#404", "user_area":"#404", "user_city":"#404"}';
	 var data = {
	 typeData: 'userInfo',
	 sessionData: sessionData
	 };
	 // отправить массив на сервер
	 sendAjax(data);
	 }
	 );
	 },
	 function (err) {
	 // обработка ошибки
	 
	 // готовим к отправке:
	 var sessionData = '{"user_country":"#404", "user_area":"#404", "user_city":"#404"}';
	 var data = {
	 typeData: 'userInfo',
	 sessionData: sessionData
	 };
	 // отправить массив на сервер
	 sendAjax(data);
	 }
	 );
	 }
	 */
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
 * проверяет правильность ввода ИНН
 * @param inn
 * @returns boolean
 */
function isValidInn(inn) {

	var minYear = 18; // минимальный возраст в годах
	var maxYear = 85; // максимальный возраст в годах

	var flagValid = true;

	if (inn.length > 0) {
		var dateB = new Date(1900, 0, 1); // дата 01.01.1900
		var days = parseInt(inn.substring(0, 5)); // количество дней от 01.01.1900 
		dateB.setDate(dateB.getDate() + days - 1); // ДР

		var dayB = $("#day").val();
		var monthB = $("#month").val();
		var yearB = $("#year").val();

		// console.log('год: ' + dateB.getFullYear() + ' месяц: ' + (dateB.getMonth() +1) + ' день: ' + dateB.getDate());
		// alert('год ' + dateB.getFullYear() + ' месяц ' + (dateB.getMonth() + 1) + ' день ' + dateB.getDate() + ' Дата из input: ' + dayB + '.' + monthB + '.' + yearB);

		// сравниваем дату из ИНН с введенной:
		if (parseInt(dayB) !== dateB.getDate() || parseInt(monthB) !== (dateB.getMonth() + 1) || parseInt(yearB) !== dateB.getFullYear()) {
			flagValid = false;
		}
		// проверяем возраст:
		var dateNow = new Date();
		if ((parseInt(yearB) > dateNow.getFullYear() - minYear) || (parseInt(yearB) < dateNow.getFullYear() - maxYear)) {
			flagValid = false;
		}
	}

	return flagValid;
}

/**
 * обрабатывает нажатиие кнопок на нотификациях
 * @param event
 * @returns
 */
function notifyOnClick(event) {

	var button = event.target;
	var btnType = '';

	$('.js_div_notify').addClass('hidden');

	// если не кнопка "крестик"
	if (!$(button).hasClass('js_btn_notify_close')) {

		if ($(button).hasClass('js_btn_notify_ok')) {
			btnType = 'OK';
		} else if ($(button).hasClass('js_btn_notify_recall')) {
			btnType = 'recall';
		}

		var data = {
			typeData: "notifyClick",
			btnType: btnType,
			notifyId: $(".js-notifyId").first().text()
		};

		// отправить массив на сервер
		// console.log("Передаем запрос ajax 'notifyClick'");
		// console.log(data);
		sendAjax(data);

		// если кнопка "крестик"
	} else {

		var data = {
			typeData: "notifyClosed",
			notifyId: $(".js-notifyId").first().text()
		};

		// отправить массив на сервер
		// console.log("Передаем запрос ajax 'notifyClosed'");
		// console.log(data);
		sendAjax(data);
	}

}

/**
 * делает активной кнопку "Оформить кредит" при постановке галочки "согласен"
 */
function onChangeAgree() {

	if ($("#agree").prop('checked')) {
		$('#buttonCreateCredit').removeAttr('disabled');
	} else {
		$('#buttonCreateCredit').attr('disabled', true);
	}
}

/**
 * Показывает поля в зависимости от Типа занятости
 */
function onChangeBusynessType(fromElement) {

	var selectedType = $("#BusynessType").val();

	if (fromElement == undefined) {

		// Такое очищение, походу, работает во всех браузерах нормально.
		var list = document.getElementById('mainSource');

		if (list) {
			//console.log('очистка mainSource в количестве ' + list.length);
			while (list.length > 0)
				list.options[0] = null;
		}
	}

	// добавляет нужные option
	function addOption(arrNumber) {

		if (!(fromElement == undefined) || !list)
			return;

		if (arrNumber.length > 0) {
			$("#tr_mainSource").removeClass("hidden");

			// перебор массива требуемых option:
			arrNumber.forEach(function (item, i, arr) {
				// получаем текст элемента из общего селекта
				var optionText = $("#mainSourceStandard option[value='" + item + "']").html();
				list.options[i] = new Option(optionText, item, false, false);
				//console.log('Добавление ' + list.options[i].text);
			});

			// получаем значение выбранного элемента
			var selectedOption = $("#mainSourceStandard :selected").val();
			//console.log('selectedOption=' + selectedOption);
			$("#mainSource option[value='" + selectedOption + "']").attr("selected", "selected");
			// устанавливаем selected методом из bootstrap (лучше делать refresh)
			//$('#mainSource').selectpicker('val', selectedOption);

			$("#mainSource").trigger("chosen:updated");
			// метод для обновления select с классом selectpicker
			if ($("#mainSource").length)
				$("#mainSource").selectpicker('refresh');
		} else {
			$("#tr_mainSource").addClass("hidden");
		}
	}

	switch (selectedType) {
		case "1": // // Работаю

			// удаление лишних options (от последнего к первому!!!):
			//delOption([10, 9, 1]);

			// добавление options:
			addOption(['6', '8', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if ($("#tr_workType").hasClass("hidden"))
				$("#tr_workType").removeClass("hidden"); // Вид деятельности
			if ($("#tr_company").hasClass("hidden"))
				$("#tr_company").removeClass("hidden"); // Название компании
			if ($("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").removeClass("hidden"); // Должность
			$('#dolj').attr('required', true);

			if ($("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").removeClass("hidden"); // Рабочий телефон компании:
			// if ($("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").removeClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			// if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			// устанавливаем признак "Есть источник дохода" 
			//$("#sourceIncome [value='1']").attr("selected", "selected");
			// устанавливаем selected методом из bootstrap
			//$('#sourceIncome').selectpicker('val', "1");
			// делаем признак "Есть источник дохода" скрытым
			//$("#tr_sourceIncome").addClass("hidden");	// Основной источник дохода

			/*
			 if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }
			 */

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "2": // Предприниматель

			// добавление options:
			addOption(['6', '8', '11', '12']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if ($("#tr_workType").hasClass("hidden"))
				$("#tr_workType").removeClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "3": // Не работаю

			// добавление options:
			addOption(['5', '6', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "4": // Учусь

			// добавление options:
			addOption(['1', '6', '8', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if ($("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").removeClass("hidden"); // Название учебного заведения:
			if ($("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").removeClass("hidden"); // Специализация факультета
			if ($("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").removeClass("hidden"); // Степень/квалификация после выпуска
			// if ($("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").removeClass("hidden");	// Бюджет или контракт?
			// if ($("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").removeClass("hidden");	// Форма обучения:
			// if ($("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").removeClass("hidden");	// Получаете первое высшее образование?
			// if ($("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").removeClass("hidden");	// Когда Вы начали учиться
			if ($("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").removeClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "5": // Пенсионер

			// добавление options:
			addOption(['2', '6', '8', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "6": // Инвалид

			// добавление options:
			addOption(['2', '3', '6', '8', '9', '11']);

			if ($("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").removeClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "7": // Домохозяйка / Домохозяин

			// добавление options:
			addOption(['6', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			$('#dolj').removeAttr('required');

			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "8": // Декрет

			// добавление options:
			addOption(['4', '6', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;

		case "9": // Уволена / Уволен

			// добавление options:
			addOption(['3', '5', '6', '9', '11']);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн ):
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			if ($("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").removeClass("hidden"); // Цель получения займа
			if ($("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").removeClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if ($("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").removeClass("hidden"); // Причина увольнения:
			// if ($("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").removeClass("hidden");	// Планируете ли искать новую работу?
			break;

		default:
			// добавление options:
			addOption([]);

			if (!$("#tr_groupDisability").hasClass("hidden"))
				$("#tr_groupDisability").addClass("hidden"); // Группа инвалидности

			if (!$("#tr_workType").hasClass("hidden"))
				$("#tr_workType").addClass("hidden"); // Вид деятельности
			if (!$("#tr_company").hasClass("hidden"))
				$("#tr_company").addClass("hidden"); // Название компании
			if (!$("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").addClass("hidden"); // Должность
			$('#dolj').removeAttr('required');

			if (!$("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").addClass("hidden"); // Рабочий телефон компании:
			// if (!$("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").addClass("hidden");	// Стаж работы:
			if (!$("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").addClass("hidden"); // Расходы на семью:
			if (!$("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").addClass("hidden"); // Месячный доход ( грн ):
			// if (!$("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").addClass("hidden");	// Следующее получение дохода:
			// if (!$("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").addClass("hidden");	// Как часто Вы получаете доход
			if (!$("#tr_purposeLoan").hasClass("hidden"))
				$("#tr_purposeLoan").addClass("hidden"); // Цель получения займа
			if (!$("#tr_sumPayLoans").hasClass("hidden"))
				$("#tr_sumPayLoans").addClass("hidden"); // Сумма платежей по кредитам
			//if ($("#tr_sourceIncome").hasClass("hidden")) $("#tr_sourceIncome").removeClass("hidden");	// Есть ли у Вас источник дохода

			if (!$("#tr_nameUniversity").hasClass("hidden"))
				$("#tr_nameUniversity").addClass("hidden"); // Название учебного заведения:
			if (!$("#tr_Specializationfaculty").hasClass("hidden"))
				$("#tr_Specializationfaculty").addClass("hidden"); // Специализация факультета
			if (!$("#tr_qualification").hasClass("hidden"))
				$("#tr_qualification").addClass("hidden"); // Степень/квалификация после выпуска
			// if (!$("#tr_isBudget").hasClass("hidden")) $("#tr_isBudget").addClass("hidden");	// Бюджет или контракт?
			// if (!$("#tr_formTraining").hasClass("hidden")) $("#tr_formTraining").addClass("hidden");	// Форма обучения:
			// if (!$("#tr_isFirstEducation").hasClass("hidden")) $("#tr_isFirstEducation").addClass("hidden");	// Получаете первое высшее образование?
			// if (!$("#tr_beginLearn").hasClass("hidden")) $("#tr_beginLearn").addClass("hidden");	// Когда Вы начали учиться
			if (!$("#tr_studentID").hasClass("hidden"))
				$("#tr_studentID").addClass("hidden"); // Номер студенческого билета

			/*if ($("#tr_mainSource").hasClass("hidden") && ( $('input[name="profile[sourceIncome]"]:checked').val() == '1')) {
			 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
			 }*/

			if (!$("#tr_reasonDismissal").hasClass("hidden"))
				$("#tr_reasonDismissal").addClass("hidden"); // Причина увольнения:
			// if (!$("#tr_planNewJob").hasClass("hidden")) $("#tr_planNewJob").addClass("hidden");	// Планируете ли искать новую работу?
			break;
	}

	//  проверяем mainSource
	selectedType = $("#mainSource").val();

	switch (selectedType) {
		case "8": // // Зарплата

			if ($("#tr_workType").hasClass("hidden"))
				$("#tr_workType").removeClass("hidden"); // Вид деятельности
			if ($("#tr_company").hasClass("hidden"))
				$("#tr_company").removeClass("hidden"); // Название компании
			if ($("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").removeClass("hidden"); // Должность
			if ($("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").removeClass("hidden"); // Рабочий телефон компании
			// if ($("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").removeClass("hidden");	// Стаж работы
			if ($("#tr_costFamily").hasClass("hidden")) {
				$('#costFamily').attr('required', true);
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью
			}
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden")) {
				$('#GrossMonthlyIncome').attr('required', true);
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн )
			}
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода
			//if ($("#tr_oftenPay").hasClass("hidden")) {
			//	$('#oftenPay').attr('required', true);
			//	$("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			//}
			break;

		case "9": // // Нет дохода

			break;
			if (!$("#tr_GrossMonthlyIncome").hasClass("hidden")) {
				$('#GrossMonthlyIncome').removeAttr('required');
				$("#tr_GrossMonthlyIncome").addClass("hidden"); // Месячный доход ( грн ):
			}
			// if (!$("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").addClass("hidden");	// Следующее получение дохода:
			// if (!$("#tr_oftenPay").hasClass("hidden")) {
			// 	$('#oftenPay').removeAttr('required');
			//	$("#tr_oftenPay").addClass("hidden");	// Как часто Вы получаете доход
			// }
			if (!$("#tr_costFamily").hasClass("hidden")) {
				$('#costFamily').removeAttr('required');
				$("#tr_costFamily").addClass("hidden"); // Расходы на семью:
			}
			break;

		default:
			break;
	}

	/*				// Такое очищение, походу, работает во всех браузерах нормально.
	 var list = document.getElementById(idSelect + '_city');
	 console.log('очистка ' + idSelect + '_city в количестве ' + list.length);
	 while (list.length > 0) list.options[0] = null;
	 list.options[0] = new Option('', 0, false, false);
	 list.disabled = "disabled";
	 
	 list.disabled = false;
	 for(var i = 0; i < arrAddress.length; i++) {
	 list.options[i+1] = new Option(arrAddress[i]['name'], arrAddress[i]['id'], false, false);
	 // console.log('добавление ' + idSelect + '_city: i=' + i +
	 // ' ' + arrAddress[i]['name'] + ' ' + arrAddress[i]['id']);
	 }
	 
	 $("#" + idSelect + '_city').trigger("chosen:updated");
	 
	 // метод для обновления select с классом selectpicker
	 $("#" + idSelect + '_city').selectpicker('refresh');
	 */

}


/**
 * Открываем полле вода для описания своего варианта в поле "Цель получения займа"
 *
 *
 */

function myOwnTargetLoan() {
	$('#purposeLoan').on('changed.bs.select', function (e) {
		if ($(e.target).selectpicker('val') == 9) {
			$('.js-showing').removeClass('hidden');
		} else {
			$('.js-showing').addClass('hidden');
		}
	})
}


/**
 * обрабатывает изменение даты
 * @param string typeDate (Birthday | Passport)
 */
function onChangeDate(typeDate) {

	switch (typeDate) {
		case "Birthday":
			var year = document.getElementById('year').value;
			var month = document.getElementById('month').value;
			var day = document.getElementById('day').value;
			var strDate = year + '-' + month + '-' + day;
			// console.log(strDate);
			// если всё заполнено:
			if (year && month && day) {
				var newDate = new Date(strDate);
				if (newDate == 'Invalid Date') {
					$("#spanErrorDateBirthday").removeClass("hidden");
					$("#dateBirthday").val("");
					$("#divErrorDateBirthday").find(".error_text").removeClass("hidden");
					$("#divErrorDateBirthday").children().addClass("has-error");
				} else {
					$("#spanErrorDateBirthday").addClass("hidden");
					$("#dateBirthday").val("newDate");
					$("#divErrorDateBirthday").find(".error_text").addClass("hidden");
					$("#divErrorDateBirthday").find(".has-error").removeClass("has-error");
				}
			}
			break;

		case "Passport":
			var year = document.getElementById('PassportRegistrationYear').value;
			var month = document.getElementById('PassportRegistrationMonth').value;
			var day = document.getElementById('PassportRegistrationday').value;
			var strDate = year + '-' + month + '-' + day;
			//console.log(strDate);
			//console.log(new Date(strDate));
			if (year && month && day) {
				var newDate = new Date(strDate);
				if (newDate == 'Invalid Date') {
					$("#spanErrorDatePassport").removeClass("hidden");
					$("#datePassport").val("");
					$("#divErrorDatePassport").find(".error_text").removeClass("hidden");
					$("#divErrorDatePassport").children().addClass("has-error");
				} else {
					$("#spanErrorDatePassport").addClass("hidden");
					$("#datePassport").val("newDate");
					$("#divErrorDatePassport").find(".error_text").addClass("hidden");
					$("#divErrorDatePassport").find(".has-error").removeClass("has-error");
				}
			}
			break;
	}

}

/**
 * обрабатывает изменение поля с Datepicker
 * @param string flag
 * @param string idDatepicker
 */
function onChangeDatepicker(flag, idDatepicker) {

	// console.log('onChangeDatepicker flag = '+ flag  + ' id = ' + idDatepicker);

	if (flag == 'onSelect') {
		var DateShow = $(idDatepicker).val();
		// сохраняем дату в правильном формате:
		$("#prolongationDdate").val(DateShow);
		DateShow = DateShow.substring(8, 10) + ' ' + getMonthLang(DateShow.substring(5, 7)) + ' ' + DateShow.substring(0, 4);
		// console.log('DateShow = '+ DateShow);
		$(idDatepicker).val(DateShow);
	}
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
 * Показывает поля в зависимости от Основного источника дохода
 */
function onChangeMainSource() {

	// пересчет BusynessType
	onChangeBusynessType("mainSource");

	var selectedType = $("#mainSource").val();

	switch (selectedType) {
		case "8": // Зарплата

			if ($("#tr_workType").hasClass("hidden"))
				$("#tr_workType").removeClass("hidden"); // Вид деятельности
			if ($("#tr_company").hasClass("hidden"))
				$("#tr_company").removeClass("hidden"); // Название компании
			if ($("#tr_dolj").hasClass("hidden"))
				$("#tr_dolj").removeClass("hidden"); // Должность
			if ($("#tr_work_tel").hasClass("hidden"))
				$("#tr_work_tel").removeClass("hidden"); // Рабочий телефон компании
			// if ($("#tr_vremyaorg").hasClass("hidden")) $("#tr_vremyaorg").removeClass("hidden");	// Стаж работы
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью
			$('#costFamily').attr('required', true);
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн )
			$('#GrossMonthlyIncome').attr('required', true);
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			// $('#oftenPay').attr('required', true);
			break;

		case "9": // Нет дохода

			if (!$("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").addClass("hidden"); // Месячный доход ( грн ):
			$('#GrossMonthlyIncome').removeAttr('required');
			// if (!$("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").addClass("hidden");	// Следующее получение дохода:
			// if (!$("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").addClass("hidden");	// Как часто Вы получаете доход
			// $('#oftenPay').removeAttr('required');
			if (!$("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").addClass("hidden"); // Расходы на семью:
			$('#costFamily').removeAttr('required');
			break;

		default:
			if ($("#tr_GrossMonthlyIncome").hasClass("hidden"))
				$("#tr_GrossMonthlyIncome").removeClass("hidden"); // Месячный доход ( грн )
			$('#GrossMonthlyIncome').attr('required', true);
			// if ($("#tr_nextPay").hasClass("hidden")) $("#tr_nextPay").removeClass("hidden");	// Следующее получение дохода:
			// if ($("#tr_oftenPay").hasClass("hidden")) $("#tr_oftenPay").removeClass("hidden");	// Как часто Вы получаете доход
			// $('#oftenPay').attr('required', true);
			if ($("#tr_costFamily").hasClass("hidden"))
				$("#tr_costFamily").removeClass("hidden"); // Расходы на семью
			$('#costFamily').attr('required', true);
			break;
	}
}

/**
 * Показывает поля в зависимости от Типа паспорта
 */
function onchangePassportType(passportType) {

	// var selectedType = $("#passportType").val();
	var selectedType = passportType;

	// получаем значение выбранного элемента года паспорта
	var selectedOption = $("#PassportRegistrationYear :selected").val();

	// получаем год рождения:
	var yearB = parseInt($("#year :selected").val());
	// получаем текущий год:
	var dateReal = new Date();
	var year = dateReal.getFullYear();

	// Такое очищение, походу, работает во всех браузерах нормально.
	var list = document.getElementById('PassportRegistrationYear');
	//console.log('очистка PassportRegistrationYear в количестве ' + list.length);
	while (list.length > 0)
		list.options[0] = null;

	// добавляет нужные option от NumberBegin до NumberEnd
	function addOptionYear(NumberBegin, NumberEnd) {

		for (var i = NumberBegin; i <= NumberEnd; i++) {
			// получаем текст элемента
			var optionText = i.toString();
			list.options[i - NumberBegin] = new Option(optionText, optionText, false, false);
			//console.log('Добавление ' + list.options[i].text);
		}
		;

		//console.log('selectedOption=' + selectedOption);
		$("#PassportRegistrationYear [value='" + selectedOption + "']").attr("selected", "selected");
		// устанавливаем selected методом из bootstrap
		if ($('#PassportRegistrationYear').length)
			$('#PassportRegistrationYear').selectpicker('val', selectedOption);

		$("#PassportRegistrationYear").trigger("chosen:updated");
		// метод для обновления select с классом selectpicker
		if ($("#PassportRegistrationYear").length)
			$("#PassportRegistrationYear").selectpicker('refresh');
	}

	switch (selectedType) {
		case "1": // Паспорт старого образца

			if ($("#passportType_1_2").hasClass("hidden"))
				$("#passportType_1_2").removeClass("hidden"); // делаем видимыми поля ввода данных паспорта

			if ($("#passportType_1").hasClass("hidden"))
				$("#passportType_1").removeClass("hidden"); // Паспорт старого образца
			if (!$("#passportType_2").hasClass("hidden"))
				$("#passportType_2").addClass("hidden"); // Паспорт нового образца
			// устанавливаем required соответственно:
			$('#passportSeries').attr('required', true);
			$('#passportNumber').attr('required', true);
			$('#passportReestr').removeAttr('required');
			$('#passportNumberDoc').removeAttr('required');

			// $('#passportOld').attr('required', true);
			// $('#passportNew').removeAttr('required');

			if (yearB > 1977) {
				addOptionYear(yearB + 16, year); // добавляет список годов от и до
			} else {
				addOptionYear(1994, year); // добавляет список годов от и до
			}

			break;

		case "2": // Паспорт нового образца

			if ($("#passportType_1_2").hasClass("hidden"))
				$("#passportType_1").removeClass("hidden"); // делаем видимыми поля ввода данных паспорта

			if (!$("#passportType_1").hasClass("hidden"))
				$("#passportType_1").addClass("hidden"); // Паспорт старого образца
			if ($("#passportType_2").hasClass("hidden"))
				$("#passportType_2").removeClass("hidden"); // Паспорт нового образца
			// устанавливаем required соответственно:
			$('#passportSeries').removeAttr('required');
			$('#passportNumber').removeAttr('required');
			$('#passportReestr').attr('required', true);
			$('#passportNumberDoc').attr('required', true);

			// $('#passportOld').removeAttr('required');
			// $('#passportNew').attr('required', true);

			if (yearB > 2000) {
				addOptionYear(yearB + 14, year); // добавляет список годов от и до
			} else {
				addOptionYear(2015, year); // добавляет список годов от и до
			}

			break;

		default:
			if ($("#passportType_1").hasClass("hidden"))
				$("#passportType_1").removeClass("hidden"); // Паспорт старого образца
			if (!$("#passportType_2").hasClass("hidden"))
				$("#passportType_2").addClass("hidden"); // Паспорт нового образца
			//$('#passportSeries').attr('required', true);
			//$('#passportNumber').attr('required', true);
			//$('#passportReestr').removeAttr('required');
			//$('#passportNumberDoc').removeAttr('required');
			$('#passportOld').attr('required', true);
			$('#passportNew').removeAttr('required');

			if (yearB > 1977) {
				addOptionYear(yearB + 16, year); // добавляет список годов от и до
			} else {
				addOptionYear(1994, year); // добавляет список годов от и до
			}

			break;
	}
}


/*
 * пересчитывает список городов в зависимости от измененной области
 * 
 * @param string
 *            idSelect - id select-а области
 * @param string
 *            url - адрес CRM
 *
 /*
 function onChangeRegion(idSelect, url) {
 
 var selectedArea = $("#" + idSelect).val();
 
 // startLoadingAnimation(); // - запустим анимацию загрузки
 
 if (flagRunQuery)
 return;
 
 flagRunQuery = true;
 
 // отправка запроса на сервер
 console.log('Запрос списка городов: область=' + selectedArea);
 $.ajax({
 url : url + 'get_city',
 type : 'POST',
 data : {
 region : selectedArea
 },
 dataType : 'json',
 success : function(json) {
 if (json) {
 var js = json;
 console.log('Получено данных по области ' + js.region + ': ' + js.cities.length);
 
 var arrAddress = js.cities;
 
 flagRunQuery = false;
 
 // Такое очищение, походу, работает во всех браузерах нормально.
 var list = document.getElementById(idSelect + '_city');
 console.log('очистка ' + idSelect + '_city в количестве ' + list.length);
 while (list.length > 0) list.options[0] = null;
 list.options[0] = new Option('', 0, false, false);
 list.disabled = "disabled";
 
 list.disabled = false;
 for(var i = 0; i < arrAddress.length; i++) {
 list.options[i+1] = new Option(arrAddress[i]['name'], arrAddress[i]['id'], false, false);
 // console.log('добавление ' + idSelect + '_city: i=' + i +
 // ' ' + arrAddress[i]['name'] + ' ' + arrAddress[i]['id']);
 }
 
 $("#" + idSelect + '_city').trigger("chosen:updated");
 
 // метод для обновления select с классом selectpicker
 $("#" + idSelect + '_city').selectpicker('refresh');
 
 // stopLoadingAnimation(); // останавливаем анимацию
 
 }
 },
 error: function(r, m, e){
 
 flagRunQuery = false;
 console.log('url='+url);
 console.log('e='+e);
 console.log(r);
 }
 });
 
 }
 */

/**
 * Показывает поле "Основной источник дохода mainSource" и "Месячный доход" GrossMonthlyIncome в зависимости от SourceIncome 
 */
function onchangeSourceIncome() {
	/*	if ( $('input[name="profile[sourceIncome]"]:checked').val() == '1') {
	 $("#tr_mainSource").removeClass("hidden");	// Основной источник дохода
	 $("#tr_GrossMonthlyIncome").removeClass("hidden");	// Месячный доход
	 } else {
	 $("#tr_mainSource").addClass("hidden");	// Основной источник дохода
	 $("#tr_GrossMonthlyIncome").addClass("hidden");	// Месячный доход
	 }
	 */
}

/**
 * обрабатывает изменение строки поиска адреса
 * @param prefix
 */
function onChangeSuggest(prefix) {
	if (prefix !== '') {
		var prefix_ = prefix + '_';
	} else {
		var prefix_ = '';
	}
	console.log('onChangeSuggest');
	var adr = $("#" + prefix_ + "suggest").val();
	getParserAddress(adr, prefix);

}

/**
 * обрабатывает Click кнопки "Ввести другой номер"
 * @returns
 */
function onClickAnotherNumber() {
	$('#div_code').addClass('hidden');
	$('#div_auth').addClass('hidden');
	$('#phone').removeAttr("disabled");
	$('#phone').removeAttr("disabled");
	$('#buttonGetCode').removeClass("hidden");
	// console.log('onClickAnotherNumber');
	flagReg.reCaptcha = false;
	grecaptcha.reset();

	return false;
}

/**
 * обрабатывает кнопки выбора дополнительных карт
 * @returns
 */
function onClickCardsAdditional(action) {

	$("#radio-action").val(action);

	console.log("onClickCardsAdditional " + action);

	window.document.forms['form-cardsAdditional'].submit();
}

/**
 * обрабатывает кнопку (x) "Закрыть форму посылки email"
 */
function onClickCloseEmail() {

	$("#div_SendEmail").addClass("bottom-call-hidden");
	$("#div_resultEmail").addClass("bottom-call-hidden");
	$("#button_sendMe").removeAttr("disabled");
}

/**
 * запускает виджет от MyCredit
 * @returns
 */
function onClockCreateWidget(satelliteId) {

	// делаем невидимым калькулятор:
	$(".slider-action").addClass('hidden');

	var widgetOptions = {
		sat_id: satelliteId, // id опроса
		container_id: 'MC_container',
		money: $('[id ^= money-value]').val(),
		days: $('[id ^= day-value]').val()
	};

	createWidget(widgetOptions);
}

/**
 * обрабатывает Click кнопки "Получить код"
 */
function onClickGetCode() {

	// console.log('onClickGetCode');

	if (!flagReg.phone || !flagReg.reCaptcha)
		return false;

	$('#buttonGetCode').attr('disabled', true);

	ga('send', 'event', 'SMS', 'Click'); // аналитика Google

	$("#mobile-phone").val($("#phone").val());
	//sendCodeReg($("#phone").val(), $("#captcha").val());	// отправляем код (устаревшее)
	sendCodeReg($("#phone").val(), $("#g-recaptcha-response").val()); // отправляем код
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
;

/*
 * Обрабатывает Click на GoogleStars, отправляет результат на сервер
 *
 function onClickGoogleStars(mark) {
 
 // готовим данные для отправке:
 var data = {
 typeData: 'googleStars',
 mark: mark
 };
 
 var url = "/ru/?ajax";	
 
 $.ajax({
 url: url,
 type: 'POST',
 data: {data: data},
 dataType: 'json',
 success: function(json){
 if(json) {
 var js = json;
 
 $("#ratingScore").text(js.message_details.score);
 $("#ratingCountScore").text(js.message_details.countScore);
 var score = Math.round(Number(js.message_details.score));
 $('#input-1').rating('update', score);
 //$('#input-1').rating('refresh', {disabled: true});
 
 // console.log(js);
 };
 },
 
 error: function(jqXHR, textStatus, errorThrown){
 // console.log(jqXHR); // вывод JSON в консоль
 console.log('Сообщение об ошибке от сервера: '+textStatus); // вывод JSON в консоль
 // console.log(errorThrown); // вывод JSON в консоль
 }
 });
 
 // отправить массив на сервер
 //console.log("Передаем запрос ajax 'userInfo'");
 //sendAjax(data);
 }
 */

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
 * обрабатывает события для поиска элементов в разделе "Справка"
 * @returns
 */
function onClickForSearch(event) {

	// console.log('onClickForSearch');

	var forSearch = $("#search_faq").val();
	if (forSearch.length > 3) {

		$('#accordion').addClass('hidden');

		// получаем список слов поиска:
		var words = forSearch.split(' '); // слова из поиска
		var minMatchedwords = 2; // минимальное количество совпадений слов, требуемое для нахождения элемента
		var minWordLength = 3; // минимальная длина учитываемого в поиске слова 

		// var contains = $('div.js_faq_search:contains("' + forSearch + '")');	// удовлетворяющие условию элементы
		var divCount = 0;
		// регулярное выражение для поиска:
		var reg = new RegExp(forSearch, "i");
		$('.js_faq_search').each(function (key, value) {
			// Если есть удовлетворяющие условию элементы:
			if (reg.test($(value).html())) {
				$(value).removeClass('hidden');
				divCount++;
			} else {

				// поиск результата по нескольким словам:
				var numberMatchedwords = 0; // количество найденных слов
				$(words).each(function (wordKey, word) {
					if (word.length >= minWordLength) {
						var wordReg = new RegExp(word, "i");
						// Если есть удовлетворяющие условию элементы:
						if (wordReg.test($(value).html())) {
							numberMatchedwords++;
						}
					}
				});
				if (numberMatchedwords >= minMatchedwords) {
					$(value).removeClass('hidden');
					divCount++;
				} else {
					$(value).addClass('hidden');
				}
				// console.log(words);				
				// console.log('numberMatchedwords = ' + numberMatchedwords);				
				// конец поиск результата по нескольким словам:

			}
		});

		// если ничего не найдено - сообщение:
		if (divCount == 0) {
			$('#js_zero').removeClass('hidden');
			$('#js_found').addClass('hidden');
		} else {
			$('#js_zero').addClass('hidden');
			$('#js_found').removeClass('hidden');
		}

		// отправляем в аналитику введенные данные запроса:
		if (timerId)
			clearTimeout(timerId); // если уже есть таймер - удаляем
		timerId = setTimeout(function (forSearch) {
			if (forSearch === $("#search_faq").val()) {
				ga('send', 'pageview', encodeURI('/search_results/?q=' + forSearch));
				// console.log("'send', 'pageview', '" + encodeURI('/search_results/?q=' + forSearch));
			}
		}, 10000, forSearch);

	} else {

		$('#accordion').removeClass('hidden');

		$('.js_faq_search').addClass('hidden');
		$('#js_zero').addClass('hidden');
		$('#js_found').addClass('hidden');
	}
}

/**
 * обрабатывает событие нажатия кнопки рейтинга страницы
 * @param int param
 * @returns
 */
function onClickLikePage(param) {

	var url = "/ru/?ajax";
	var data = {
		typeData: 'clickPageLike',
		pageId: $('#span_page_rating').text(),
		like: param
	};

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
				if (js.message == 'OK') {
					$("#errorCaptcha").addClass("hidden");
					$("#span_like_positive").text(js.pageRatingPositive); // перезаписать положительные отзывы
					$("#span_like_negative").text(js.pageRatingNegative); // перезаписать отрицательные отзывы
					$('.btn-like').attr('disabled', true); // отключить кнопки
				} else if (js.message == 'existPhone') {
				} else {
					console.log(js.message_details);
				}
				// console.log(js);
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
 * обрабатывает нажатиие на меню для скрытия/показа элементов
 * @param event
 * @returns boolean
 */
function onClickMenuQuestions(event) {

	// console.log('onClickMenuQuestions');

	$('#accordion').removeClass('hidden');
	$('.js_faq_search').addClass('hidden');
	$('#js_found').addClass('hidden');
	$('#js_zero').addClass('hidden');
	$('#search_faq').val('');

	return false;
}

/**
 * обрабатывает Click на форме дополнительных данных (StudentId, и пр.)
 */
function onClickOtherData(action) {

	ga('send', 'event', action, 'Click'); // аналитика Google

	if (window.document.forms['otherData'] != null)
		window.document.forms['otherData'].submit();
}

/**
 *  Обрабатывает кнопку "Обновить условия" - обновить по кредитному продукту
 */
function onclickRefreshProduct() {

	// удаляем данные, если были:
	document.getElementById("money-value-submit").value = '';
	document.getElementById("day-value-submit").value = '';

	if (window.document.forms['form_credit'] != null)
		window.document.forms['form_credit'].submit();

	// console.log('onclickRefreshProduct');
}

/**
 * обрабатывает кнопку "Отправить email"
 */
function onClickSendEmail() {

	var url = "/ru/?ajax";

	var data = {
		typeData: 'sendEmailtoSupport',
		fromName: $('#sername').val(),
		fromEmail: $('#email').val(),
		message: $('#message').val()
	};

	// отправить массив на сервер
	console.log("Передаем запрос ajax " + url);

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
					$("#div_SendEmail").addClass("bottom-call-hidden");
					$("#div_resultEmail").removeClass("bottom-call-hidden");
					$("#thanks").modal("show");
					$("#message").val("");
					$("#button_sendMe").removeAttr("disabled");
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

	return false;
}

/**
 * обрабатывает кнопку "Напишите нам"
 */
/* Удалено
function onClickSendMe() {

	$("#div_SendEmail").removeClass("bottom-call-hidden");
	$("#div_resultEmail").addClass("bottom-call-hidden");
	$("#button_sendMe").attr("disabled", true);
}
*/

/**
 * открывает форму начала регистрации карты
 * @returns
 */
function onClickStartVerify() {
	clearInterval(refreshTimerId);
	$("#div_card_list").addClass("hidden");
	$(".new-repayment-data").removeClass("hidden");
	$("#div_step1").removeClass("hidden");
	$(".new-repayment").removeClass("hidden");

}

/**
 * обрабатывает кнопку "Согласен с договором"
 */
function onClickSubmitConfirmDog() {

	var credit_id = $("#credid").val();
	var amount = $("#amount").val();

	/*
	 var data = {
	 "ecommerce": {
	 "currencyCode": "UAH",
	 "purchase": {
	 "actionField": {
	 "id" : credit_id,
	 "goal_id" : "27341109"	// Цель "clickAgreement" (ID цели (число). Указывается, если данное действие и было целью.)
	 },
	 "products": [
	 {
	 //"id": "1",
	 "name": "Кредит",
	 "price": parseFloat(amount),
	 "brand": "MyCredit"
	 //"category": "Одежда/Мужская одежда/Толстовки и свитшоты",
	 //"variant": "Оранжевый цвет"
	 },
	 ]
	 }
	 }
	 };
	 */

	// console.log(data);

	// dataLayer.push(data); // покупка продукта (кредита) для Yandex

	ga('send', 'pageview', '/poluchil-sms-soglasen'); // аналитика
	// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickAgreement');");	// аналитика Yandex

	window.document.forms['form_dog'].submit();
}

/**
 * обрабатывает кнопку "Отправить email"
 */
function onClickSubmitEmail() {

	window.document.forms['sendMail'].submit();
}

/**
 * обрабатывает кнопку "Отправить восстановление пароля"
 */
function onClickSubmitForgot() {

	// делаем временно кнопку неактивной:
	$('#buttonSendPhone').attr('disabled', true);

	var recoveryPassword = document.getElementById("recoveryPassword").value;
	recoveryPassword = recoveryPassword.replace(/\s|\(|\)/g, ""); // удаляем пробельные символы и скобки
	var flag = false; // признак недопустимости номера / email

	if (/^(\+380\d{9})$/.test(recoveryPassword)) { // phone

		var strNum = recoveryPassword.replace(/\D+/g, ""); // оставляем только цифры
		if (recoveryPassword.substring(0, 4) !== '+380') {
			flag = true;
		} else {
			var prefix = strNum.substring(3, 5); // префикс оператора
			flag = true;
			// массив префиксов мобильных телефонов:
			arrPrefix.forEach(function (item, i, arr) {
				if (item == prefix)
					flag = false;
			});
		}
		// если недопустимый номер:
		if (flag) {
			$("#recoveryPassword").val("+380"); // начальное значение
			$("#recoveryPassword").focus(); // установить фокус
			$("#recoveryPassword").selectionStart = 4; // позиция курсора 
			$("#spanRecoveryPassword").removeClass("hidden");
			$("#divRecoveryPassword").addClass("has-error");
			$("#span_api_error").addClass("hidden");
			console.log('phone не соответствует');
		} else {
			$("#recoveryPassword").val("+" + strNum); // значение типа +380671111111
			$("#phone").val("+" + strNum); // значение типа +380671111111
			$("#spanRecoveryPassword").addClass("hidden");
			$("#divRecoveryPassword").removeClass("has-error").addClass('has-success');
			$("#span_api_error").removeClass("hidden");
			console.log('phone соответствует');
		}

	} else if (/^[^@]+@([^@]+\.)+[^@]+$/.test(recoveryPassword)) { // e-mail
		$("#spanRecoveryPassword").addClass("hidden");
		$("#divRecoveryPassword").removeClass("has-error").addClass('has-success');
		$("#email").val(recoveryPassword); // значение email
		$("#span_api_error").removeClass("hidden");
		console.log('e-mail соответствует');
	} else {
		flag = true;
		$("#spanRecoveryPassword").removeClass("hidden");
		$("#divRecoveryPassword").addClass("has-error");
		$("#span_api_error").addClass("hidden");
		console.log('e-mail не соответствует');
	}

	if (!flag) {
		window.document.forms['forgotForm'].submit();
	} else {
		$('#buttonSendPhone').removeAttr('disabled');
	}
	;
}


/**
 * Обрабатывает кнопки submit отправки формы
 * @param string form - имя формы
 * @param string prefix - имя калькулятора, или путь
 */
function onClickSubmitForm(form, prefix) {

	// console.log('form = ' + form); 

	sendPageInputType(); // высылает на сервер способ введения информации
	setTimeout(commitForm, 500); // задержка, без нее возвращается ошибка (ответ от ajax)

	function commitForm() {
		if (form === 'myData') {

			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickContacts');");	// аналитика Yandex

			window.document.forms["myData"].submit();
		}

		if (form === 'myData2') {

			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickContacts');");	// аналитика Yandex

			window.document.forms["myData2"].submit();
		}

		/*
		 if (form == 'js-form-3') {
		 
		 // yaCounter37666645.reachGoal('clickEmployment');	// аналитика Yandex
		 // eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickEmployment');");	// аналитика Yandex
		 
		 submitCredit(prefix);
		 }
		 */

		if (form === 'form_prolongation') {

			ga('send', 'event', 'LKextend', 'Click'); // аналитика Google
			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickContacts');");	// аналитика Yandex

			window.document.forms["form_prolongation"].submit();
		}
		
		if (form === 'form_continueLoan') {

			ga('send', 'event', 'Prolong', 'Click'); // аналитика Google

			window.document.forms["form_continueLoan"].submit();
		}
		


	}
}

/**
 * обрабатывает кнопки посылки информации для партнерской программы
 * @returns
 */
function onClickSubmitPartner(event) {

	var button = event.target;

	var action = $(button).closest('form').find('input[name=Action]').val();
	var tellingFriends = $(button).closest('form').find('input[name=out_TellingFriends]').val();
	var distributeFlyers = $(button).closest('form').find('input[name=out_DistributeFlyers]').val();
	var advertisementsOnStands = $(button).closest('form').find('input[name=out_AdvertisementsOnStands]').val();
	var glueAds = $(button).closest('form').find('input[name=out_GlueAds]').val();
	var sendActiveLinks = $(button).closest('form').find('input[name=out_SendActiveLinks]').val();
	var name = $(button).closest('form').find('input[name=Name]').val();
	var email = $(button).closest('form').find('input[type=email]').val();
	var tel = $(button).closest('form').find('input[type=tel]').val();

	if (validate($(button).closest('form'))) {

		$("[name=name]").val(name);
		$("[name=email]").val(email);
		$("[name=tel]").val(tel);

		// клиентские данные:
		var clientData = {};
		if (tellingFriends === '1')
			clientData.tellingFriends = tellingFriends;
		if (distributeFlyers === '1')
			clientData.distributeFlyers = distributeFlyers;
		if (advertisementsOnStands === '1')
			clientData.advertisementsOnStands = advertisementsOnStands;
		if (glueAds === '1')
			clientData.glueAds = glueAds;
		if (sendActiveLinks === '1')
			clientData.sendActiveLinks = sendActiveLinks;

		var data = {
			typeData: 'sendClientContactInfo',
			Name: name,
			Phone: tel,
			Email: email,
			Action: action,
			Data: clientData
		};

		// console.log(data);
		// отправить массив на сервер
		// console.log("Передаем запрос 'sendClientContactInfo'");
		sendAjax(data);

		$(button).closest('.modal.fade').modal('hide');

		$("#thanks").modal('show'); // сообщение "Отправлено"
	}

}

function onClickSubmitProlong() {
	
}

/**
 * Обрабатывает три кнопки регистрации
 * @param string form - имя формы
 * @param string prefix - имя калькулятора, или путь
 */
function onClickSubmitReg(form, prefix) {

	// console.log('form = ' + form); 

	$(".js-btn-success").attr('disabled', true); // если нажата кнопка сабмита, дизейблим ее

	sendPageInputType(); // высылает на сервер способ введения информации
	setTimeout(commitForm, 500); // задержка, без нее возвращается ошибка (ответ от ajax)

	//if (validate($(this).parents(".js_validate"))) {
	function commitForm() {

		if (form === 'js-form-1') {

			ga('send', 'event', 'Dalee1', 'Click'); // аналитика Google
			// yaCounter37666645.reachGoal('clickContacts');	// аналитика Yandex
			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickContacts');");	// аналитика Yandex

			window.document.forms["js-form-1"].submit();
		}
		if (form === 'js-form-1-1') {

			ga('send', 'event', 'Kod', 'Click'); // аналитика Google
			if (prefix === 'fb') {
				fbq('track', 'Lead')
			}
			; // аналитика Facebook

			$("#sms-phone").val($("#phone").val());

			// console.log("js-form-1-1 submit");
			// console.log($("#sms-phone").val());
			//setTimeout("window.document.forms["js-form-1-1"].submit();", 500);

			window.document.forms["js-form-1-1"].submit();
		}
		if (form === 'js-form-1-2') {

			ga('send', 'event', 'Kabinet', 'Click'); // аналитика Google
			window.document.forms["js-form-1-2"].submit();
		}
		if (form == 'js-form-2') {

			$("#btn-js-form-2").attr('disabled', true);

			ga('send', 'event', 'Dalee2', 'Click'); // аналитика Google
			// yaCounter37666645.reachGoal('clickPersonalData');	// аналитика Yandex
			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickPersonalData');");	// аналитика Yandex

			window.document.forms["js-form-2"].submit();
		}
		if (form == 'js-form-3') {

			$("#buttonCreateCredit").attr('disabled', true);

			ga('send', 'event', 'Dalee3', 'Click'); // аналитика Google
			// yaCounter37666645.reachGoal('clickEmployment');	// аналитика Yandex
			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickEmployment');");	// аналитика Yandex

			$("#buttonCreateCredit").attr('disabled', true);
			submitCredit(prefix);
		}

		if (form == 'cardsForm') {

			ga('send', 'event', 'Dalee4', 'Click'); // аналитика Google
			// yaCounter37666645.reachGoal('clickEmployment');	// аналитика Yandex
			// eval("yaCounter" + YandexMetrikaId + ".reachGoal('clickBankCard');");	// аналитика Yandex

			$("#verifyOK").val('OK');
			window.document.forms['cardsForm'].submit();

			// location.href = prefix;
		}
	}
	//}
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
 * проверяет, есть ли фрейм WayForPay. Если нет, перегружает страницу
 */
function onCloseIframe() {

	var timerId = setInterval(function () {
		if ($("iframe").is("[name=WFPWidgetFrame]")) {
			// console.log('is frame');
		} else {
			clearInterval(timerId);
			// если форма верификации:
			if ($("form").is("#cardsForm"))
				location.href = $('#cardsForm').attr('action');
			// если форма пролонгации:
			if ($("form").is("#form_pay_prolongation"))
				location.href = $('#form_pay_prolongation').attr('action');
			// если форма оплаты:
			if ($("form").is("#form_pay"))
				location.href = '/' + $('#lang').text() + '/lichnyj-kabinet/moi-kredity/';
		}
	}, 500);

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

/**
 * имитирует нажатие Enter после ввода и записи значения
 * @param id
 * @param event
 */
function onKeyUpToEnter(id, event) {

	if ((event.keyCode == 13) && (document.getElementById(id).value.replace(/\s+/g, '') !== '')) {
		// console.log("keydown");
		var e = jQuery.Event("keydown", {which: 13}); //enter
		$('#' + id).trigger(e);
	}
}

/*
 * запускает работу подсказку ввода адреса от Яндекса
 * @param ymaps
 *
 function onLoad (ymaps) {
 
 var boundedBy = [[45.23, 22.12], [52.20, 40.11]],	//  // Украина
 boundedBy1 = [[47.93, 22.68], [51.63, 29.58]],	// Украина 1 часть
 boundedBy2 = [[45.26, 29.58], [52.20, 35.39]],	// Украина 2 часть
 boundedBy3 = [[46.30, 35.39], [50.44, 39.51]];	// Украина 3 часть
 
 if ($("input").is("#suggest")) {
 
 var suggestView = new ymaps.SuggestView('suggest',{offset:[7,12], boundedBy:boundedBy, strictBounds:true});	// Украина
 // var suggestView1 = new ymaps.SuggestView('suggest',{offset:[7,12], boundedBy:boundedBy1, strictBounds:true});	// Украина 1 часть
 // var suggestView2 = new ymaps.SuggestView('suggest',{offset:[7,12], boundedBy:boundedBy2, strictBounds:true});	// Украина 2 часть
 // var suggestView3 = new ymaps.SuggestView('suggest',{offset:[7,12], boundedBy:boundedBy3, strictBounds:true});	// Украина 3 часть
 // var suggestView1 = new ymaps.SuggestView('suggest',{offset:[7,12], boundedBy:[[61, 34], [62, 35]], strictBounds:true});	// Петрозаводск
 
 var funcSelect = function (e) {
 
 //console.log('funcSelect');
 // console.log(e);
 var adr = $("#suggest").val();
 // парсим адрес:
 getParserAddress(adr, '');
 }
 
 suggestView.events.add('select', function(e) {funcSelect(e)});	// Украина
 // suggestView1.events.add('select', function(e) {funcSelect(e)});	// Украина 1 часть
 // suggestView2.events.add('select', function(e) {funcSelect(e)});	// Украина 2 часть
 // suggestView3.events.add('select', function(e) {funcSelect(e)});	// Украина 3 часть
 
 // suggestView.events.add('optionschange', function (e) {
 //    console.log('optionschange');
 // });
 }
 
 if ($("input").is("#fact_suggest")) {
 
 var suggestViewFact = new ymaps.SuggestView('fact_suggest',{offset:[7,12], boundedBy:boundedBy, strictBounds:true});	// Украина
 // var suggestViewFact1 = new ymaps.SuggestView('fact_suggest',{offset:[7,12], boundedBy:boundedBy, strictBounds:true});	// Украина 1 часть
 // var suggestViewFact2 = new ymaps.SuggestView('fact_suggest',{offset:[7,12], boundedBy:boundedBy, strictBounds:true});	// Украина 2 часть
 // var suggestViewFact3 = new ymaps.SuggestView('fact_suggest',{offset:[7,12], boundedBy:boundedBy, strictBounds:true});	// Украина 3 часть
 
 var funcSelectFast = function (e) {
 //console.log('select');
 //console.log(e);
 var adr = $("#fact_suggest").val();
 // парсим адрес:
 getParserAddress(adr, 'fact');
 }
 
 suggestViewFact.events.add('select', function (e) {funcSelectFast(e)});	// Украина
 // suggestViewFact1.events.add('select', function (e) {funcSelectFast(e)});	// Украина 1 часть
 // suggestViewFact2.events.add('select', function (e) {funcSelectFast(e)});	// Украина 2 часть
 // suggestViewFact3.events.add('select', function (e) {funcSelectFast(e)});	// Украина 3 часть
 
 // suggestViewFact.events.add('optionschange', function (e) {
 //    console.log('optionschange');
 // });
 }
 }
 */

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

/**
 * обрабатывает событие истечения срока действия проверенной каптчи
 */
function onReCaptchaExpired() {

	$('#buttonGetCode').attr('disabled', true);
	flagReg.reCaptcha = false;

	// console.log('onReCaptchaExpired ok');
	// console.log(flagReg);
}

/**
 * выполняется после загрузки скрипта grecaptcha
 */
function onReCaptchaloadCallback() {

	// console.log('start 6 recaptchaOnloadCallback');

	var dataSitekey = '',
			idCaptcha;
	if ($("#buttonGetCode").length) {
		dataSitekey = ($("#buttonGetCode").attr("data-sitekey") != undefined) ? $("#buttonGetCode").attr("data-sitekey") : '';
		idCaptcha = "buttonGetCode";
	} else if ($("#buttonLogin1").length) {
		dataSitekey = ($("#buttonLogin1").attr("data-sitekey") != undefined) ? $("#buttonLogin1").attr("data-sitekey") : '';
		idCaptcha = "buttonLogin1";
	}

	if (dataSitekey.length > 5) {
		var widgetId = grecaptcha.render(idCaptcha, {
			// "sitekey": dataSitekey, 
			// "callback": "onReCaptchaVerify"
		});
		grecaptcha.reset(widgetId);
	}
}

/**
 * обрабатывает событие проверки каптчи на стороне клиента
 * @param response
 */
function onReCaptchaVerify(response) {

	if (flagReg.phone) {
		$('#buttonGetCode').removeAttr('disabled');
	}

	flagReg.reCaptcha = true;

	// console.log('onReCaptchaVerify ok');
	// console.log('g-recaptcha-response = ' + $("#g-recaptcha-response").val());
	// console.log(flagReg);

	// отправляем каптчу на проверку, и открываем форму ввода кода: 
	onClickGetCode();
}

/**
 * обрабатывает событие проверки каптчи на стороне клиента при входе
 * @param response
 */
function onReCaptchaVerifyAuth(response) {

	// console.log('onReCaptchaVerifyAuth ok');

	// записываем код каптчи:
	//$("#captcha").val( $("#g-recaptcha-response").val() );
	//$("#captcha").val(response);

	if (response.length != 0) {
		$("#captcha").val(response);
	} else {
		$("#captcha").val('no response');
	}

	grecaptcha.reset(); // сброс капчи

	onClickLogin();
}

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
 * распечатывает елемент
 */
function printElement(element) {

	/*
	 function initOnLoad() {
	 if(win.location.href == window.location.href) {
	 loadComplete();
	 } else {
	 console.log('setTimeout');
	 setTimeout(function() {initOnLoad();}, 5000);
	 }
	 }
	 
	 function loadComplete() {
	 //Действия после появления необходимого элемента в DOM
	 console.log('win.loadComplete');
	 console.log('loadComplete  win.location.hostname = ' + win.location.hostname + ' win.location.href = ' + win.location.href);
	 win.print();
	 $('iframe').remove();
	 }
	 */

	// стили для принта:
	var printing_css = '<style media=print></style>';
	// записываем в переменную содержимое элемента:
	var html_to_print = printing_css + $(element).html();
	// создадим iframe (он будет контейнером нашего нового window):
	var iframe = $('<iframe id="print_frame">');
	$('body').append(iframe);
	// получим объекты document и window новосозданного iFrame:
	var doc = $('#print_frame')[0].contentDocument || $('#print_frame')[0].contentWindow.document;
	var win = $('#print_frame')[0].contentWindow || $('#print_frame')[0];

	doc.getElementsByTagName('body')[0].innerHTML = html_to_print;

	//win.location.href = window.location.href;
	// initOnLoad();

	win.print();
	$('iframe').remove();
}

/**
 * отправляет email-адрес для записи (нужно для последующей отправки уведомлений) 
 */
function recordEmailSiteOnline() {

	var url = "/ru/?ajax";

	var address = $("#email").val();

	var data = {
		typeData: 'recordEmailSiteOnline',
		email: address
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
				if (js.message == 'OK') {
					$("#spanResultOk").removeClass("hidden");
					$("#spanResultError").addClass("hidden");
					$("#email").val("");
				} else {
					$("#spanResultOk").addClass("hidden");
					$("#spanResultError").removeClass("hidden");
				}

				// console.log(js);
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера recordEmailSiteOnline: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль
		}
	});

}

/**
 * отправляет номер телефона для записи
 * @param phoneId		- ID-элемента с введенным номером телефона
 * @param filePrefix	- начало имени файла
 * @param phoneFrom		- с какой формы запись
 * @param partner		- партнер, от которого пришли
 * @returns
 */
function recordPhone(phoneId, filePrefix, phoneFrom, partner) {

	var url = "/ru/?ajax";

	var phone = $("#"+phoneId).val();

	var data = {
		typeData: 'recordPhone',
		phone: phone,
		filePrefix: filePrefix,
		phoneFrom: phoneFrom,
		partner: partner
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
				if (js.message == 'OK') {
					$("#div_promocode").removeClass("hidden");
					$("#div_Vodafone").addClass("hidden");
				} else {
					$("#div_promocode").addClass("hidden");
					$("#div_Vodafone").removeClass("hidden");
				}

				// console.log(js);
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера recordEmailSiteOnline: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль
		}
	});

}

/*
 * редиректит пользователя в BankID
 * @returns {Boolean}
 *
 function redirectToBankID() {
 
 var url = "/ru/?ajax";	
 
 var data = {
 typeData: 'redirectToBankID'
 };
 
 // отправить массив на сервер
 console.log("Передаем запрос ajax " + url);
 
 $.ajax({
 url: url,
 type: 'POST',
 data: {data: data},
 dataType: 'json',
 //dataType: 'html',
 success: function(json){
 if(json) {
 //var js = JSON.parse(json);
 var js = json;
 
 //console.log(js);
 if (js.message == 'OK') {
 location.href = js.url;
 }
 };
 },
 
 error: function(jqXHR, textStatus, errorThrown){
 // console.log(jqXHR); // вывод JSON в консоль
 console.log('Сообщение об ошибке от сервера: '+textStatus); // вывод JSON в консоль
 // console.log(errorThrown); // вывод JSON в консоль
 }
 });
 
 return false;
 }
 */

/**
 * Запрашивает состояние счетчика заявок, и обновляет его
 */
function refreshCounters() {

	var url = "/ru/?ajax";
	var data = {
		typeData: 'refreshCounters',
	};

	// console.log(data);

	$.ajax({
		url: url,
		type: 'POST',
		data: {data: data},
		dataType: 'json',
		//dataType: 'html',
		success: function (json) {
			if (json) {
				//var js = JSON.parse(json);
				var js = json;
				if (js.message == 'OK') {
					var counter = js.counter;
					if ($(".counter-item-4").first().html() !== counter.substring(0, 1))
						$(".counter-item-4").html(counter.substring(0, 1));
					if ($(".counter-item-3").first().html() !== counter.substring(1, 2))
						$(".counter-item-3").html(counter.substring(1, 2));
					if ($(".counter-item-2").first().html() !== counter.substring(2, 3))
						$(".counter-item-2").html(counter.substring(2, 3));
					if ($(".counter-item-1").first().html() !== counter.substring(3, 4))
						$(".counter-item-1").html(counter.substring(3, 4));
				}
				// console.log(js);
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

/**
 * перезаписывает данные в калькуляторе кредита
 * 
 * @returns {Boolean}
 */
function reloadCred(typeSlider) {

	// typeSlider = 'large' - большой слайдер

	/*
	 if (typeSlider == 'large') {
	 var prefix = '_large';
	 } else {
	 var prefix = '';
	 }
	 */

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

	/*
	 // заполняем малый слайдер:
	 if ($("span").is("#span_amount")) document.getElementById("span_amount").innerHTML = '' + money;
	 if ($("span").is("#span_amount_day")) document.getElementById("span_amount_day").innerHTML = '' + day;
	 if ($("span").is("#span_amount_all")) document.getElementById("span_amount_all").innerHTML = credCalculation(money, day, percent) + '';
	 if ($("span").is("#span_amount_all_end")) document.getElementById("span_amount_all_end").innerHTML = ' '+ lastDay + '.' + lastMonth + '.' + lastYear;
	 if ($("span").is("#span_get_money")) document.getElementById("span_get_money").innerHTML = ' <strong>' + hour + ':' + minute + '</strong>';
	 // заполняем дня - день - дней:
	 if ($("span").is("#dayMinDay")) document.getElementById("dayMinDay").innerHTML = getDayLang(minDay.toString()); // "дней" в minDay
	 if ($("span").is("#dayMaxDay")) document.getElementById("dayMaxDay").innerHTML = getDayLang(maxDay.toString()); // "дней" в maxDay
	 if ($("span").is("#daySelectDay")) document.getElementById("daySelectDay").innerHTML = dayStr; // "дней" выбрано
	 if ($("span").is("#dayLargeDay")) document.getElementById("dayLargeDay").innerHTML = dayStr; // "дней" всего
	 
	 // заполняем большой слайдер:
	 if ($("span").is("#span_amount_large")) document.getElementById("span_amount_large").innerHTML = '' + money;
	 if ($("span").is("#span_amount_day_large")) document.getElementById("span_amount_day_large").innerHTML = '' + day;
	 if ($("span").is("#span_amount_all_large")) document.getElementById("span_amount_all_large").innerHTML = credCalculation(money, day, percent) + '';
	 if ($("span").is("#span_amount_all_end_large")) document.getElementById("span_amount_all_end_large").innerHTML = ' '+ lastDay + ' ' + getMonthLang(lastMonth) + ' ' + lastYear;
	 if ($("span").is("#span_get_money_large")) document.getElementById("span_get_money_large").innerHTML = ' <strong>' + hour + ':' + minute + '</strong>';
	 // заполняем дня - день - дней:
	 if ($("span").is("#dayMinDay_large")) document.getElementById("dayMinDay_large").innerHTML = getDayLang(minDay.toString()); // "дней" в minDay
	 if ($("span").is("#dayMaxDay_large")) document.getElementById("dayMaxDay_large").innerHTML = getDayLang(maxDay.toString()); // "дней" в maxDay
	 if ($("span").is("#daySelectDay_large")) document.getElementById("daySelectDay_large").innerHTML = dayStr; // "дней" выбрано
	 if ($("span").is("#dayLargeDay_large")) document.getElementById("dayLargeDay_large").innerHTML = dayStr; // "дней" всего
	 */

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
	/*
	 // заполняем значения несвоего слайдера:
	 if (typeSlider == 'large') {
	 if ($("input[id='money-value']").length > 0) document.getElementById("money-value").value = '' + money;
	 if ($("input[id='day-value']").length > 0) document.getElementById("day-value").value = '' + day;
	 } else {
	 if ($("input[id='money-value_large']").length > 0) document.getElementById("money-value_large").value = '' + money;
	 if ($("input[id='day-value_large']").length > 0) document.getElementById("day-value_large").value = '' + day;
	 }
	 */

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
	
	// форматирование 
	//var val1 = document.getElementById('span_amount_all');
	//var formatedNumber1 = accounting.formatNumber(val1.innerHTML);
	//val1.innerHTML = formatedNumber1;

	// для лендинга:
	/*
	 if (parseInt(day) > 35) { 
	 $("#spanLanding").removeClass("hidden");
	 } else {
	 $("#spanLanding").addClass("hidden");
	 }
	 */

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

/**
 * отправляет код регистрации по СМС
 * @param  string phone
 */
function sendCodeReg(phone, captcha) {

	// получаем язык сайта 
	var lang = document.getElementById('lang').innerHTML;

	var url = "/ru/?ajax";
	var data = {
		typeData: 'sendCodeReg',
		phone: phone,

		// captcha: captcha, 
		// captcha1: window.btoa(captcha), 
		captcha1: captcha,
		lang: lang
	};

	// console.log(data);

	$.ajax({
		url: url,
		type: 'POST',
		data: {data: data},
		dataType: 'json',
		//dataType: 'html',
		success: function (json) {
			if (json) {
				//var js = JSON.parse(json);
				var js = json;
				if (js.message == 'OK') {
					$("#errorCaptcha").addClass("hidden");
					// $("#code-modal").modal("show");	// показать модальное окно ввода кода
					$("#div_code").removeClass("hidden"); // показать поля ввода кода
					$('#buttonGetCode').addClass('hidden', true);
					$('#phone').attr('disabled', true);
				} else if (js.message == 'existPhone') {
					$("#errorCaptcha").addClass("hidden");
					$('#phone').attr('disabled', true);
					$('#buttonGetCode').addClass("hidden");
					$('#login').val($('#phone').val());
					$("#div_auth").removeClass("hidden"); // показать поля ввода пароля на вход
				} else {
					$("#errorCaptcha").removeClass("hidden");
					if ($("#isReCaptcha_enabled").text() == "1")
						grecaptcha.reset(); // сброс капчи
					// $('#buttonGetCode').attr('disabled', true);
					$('#buttonGetCode').removeAttr('disabled');
					flagReg.reCaptcha = false;
					// console.log(js.message_details);
				}
				// console.log(js);
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

/**
 * отправляет на сервер команду послать письмо подтверждения почтового ящика
 * @returns
 */
function sendConfirmEmail() {

	var url = "/ru/?ajax";
	var data = {
		typeData: 'sendConfirmEmail'
	};

	$.ajax({

		url: url,
		type: 'POST',
		data: {data: data},
		dataType: 'json',
		success: function (json) {
			// console.log(json);
			var link = $('#confirmEmail');
			var alreadyMessage = $('#already-send');
			if (json.message) {
				$("#confirm-success").modal();
				link.addClass('hidden').prev().addClass('hidden');
				alreadyMessage.removeClass('hidden');
			} else {
				$("#confirm-error").modal();
				link.removeClass('hidden').prev().removeClass('hidden');
				alreadyMessage.addClass('hidden');
			}
		},
		error: function (jqXHR, textStatus) {
			console.log('Отправка сообщения: ' + textStatus); // вывод JSON в консоль
		}
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

function setDatepicker(idDatepicker, dateBegin, dateEnd) {

	$(document).ready(function () {

		$(idDatepicker).Zebra_DatePicker({

			// execute a function whenever the user changes the view 
			//(days/months/years), as well as when the user 
			// navigates by clicking on the "next"/"previous" icons 
			// in any of the views
			onChange: function (view, elements) {

				onChangeDatepicker('onChange', idDatepicker);

				// on the "days" view...
				if (view == 'days') {

					// iterate through the active elements in the view
					elements.each(function () {

						// to simplify searching for particular dates, 
						// each element gets a "date" data attribute which 
						// is the form of: 
						// - YYYY-MM-DD for elements in the "days" view
						// - YYYY-MM for elements in the "months" view
						// - YYYY for elements in the "years" view

						// so, because we're on a "days" view,
						// let's find the 24th day using a regular 
						// expression (notice that this will apply to 
						// every 24th day of every month of every year)
						/*
						 if ($(this).data('date').match(/\-24$/))
						 
						 // and highlight it!
						 $(this).css({
						 backgroundColor:    '#C40000',
						 color:              '#FFF'
						 });
						 */
					});

				}
			},

			onSelect: function (view, elements) {

				onChangeDatepicker('onSelect', idDatepicker);

			}

		});

		// получаем язык сайта 
		var lang = document.getElementById('lang').innerHTML;
		switch (lang) {

			case "ru":
				var days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четврег', 'Пятница', 'Суббота'];
				var months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
				var days_abbr = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
				break;

			case "ua":
				var days = ['Неділя', 'Понеділок', 'Вівторок', 'Середа', 'Четвер', 'П`ятниця', 'Субота'];
				var months = ['Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'];
				var days_abbr = ['Нд', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
				break;
		}

		// console.log('datepicker lang = ' + lang + '  idDatepicker = ' + idDatepicker);
		// console.log('datepicker dateBegin = ' + dateBegin + '  dateEnd = ' + dateEnd);

		var datepicker = $(idDatepicker).data('Zebra_DatePicker');

		if (datepicker != undefined) {
			datepicker.update({
				days: days,
				months: months,
				show_select_today: false,
				// direction: [1, 30],
				direction: [dateBegin, dateEnd],
				show_clear_date: false,
				offset: [0, 232],
				// format: 'd F Y',
				format: 'Y-m-d',
				days_abbr: days_abbr,
				select_other_months: true
			});
		}

		/*
		 // assuming the controls you want to attach the plugin to
		 // have the "datepicker" class set
		 $(datepicker).Zebra_DatePicker({
		 days: days,
		 months: months,
		 show_select_today: false,
		 direction: [1, 30],
		 show_clear_date: false,
		 offset: [0, 232],
		 format: 'd F Y',
		 days_abbr: days_abbr,
		 select_other_months: true
		 });
		 */

	});
}


/**
 * открывает модальное окно авторизации
 */
function showModalAuth(login) {
	$("[name=\'auth[login]\']").val(login);
	$("#modal_auth").modal("show");

	// console.log('login = ', login);
}

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
 * запускает submit формы списка кредитов (действие по выбранному кредиту)
 * 
 * @param int href
 * @param int cred_id
 * @returns {Boolean}
 */
function submit_credits_form(href, cred_id) {

	// document.getElementById("cred_id").value = cred_id;
	document.getElementById("cred_number").value = cred_id;
	document.getElementById("href").value = href;

	if (window.document.forms['credits_form'] != null)
		window.document.forms['credits_form'].submit();

	// console.log('credits_form submit');

	return false;
}

/**
 * запускает submit формы кредита
 * @param formKey
 * @param href
 * @param amount
 * @returns
 */
function submitCreditsForm(formKey, href, amount) {

	if (href !== 0)
		document.getElementById("href_" + formKey).value = href;
	if (amount !== 0)
		document.getElementById("amount_" + formKey).value = amount;

	if (window.document.forms['credits_form_' + formKey] != null)
		window.document.forms['credits_form_' + formKey].submit();

	console.log('submit credits_form_' + formKey);

	return false;
}

/**
 * запускает submit формы оплаты кредита
 * @param isCurrentCard
 * @param IsVisaCheckoutPayment
 * @returns {Boolean}
 */
function submitPay(isCurrentCard, isVisaCheckoutPayment) {

	$('.js-btn-pay').attr('disabled', true); // дизейблим кнопки оплат

	ga('send', 'event', 'LKrepay', 'Click'); // аналитика Google

	if (isCurrentCard == 1) {
		document.getElementById("isCurrentCard").value = "1";
	} else {
		document.getElementById("isCurrentCard").value = "0";
	}

	if (isVisaCheckoutPayment === 1) {
		document.getElementById("IsVisaCheckoutPayment").value = "1";
	}

	window.document.forms['form_pay'].submit();

	console.log('submitPay=' + isCurrentCard);

	return false;
}

/**
 * отсылает данные калькулятора через ajax на локальный сервер для сохранения в $_SESSION, закрывает калькулятор
 * @param prefix
 */
function submitSlider(prefix) {

	var money = document.getElementById("money-value_" + prefix).value;
	var day = document.getElementById("day-value_" + prefix).value;

	var data = {
		typeData: 'submitSlider',
		orderCredit: {
			'money-value': money,
			'day-value': day
		},
	};

	// отправить массив на сервер
	console.log("Передаем запрос ajax 'submitSlider'");
	sendAjax(data);

	$("#modal_slider").modal("hide");
	//$(".hidden-slider").removeClass("open-slider");
	//$(".change-button").removeClass("hidden");
}

/*
 * тестовая функция для проверки WFP
 * @param id
 *
 function test_verify_card(id) {
 var wayforpay = new Wayforpay();
 //var themeWfp = '<!DOCTYPE html><html lang="ru"><head></head><body><div>qqqqqqqq</div></body></html>';
 
 wayforpay.run({
 merchantAccount : "test_merch_n1",
 merchantDomainName : "www.market.ua",
 authorizationType : "SimpleSignature",
 merchantSignature : "b95932786cbe243a76b014846b63fe92",
 orderReference : "DH783023",
 orderDate : "1415379863",
 amount : "1547.36",
 currency : "UAH",
 productName : "Процессор Intel Core i5-4670 3.4GHz",
 productPrice : "1000",
 productCount : "1",
 clientFirstName : "Вася",
 clientLastName : "Васечкин",
 clientEmail : "some@mail.com",
 clientPhone: "380631234567",
 
 serviceUrl: "http://1bank.com.ua/ru/?verify",
 language: "RU",
 requestType: "VERIFY"
 // theme : themeWfp
 },
 function (response) {
 // on approved             
 console.log('on approved');
 },
 function (response) {
 // on declined
 console.log('on declined');
 },
 function (response) {
 // on pending or in processing
 console.log('on pending or in processing');
 }
 );
 }
 */

/**
 * Проверяет необходимость перезагрузки страницы Мои карты, при необходимости - перегружает
 */
function tranzzoCheckRefreshPage(interval_refresh_page) {
	var url = "/ru/?ajax";

	var data = {
		typeData: 'tranzzoCheckRefreshPage'
	};

	refreshTimerId = setInterval(function () {
		$.ajax({
			url: url,
			type: 'POST',
			data: {data: data},
			dataType: 'json',
			success: function (json) {
				if (json) {
					var js = json;
					// console.log(js);
					if ((js.message == 'OK') && (js.toRefresh == 'yes')) {
						location = location.href;
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

	}, interval_refresh_page * 1000);

	return false;
}

/**
 * отправляет данные по карте в CRM при нажатии на кнопку
 * @returns
 */
function tranzzoPayAnotherCard() {

	// $("#button_sendCard").attr('disabled', true);	// дизейблим кнопку отправки

	$(".new-repayment-data").removeClass("hidden");
	$("#div_step1").addClass("hidden");
	$("#div_waiting").removeClass("hidden");
	$('#wait-modal').modal('show'); // Ожидание через modal
	$("#div_error").addClass("hidden");
	$('#data-error').modal('hide'); // Error через modal

	var cardNumber = $("#card_number_1").val().trim() + $("#card_number_2").val().trim() + $("#card_number_3").val().trim() + $("#card_number_4").val().trim();
	var cardDateMonth = +$("#card_month").val();
	var cardDateYear = +$("#card_year").val();
	var cardCvv2 = $("#cvv2").val();
	var amount = +$("#js-repayment-sum").val();
	var backUrl = $("#backUrl").text();
	var lang = document.getElementById('lang').innerHTML;

	var url = "/ru/?ajax";

	var data = {
		typeData: 'payAnotherCard',
		cardNumber: cardNumber,
		cardDateMonth: cardDateMonth,
		cardDateYear: cardDateYear,
		cardCvv2: cardCvv2,
		amount: amount,
		backUrl: backUrl,
		lang: lang
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

				// console.log(js);
				if (js.message == 'OK') {
					// если карта 3Ds:
					if (js.data.CardType == 1) {
						if (js.data.Url) {
							// заполняем форму для внешнего поста:
							// console.log(js.data);
							location.href = js.data.Url;
						}
						// если не 3Ds, заполняем форму для внешнего поста, или просто уходим по ссылке на наш сайт:
					} else {
						if (js.data.Url) {
							// заполняем форму для внешнего поста:
							// console.log(js.data);
							location.href = js.data.Url;
						} else {
							location.href = $("#backUrl").text();
						}
					}

				} else {
					$("#div_waiting").addClass("hidden");
					$('#wait-modal').modal('hide'); // Ожидание через modal
					$("#div_error").removeClass("hidden");
					$("#span_error").text(js.message_details);
					$('#data-error').modal('show'); // Error через modal

					$("#div_card_list").removeClass("hidden");
					$("#div_btn_pay").removeClass("hidden");
					$("#div_btn_pay_another").removeClass("hidden");

					$("#div_step1").addClass("hidden");
				}
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль

			$("#div_waiting").addClass("hidden");
			$('#wait-modal').modal('hide'); // Ожидание через modal
			$("#div_error").removeClass("hidden");
			$('#data-error').modal('show'); // Error через modal
			//$("#div_step1").removeClass("hidden");
			$("#button_sendCard").removeAttr("disabled");
		}
	});
	return false;
}

/**
 * обрабатывает второй шаг верификации карты
 * @returns
 */
function tranzzoPayStep2(CardId) {

	// console.log('tranzzoPayStep2');

	// $("#div_step1").addClass("hidden");
	// $("#div_waiting").removeClass("hidden");

	// var cardNumber = $("#card_number").val();
	// var cardNumber = $("#card_number_1").val().trim() + $("#card_number_2").val().trim() + $("#card_number_3").val().trim() + $("#card_number_4").val().trim();

	var url = "/ru/?ajax";

	var data = {
		typeData: 'checkStatusCard',
		// cardNumber: cardNumber
		CardId: CardId
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

				// console.log(js);
				if (js.message == 'OK') {
					$("#div_waiting").addClass("hidden");
					$('#wait-modal').modal('hide'); // Ожидание через modal
					// если 3ds:
					if (js.status == 1) {
						if (js.params) {
							// заполняем форму для внешнего поста:
							// console.log(js.params);
							location = js.params.VerifiedURL;
						}
					}
					// если не 3ds:
					if (js.status == 2) {
						$("#div_waiting").addClass("hidden");
						$('#wait-modal').modal('hide'); // Ожидание через modal
						$("#div_step2").removeClass("hidden");
					}
				} else {
					$("#div_card_list").removeClass("hidden");
					$("#div_waiting").addClass("hidden");
					$('#wait-modal').modal('hide'); // Ожидание через modal
					$("#div_error").removeClass("hidden");
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

			$("#div_waiting").addClass("hidden");
			$('#wait-modal').modal('hide'); // Ожидание через modal
			$("#div_error").removeClass("hidden");
			$('#data-error').modal('show'); // Error через modal
			// $("#button_sendCard").removeAttr("disabled");
		}
	});

	return false;
}

/**
 * обрабатывает второй шаг верификации карты - ввод кода, если карта не 3ds
 * @returns
 */
function tranzzoPayStep2_SendCode() {

	// console.log('tranzzoPayStep2_SendCode');

	$("#div_step2").addClass("hidden");
	$("#div_waiting").removeClass("hidden");
	$('#wait-modal').modal('show'); // Ожидание через modal
	$("#div_error").addClass("hidden");
	$('#data-error').modal('hide'); // Error через modal

	// var cardNumber = $("#card_number").val();
	var cardNumber = $("#card_number_1").val().trim() + $("#card_number_2").val().trim() + $("#card_number_3").val().trim() + $("#card_number_4").val().trim();
	var sendCode = $("#sendCode").val();
	var cardId = $("#cardId").val();
	var lang = document.getElementById('lang').innerHTML;

	var url = "/ru/?ajax";

	var data = {
		typeData: 'paySendCode',
		// cardNumber: cardNumber,
		cardId: cardId,
		sendCode: sendCode,
		lang: lang
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

				// console.log(js);
				if (js.message == 'OK') {
					// $("#div_waiting").addClass("hidden");
					// $("#div_step2").removeClass("hidden");
					// $("#button_sendMe").removeAttr("disabled");
					//location.href = js.url;
					location.reload(true);
				} else {
					$("#div_waiting").addClass("hidden");
					$('#wait-modal').modal('hide'); // Ожидание через modal
					$("#div_error").removeClass("hidden");
					$("#span_error").text(js.message_details);
					$('#data-error').modal('show'); // Error через modal
					$("#div_card_list").removeClass("hidden");

				}
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль

			$("#div_waiting").addClass("hidden");
			$('#wait-modal').modal('hide'); // Ожидание через modal
			$("#div_error").removeClass("hidden");
			$('#data-error').modal('show'); // Error через modal
			// $("#button_sendCard").removeAttr("disabled");
		}
	});

	return false;

}

/**
 * отправляет данные по карте в CRM при нажатии на кнопку
 * @returns
 */
function tranzzoSendCardDetails() {

	$(".new-repayment").addClass("hidden");
	$("#div_step1").addClass("hidden");
	$("#div_waiting").removeClass("hidden");
	$('#wait-modal').modal('show'); // Ожидание через modal
	$("#div_error").addClass("hidden");
	$('#data-error').modal('hide'); // Error через modal

	var cardNumber = $("#card_number_1").val().trim() + $("#card_number_2").val().trim() + $("#card_number_3").val().trim() + $("#card_number_4").val().trim();
	var cardDateMonth = +$("#card_month").val();
	var cardDateYear = +$("#card_year").val();
	var cardCvv2 = $("#cvv2").val();
	var backUrl = $("#backUrl").text();
	var lang = document.getElementById('lang').innerHTML;

	var url = "/ru/?ajax";

	var data = {
		typeData: 'sendCardDetails',
		cardNumber: cardNumber,
		cardDateMonth: cardDateMonth,
		cardDateYear: cardDateYear,
		cardCvv2: cardCvv2,
		backUrl: backUrl,
		lang: lang
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

				// console.log(js);
				if (js.message == 'OK') {
					$("#cardId").val(js.CardId);
					tranzzoPayStep2(js.CardId); // переходим на второй шаг верификации
				} else {
					$("#div_waiting").addClass("hidden");
					$('#wait-modal').modal('hide'); // Ожидание через modal
					$("#div_error").removeClass("hidden");
					$("#span_error").text(js.message_details);
					$('#data-error').modal('show'); // Error через modal
					$("#div_card_list").removeClass("hidden");
				}
			}
			;
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль

			$("#div_waiting").addClass("hidden");
			$('#wait-modal').modal('hide'); // Ожидание через modal
			$("#div_error").removeClass("hidden");
			$('#data-error').modal('show'); // Error через modal
			//$("#div_step1").removeClass("hidden");
			$("#button_sendCard").removeAttr("disabled");
		}
	});

	return false;

}

/**
 * открывает форму начала оплаты другой картой
 * @returns
 */
function tranzzoStartPayAnotherCard() {

	$("#div_card_list").addClass("hidden");
	$("#div_btn_pay").addClass("hidden");
	$("#div_btn_pay_visa").addClass("hidden");
	$("#div_btn_pay_another").addClass("hidden");
	$("#div_error").addClass("hidden");
	$('#data-error').modal('hide'); // Error через modal

	$(".new-repayment-data").removeClass("hidden");
	$("#div_step1").removeClass("hidden");
}

/**
 * проверяет правильность заполнения полей в "Моих данных", выставляет флаг на соответствующей закладке
 */
function validateMyData() {

	if (validate($("#myData"))) {
		$("#span_myData").addClass('hidden');
	} else {

		// Спаны с ошибками (Саша)
		var notRequiredElements = $(this).parents(".js_validate").find('.has-error').closest('.personal-table-data').find('input').not('input[required]').not('input[type="hidden"]');
		$(notRequiredElements).closest('div').addClass('has-success');
		$(this).parents(".js_validate").find('.has-error').closest('.personal-table-data').addClass('has-error');

		$("#span_myData").removeClass('hidden');
	}
	;
	if (validate($("#myData2"))) {
		$("#span_myData2").addClass('hidden');
	} else {
		// Спаны с ошибками (Саша)
		var notRequiredElements = $(".js_validate").find('.has-error').closest('.personal-table-data').find('input').not('input[required]').not('input[type="hidden"]');
		$(notRequiredElements).closest('div').addClass('has-success');
		$(".js_validate").find('.has-error').closest('.personal-table-data').addClass('has-error');

		$("#span_myData2").removeClass('hidden');
	}
	;
}
/**
 * запускает верификацию карты
 * 
 * @param id
 * @return bool
 */
function verify_card(id) {
	if (id == '')
		id = '0';
	document.getElementById("verify-id").value = id;
	window.document.forms['cardsForm'].submit();
	return false;
}

/**
 * запускает виджет WayForPay
 * @param data
 * @returns
 */
function widgetW4p(data) {
	console.log('on widget');
	var wayforpay = new Wayforpay();

	wayforpay.run(data,
			function (response) {
				// on approved             
				console.log('on approved');
			},
			function (response) {
				// on declined
				console.log('on declined');
			},
			function (response) {
				// on pending or in processing
				console.log('on pending or in processing');
			}
	);
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

/*
 function temp() {
 
 $('.selectpicker').selectpicker({
 title: 'Выберите',
 dropupAuto: false
 });
 
 switch (document.getElementById("lang").innerHTML) {
 case "ru":
 titleSelect = 'Выберите';
 break;
 case "ua":
 titleSelect = 'Виберіть';
 break
 case "en":
 titleSelect = 'Select';
 break
 default:
 titleSelect = 'Выберите';
 }
 onclick='window.document.forms['form_dog'].reset(); return false;'
 window.document.forms['myData'].reset();
 
 console.log('lang='+document.getElementById("lang").innerHTML+' titleSelect='+titleSelect);
 $('.selectpicker').selectpicker({
 title: titleSelect,
 dropupAuto: false
 });
 
 }
 */

// запускаем переход на следующее поле по Enter и Tab
$(function () {

	//var a = $("input, select, button");
	//var a = $("input, button").closest(div:not(".hidden"));
	//var a = $("input, button").parents(":not(.hidden)").not(".hidden");

	var a = $(".js_validate input:not([type=hidden]), .js_validate button:not([type=hidden]), .js_validate textarea:not([type=hidden])");

	// var a = $("input:visible, button:visible, textarea:visible");
	// console.log(a);

	if ($('.selectpicker').length) {
		$('.selectpicker').selectpicker({
			"liveSearchStyle": "startsWith"

		}).on('loaded.bs.select', function (e) {
			$('.btn.dropdown-toggle').attr('tabindex', '0');
		});
	}

	if ($('#card_month').length && $('#card_year').length) {
		$('#card_month').on('changed.bs.select', function (e) {
			$('#card_year').selectpicker('toggle');
		});
	}

	$('[data-id=card_month]').closest('.bootstrap-select').find('.bs-searchbox .form-control').on('keydown', function (e) {
		console.log(e.keyCode);
		if ((e.keyCode) == 13) {
			//$('[data-id=card_year]').trigger('click');
		}
	});


	a.each(function (i, item) {
		$(item).keydown(function (e) {
			switch (e.keyCode) {
				case 13:
					if (!validate($(this).parents(".js_validate"), this.id)) {
						e.preventDefault();
					} else {
						a[i + 1].focus();
						console.log(a[i + 1]);
						if ($(a[i + 1]).hasClass('dropdown-toggle')) {
							//$(a[i + 1]).trigger('click');
						}
					}
				case 9:
					if (!validate($(this).parents(".js_validate"), this.id)) {
						console.log('отмена действия');
						e.preventDefault();
						$(e.target).focus();
					}
				default:
			}
		});
	});



	// $('.selectpicker').selectpicker('refresh');

	//var parents = $("input, button").parents(".hidden").find("input, button").attr('disabled', 'disabled');
	//console.log(parents);
	//var a = $("input, button").is();

	//a = a.not();
	/*	Закоменчено нестандартное поведение на элементах формы при нажатии Enter
	 */
	// a.each(function(c, b) {

	//     b = $(b);

	//     var d = c + 1 == a.length ? a.eq(0) : a.eq(c + 1);
	//     b.keydown(function(a) {

	//         // console.log(a);
	//         // console.log(a.currentTarget);
	//         // console.log('id='+a.currentTarget.id);
	//         // console.log('this.id='+this.id);
	//         // console.log(document.activeElement);			
	//         // ничего не делать на определенных id:
	//         if (a.currentTarget.id === 'message') {
	//             return;
	//         }

	//         // если Enter, или Tab:
	//         if (13 == a.which || 9 == a.which) {
	//             // если проходит валидацию (передаем ID элемента):
	//             if (validate($(this).parents(".js_validate"), this.id)) {

	//                 var nextElement = $(this);
	//                 // var Elements = $("input:visible, button:visible, textarea:visible");
	//                 var Elements = $("input:visible, button:visible, textarea:visible, a[role='button']").not('.btn-user-close, .slider-money--control');
	//                 var countElement = $("input:visible, button:visible, textarea:visible, a[role='button']").not('.btn-user-close, .slider-money--control').length;
	//                 // console.log('Elements=');console.log(Elements);

	//                 var flagThis = false;
	//                 //console.log('Elements.length= '+Elements.length)+' this.attr='+$(this).attr('data-id');

	//                 for (var i = 0; i < countElement; i++) {

	//                     // console.log('Elements.id= '+$(Elements[i]).attr('id') + ' Elements.data-id= '+$(Elements[i]).attr('data-id'));

	//                     if (($(Elements[i]).attr('id') == $(this).attr('id')) && ($(Elements[i]).attr('data-id') == $(this).attr('data-id'))) {
	//                         flagThis = true;
	//                         continue;
	//                     }

	//                     if (!flagThis) {
	//                         // console.log('not flagThis');
	//                         continue;
	//                     } else {
	//                         //console.log('nextElement= '+ Elements[i].getAttribute('id'));
	//                         nextElement = Elements[i];
	//                         break;
	//                     }
	//                 }

	//                 d = nextElement;

	//                 a.preventDefault(),
	//                     //d.select(), 
	//                     d.focus();

	//                 // console.log(document.activeElement);

	//                 //$(d).closest('.bootstrap-select').addClass('open');	// проставляем на следующем элементе
	//                 $(d).attr('aria-expanded', true); // проставляем на следующем элементе
	//                 b.closest('.bootstrap-select').removeClass('open'); // отменяем на данном элементе
	//                 b.attr('aria-expanded', false); // отменяем на данном элементе
	//                 // console.log('b.tagName= ' + $(b)[0].tagName + ' b.id='+b.attr('id')+' d.tagName= ' + $(d)[0].tagName +' d.id='+$(d).attr('id')+' d.name='+$(d).attr('name'));

	//                 // если нажат Enter на кнопке:
	//                 if ((13 == a.which) && ($(b)[0].tagName == 'BUTTON') && (!$(b).hasClass("dropdown-toggle"))) {
	//                     $(b).trigger('click');
	//                 }
	//                 // если нажат Enter и перешло на кнопку:
	//                 if ((13 == a.which) && ($(d)[0].tagName == 'BUTTON') && (!$(d).hasClass("dropdown-toggle"))) {
	//                     $(d).trigger('click');
	//                 }
	//             }
	//         }
	//     })
	// })

	// для теста:
	/*
	 $(document).ready(function() {
	 //var x = $("[class^=nameSlider]").text();
	 var x = [];
	 $("[class^=nameSlider]").each(function(i, elem) { 
	 x.push($(elem).text());
	 }
	 );
	 console.log('x = '+x);
	 });
	 */

});

//// запускаем плагин Google Stars:
//$('#input-1').rating({
//    step: 1,
//    animation: true
//});

$(document).ready(function () {
	// загрузка скриптов:
	downloadJS(0);

	// перенесено с главной
	// getSessionData();
	onLoadSlider();

	var score = Math.round(Number($("#ratingScore").text()));
	// $('#input-1').rating('update', score);
	// $('#input-1').rating('refresh', {disabled: false});

	if (($('button').is('#buttonGetCode')) && ((($('#phone').val() !== undefined) ? $('#phone').val().length : 0) < 10)) {

		// выключаем кнопку "Получить код СМС" (так как Гугл устанавливает ее активной)
		setTimeout(function () {
			// console.log("buttonGetCode disabled");
			$('#buttonGetCode').attr('disabled', true);
			$('.rc-anchor').addClass('hidded');
			$('.rc-anchor-normal-footer smalltext').addClass('hidden');
		}, 1000);

		// запускает обновление счетчика:
		var timerId = setInterval(function () {
			refreshCounters();
		}, 20000);
	}

	// если есть элементы партнерской программы:
	if ($(".js-btn-partner").length > 0) {

		// событие при нажатии на кнопку записи партнерской информации:
		$(".js-btn-partner").on('click', function (event) {
			onClickSubmitPartner(event);
		});

		// событие при изменении checkbox партнерской информации:
		$(":checkbox").change(function () {

			var flag;

			if (this.checked) {
				$("[name=" + this.name + "]").attr('checked', true);
				flag = '1';
			} else {
				$("[name=" + this.name + "]").removeAttr('checked');
				flag = '0';
			}
			$("[name=out_" + this.name + "]").val(flag);
		});

		// событие при нажатии в поле телефон:
		$("#Phone_1").keyup(function (event) {
			onKeyUpPhone('Phone_1', true);
		});
		$("#Phone_2").keyup(function (event) {
			onKeyUpPhone('Phone_2', true);
		});

	}

	// если есть элементы для поиска в "Справке":
	if ($(".search-icon").length > 0) {

		// событие при нажатии на кнопку поиска:
		$(".search-icon").on('click', function (event) {
			onClickForSearch(event);
		});

		// событие при нажатии в поле поиск:
		$("#search_faq").keyup(function (event) {
			onClickForSearch(event);
		});

		// событие при нажатии элемента в меню:
		$(".js-question-click").on('click', function (event) {
			onClickMenuQuestions(event);
		});
	}

	// если есть элементы для рейтинга страницы:
	if ($("#span_page_rating").length > 0) {

		// событие при нажатии на кнопку положительного отзыва:
		$("#btn_like_positive").on('click', function (event) {
			onClickLikePage(1);
		});

		// событие при нажатии на кнопку отрицательного отзыва:
		$("#btn_like_negative").on('click', function (event) {
			onClickLikePage(-1);
		});
	}

	// если есть элементы ввода карты:
	if ($(".js-card").length > 0) {
		// событие при вводе номера карты:
		$(".js-card").on('keyup', function (event) {
			var input = $(this).val().replace(/_+/g, '');
			// переход на следующий input, если ввели четыре цифры
			if (input.length == 4) {
				validate($(this).parents(".js_validate"), this.id);
				$(this).closest('div').next().find('input').focus();
			}



			switch (event.keyCode) {
				case 8:
					if (input.length == 0) {
						$(this).closest('div').prev().find(".js-card").focus();
					}
					break;
				case 39:
					$(this).closest('div').next().find(".js-card").focus();
					break;
				case 37:
					$(this).closest('div').prev().find(".js-card").focus();
					break;
				default:
			}
		});
	}

	// если есть элементы для выбора доп. карт:
	if ($("#btn-cardsAdditional-select").length > 0) {

		// событие при нажатии на кнопку "Сделать основной":
		$("#btn-cardsAdditional-select").on('click', function (event) {
			onClickCardsAdditional('main');
		});

		// событие при нажатии на кнопку удаления:
		$("#btn-cardsAdditional-delete").on('click', function (event) {
			onClickCardsAdditional('delete');
		});
	}
	
	$('.js-show-promocode-modal').on('click', function() {
		$($(this).data('target')).find('.js-calc-promocode').data('type', $(this).data('type'));
		$($(this).data('target')).find('.js-calc-promocode').data('prefix', $(this).data('prefix'));
	});

	// событие при нажатии на кнопку ввода промокода на калькуляторе:
	$(".js-calc-promocode").on('click', function (event) {
		onClickGetPromocode($(this).data('type') + '_' + $(this).data('prefix'));
	});

	// если есть элементы modal для перехода на другую страницу:
	if ($("#div-beforeunload").length > 0) {

		// событие при нажатии на ссылку:
		$('body').on('click', 'a[href^="http"][id!="a-beforeunload"]:not([transport]), a[href^="/"]:not(.confirm), a.lang-link', function (e) {
			e.preventDefault(); // отключить обработчик
			// console.log(e);
			var href = e.currentTarget.attributes.href.value;

			$('#a-beforeunload').attr('href', href);
			$('#span-beforeunload').text($('#span_beforeunload_text').text());
			$('#div-beforeunload').modal('show');
		});

		// Опрос о причине ухода со страницы
		$('.js-leaving-page-interview').on('click', function (e) {
			e.preventDefault();

			var $this = $(this);

			var reason = $('input[name="leaving_page_reason"]:checked').val();

			if (!reason) {
				return false;
			}

			var data = {
				typeData: 'leavingPageReason',
				pageId: $this.data('id'),
				leavingPageReason: reason
			};

			$.ajax({
				url: '/ru/?ajax',
				type: 'POST',
				data: {data: data},
				dataType: 'json',
				success: function (json) {
					window.location = $this.attr('href');
				},
				error: ajaxError
			});
		});
	}
	// console.log($('a[href^="http"]'));

	// если есть признак выводить модалку от PHP:
	if (($("#span-flag-showMessage").length > 0) && ($("#span-flag-showMessage").text() !== '')) {
		$('#registration-error').modal('show');
	}

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

	// если есть кнопка подтверждения почтового ящика:
	if ($("#confirmEmail").length > 0) {
		$("#confirmEmail").on('click', function (event) {
			if (event.preventDefault()) {
				event.preventDefault();
			} else {
				event.returnValue = false;
			}
			sendConfirmEmail(); // даем команду тклендеру послать письмо подтверждения почтового ящика 
		});
	}

	// если есть кнопка/ссылка просмотра доп.соглашения:
	if ($(".js-btn-dopdogovor").length > 0) {
		$(".js-btn-dopdogovor").on('click', function (event) {

			$(this).closest('form')[0].submit();
		});
	}
	;

	// если есть секции нотификации с кнопками:
	if ($(".js_div_notify").length > 0) {

		$(".js_btn_notify_close, .js_btn_notify_ok, .js_btn_notify_recall").on('click', function (event) {
			notifyOnClick(event); // обрабатывает кнопки нотифмкаций 
		});
	}

	// если есть секции нотификации с popup:
	if ($(".js_div_notify_popup").length > 0) {

		// добавляем класс для показа popup через время:
		setTimeout(function () {
			$('.js_div_notify_popup').addClass('active');
		}, 3000);

		$(".js_btn_notify_popup").on('click', function (event) {

			// обрабатываем кнопку "закрыть" (крестик) 
			var button = event.target;
			// console.log(button);
			$(button).closest('.js_div_notify_popup').removeClass('active');

			var data = {
				typeData: "notifyClosed",
				notifyId: $(button).closest('.js_div_notify_popup').children(".js_notifyId").first().text()
			};

			// отправить массив на сервер
			// console.log("Передаем запрос ajax 'notifyClosed'");
			// console.log(data);
			sendAjax(data);
		});
	}

	// если есть секции нотификации с Modal:
	if ($(".js_div_notify_modal").length > 0) {

		var notifyModal = $(".js_div_notify_modal").first(); // модалка

		setTimeout(function () {
			$(notifyModal).modal('show');
		}, 5000);

		$(".js_btn_notify_modal").on('click', function (event) {

			// обрабатываем кнопку "закрыть" (крестик) 
			// var button = event.target;
			// console.log(button);
			$(notifyModal).modal('hide');

			var data = {
				typeData: "notifyClick",
				btnType: "OK",
				notifyId: $(notifyModal).find(".js_notifyId").first().text()
			};

			// отправить массив на сервер
			// console.log("Передаем запрос ajax 'notifyClick'");
			// console.log(data);
			sendAjax(data);
		});
	}

	// Показываем модалку через 60 сек.
	if ($('.js-modal-show-60').length > 0) {
		setTimeout(function () {
			$('.js-modal-show-60').modal('show');
        }, 5000);
    }

	// если есть секции с кнопками сохранения введенного телефона:
	if ($("input").is("#phoneFromForm")) {

		$('#btn-phoneFromForm').attr('disabled', true);

		$("#btn-phoneFromForm").on('click', function (event) {

			var phoneId = "phoneFromForm";
			var filePrefix = 'phoneFromForm';
			var phoneFrom = 'Осенний розыгрыш';
			var partner = $("#utm_source").val();

			recordPhone(phoneId, filePrefix, phoneFrom, partner);
		});
		
		$("#phoneFromForm").on('keyup', function (event) {
			
			// проверяем правильность ввода телефона:
			if((onKeyUpPhone("phoneFromForm", true)) && ($("#phoneFromForm").val().replace(/\D/g, "").length == 12)) {
				$("#btn-phoneFromForm").removeAttr("disabled");
			} else {
				$('#btn-phoneFromForm').attr('disabled', true);
			}
		});
	}
	
	// Обработка формы подписки
	$('.js-email-subscribe').on('submit', function(e) {
		e.preventDefault();
		
		var form = this,
			data = {
				typeData: 'addEsputnikSubscriber',
				name: $(form).find('[name=name]').val(),
				email: $(form).find('[name=email]').val()
			};

		$('.personalEmail__modal-form').modal('hide');
		$('#wait-modal-common').modal('show');

		sendAjax(data, function(response) {
			$('#wait-modal-common').modal('hide');
			if (response.message == 'OK') {
				var $thanksForSubscribePopup = $('#thanks-for-subscribe-popup');

				$thanksForSubscribePopup.modal('show');
				
				if (form.id == 'esputnik-subscribe') {
					$thanksForSubscribePopup.find('.modal-dialog__personalEmail').addClass('modal-dialog__personalEmail--white');
				}
			} else {
				alert('Ошибка на сервере! Попробуйте еще раз.'); //TODO
			}
		});
	});
	
	/* Обрабатывает форму обратной связи.
	 * замыкание в значительной степени повторяет onClickSendEmail(),
	 * но несколько универсальнее.
	 * onClickSendEmail() оставляем пока на всякий случай.
	 */
	$('.js-send-feedback').on('click', function () {
		var $form = $($(this).parent('form'));

		if (!validate($form)) {
			return false;
		}

		var url = "/ru/?ajax",
				$messageTextarea = $form.find('textarea[name="sendMail[message]"]');

		var data = {
			typeData: 'sendEmailtoSupport',
			fromName: $form.find('input[name="sendMail[sername]"]').val(),
			fromEmail: $form.find('input[name="sendMail[email]"]').val(),
			message: $messageTextarea.val()
		};

		// отправить массив на сервер
		console.log("Передаем запрос ajax " + url);

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
						$("#div_SendEmail").addClass("bottom-call-hidden");
						$("#div_resultEmail").removeClass("bottom-call-hidden");
						$("#thanks").modal("show");
						$messageTextarea.val("");
						$("#button_sendMe").removeAttr("disabled");
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
		
	});
	
	// Сохраняет все отзывы на странице
	$('.js-save-all-reviews').on('click', function() {
		var data = {
			'typeData': 'reviewsAll',
			'data': []
		};
		
		$('.form-review').each(function() {
			var formData = {
				id: this.elements['reviews[id]']['value'],
				name: this.elements['reviews[name]']['value'],
				date_updated: this.elements['reviews[date_updated]']['value'],
				status: this.elements['reviews[status]'].checked ? this.elements['reviews[status]']['value'] : '',
				review: this.elements['reviews[review]']['value'],
				moderator: this.elements['reviews[moderator]']['value'],
				answer: this.elements['reviews[answer]']['value'],
			};
			
			data.data.push(formData);
		});
		
		sendAjax(data, function(response) {
			if (response.message == 'OK') {
				location = location.href;
			}
		});
	});
	
	// Показывает историю платежей
	$('.js-payments-toggle').on('click', function(e) {
		e.preventDefault();
		
		$payments = $('#payments-wrapper-' + $(this).data('target-id'));
		
		if (!$payments.length) {
			return;
		}
		
		$('#credits-history-wrapper').toggle();
		$payments.toggle();
		document.body.scrollTop = 0; // For Safari
		document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
	});

	// если есть кнопки пролонгации
	$('.js-btn-prolongate').on('click', function() {
	
		onClickSubmitForm('form_continueLoan', '');
	});

	// если есть секции с фото:
	if ($("input").is("#myFotoLK")) {
	
		// функция загрузки фото
		$('#myFotoLK').change(function(event) {
		    
			event.stopPropagation(); // остановка всех текущих JS событий
			event.preventDefault();  // остановка дефолтного события для текущего элемента

			var maxFileSize = 5242880;    // 5*1024*1024;
			var ext = this.files[0].name.split('.').pop().toLowerCase();	// расширение файла
			
			if($.inArray(ext, ['gif','png','jpg','jpeg', 'bmp']) == -1) {
				var invalidExt = true; 
			} else {
				var invalidExt = false; 
			}
			
			if ((this.files[0].size < maxFileSize) && (!invalidExt)) {
				uploadFile('myFotoLK', 'photoLK', 'update');	// photoLK - тип посылки, 'update' - признак добавления файлов
			} else {
				if (invalidExt) {
					$('#span_error').text($('#span_error_fileExt').text() + ': gif, png, jpg, jpeg, bmp'); // Ошибка через modal 'Превышен максимальный размер файла'
				} else {
					var maxFileSizeMb = (maxFileSize / 1024 / 1024).toFixed(1);	// максимальный размер в мегобайтах с кол.цифр после запятой 
					$('#span_error').text($('#span_error_fileSize').text() + ' ' + maxFileSizeMb + ' Mb'); // Ошибка через modal 'Превышен максимальный размер файла'
				}
				$('#data-error').modal('show'); // Ошибка через modal
			}
	    });

		// функция удаления фото
		$('#btn-delete').on('click', function(event) {
		    
			var photoId = $("#myFotoLK_id").val();
			
			deleteUploadedFile(photoId, 'photoLK');
	    });
	}
	
	// Обработка селекта с показом соответствующего выбору текстового поля
	// и включение для этого поля обязательности
	$('select.js-select-has-depends').on('change', function() {
		var select = this,
			selectedValue = $(this).find('option:selected').val();
		
		$('.' + select.id + '-depend').each(function() {
			if (this.id == select.id + '-depend-' + selectedValue) {
				$(this).removeClass('hidden');
				
				if ($(this).data('required')) {
					$(this).prop('required', true);
				}
			} else {
				$(this).addClass('hidden');
				$(this).prop('required', false);
				$(this).next('.error_text').hide();
				$(this).parents('.has-error').removeClass('has-error');
			}
		});
	});

	// если есть секции с партнерами:
	if ($(".js-partners-item").length > 0) {

		$(".js-partners-item").on('click', function (event) {

			// обрабатываем клик по партнеру (заполняем форму): 
			$('#partner_id').val($(this).data("id"));
			$('#partner_name').val($(this).data("name"));
			$('#partner_company').val($(this).data("company"));
			$('#partner_email').val($(this).data("email"));
			$('#partner_info').val($(this).data("info"));
			$('#partner_accesses').val($(this).data("accesses"));
			$('#partner_status').val($(this).data("status"));
		});

	}


	//

	//================================================================================================================    
	/*   
	 function Unloader(){
	 
	 var o = this;
	 
	 this.unload = function(evt)
	 {
	 var message = "Вы уверены, что хотите покинуть страницу оформления заказа?";
	 if (typeof evt == "undefined") {
	 evt = window.event;
	 }
	 if (evt) {
	 evt.returnValue = message;
	 }
	 return message;
	 }
	 
	 this.resetUnload = function()
	 {
	 $(window).off('beforeunload', o.unload);
	 
	 setTimeout(function(){
	 $(window).on('beforeunload', o.unload);
	 }, 2000);
	 }
	 
	 this.init = function()
	 {
	 
	 $(window).on('beforeunload', o.unload);
	 
	 $('a').on('click', function(){o.resetUnload});
	 $(document).on('submit', 'form', function(){o.resetUnload});
	 $(document).on('keydown', function(event){
	 if((event.ctrlKey && event.keyCode == 116) || event.keyCode == 116){
	 o.resetUnload;
	 }
	 });
	 }
	 this.init();
	 }
	 
	 $(function(){
	 if(typeof window.obUnloader != 'object')
	 {
	 window.obUnloader = new Unloader();
	 }
	 })
	 */
	//================================================================================================================    


});

// Событие onClick на GoogleStars:
//$(".vote-section .star").on("click", function() {
//	var _mark = parseInt($(this).index()) + 1;
//	onClickGoogleStars(_mark);
//})
