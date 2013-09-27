<h1>$Title</h1>

$Content

<% if $Children %>
	<h2>Reports</h2>

	<ul id="reports">
		<% loop $Children %>
			<li>
				<a href="$Link">$Title</a> (<a href="$Link('preview')">preview</a>)
			</li>
		<% end_loop %>
	</ul>
<% end_if %>

<% if $Form %>
	<h2>Create Report</h2>
	$Form
<% end_if %>
