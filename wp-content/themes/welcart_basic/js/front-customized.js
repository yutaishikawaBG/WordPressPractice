(function ($) {
	$(document).ready(function () {
		$('.snav i').click(function () {
			if (!$(this).parent().hasClass('On')) {
				$('.snav div').removeClass('On');
			}
			$(this).parent().toggleClass('On');
		});

		$('#toTop').hide();
		$(window).scroll(function () {
			if ($(this).scrollTop() > 100) {
				$('#toTop').fadeIn();
			} else {
				$('#toTop').fadeOut();
			}
		});

		$('#toTop a').click(function () {
			const speed = 800;
			const href = $(this).attr('href');
			const target = $(
				href === '#masthead' || href === '' ? 'html' : href
			);
			const position = target.offset().top;
			$('html, body').animate({ scrollTop: position }, speed, 'swing');
			return false;
		});
	});
})(jQuery);
