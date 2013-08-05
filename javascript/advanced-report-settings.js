jQuery(function($) {
	$(document).on("multiValueFieldAdded", ".advanced-report-fields", function(e) {
		var field = $(e.target);
		var fields = $("#Form_ItemEditForm_ReportFields");
		var headers = $("#Form_ItemEditForm_ReportHeaders");

		if(fields.children().length > headers.children().length) {
			var header = headers.find("input:last").val(field.val()).trigger("keyup");

			field.change(function() {
				header.val(field.val());
			});
		}
	})
});
