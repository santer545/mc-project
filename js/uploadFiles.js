/**
 * Файл с набором функций на JavaScript для передачи файлов на сервер,
 * используется для взаимодействия фронтенда с API CRM MyCredit
 * 
 * @author Игорь Стебаев <Stebaevin@gmail.com>
 * @copyright Copyright (c) 2018- Mycredit Company
 * @version 1.0
 * @package DesignAPI
 * @link https://mycredit.ua
 */
	

/**
 * производит действия после успешного удаления файла
 * @param inputId
 * @param response
 * @returns
 */
function callbackDeleteUpload(uploadGoal, response) {

	switch (uploadGoal) {

	case "photoLK":

		$("#addFoto_data").removeClass("restart");
		$("#span_label").addClass("hidden");
		$("#btn-delete").addClass("hidden");
		$("#span-no-photo").removeClass("hidden");
		$("#span-photo-exist").addClass("hidden");
		$("#span-photoName").text();
		$('#myFotoLK_img').addClass("hidden");
		$('#myFotoLK_img').attr('src', '');
		
		break;

	case "ua":
		break;

	}
	
	// console.log("callbackDeleteUpload " + uploadGoal);
	// console.log("response:");
	// console.log(response);
}

/**
 * производит действия при после успешной отправки файлов
 * @param uploadGoal
 * @param response
 * @returns
 */
function callbackUpload(uploadGoal, response) {

	switch (uploadGoal) {

	case "photoLK":

		$("#addFoto_data").addClass("restart");
		$("#span_label").removeClass("hidden");
		$("#btn-delete").removeClass("hidden");
		$("#span-no-photo").addClass("hidden");
		$("#span-photo-exist").removeClass("hidden");
		$("#span-photoName").text(response.result[0].DefaultImageName);
		$("#myFotoLK_id").val(response.result[0].Id);
		$('#myFotoLK_img').removeClass("hidden");
		$('#myFotoLK_img').attr('src', response.result[0].Url);
		
		break;

	case "ua":
		break;

	}
	
	// console.log("callbackUpload "+uploadGoal);
	// console.log("response:");
	// console.log(response.result[0].DefaultImageName);
}

/**
 * подает команду на удаление загруженного файла
 * @param fileId
 * @param uploadGoal
 * @returns
 */
function deleteUploadedFile(fileId, uploadGoal) {

	$('#wait-modal-common').modal('show'); // Ожидание через modal
	
	// console.log('deleteUploadedFile');
	
	var url = "/ru/?ajax";

	var data = {
		typeData: 'UploadedFileDelete',
		fileId: fileId,
		uploadGoal: uploadGoal
	};

	// отправить массив на сервер
	// console.log("Передаем запрос ajax " + url);

	$.ajax({

		url: url,
		type: 'POST',
        data: data,
		dataType: 'json',
		success: function (json) {
			if (json) {
				var js = json;
				// console.log(js);
				if (js.message == 'OK') {
					callbackDeleteUpload(uploadGoal, js);	
				}
			}
			$('#wait-modal-common').modal('hide'); // Ожидание через modal
		},
		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль
			$('#wait-modal-common').modal('hide'); // Ожидание через modal
		}
	});
}

/**
 * пересылает файлы на сервер
 * @param inputId
 * @param uploadGoal - цель передачи файлов
 * @parem flagAdd - если равен 'add', то добавлять к существующим
 * @returns
 */
function uploadFile(inputId, uploadGoal, flagAdd){
	
	$('#wait-modal-common').modal('show'); // Ожидание через modal
	
	var myfiles = $('#'+inputId).prop('files');
    // console.log(inputId+':');
    // console.log(myfiles);

	// создадим объект данных формы
	var data = new FormData();

	// заполняем объект данных файлами в подходящем для отправки формате
	$.each( myfiles, function( key, value ){
		data.append( key, value );
	});

	// добавим переменную для идентификации запроса
	data.append( 'typeData', 'uploadFile');
	data.append( 'uploadGoal', uploadGoal );
	data.append( 'flagAdd', flagAdd );
		    
//			var input = document.querySelector("#myFotoLK"); // выбираем поле с id=myFotoLK
//			// перебираем все введенные файлы :
//			Array.prototype.forEach.call(input.files, function(file) {
//				var reader = new FileReader();
//				// событие закачки файла:
//				reader.addEventListener("load", function() {
//					fileContent = reader.result;          // присваиваем значение бинарного файла
//					fileContent = new Uint8Array(fileContent);  // переводим из буфера в массив
//					console.log("Читаем файл", file.name, "размером", fileContent.length);
//					// передача после считывания файла:
//					//toSend('SendFilesFNS', sessionKey, recipientCode, filename, fileContent, flagLetter, formName, varErrorMessage);
//				});
//				//reader.readAsBinaryString(file);
//				reader.readAsArrayBuffer(file); // чтение из файла в буфер
//			});
		    
	var url = "/ru/?ajax";

	// отправить массив на сервер
	// console.log("Передаем запрос ajax " + url);

	$.ajax({

		cache: false,
        contentType: false,
        processData: false,

		url: url,
		type: 'POST',
        data: data,
		// data: {data: data},
		dataType: 'json',
		success: function (json) {
			if (json) {
				var js = json;
				// console.log(js);
				if (js.message == 'OK') {
					callbackUpload(uploadGoal, js);	
				} else {
					console.log(js.message_details);
				}
			}
			$('#wait-modal-common').modal('hide'); // Ожидание через modal
		},

		error: function (jqXHR, textStatus, errorThrown) {
			// console.log(jqXHR); // вывод JSON в консоль
			console.log('Сообщение об ошибке от сервера: ' + textStatus); // вывод JSON в консоль
			// console.log(errorThrown); // вывод JSON в консоль
			$('#wait-modal-common').modal('hide'); // Ожидание через modal
		}
	});
}

