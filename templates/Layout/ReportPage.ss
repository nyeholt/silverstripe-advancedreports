
<h1>$Title</h1>

<div id="ReportFormLeft">
	<% if Reports %>
	<table class="data">
		<thead>
			<tr>
				<th>Created</th>
				<th>Download</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
	<% control Reports %>
			<tr>
				<td>$Created.Nice</td>
				<td><a href="$HTMLFile.Link" target="_blank">HTML</a> <a href="$CSVFile.Link" target="_blank">CSV</a> <a href="$PDFFile.Link" target="_blank">PDF</a></td>
				<td>
					<form method="post" action="{$Top.Link}DeleteSavedReportForm" onsubmit="return confirm('Are you sure?');">
						<input type="hidden" name="SecurityID" value="$Top.SecurityID" />
						<input type="hidden" name="ReportID" value="$ID" />
						<input type="submit" value="X" action="action_deletereport" />
					</form>
				</td>
			</tr>
	<% end_control %>
		</tbody>
	</table>
	<% end_if %>
</div>
<div id="ReportFormRight">
	$Content
	$ReportForm
</div>
