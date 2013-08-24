(function($) {
	$.entwine("ss", function($) {
		$("#Form_ItemEditForm_ScheduleEvery").entwine({
			onadd: function() {
				this.update();
			},
			onchange: function() {
				this.update();
			},
			update: function() {
				$("#ScheduleEveryCustom")[this.val() == "Custom" ? "show" : "hide"]();
			}
		});
	});
})(jQuery);
