// Animation on modal display
$('#addPatientModal, #editModal').on('show.bs.modal', function (event) {
    $(this).find('.modal-content').addClass('fade-in');
    setTimeout(() => {
        $(this).find('.modal-content').addClass('show');
    }, 100);
});

$('#addPatientModal, #editModal').on('hide.bs.modal', function () {
    $(this).find('.modal-content').removeClass('show');
    setTimeout(() => {
        $(this).find('.modal-content').removeClass('fade-in');
    }, 400);
});

// Button hover effect
$('.btn').hover(function() {
    $(this).css('box-shadow', '0px 8px 15px rgba(0, 0, 0, 0.1)');
}, function() {
    $(this).css('box-shadow', 'none');
});

// Smooth scroll animation for better UI experience
$('a[href^="#"]').on('click', function (e) {
    e.preventDefault();

    var target = this.hash;
    var $target = $(target);

    $('html, body').stop().animate({
        'scrollTop': $target.offset().top
    }, 900, 'swing');
});
