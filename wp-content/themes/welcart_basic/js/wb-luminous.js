// luminous JavaScript Document

(function ($) {
	$(document).ready(function () {
		const imgTag =
			'a[href$=jpg],a[href$=jpeg],a[href$=gif],a[href$=png],a[href$=webp]';
		if ($(imgTag).length) {
			const tagLength = $(imgTag).length;

			if (tagLength > 1) {
				$(imgTag).attr('class', 'luminous-g');
				new LuminousGallery(document.querySelectorAll('.luminous-g'));
			} else {
				$(imgTag).attr('class', 'luminous');
				const luminousTrigger = document.querySelector('.luminous');
				new Luminous(luminousTrigger);
			}
		}
	});
})(jQuery);
