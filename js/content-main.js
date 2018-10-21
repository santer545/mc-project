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
    playVideoAbout();
    stickySidebar();
    dropdown();
    bannerCarousel();
    stickyCalc();
    stickyCalcHeight();
    playVideo();
})


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

// карусель для банера
function bannerCarousel() {
    $('.js-banner').slick({
        arrows: false,
        nextArrow: '<span class="icon-programm-arrow-right"><span class="path1"></span><span class="path2"></span></span>',
        prevArrow: '<span class="icon-programm-arrow-left"><span class="path1"></span><span class="path2"></span></span>',
        lazyLoad: 'progressive',
        responsive: [{
            breakpoint: 767,
            settings: {
                arrows: false,
                slidesToShow: 1,
                dots: true
            }
        }]
    });
}


// отображение колбека
function showCallback() {
    $('.js-callback-close').on('click', function() {
        $('.js-callback').removeClass('open');
    })

    $('.js-callback-show').on('click', function() {
        $('.js-callback').addClass('open');
    })
}

// отображение попапа для акций
function sharePopupShow() {
    setTimeout(function() {
        $(".js-share-popup").addClass("active")
    }, 8000)
}

// закрыть попап
function sharePopupClose() {
    $(".js-share-close").click(function() {
        $(this).closest(".share-popup").removeClass("active")
    })
}

// отображение меню на мобайл
function menuShowHide() {
    $('.js-gamburger').click(function() {
        $('.js-menu').addClass('open');
    });

    $('.js-close-nav').click(function() {
        $('.js-menu').removeClass('open');
    })
}

// карусели для документов
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


// кастомный селект
function customSelect() {
    $('select').selectric();
}

// lazy load картинок
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

// аккордион
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

// запуск видео на контентных страницах
function playVideo() {
    
    $('.js-frame').click(function() {
        var video = $(this).find('.js-video')[0];
        $(this).addClass('play');
        $('.numbers-play').addClass('hidden');
        $('.numbers-gif').addClass('hidden');
        video.play();
    });
    
}

// запуск и остановка видео на странице о нас
function playVideoAbout() {
    var video = document.getElementById('video-about');
    if ($('#aboutVideo').length) {
        $('#aboutVideo').on('hidden.bs.modal', function() {
            video.pause();
        });
    }
}

// липкий сайдбар на контентных страницах
function stickySidebar() {
    var sticky = $('#sidebar').stickySidebar({
        topSpacing: 100,
        bottomSpacing: 60
    });
}

// отображение липкого калькулятора сверху в контенте
function stickyCalc() {
    $('.js-open-calc').on('click', function() {
        $(this).attr('disabled', 'disabled');
        $('.js-sticky').addClass('active');
        var stickyHeight = stickyCalcHeight() + $('.js-header').height() + 'px';

        $('.js-header').animate({top: stickyHeight}, 300);
    });

    $('.js-sticky-close').on('click', function() {
        $('.js-open-calc').removeAttr('disabled');
        $('.js-header').animate({top: '0px'}, 300);
        $('.js-sticky').removeClass('active');
    });
}

// высота липкого калькулятора
function stickyCalcHeight() {
    return $('.js-sticky').height();
}

SVG.on(document, 'DOMContentLoaded', function() {

    // witch

    var w_hand = SVG.select('.witch-hand');
    w_hand.delay(8000).animate(400).rotate(25).rotate(0);

    var witch = SVG.select('#witch');
    witch.delay(10000).animate(400).translate(-200, -400);


    // monstr

    var monstr_r_leg = SVG.select('.m_r_leg');
    monstr_r_leg.animate().rotate(-75).reverse().loop();

    var monstr_l_leg = SVG.select('.m_l_leg');
    monstr_l_leg.delay(300).animate().rotate(55).reverse().loop();

    var monstr = SVG.select('#monster');
    monstr.animate(14000).translate(-500,0).animate().rotate(-80).delay(200).animate().opacity(0);


    // pumpkin

    var pump = SVG.select('.pumpkin-pers');
    pump.delay(5000).animate(2000).rotate(10).after(function(finish) {
        this.animate(2000).rotate(-10).reverse().loop(3).delay(500).translate(0, 400).delay(200).translate(400, 400).delay(200).translate(400, 0).delay(1700).animate().opacity(0);
    });


    // zombie

    var zombie_hand = SVG.select('.zombie-hand');
    zombie_hand.delay(3000).animate().rotate(100);

    var zombie_r_leg = SVG.select('.zombie-r-leg');
    zombie_r_leg.delay(4000).animate(2000).rotate(-100).after(function(finish) {
        this.animate(2000).rotate(-80).loop(6);
    });

    var zombie_l_leg = SVG.select('.zombie-l-leg');
    zombie_l_leg.delay(5000).animate(2000).rotate(-12).after(function(finish) {
        this.animate(2000).rotate(-18).loop(6);
    });

    var zombie = SVG.select('#zombie');
    zombie.delay(6000).animate(10000).translate(-100, 0).delay(14000).animate().opacity(0);

    var tooth_1 = SVG.select('.tooth-1');
    tooth_1.delay(16000).animate().style('fill:#000;');

    var tooth_2 = SVG.select('.tooth-2');
    tooth_2.delay(17000).animate().style('fill:#000;').animate().rotate(4).after(function(finish) {
        this.animate().rotate(-4).delay(500).animate().opacity(0);
    })


    // death

    var death_hand = SVG.select('.death-right-hand');
    death_hand.delay(2000).animate().rotate(20).loop(7);

    var death = SVG.select('#death');
    death.delay(10000).animate().opacity(0).delay(500).style('display','none');


    // vampire

    var bat_vamp = SVG.select('#bat-vamp');
    bat_vamp.delay(6000).animate(200).scale(0, 0).opacity(0);

    var vamp_hand = SVG.select('.vamp-hand');
    vamp_hand.animate().rotate(10).after(function(finish) {
        this.animate().rotate(-10).reverse().loop(10);
    })

    var vamp = SVG.select('#vampire');
    vamp.delay(13000).animate().opacity(0).style('display', 'none');
});