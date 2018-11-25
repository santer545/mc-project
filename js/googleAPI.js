/**
 * Файл с набором функций на JavaScript для работы с Google,
 * используется для взаимодействия фронтенда с API CRM 
 * 
 * @author Игорь Стебаев <Stebaev@mail.ru>
 * @copyright Copyright (c) 2017 Artjoker Company
 * @version 1.0
 * @package DesignAPI
 * @link http://www
 */

  // This example displays an address form, using the autocomplete feature
  // of the Google Places API to help users fill in the information.

  // This example requires the Places library. Include the libraries=places
  // parameter when you first load the API. For example:
  // <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

var placeSearch, autocomplete, autocomplete_fact, geolocation;
/*
var componentForm111111 = {
		street_number: 'short_name',
		route: 'long_name',
		locality: 'long_name',
		administrative_area_level_1: 'short_name',
		country: 'long_name',
		postal_code: 'short_name'
	};
*/

var componentForm = {
		street_number: {name: 'short_name', id:'House'},
		route: {name: 'long_name', id:'adress'},
		locality: {name: 'long_name', id:'obl_city'},
		administrative_area_level_2: {name: 'short_name', id:'obl'},	// Для Киева
		administrative_area_level_1: {name: 'short_name', id:'obl'},
		//country: {name: 'long_name', id:''},
		//postal_code: {name: 'short_name', id:''}
	};

function initAutocomplete() {
   
	// Create the autocomplete object, restricting the search to geographical
	// location types.
	if (document.getElementById('autocomplete')) {
		autocomplete = new google.maps.places.Autocomplete(
				(document.getElementById('autocomplete')),	// @type {!HTMLInputElement}
				{
					// types: ['geocode'],
					types: ['address'],
					componentRestrictions: {country: 'ua'}
				}
		);
		// When the user selects an address from the dropdown, populate the address
		// fields in the form.
		autocomplete.addListener('place_changed', fillInAddress);
	}

	if (document.getElementById('fact_autocomplete')) {
		autocomplete_fact = new google.maps.places.Autocomplete(
				(document.getElementById('fact_autocomplete')),	// @type {!HTMLInputElement}
				{
					// types: ['geocode'],
					types: ['address'],
					componentRestrictions: {country: 'ua'}
	            }
		);
		// When the user selects an address from the dropdown, populate the address
		// fields in the form.
		autocomplete_fact.addListener('place_changed', fillInAddress_fact);
	}
	
	geolocate();
	//getSessionData();

}

function fillInAddress() {
	fillInAddressDetail('');
}

function fillInAddress_fact() {
	fillInAddressDetail('fact');
}

function fillInAddressDetail(prefix) {
	
	if (prefix !== '') {
		prefix = prefix + '_';

		// Get the place details from the autocomplete object.
		var place = autocomplete_fact.getPlace();

	} else {
		
		// Get the place details from the autocomplete object.
		var place = autocomplete.getPlace();
	}

	for (var component in componentForm) {
		document.getElementById(prefix + componentForm[component].id).value = '';
		document.getElementById(prefix + componentForm[component].id).disabled = false;
	}

	// console.log(place.address_components);
    
	// Get each component of the address from the place details
	// and fill the corresponding field on the form.
	for (var i = 0; i < place.address_components.length; i++) {
		var addressType = place.address_components[i].types[0];
		if (componentForm[addressType]) {
			var val = place.address_components[i][componentForm[addressType]['name']];
			document.getElementById(prefix + componentForm[addressType]['id']).value = val;
		}
	}
}

// Bias the autocomplete object to the user's geographical location,
// as supplied by the browser's 'navigator.geolocation' object.
function geolocate() {
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {

			geolocation = {
					lat: position.coords.latitude,
					lng: position.coords.longitude
			};
			var circle = new google.maps.Circle({
				center: geolocation,
				radius: position.coords.accuracy
			});
			if (document.getElementById('fact_autocomplete'))
				autocomplete.setBounds(circle.getBounds());
			if (document.getElementById('fact_autocomplete'))
				autocomplete_fact.setBounds(circle.getBounds());
			
			getSessionData();
			
			// getUserLocation();
		}, getSessionData);
	}
}

/**
 * Получает данные геолокации пользователя, и передает на сервер
 */
/* function getUserLocation() {

	// return;	// временно отключаем 
	
	// console.log(position);
	var geocoder = new google.maps.Geocoder,
		sessionData = {
			user_country: '#404',
			user_area: '#404',
			user_city: '#404',
			user_formatted_address: '#404',
			user_geometry: '#404'
		};
	
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
	});
	
	return sessionData;
}*/


