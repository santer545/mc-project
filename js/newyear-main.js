function start_puzzle(x) {
    $('#puzzle_solved').hide();
    $('#source_image').snapPuzzle({
        rows: 5,
        columns: 5,
        pile: '#pile',
        containment: '#puzzle-containment',
        onComplete: function() {
            $('#source_image').fadeOut(150).fadeIn();
            $('#puzzle_solved').show();
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
    $('#pile').height($('#source_image').height());
    start_puzzle(3);

    $('.restart-puzzle').click(function() {
        $('#source_image').snapPuzzle('destroy');
        start_puzzle($(this).data('grid'));
    });

    $(window).resize(function() {
        $('#pile').height($('#source_image').height());
        $('#source_image').snapPuzzle('refresh');
    });
});

function startToPlay() {
    $('.js-play').on('click', function(e) {
        e.preventDefault();
        console.log('Jopa!!!');
        $('#start-modal').modal('hide');
        $('.first-screen').addClass('process');
        $('html, body').animate({
          scrollTop: $("div.second-screen").offset().top
        }, 1000)
        $('.second-screen').addClass('active');
    })
}

