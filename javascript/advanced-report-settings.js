jQuery(function($) {
	$(document).on("multiValueFieldAdded", ".advanced-report-fields", function(e) {
		var self = $(this);
		var field = $(e.target);

		var fields = self.find("ul.advanced-report-field-names");
		var headers = self.find("ul.advanced-report-field-headers");

		if(fields.children().length > headers.children().length) {
			var header = headers.find("input:last").val(field.val()).trigger("keyup");

			field.change(function() {
				header.val(field.val());
			});
		}
	})
});
