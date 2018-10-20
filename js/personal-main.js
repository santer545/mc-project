$(function() {
    domRangeCreate();
    calculatorMain();
    panelClose();
    customSelect();
    showChangePAsswordBox();
    bonusCarousel();
})

function domRangeCreate() {
    $('.js-promocode').on('click', function() {
        var target = $(this).closest('.js-promocode-area').find('.js-promocode-text').get(0);
        var rng, sel;
        if (document.createRange) {
            rng = document.createRange();
            rng.selectNode(target)
            sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(rng);
            document.execCommand("copy");
        } else {
            var rng = document.body.createTextRange();
            rng.moveToElementText(target);
            rng.select();
        }
    })
}

function panelClose() {
    $('.js-panel-close').on('click', function() {
        $(this).closest('.js-panel').addClass('hidden');
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

function customSelect() {
    $('.selectpicker').selectric();
}

function showChangePAsswordBox() {
    $('.js-change-password').on('click', function() {
        $('.js-password-box').toggleClass('active');
    })
}

function bonusCarousel() {
    $('.js-bonus-carousel').slick({
        slidesToShow: 4,
        nextArrow: '<span class="icon-programm-arrow-right"><span class="path1"></span><span class="path2"></span></span>',
        prevArrow: '<span class="icon-programm-arrow-left"><span class="path1"></span><span class="path2"></span></span>',
        responsive: [{
                breakpoint: 1025,
                settings: {
                    slidesToShow: 2,
                    arrows: false,
                    dots: true
                },
            },
            {
                breakpoint: 767,
                settings: {
                    arrows: false,
                    slidesToShow: 1,
                    dots: true
                }
            }
        ]
    });
}

SVG.on(document, 'DOMContentLoaded', function() {
    // Franky

    var frank_hand = SVG.select('.franky-hand');
    var brain_top = SVG.select('.brain-top');
    var brain_middle = SVG.select('.brain-middle');

    frank_hand.delay(4000).animate(2000).rotate(35).reverse(true).loop();

    brain_middle.delay(2000).animate().style('opacity', 1);
    brain_top.delay(2500).animate().style('opacity', 1);

});