<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Word Statistics</title>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="fonts/css/font-awesome.css">
    <link rel="stylesheet" href="fonts/css/font-awesome.min.css">

    <style>
        body{
            background-image: url("img/words-blog2.jpg");
        }
		table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            background-color: whitesmoke;
            padding: 4px 0px 4px 0px;
        }
    </style>
</head>

<body>
<nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="home.php">
                <img src="img/word_stats.png" class="img-responsive" style="margin-top: -5px;">
            </a>
        </div>
    </div>
</nav>

<div class="container" style="margin-top: 110px;">
    <div class="row">
        <div class="col-sm-12">
			<h2><b><i>Word</i> in a file</b></h2><br><br>

            <div class="col-lg-2 col-sm-2 col-md-2 col-lg-offset-1 col-md-offset-1 col-sm-offset-1">
                <img src="img/my_documents.png" class="img-responsive">
            </div>
					
			
			<!-- F-CIJA ZA CITANJE NA TEXT OD PDF -->
			
			<?php
				function decodeAsciiHex($input) {
					$output = "";

					$isOdd = true;
					$isComment = false;

					for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
						$c = $input[$i];

						if($isComment) {
							if ($c == '\r' || $c == '\n')
								$isComment = false;
							continue;
						}

						switch($c) {
							case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
							case '%': 
								$isComment = true;
							break;

							default:
								$code = hexdec($c);
								if($code === 0 && $c != '0')
									return "";

								if($isOdd)
									$codeHigh = $code;
								else
									$output .= chr($codeHigh * 16 + $code);

								$isOdd = !$isOdd;
							break;
						}
					}

					if($input[$i] != '>')
						return "";

					if($isOdd)
						$output .= chr($codeHigh * 16);

					return $output;
				}
				function decodeAscii85($input) {
					$output = "";

					$isComment = false;
					$ords = array();
					
					for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
						$c = $input[$i];

						if($isComment) {
							if ($c == '\r' || $c == '\n')
								$isComment = false;
							continue;
						}

						if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
							continue;
						if ($c == '%') {
							$isComment = true;
							continue;
						}
						if ($c == 'z' && $state === 0) {
							$output .= str_repeat(chr(0), 4);
							continue;
						}
						if ($c < '!' || $c > 'u')
							return "";

						$code = ord($input[$i]) & 0xff;
						$ords[$state++] = $code - ord('!');

						if ($state == 5) {
							$state = 0;
							for ($sum = 0, $j = 0; $j < 5; $j++)
								$sum = $sum * 85 + $ords[$j];
							for ($j = 3; $j >= 0; $j--)
								$output .= chr($sum >> ($j * 8));
						}
					}
					if ($state === 1)
						return "";
					elseif ($state > 1) {
						for ($i = 0, $sum = 0; $i < $state; $i++)
							$sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
						for ($i = 0; $i < $state - 1; $i++)
							$ouput .= chr($sum >> ((3 - $i) * 8));
					}

					return $output;
				}
				function decodeFlate($input) {
					return @gzuncompress($input);
				}

				function getObjectOptions($object) {
					$options = array();
					if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
						$options = explode("/", $options[1]);
						@array_shift($options);

						$o = array();
						for ($j = 0; $j < @count($options); $j++) {
							$options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));
							if (strpos($options[$j], " ") !== false) {
								$parts = explode(" ", $options[$j]);
								$o[$parts[0]] = $parts[1];
							} else
								$o[$options[$j]] = true;
						}
						$options = $o;
						unset($o);
					}

					return $options;
				}
				function getDecodedStream($stream, $options) {
					$data = "";
					if (empty($options["Filter"]))
						$data = $stream;
					else {
						$length = !empty($options["Length"]) ? $options["Length"] : strlen($stream);
						$_stream = substr($stream, 0, $length);

						foreach ($options as $key => $value) {
							if ($key == "ASCIIHexDecode")
								$_stream = decodeAsciiHex($_stream);
							if ($key == "ASCII85Decode")
								$_stream = decodeAscii85($_stream);
							if ($key == "FlateDecode")
								$_stream = decodeFlate($_stream);
						}
						$data = $_stream;
					}
					return $data;
				}
				function getDirtyTexts(&$texts, $textContainers) {
					for ($j = 0; $j < count($textContainers); $j++) {
						if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainers[$j], $parts))
							$texts = array_merge($texts, @$parts[1]);
						elseif(preg_match_all("#Td\s*(\(.*\))\s*Tj#ismU", $textContainers[$j], $parts))
							$texts = array_merge($texts, @$parts[1]);
					}
				}
				function getCharTransformations(&$transformations, $stream) {
					preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU", $stream, $chars, PREG_SET_ORDER);
					preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU", $stream, $ranges, PREG_SET_ORDER);

					for ($j = 0; $j < count($chars); $j++) {
						$count = $chars[$j][1];
						$current = explode("\n", trim($chars[$j][2]));
						for ($k = 0; $k < $count && $k < count($current); $k++) {
							if (preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is", trim($current[$k]), $map))
								$transformations[str_pad($map[1], 4, "0")] = $map[2];
						}
					}
					for ($j = 0; $j < count($ranges); $j++) {
						$count = $ranges[$j][1];
						$current = explode("\n", trim($ranges[$j][2]));
						for ($k = 0; $k < $count && $k < count($current); $k++) {
							if (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is", trim($current[$k]), $map)) {
								$from = hexdec($map[1]);
								$to = hexdec($map[2]);
								$_from = hexdec($map[3]);

								for ($m = $from, $n = 0; $m <= $to; $m++, $n++)
									$transformations[sprintf("%04X", $m)] = sprintf("%04X", $_from + $n);
							} elseif (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU", trim($current[$k]), $map)) {
								$from = hexdec($map[1]);
								$to = hexdec($map[2]);
								$parts = preg_split("#\s+#", trim($map[3]));
								
								for ($m = $from, $n = 0; $m <= $to && $n < count($parts); $m++, $n++)
									$transformations[sprintf("%04X", $m)] = sprintf("%04X", hexdec($parts[$n]));
							}
						}
					}
				}
				function getTextUsingTransformations($texts, $transformations) {
					$document = "";
					for ($i = 0; $i < count($texts); $i++) {
						$isHex = false;
						$isPlain = false;

						$hex = "";
						$plain = "";
						for ($j = 0; $j < strlen($texts[$i]); $j++) {
							$c = $texts[$i][$j];
							switch($c) {
								case "<":
									$hex = "";
									$isHex = true;
								break;
								case ">":
									$hexs = str_split($hex, 4);
									for ($k = 0; $k < count($hexs); $k++) {
										$chex = str_pad($hexs[$k], 4, "0");
										if (isset($transformations[$chex]))
											$chex = $transformations[$chex];
										$document .= html_entity_decode("&#x".$chex.";");
									}
									$isHex = false;
								break;
								case "(":
									$plain = "";
									$isPlain = true;
								break;
								case ")":
									$document .= $plain;
									$isPlain = false;
								break;
								case "\\":
									$c2 = $texts[$i][$j + 1];
									if (in_array($c2, array("\\", "(", ")"))) $plain .= $c2;
									elseif ($c2 == "n") $plain .= '\n';
									elseif ($c2 == "r") $plain .= '\r';
									elseif ($c2 == "t") $plain .= '\t';
									elseif ($c2 == "b") $plain .= '\b';
									elseif ($c2 == "f") $plain .= '\f';
									elseif ($c2 >= '0' && $c2 <= '9') {
										$oct = preg_replace("#[^0-9]#", "", substr($texts[$i], $j + 1, 3));
										$j += strlen($oct) - 1;
										$plain .= html_entity_decode("&#".octdec($oct).";");
									}
									$j++;
								break;

								default:
									if ($isHex)
										$hex .= $c;
									if ($isPlain)
										$plain .= $c;
								break;
							}
						}
						$document .= "\n";
					}

					return $document;
				}

				function pdf2text($filename) {
					$infile = @file_get_contents($filename, FILE_BINARY);
					if (empty($infile))
						return "";

					$transformations = array();
					$texts = array();

					preg_match_all("#obj(.*)endobj#ismU", $infile, $objects);
					$objects = @$objects[1];

					for ($i = 0; $i < count($objects); $i++) {
						$currentObject = $objects[$i];

						if (preg_match("#stream(.*)endstream#ismU", $currentObject, $stream)) {
							$stream = ltrim($stream[1]);

							$options = getObjectOptions($currentObject);
							if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"])))
								continue;

							$data = getDecodedStream($stream, $options); 
							if (strlen($data)) {
								if (preg_match_all("#BT(.*)ET#ismU", $data, $textContainers)) {
									$textContainers = @$textContainers[1];
									getDirtyTexts($texts, $textContainers);
								} else
									getCharTransformations($transformations, $data);
							}
						}
					}

					return getTextUsingTransformations($texts, $transformations);
				}
				?> 
				
				
				<!-- F-CIJA ZA CITANJE NA TEXT OD MicrosoftWord FILE -->
				<?php
					function read_docx($filename){
						$striped_content = '';
						$content = '';

						if(!$filename || !file_exists($filename)) return false;

						$zip = zip_open($filename);
						if (!$zip || is_numeric($zip)) return false;

						while ($zip_entry = zip_read($zip)) {

							if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

							if (zip_entry_name($zip_entry) != "word/document.xml") continue;

							$content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

							zip_entry_close($zip_entry);
						}
						zip_close($zip);      
						$content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
						$content = str_replace('</w:r></w:p>', "\r\n", $content);
						$striped_content = strip_tags($content);

						return $striped_content;
					}
				?>
				
				
				
			<div class="col-sm-7 col-md-7 col-lg-7 text-center col-lg-offset-1 col-md-offset-1 col-sm-offset-1">
				<table style="width:100%" class="text-center">
				<tr>
					<td><b>File name</b></td>
					<td> 
						<?php echo $_FILES["file"]["name"];	?>
					</td>
				</tr>
					
				<?php
				$word = $_REQUEST['word'];
				echo "<h3>The <b>word</b> will be searching for in the file is: " . "<strong><u>" . $word . "</u></strong></h3><br><br>";
				
				if ($_FILES["file"]["error"] > 0){
					echo "<h3>Error: </h3>" . $_FILES["file"]["error"] . "<br />";
				  } else {
					if($_FILES["file"]["type"] == "application/pdf"){	
						$filename = $_FILES["file"]["tmp_name"]; //ja naoga patekata kaj so e socuvan fajlot
						$handle = fopen($filename, "r"); //go otvara fajlot za da ima pristap do sodrzinata
						$contents = fread($handle, filesize($filename)); //ja zacuvuva sodrzinata vo promenliva
						$result = pdf2text($filename);
					}
					else if(contains("document", $_FILES["file"]["type"])){						
						$filename = $_FILES["file"]["tmp_name"];
						$handle = fopen($filename, "r"); 
						$contents = fread($handle, filesize($filename));
						$microsoftWord_file = read_docx($filename);
					}
					else if($_FILES["file"]["type"] == "text/plain"){
						$filename = $_FILES["file"]["tmp_name"]; 
						$handle = fopen($filename, "r"); 
						$contents = fread($handle, filesize($filename)); 
					}
					else{
						echo "NEMAME FUNKCIONALNOST ZA DRUGI TIPOVI NA FAJLOVI";
					}
				  }
				 
					function contains($needle, $haystack){ //da se najde document vo toa ogromnoto ime kaj type 
						return strpos($haystack, $needle) !== false;
					}
				?>
			  
				<tr>
					<th class="text-center">Word has appeared in the text</th>				
					<td>
						<?php
							if($_FILES["file"]["type"] == "text/plain"){								//ova neso ne rabote
								$split_strings = preg_split('/[\s,]+/', $contents);
							}
							else if($_FILES["file"]["type"] == "application/pdf"){
								$split_strings = preg_split('/[\s,]+/', $result);
							}
							else if(contains("document", $_FILES["file"]["type"])){
								$split_strings = preg_split('/[\s,]+/', $microsoftWord_file);
							}
							$test = str_replace(array('?',"!",",",";",":",".","@","#","$","%","^","&","*","(",")","[","]","{","}","<",">","/","\\","+","-","=","_","~","`","|","\'","\""), "", $contents);	
								//samo za " i ' ne raboti
								
								$razdeli_test = preg_split('/[\s,]+/', $test);			//sekoj zbor da ima value
								$counter1 = 0;
								$word = strtolower($word);
								
								foreach($razdeli_test as $str){
									if(strcmp($word, strtolower($str)) == 0){
										$counter1+=1;
									}
								}
								echo $counter1 . " times";
						?>
					</td>
				</tr>
				<tr>
					<th class="text-center">Number of total chars the word owns</th>
					<td>
						<?php
							if($_FILES["file"]["type"] == "text/plain"){
								$charsInWord =  count_chars($word,1);
							}
							else if($_FILES["file"]["type"] == "application/pdf"){
								$charsInWord =  count_chars($word,1);
							}
							else if(contains("document", $_FILES["file"]["type"])){
								$charsInWord =  count_chars($word,1);
							}
							$counter2 = 0;
							foreach($charsInWord as $key=>$value){
								$counter2 += $value;
							}
							echo $counter2;
						?>
					</td>
				</tr>
				<tr>
					<th class="text-center">Number of different chars the word owns</td>
					<td>
						<?php
							echo strlen(count_chars($word, 3));
						?>
					</td>
				</tr>
				<tr>
					<th class="text-center">Number of numerals chars the word owns</th>
					<td>
						<?php
							$charsInString = str_split($word);
							$counter3 = 0;
							foreach($charsInString as $ch){
								if(is_numeric($ch)){
									$counter3 += 1;
								}
							}
							echo $counter3;
						?>
					</td>
				</tr>
				<tr>
					<th class="text-center">Word appears at the beginning of the sentence</th>
					<td>
						<?php
							if($_FILES["file"]["type"] == "text/plain"){
								$sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $contents);
							}
							else if($_FILES["file"]["type"] == "application/pdf"){
								$sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $result);
							}
							else if(contains("document", $_FILES["file"]["type"])){
								$remove_new_line = preg_replace('/[\ \n]+/', ' ', $microsoftWord_file);
								$sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $remove_new_line);				//САМО ЗА WORD НЕ РАБОТИ
							}
							$counterBeginning = 0;
							$counterEnd = 0;
							foreach($sentences as $recenica){
								$recenica = str_replace(".", "", $recenica);//da se izvadi tockata od posledniot string
								$r = explode(" ", $recenica);
								if(strcmp(strtolower($r[0]), $word) == 0)
									$counterBeginning += 1;
								else if(strcmp(strtolower($r[count($r)-1]), $word) == 0)
									$counterEnd += 1;
							}
							echo $counterBeginning . " times";
						?>
					</td>
				</tr>
				<tr>
					<th class="text-center">Number of times the word appears at the end of the sentence</th>
					<td>
					<?php
						$counterEnd = 0;
						foreach($sentences as $recenica){
							$recenica = str_replace(".", "", $recenica);		//da se izvadi tockata od posledniot string
							$r = explode(" ", $recenica);
							if(strcmp(strtolower($r[count($r)-1]), $word) == 0)
								$counterEnd += 1;
						}
						echo $counterEnd . " times";
					?>
					</td>
				</tr>	
			</table>	
		</div>
	</div>
    </div>
</div><br><br><br>
<br><br>

<div class="navbar navbar-fixed-bottom" style="padding-top: 15px; color: dimgray; margin-bottom: 0px; background-color: black; border-radius: 0px; opacity: 0.8; margin-top: 40px;">
    <p class="text-center">&copy; Copyrights FINKI</p>
</div>
</body>
</html>