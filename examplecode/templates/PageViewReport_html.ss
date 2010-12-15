<html>
	<head>
		<% base_tag %>
		<% require css(advancedreports/css/html-report.css) %>
	</head>
	<body>
		<div id="PrintCoverPage">
			<div class="printLogo">
			</div>
			<h1>$Title</h1>
			<p class="reportDescription">$Description</p>
			<p>Generated $LastEdited.Nice</p>
		</div>
		<div class="landscape newPage">
			<% if Format = pdf %>
			<% include PdfHeaderFooter %>
			<% end_if %>
			$ReportContent
		</div>
	</body>
</html>