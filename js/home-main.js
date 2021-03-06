$(function() {
    lazyLoadImages();
    calculatorMain();
    customScr();
    carousels();
    dropdown();
    maskes();
    //    showCallback();
    motivationCarousel();
    videoModalShow();
    randomId();
    playMainVideo();
    goToAnchor();
    accordion();
    showTooltip();
    menuShowHide();
    promoPopup();
    promoEnable();
    androidDetected();
    domRangeCreate();
    slideChange();
})

$(window).resize(function() {
    motivationCarousel();
    accordion();
})

$(window).on('resize', carousels);


// выпадающее меню
function dropdown() {
    $('.js-parent').on('click', function(e) {
        $(this).toggleClass('active');
        $(this).find('.triangle').toggleClass('active');
        $(this).find('.js-dropdown').toggleClass('active');
    })
}


// Карусели на главной странице
function carousels() {
    $('.js-banner').not('.slick-initialized').slick({
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

    $('.js-programms').not('.slick-initialized').slick({
        slidesToShow: 3,
        lazyLoad: 'ondemand',
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

    $('.js-reviews').not('.slick-initialized').slick({
        slidesToShow: 2,
        nextArrow: '<span class="icon-programm-arrow-right"><span class="path1"></span><span class="path2"></span></span>',
        prevArrow: '<span class="icon-programm-arrow-left"><span class="path1"></span><span class="path2"></span></span>',
        responsive: [{
            breakpoint: 991,
            settings: {
                arrows: false,
                slidesToShow: 1,
                dots: true
            }
        }]
    });
    $('.js-pressa').not('.slick-initialized').slick({
        slidesToShow: 3,
        lazyLoad: 'ondemand',
        slidesToScroll: 3,
        speed: 800,
        infinite: true,
        arrows: false,
        dots: true,
        autoplay: true,
        autoplaySpeed: 3500,
        responsive: [{
                breakpoint: 1060,
                settings: {
                    arrows: false,
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    dots: true
                }
            },
            {
                breakpoint: 767,
                settings: {
                    arrows: false,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    dots: true
                }
            }
        ]
    });
}


// карусель Почему мы
function motivationCarousel() {
    if ($(window).width() < 991) {
        $('.js-motivation').not('.slick-initialized').slick({
            arrows: false,
            slidesToShow: 1,
            dots: true
        });
    } else {
        if ($('.js-motivation').hasClass('slick-initialized')) {
            $('.js-motivation').slick('unslick');
        }
    }
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


// маски для полей ввода
function maskes() {
    $(".js-phone").mask("+38099 999 9999", {
        autoclear: false
    });
}


// кастомный скролл
function customScr() {
    $('.js-seo').jScrollPane({
        arrowScrollOnHover: true
    });
}

// отобразить колбек
// function showCallback() {
//     $('.js-callback-close').on('click', function() {
//         $('.js-callback').removeClass('open');
//     })

//     $('.js-callback-show').on('click', function() {
//         $('.js-callback').addClass('open');
//     })
// }

// проверка браузера
function get_name_browser() {
    // получаем данные userAgent
    var ua = navigator.userAgent;
    // с помощью регулярок проверяем наличие текста,
    // соответствующие тому или иному браузеру
    if (ua.search(/Chrome/) > 0) return 'Google Chrome';
    if (ua.search(/Firefox/) > 0) return 'Firefox';
    if (ua.search(/Opera/) > 0) return 'Opera';
    if (ua.search(/Safari/) > 0) return 'Safari';
    if (ua.search(/MSIE/) > 0) return 'Internet Explorer';
    // условий может быть и больше.
    // сейчас сделаны проверки только 
    // для популярных браузеров
    return 'not defined';
}

// lazy load картинок
function lazyLoadImages() {
    $('.js-lazy').Lazy({
        scrollDirection: 'vertical',
        effect: 'fadeIn',
        effectTime: 1000,
        threshold: 0
    });
}

// показать видео на клик
function videoModalShow() {
    var index;
    $('.people-item').on('click', function() {
        index = $(this).index();
    })

    $('#video-modal').on('shown.bs.modal', function(e) {
        $('.js-video-carousel').slick({
            fade: true,
            slidesToShow: 1,
            nextArrow: '<span class="icon-programm-arrow-right"><span class="path1"></span><span class="path2"></span></span>',
            prevArrow: '<span class="icon-programm-arrow-left"><span class="path1"></span><span class="path2"></span></span>'
        });
        $('.js-video-carousel').slick('slickGoTo', index);
    })

    $('#video-modal').on('hidden.bs.modal', function(e) {
        $('.js-video-carousel').slick('unslick');
        $('.js-video-item').each(function(i, item) {
            item.pause();
        })
    })
}

// random user popup

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

// функция рандомного выбора чисел
function randomId() {
    var randomMin = 1,
        randomMax = 19;
    var random1 = getRandomInt(randomMin, randomMax);

    var id_item1 = "item-" + random1;

    for (i = randomMin; i <= randomMax; i++) {
        if (i != random1) {
            $('#item-' + i).removeClass('active');
        } else {
            setTimeout(function() {
                $('#item-' + random1).addClass('active');
                setTimeout(function() {
                    randomId();
                }, 5000);
            }, 45000);
        }
    }

    $('.js-user-close').on('click', function() {
        $(this).closest('.info-user').removeClass('active');
    })
}

// запуск и остановка видео на главной странице
function playMainVideo() {
    var video = document.getElementById('main-page-video');
    $(".js-playVideo").on('click', function() {
        $(this).hide();
        video.play();
        $('.js-instruction-bg').hide();
        $('.js-stepVideo').addClass('active');
        $('.js-instruction-list').addClass('active');
    })

    $(".js-stepVideo").on('click', function() {
        $(this).removeClass('active');
        video.pause();
        $('.js-instruction-bg').show();
        $('.js-playVideo').show();
        $('.js-instruction-list').removeClass('active');
    })
}

// переход по якорям
function goToAnchor() {
    $('.js-anchor a')

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

// аккордион
function accordion() {
    if ($(window).width() < 768) {
        $('.js-accordion').addClass('js-toggle');
        var animateTime = 500,
            navLink = $('.js-toggle');

        navLink.click(function() {
            $(this).toggleClass('active');
            var nav = $(this).closest('.js-footer-menu').find('ul');
            if (nav.height() === 0) {
                autoHeightAnimate(nav, animateTime);
            } else {
                nav.stop().animate({ height: '0' }, animateTime);
            }
        });
    } else {
        $('.js-accordion').removeClass('js-toggle');
    }

}

/* Function to animate height: auto */
function autoHeightAnimate(element, time) {
    var curHeight = element.height(), // Get Default Height
        autoHeight = element.css('height', 'auto').height(); // Get Auto Height
    element.height(curHeight); // Reset to Default Height
    element.stop().animate({ height: autoHeight }, time); // Animate to Auto Height
}

// показывать подсказки
function showTooltip() {
    // $('.js-show-tooltip').mouseenter(function() {
    //     $(this).closest('.form-group').find('.js-tooltip').fadeIn();
    // })

    // $('.js-show-tooltip').mouseleave(function() {
    //     $(this).closest('.form-group').find('.js-tooltip').fadeOut();
    // });

    $('.js-show-tooltip').hover(
        function(e) {

            $(this).closest('.form-group').find('.js-tooltip').removeClass('hidden');
        },
        function() {
            $(this).closest('.form-group').find('.js-tooltip').addClass('hidden');
        }
    );


    $('.js-show-tooltip').mouseenter(function() {
        $(this).find('.js-slider-tooltip').fadeIn();
    })

    $('.js-show-tooltip').mouseleave(function() {
        $(this).find('.js-slider-tooltip').fadeOut();
    })
}

// показывать меню мобайл
function menuShowHide() {
    $('.js-gamburger').click(function() {
        $('.js-menu').addClass('open');
    });

    $('.js-close-nav').click(function() {
        $('.js-menu').removeClass('open');
    })
}


// промо

function promoPopup() {
    setTimeout(function() {
        $('#promocodePopup').modal('show');
    }, 900000);
}

function promoEnable() {
    $('#promo-checkbox').change(function() {
        if ($(this).is(":checked")) {
            $('.js-calc-promocode').removeAttr('disabled');
        } else {
            $('.js-calc-promocode').attr('disabled', 'disabled');
        }
    });
}

function androidDetected() {
    var isMobile = {
        Android: function() {
            return navigator.userAgent.match(/Android/i);
        },
        BlackBerry: function() {
            return navigator.userAgent.match(/BlackBerry/i);
        },
        iOS: function() {
            return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        },
        Opera: function() {
            return navigator.userAgent.match(/Opera Mini/i);
        },
        Windows: function() {
            return navigator.userAgent.match(/IEMobile/i);
        },
        any: function() {
            return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
        }
    };

    if (isMobile.Android()) {
        $('#android').modal('show');
    }
}




//  Copy promocod
function domRangeCreate() {
    $('.js-copy').on('click', function() {
        var target = $(this).closest('.js-copy-area').find('.js-copy-text').get(0);
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

// detect slick slide changes

function slideChange() {
    // On before slide change
    $('.js-banner').on('beforeChange', function(event, slick, currentSlide, nextSlide) {
        nextSlide = nextSlide + 1;
        var el = $('.slick-slide').eq(nextSlide);
        var element = el.children().children();

        if (element.hasClass('js-student-banner')) {
            $('.calculator-labe').addClass('hidden');
        } else {
            $('.calculator-labe').removeClass('hidden');
        }
    });
}

function get_name_browser() {
    // получаем данные userAgent
    var ua = navigator.userAgent;
    // с помощью регулярок проверяем наличие текста,
    // соответствующие тому или иному браузеру
    if (ua.search(/Chrome/) > 0) return 'Google Chrome';
    if (ua.search(/Firefox/) > 0) return 'Firefox';
    if (ua.search(/Opera/) > 0) return 'Opera';
    if (ua.search(/Safari/) > 0) return 'Safari';
    if (ua.search(/MSIE/) > 0) return 'Internet Explorer';
    // условий может быть и больше.
    // сейчас сделаны проверки только 
    // для популярных браузеров
    return 'not defined';
}

function renameImageForBrowsers() {

    if (get_name_browser() != 'Google Chrome') {
        $('.js-banner-webp, .nav-banner').each(function(item) {
            console.log('Outside');
            var style = $(this).attr('style');

            if (~style.indexOf("webp")) {
                var newStyle = style.substr(-7);
                var arr = style.split('');

                for (var i = 0; i < newStyle.length; i++) {
                    arr.pop();
                }

                var str = arr.join('');
                var finalStr;
                if (get_name_browser() == 'not defined' || get_name_browser() == 'Internet Explorer') {
                    finalStr = str + 'jpg';
                } else {
                    finalStr = str + '.jpg\')';
                }

                $(this).attr('style', finalStr);
            }
        });
    }
}