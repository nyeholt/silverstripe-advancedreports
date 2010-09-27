<h1>$Title</h1>
$Content


<ul id="ReportList">
<% control Children %>
<li class="$EvenOdd">
	<a href="$Link(htmlpreview)" class="reportPreviewLink">Preview</a>
	<a href="$Link" class="reportTitle">$Title</a>
</li>
<% end_control %>
</ul>


$Form