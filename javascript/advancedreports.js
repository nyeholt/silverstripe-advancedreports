(function ($) {
	$().ready(function () {
		$('.reportFieldsSelection select').livequery(function () {
			$(this).bind('multiValueFieldAdded', function () {
				// if there's a different number of list items in the headers list,
				// we know that we need to add a new one. The only way that happens is
				if ($('.reportFieldsSelection li').length > $('.reportHeadersEntry li').length) {
					var targetText = $('.reportHeadersEntry input:last');
					targetText.val($(this).find('option:selected').text());
					targetText.keyup();
					// now, need to make sure to bind the target text value to changes of MY value
					$(this).change(function () {
						targetText.val($(this).find('option:selected').text());
					});
				} else {
					// need to change the corresponding one
				}
			})
		})
	})
})(jQuery);