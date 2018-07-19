$(function() {
    carousels();
    calculatorMain();
    dropdown();
    maskes();
    customScroll();
    showCallback();
    lazyLoadImages();
    motivationCarousel();
    videoModalShow();
    randomId();
    playMainVideo();
    goToAnchor();
    accordion();
    showTooltip ();
    menuShowHide();
    carouselOnLoadShow();

    $('.calculator').attr('style', 'visibility: visible');
})

$(window).resize(function() {
    motivationCarousel();
    accordion();
})


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

    $('.js-programms').slick({
        slidesToShow: 3,
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

    $('.js-reviews').slick({
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
}

function motivationCarousel() {
    if ($(window).width() < 768) {
        $('.js-motivation').slick({
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

function customScroll() {
    $('.js-seo').jScrollPane({
        arrowScrollOnHover: true
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

function videoModalShow() {
    var index;
    $('.people-item').on('click', function() {
        index = $(this).index();
    })

    $('#video-modal').on('shown.bs.modal', function(e) {
        $('.js-video-carousel').slick({
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
        $(this).removeClass('hidden');
        video.pause();
        $('.js-instruction-bg').show();
        $('.js-playVideo').show();
        $('.js-instruction-list').removeClass('active');
    })
}

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

function showTooltip () {
    $('.js-show-tooltip').mouseenter(function() {
        $('.js-tooltip').fadeOut();
        $(this).closest('div').find('.js-tooltip').fadeIn(200);
    })

    $('.js-show-tooltip').mouseleave(function() {
        $('.js-tooltip').fadeOut();
    });


    $('.js-show-tooltip').mouseenter(function() {
        $(this).find('.js-slider-tooltip').fadeIn();
    })

    $('.js-show-tooltip').mouseleave(function() {
        $(this).find('.js-slider-tooltip').fadeOut();
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

function carouselOnLoadShow() {
    $('.js-banner').removeClass('hidden');
    $('.js-programms').removeClass('hidden');
    $('.js-reviews').removeClass('hidden');
}