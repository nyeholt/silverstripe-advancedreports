
<h1>$Title</h1>

<div id="ReportFormLeft">
	<h2>Saved Reports</h2>
	<% if Reports %>
	<table class="data reportData">
		<thead>
			<tr>
				<th>Title</th>
				<th>Download</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
	<% control Reports %>
			<tr>
				<td>
					<p>$Title</p>
					<em>$Created.Nice</em>
				</td>
				<td>
					<p><a href="$CSVFile.Link" target="_blank">CSV</a></p>
					<p><a href="$PDFFile.Link" target="_blank">PDF</a></p>
					<p><a href="$HTMLFile.Link" target="_blank">HTML</a></p>

				</td>
				<td>
					<% if CanEdit %>
					<form method="post" action="{$Top.Link}DeleteSavedReportForm" onsubmit="return confirm('Are you sure?');">
						<input type="hidden" name="SecurityID" value="$Top.SecurityID" />
						<input type="hidden" name="ReportID" value="$ID" />
						<input type="submit" value="X" action="action_deletereport" />
					</form>
					<% end_if %>
				</td>
			</tr>
	<% end_control %>
		</tbody>
	</table>
	<% end_if %>
</div>
<div id="ReportFormRight">
	$Content
	<h2>Configure Report </h2>
	$ReportForm
</div>


