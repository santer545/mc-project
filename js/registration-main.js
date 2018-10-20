$(function() {
    playRegistrationVideo();
    customSelect();
    calculatorMain();
    goToAnchor();
    dropdown();
    lazyLoadImages();
    showCallback();
    maskes();
    paswwordChecker();
})

function playRegistrationVideo() {
    var video = document.getElementById('js-registration-video');
    $('.js-registration-video-holder').click(function() {
    	$('.registration-video').addClass('active');
        video.play();
    })
}

function showCallback() {
    $('.js-callback-close').on('click', function() {
        $('.js-callback').removeClass('open');
    })

    $('.js-callback-show').on('click', function() {
        $('.js-callback').addClass('open');
    })
}

function customSelect() {
	$('.selectpicker, select').selectpicker({
        actionsBox: false,
        dropupAuto: false
    });
}

function lazyLoadImages() {
    $('.js-lazy').Lazy({
        scrollDirection: 'vertical',
        effect: 'fadeIn',
        chainable: true,
        visibleOnly: true,
        onError: function(element) {
            console.log('error loading ' + element.data('src'));
        }
    });
}

// выпадающее меню
function dropdown() {
    $('.js-parent').on('click', function(e) {
        $(this).toggleClass('active');
        $(this).find('.triangle').toggleClass('active');
        $(this).find('.js-dropdown').toggleClass('active');
    })
}


// визуализация калькулятора
function calculatorMain() {
    $("[class^=nameSlider]").each(function(i, elem) {

        var prefixSl = $(elem).text();

        var sl = $("#js-money_" + prefixSl).slider();

        $("#money-value_" + prefixSl).change(function() {
            var value = $(this).val();
            sl.slider('setValue', parseInt(value));
        });
        $("#js-money_" + prefixSl).on("slide", function(slideEvt) {
            $("#money-value_" + prefixSl).val(slideEvt.value);
            // onClickFormSlider(prefixSl);
        });
        $("#js-money_" + prefixSl).on("change", function(slideEvt) {
            $("#money-value_" + prefixSl).val(slideEvt.value.newValue);
        });
        $("#js-money_" + prefixSl).on("slideStop", function(slideEvt) {
            // анализ переключений калькулятора:
            analysisSlider(globalMoney, globalDay, prefixSl, 'money');
        });

        var sl1 = $("#js-days_" + prefixSl).slider();
        $("#day-value_" + prefixSl).change(function() {
            var defaultDay = 14;
            var value = $(this).val();
            var maxDay = $('#maxDay_' + prefixSl).val();
            var minDay = $('#minDay_' + prefixSl).val();
            maxDay = parseInt(maxDay);
            minDay = parseInt(minDay);
            var inp = sl1.slider('setValue', parseInt(value));
            if (value > maxDay || value < minDay) {
                $(this).val(defaultDay);
                sl1.slider('setValue', defaultDay);
            }
        });
        $("#js-days_" + prefixSl).on("slide", function(slideEvt) {
            $("#day-value_" + prefixSl).val(slideEvt.value);
            // onClickFormSlider(prefixSl);
        });
        $("#js-days_" + prefixSl).on("change", function(slideEvt) {
            $("#day-value_" + prefixSl).val(slideEvt.value.newValue);
        });
        $("#js-days_" + prefixSl).on("slideStop", function(slideEvt) {
            // анализ переключений калькулятора:
            analysisSlider(globalMoney, globalDay, prefixSl, 'day');
        });

    });

}

function goToAnchor() {
    $('.js-anchor')
        .not('[href="#"]')
        .not('[href="#0"]')
        .not('[href^="#tab"]')
        .click(function(event) {
            event.preventDefault();
            // On-page links
            if (
                location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') &&
                location.hostname == this.hostname
            ) {
                // Figure out element to scroll to
                var target = $(this.hash);
                target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                // Does a scroll target exist?
                if (target.length) {
                    // Only prevent default if animation is actually gonna happen
                    event.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 90
                    }, 1000, function() {
                        // Callback after animation
                        // Must change focus!
                        var $target = $(target);
                        // $target.focus();
                        if ($target.is(":focus")) { // Checking if the target was focused
                            return false;
                        } else {
                            $target.attr('tabindex', '-1'); // Adding tabindex for elements not focusable
                            // $target.focus(); // Set focus again
                        };
                    });
                }
            }
        });
}

function maskes() {
    $('.js-code').mask("999999", {
        autoclear: false
    });

    $(".js-phone").mask("+38099 999 9999", {
        autoclear: false
    });
}

function paswwordChecker() {
    var element = document.getElementById('password');
    var expBigLetter = /[А-ЯЁA-Z]+/,
        expSmallLetter = /[а-яёa-z]+/,
        expNumber = /\d+/;
    if (element) {
        element.oninput = function() {
            var val = document.getElementById('password').value;

            var count = val.length;

            var big_letter = document.getElementById('big_letter');
            var small_letter = document.getElementById('small_letter');
            var number_symbol = document.getElementById('number_symbol');
            var count_letter = document.getElementById('count_letter');


            if (count >= 6) {
                count_letter.setAttribute('class', 'active');
            } else {
                count_letter.classList.remove('active');
            }


            if (expSmallLetter.test(val)) {
                small_letter.setAttribute('class', 'active');
            } else {
                small_letter.classList.remove('active');
            }
            if (expBigLetter.test(val)) {
                big_letter.setAttribute('class', 'active');
            } else {
                big_letter.classList.remove('active');
            }
            if (expNumber.test(val)) {
                number_symbol.setAttribute('class', 'active');
            } else {
                number_symbol.classList.remove('active');
            }
        }
    }

}


SVG.on(document, 'DOMContentLoaded', function() {
    // Franky

    var ghost = SVG.select('#ghost');
    ghost.delay(4000).animate(2000).translate(140,40).translate(120,40).translate(110,40).translate(100,20).translate(0,10).translate(0,0).translate(140,-40).translate(0,-40).loop(10);

    var tongue = SVG.select('.tongue');
    var tongue_shadow = SVG.select('.tongue-shadow');
    tongue.delay(22000).animate().style('opacity', 1);
    tongue_shadow.delay(22000).animate().style('opacity', 1);
    // brain_middle.delay(2000).animate().style('opacity', 1);
    // brain_top.delay(2500).animate().style('opacity', 1);

});