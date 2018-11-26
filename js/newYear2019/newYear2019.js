
/**
 * передает данные на сервер, получает реальный уровень, и пр.
 * @param phone
 * @param name
 * @param email
 * @param level
 */
function sendInfo(phone, name, email, level) {

    var url = "/ru/?ajax";

    var data = {
        typeData: 'actions',
        actionType: 'newyear2019',
        phone: phone,
        name: name,
        email: email,
        level: level
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

                console.log(js);
            };
        },

        error: function (jqXHR, textStatus, errorThrown) {
            // console.log(jqXHR); // вывод JSON в консоль
            console.log('Сообщение об ошибке от сервера recordEmailSiteOnline: ' + textStatus); // вывод JSON в консоль
            // console.log(errorThrown); // вывод JSON в консоль
        }
    });
}
