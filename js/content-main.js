$(function() {
    calculatorMain();
    customSelect();
    contentDocumentsCarousel();
    accordion();
    showCallback();
    lazyLoadImages();
    sharePopupShow();
    menuShowHide();
    sharePopupClose();
})

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

function showCallback() {
    $('.js-callback-close').on('click', function() {
        $('.js-callback').removeClass('open');
    })

    $('.js-callback-show').on('click', function() {
        $('.js-callback').addClass('open');
    })
}

function sharePopupShow() {
    setTimeout(function() {
        $(".js-share-popup").addClass("active")
    }, 8000)
}
function sharePopupClose() {
    $(".js-share-close").click(function() {
        $(this).closest(".share-popup").removeClass("active")
    })
}

function menuShowHide() {
    $('.js-gamburger').click(function() {
        $('.js-menu').addClass('open');
    });

    $('.js-close-nav').click(function() {
        $('.js-menu').removeClass('open');
    })
}

function contentDocumentsCarousel() {
    $('.js-slider-for').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: true,
        fade: true,
        asNavFor: '.js-slider-nav'
    });
    $('.js-slider-nav').slick({
        slidesToShow: 10,
        slidesToScroll: 1,
        asNavFor: '.js-slider-for',
        arrows: true,
        focusOnSelect: true
    });
}

function customSelect() {
    $('select').selectric();
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

function accordion() {
    var accordion = $('.js-accordion');
    var allChildren = $('.js-accordion ul');
    allChildren.css('display', 'none');

    accordion.click(function(e) {
        var button = e.target;
        var subItem = $(button).next();
        var depth = $(subItem).parents().length;
        $(button).toggleClass('active');


        var allAtDepth = $(allChildren).filter(function() {
            if ($(this).parents().length >= depth && this !== subItem.get(0)) {
                return true;
            }
        });

        if ($(e.target).is('.accordion-button')) {
            $(allAtDepth).slideUp("fast");

            //slideToggle для скрыть/показать текущий контент
            subItem.slideToggle("fast");
        }
    });
}