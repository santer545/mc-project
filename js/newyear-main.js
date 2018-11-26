function start_puzzle(x) {
    $('#puzzle_solved').hide();
    $('#source_image').snapPuzzle({
        rows: x,
        columns: x,
        pile: '#pile',
        containment: '#puzzle-containment',
        onComplete: function() {
            $('#source_image').fadeOut(150).fadeIn();
            $('#puzzle_solved').show();
            $('#puzzle-gift-1').modal('show');
        }
    });
}

function showOptions() {
    $('.js-option').on('click', function() {
        $('.js-option-holder').toggleClass('active');
    })
}

$(function() {
    startToPlay();
    showOptions();
    maskes();
    $('#pile').height($('#source_image').height());
    //start_puzzle(5);

    $('.restart-puzzle').click(function() {
        $('#source_image').snapPuzzle('destroy');
        start_puzzle($(this).data('grid'));
    });

    $(window).resize(function() {
        $('#pile').height($('#source_image').height());
        $('#source_image').snapPuzzle('refresh');
    });
});

var myLevel = 5;

function startToPlay() {
    $('.js-play').on('click', function(e) {
        var phone = $('#user-phone').val();
        var name = $('#user-name').val();
        var email = $('#user-email').val();

        sendInfo(phone, name, email, 0);
        e.preventDefault();

        $('#start-modal').modal('hide');

        $('.first-screen').addClass('process');

        $('.first-screen .socks-list > div > img').removeAttr('data-toggle').removeAttr('data-target');

        

        $('html, body').animate({
            scrollTop: $("div.second-screen").offset().top - 100
        }, 1000);

        $('.second-screen').addClass('active');

        switch (myLevel) {
            case 0:
                start_puzzle(5);
                break;
            case 2:
                start_puzzle(5);
                break;
            case 3:
                start_puzzle(7);
                break;
            case 4:
                start_puzzle(7);
                break;
            case 5:
                $('#source_image').attr('src','assets/images/newyear2019/orig.jpg');
                start_puzzle(8);
                break;
            case 6:
                start_puzzle(8);
                break;
            case 7:
                start_puzzle(9);
                break;
            case 8:
                start_puzzle(9);
                break;
            case 9:
                start_puzzle(10);
                break;
        }

        if (myLevel < 6) {
            for (i = 0; i <= (myLevel + 1); i++) {
                $('.socks-list.top > div:nth-child(' + i + ')').addClass('active');
            }
        } else {
            for (i = 0; i <= (myLevel + 1); i++) {
                $('.socks-list.top > div').addClass('active');
                $('.socks-list.bottom > div:nth-child(' + i + ')').addClass('active');
            }
        }

    })
}

function maskes() {
    $(".js-phone").mask("+38099 999 9999", {
        autoclear: false
    });
}