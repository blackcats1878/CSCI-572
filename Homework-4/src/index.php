<?php

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
	if (get_magic_quotes_gpc() == 1) {
		$query = stripslashes($query);
	}

	// in production code you'll always want to use a try /catch for any
	// possible exceptions emitted  by searchingexternal_pageRankFile (i.e. connection
	// problems or a query parsing error)
	try {
		if ($_GET['algo'] == "lucene") {
			$results = $solr->search($query, 0, $limit);
		} else {
			$additionalParameters = array('sort' => 'pageRankFile desc');
			$results = $solr->search($query, 0, $limit, $additionalParameters);
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
</head>

<body>
	<form accept-charset="utf-8" method="get">
		<center>
			<h1><label for="q">Search : </label>
		</center>
		</h1>
		<center><input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" /></center>
		<br />
		<center><input type="radio" name="algo" value="lucene" <?php if (isset($_REQUEST['algo']) && $_REQUEST['algo'] == 'lucene') {
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

			if ($desc == "" || $desc == null) {
				$desc = "N/A";
			}
			if ($title == "" || $title == null) {
				$title = "N/A";
			}
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