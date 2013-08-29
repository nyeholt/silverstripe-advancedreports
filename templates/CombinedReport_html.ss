<html>
	<head>
		<% base_tag %>
		<style type="text/css">
body { font-size: 8pt; font-family: Arial, sans-serif; }

#PdfHeader { position: running(header); display: block; padding: .2em; margin: 0px 5%;  }
#PdfHeader { background: transparent url(images/report_logo.png) no-repeat bottom right;}
#PdfFooter { position: running(footer);padding: .2em; font-size: 7pt; }

#PdfHeader h1 { font-size: 12pt; font-weight: bold; }

h1 { font-size: 24pt; color: #000; }

h2 { font-size: 18pt; color: #000; }

h3 { font-size: 12pt; color: #000; }


@page portrait {
	size: A4 portrait;
	margin: 2cm 1cm;

	@top-center {
		content: element(header);
	}

	@bottom-left {
        content: element(footer);
    }
}

@page landscape {
	size: A4 landscape;
	margin: 2cm 1cm;

	@top-center {
		content: element(header);
	}

	@bottom-left {
        content: element(footer);
    }
}

#pagenumber:before { content: counter(page); }
#pagecount:before { content: counter(pages); }


table.reporttable { width: 100%; -fs-table-paginate: paginate; border-collapse: collapse; border: 0 none; margin: 20px 0 20px 0; padding: 0; }

table.reporttable thead th {
	background: #666;
	color: #FFF;
	margin: 0;
	padding: 4pt;
	height: 35px;
	border: 0 none !important;
}


table.reporttable tbody td {
	border: 0 none;
	margin: 0;
	padding: 4pt;
	border-top: 1px solid #000;
	font-size: 8pt;
}

table.reporttable tbody td.noReportData {
/*	border-top: none;*/
}

h2.reportTableName { margin: 20px 0; page-break-before: always;}

div.newPage { page-break-before: always; }

div.landscape { page: landscape; width: 27.5cm; }
div.portrait { page: portrait; width: 18.8cm; }

div.logo { background: transparent url(images/report_logo.png) no-repeat;  width: 190px; height: 65px; margin: 20px auto; }

.printOnly { display: block }

.printLogo { width: 190px; height: 65px;  margin: 30px auto; background: transparent url(images/report_logo.png) no-repeat; }
#PrintCoverPage { width: 18.8cm; text-align: center;}

		</style>
	</head>
	<body>
		<div id="PrintCoverPage">
			<div class="printLogo">
			</div>
			<h1>$Title</h1>
			<p class="reportDescription">$Description.Raw</p>
			<p>Generated $LastEdited.Nice</p>
		</div>
		
		<% control Reports %>

			<div class="landscape newPage">
				<h2>$Title</h2>
				<% if Format = pdf %>
				<% include PdfHeaderFooter %>
				<% end_if %>
				$ReportContent
			</div>
		
		<% end_control %>


	</body>
</html>