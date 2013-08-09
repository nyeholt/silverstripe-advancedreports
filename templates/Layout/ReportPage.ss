<h1>$Title</h1>

$Content

<% if $CanEditTemplate %>
	<p><a href="$Link('settings')" class="btn">Edit Report</a></p>
<% end_if %>

<div class="clear"></div>

<% if $GenerateForm %>
	<h2>Generate Report</h2>
	$GenerateForm
<% end_if %>

<div class="clear"></div>

<% if $GeneratedReports %>
	<h2>Generated Reports</h2>

	<table>
		<thead>
			<tr>
				<th>Title</th>
				<th>Generated At</th>
				<th>Links</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<% loop $GeneratedReports %>
				<tr>
					<td>$Title</td>
					<td>$Created.Nice</td>
					<td>
						<a href="$HTMLFile.Link" target="_blank">HTML</a>
						<a href="$CSVFile.Link" target="_blank">CSV</a>
						<a href="$PDFFile.Link" target="_blank">PDF</a>
					</td>
					<td>
						$DeleteForm
					</td>
				</tr>
			<% end_loop %>
		</tbody>
	</table>
<% end_if %>
