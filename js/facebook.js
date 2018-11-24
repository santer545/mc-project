/**
 * Файл с набором функций на JavaScript для работы с Facebook,
 * используется для взаимодействия фронтенда с API CRM MyCredit
 * 
 * @author Игорь Стебаев <Stebaevin@gmail.com>
 * @copyright Copyright (c) 2018 - MyCredit Company
 * @version 2.0
 * @package DesignAPI
 * @link https://mycredit.ua
 */


// Only works after `FB.init` is called
function myFacebookLogin() {

	FB_api = function () {

		FB.api(
				// response.authResponse.userID + '/email',
				'/me?', {
					fields: 'id,significant_other,domains,about,email,name,age_range,birthday,context,devices,education,first_name,gender,' +
							'languages,last_name,link,locale,location,relationship_status,religion,timezone,updated_time,work,' +
							'verified,' +
							'accounts,friendlists,friends,family,groups'
				},
				function (response) {
					if (response && !response.error) {
						//console.log('api_true');
						// console.log(response);
						console.log(response.link);
						$("#facebook").val(response.link);
						/* handle the result */
					} else {
						console.log('api_error');
						console.log(response);
					}
				}
		)
	};

	FB.getLoginStatus(function (response) {
		if (response.status === 'connected') {
			console.log('Logged in.');
			// console.log(response);

			$("#facebook_userID").val(response.authResponse.userID);
			$("#facebook_accessToken").val(response.authResponse.accessToken);

			FB_api();

		} else {
			console.log('for login');
			// FB.login();

			FB.login(function (response) {
				if (response.authResponse) {
					// var access_token = FB.getAuthResponse()['accessToken'];
					//console.log('Access Token = '+ access_token);
					// console.log('response = ');
					// console.log(response);

					$("#facebook_userID").val(response.authResponse.userID);
					$("#facebook_accessToken").val(response.authResponse.accessToken);

					FB_api();

				} else {
					console.log('User cancelled login or did not fully authorize.');
				}
			}, {scope: ''});
		}
	});

	/*
	 FB.login(function(){
	 console.log('FB.login');
	 
	 
	 }, {scope: 'publish_actions'});
	 */

}