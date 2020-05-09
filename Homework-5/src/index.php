<?php

include "SpellCorrector.php";
ini_set("memory_limit", -1);

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;

if ($query) {
	// The Apache Solr Client library should be on the include path
	// which is usually most easily accomplished by placing in the
	// same directory as this script ( . or current directory is a default
	// php include path entry in the php.ini)
	require_once('/home/annguyen/shared/solr-php-client/Apache/Solr/Service.php');

	// create a new solr service instance - host, port, and webapp
	// path (all defaults in this example)
	$solr = new Apache_Solr_Service('localhost', 8983, '/solr/mycore');

	// if magic quotes is enabled then stripslashes will be needed
	if (get_magic_quotes_gpc() == 1)
		$query = stripslashes($query);

	// SPELL CORRECTION
	$query_terms = explode(" ", $query);
	$correct_terms = [];
	foreach ($query_terms as $term)
		$correct_terms[] = SpellCorrector::correct($term);
	echo "<script>console.log('" . array_values($correct_terms)[0] . "')</script>";
	$correct_query = implode(" ", $correct_terms);
	if (strtolower($query) != strtolower($correct_query))
		$spellCheck = true;

	// in production code you'll always want to use a try /catch for any
	// possible exceptions emitted  by searchingexternal_pageRankFile (i.e. connection
	// problems or a query parsing error)
	try {
		if ($_GET["algo"] == "pagerank") {
			$algo = "pagerank";
			$additionalParameters = array('sort' => 'pageRankFile desc');
			$results = $solr->search($query, 0, $limit, $additionalParameters);
		} else {
			$algo = "lucene";
			$results = $solr->search($query, 0, $limit);
		}
	} catch (Exception $e) {
		// in production you'd probably log or email this error to an admin
		// and then show a special message to the user but for this example
		// we're going to show the full exception
		die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
	}
}

?>
<html>

<head>
	<title>PHP Solr Client Example</title>
	<link href="http://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet">
	</link>
	<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
	<script src="http://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script>
		$(function() {
			var URL_PREFIX = "http://localhost:8983/solr/mycore/suggest?q=";
			var URL_SUFFIX = "&wt=json";
			var final_suggest = [];
			var previous = "";
			$("#q").autocomplete({
				source: function(request, response) {
					var q = $("#q").val().toLowerCase();
					var sp = q.lastIndexOf(' ');
					if (q.length - 1 > sp && sp != -1) {
						final_query = q.substr(sp + 1);
						previous = q.substr(0, sp);
					} else {
						final_query = q.substr(0);
					}
					var URL = URL_PREFIX + final_query + URL_SUFFIX;
					$.ajax({
						url: URL,
						success: function(data) {
							var docs = JSON.stringify(data.suggest.suggest);
							var jsonData = JSON.parse(docs);
							var result = jsonData[final_query].suggestions;
							var j = 0;
							var suggest = [];
							for (var i = 0; i < 5 && j < result.length; i++, j++) {
								if (final_query == result[j].term) {
									--i;
									continue;
								}
								for (var l = 0; l < i && i > 0; l++) {
									if (final_suggest[l].indexOf(result[j].term) >= 0) {
										--i;
									}
								}
								if (suggest.length == 5)
									break;
								if (suggest.indexOf(result[j].term) < 0) {
									suggest.push(result[j].term);
									if (previous == "") {
										final_suggest[i] = result[j].term;
									} else {
										final_suggest[i] = previous + " ";
										final_suggest[i] += result[j].term;
									}
								}
							}
							response(final_suggest);
						},
						close: function() {
							this.value = '';
						},
						dataType: 'jsonp',
						jsonp: 'json.wrf'
					});
				},
				minLength: 1
			})
		});
	</script>
</head>

<body>
	<form accept-charset="utf-8" method="get">
		<center>
			<h1><label for="q">Search : </label></h1>
		</center>
		<center>
			<input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" />
		</center>
		<br />
		<center>
			<input type="radio" name="algo" value="lucene" <?php if (isset($_REQUEST['algo']) && $_REQUEST['algo'] == 'lucene') {
																echo 'checked="checked"';
															} ?>> Lucene
			<input type="radio" name="algo" value="pagerank" <?php if (isset($_REQUEST['algo']) && $_REQUEST['algo'] == 'pagerank') {
																	echo 'checked="checked"';
																} ?>> Page Rank</center>
		<br />
		<center><input type="submit" /></center>
	</form>

	<?php

	// display results
	if ($results) {
		$total = (int) $results->response->numFound;
		$start = min(1, $total);
		$end = min($limit, $total);
		if ($spellCheck) {
			echo "Showing results for ", $query;
			$link = "?q=$correct_query&algo=$algo";
			echo "<br>Did you mean <a href='$link'>$correct_query</a>?";
		}
	?>
		<div>Results <?php echo $start; ?> - <?php echo $end; ?> of <?php echo $total; ?>:</div>
		<ol>
		<?php
		// iterate result documents
		$csv = array_map('str_getcsv', file('/home/annguyen/shared/data/URLtoHTML_nytimes_news.csv'));
		foreach ($results->response->docs as $doc) {
			$id = $doc->id;
			$title = $doc->title;
			$url = $doc->og_url;
			$desc = $doc->og_description;

			if ($desc == "" || $desc == null)
				$desc = "N/A";
			if ($title == "" || $title == null)
				$title = "N/A";
			if ($url == "" || $url == null) {
				foreach ($csv as $row) {
					$cmp = "/home/annguyen/shared/data/nytimes" + $row[0];
					if ($id == $cmp) {
						$url = $row[1];
						unset($row);
						break;
					}
				}
			}

			echo "Title : <a href = '$url'>$title</a></br>";
			echo "URL : <a href = '$url'>$url</a></br>";
			echo "ID : $id</br>";
			echo "Description : $desc </br></br>";
		}
	}
		?>
</body>

</html>