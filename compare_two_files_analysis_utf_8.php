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
			font-family: "Calibri";
        }
		table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 4px 0px 4px 0px;
        }
		i {
			color: gray;
		}
    </style>
	
	<script>
		 var showText = function (target, message, index, interval) {
			 if (index < message.length) {
				 $(target).append(message[index++]);
				 setTimeout(function () { showText(target, message, index, interval); }, interval);
			 }
		 }

		 $(function () {
			 document.getElementById('Lorem').style.fontWeight = "900";
			 showText("#Lorem", "Word statistics of two files", 0, 100);
		 });		
	</script>
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
			<a href="info.php">
				<i class="fa fa-info-circle pull-right" aria-hidden="true" style="color: white; font-size: 25px; padding-top: 14px;"></i>
			</a>
        </div>
    </nav>

     <div class="container" style="margin-top: 80px;">
    <div class="row">
        <div class="col-sm-12 col-lg-12 col-md-12 col-xs-12">
            <a href="compare_two_files.php"><i class="fa fa-arrow-left" aria-hidden="true" style="font-size: 25px; margin-top: -10px; float: left;" ></i></a>
            <h1 class="text-center" id="Lorem"></h1>
			<br>

                <table style="width:100%" class="text-center">
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
										
										<!-- F-CIJA ZA CITANJE NA TEXT OD MicrosoftWord FILE .DOC -->
										<?php
											function read_doc_file($filename) {
												 if(file_exists($filename))
												{
													if(($fh = fopen($filename, 'r')) !== false ) 
													{
														$headers = fread($fh, 0xA00);

														   $n1 = ( ord($headers[0x21C]) - 1 );
														   $n2 = ( ( ord($headers[0x21D]) - 8 ) * 256 );
														   $n3 = ( ( ord($headers[0x21E]) * 256 ) * 256 );
														   $n4 = ( ( ( ord($headers[0x21F]) * 256 ) * 256 ) * 256 );
														   $textLength = ($n1 + $n2 + $n3 + $n4);

														   $extracted_plaintext = fread($fh, $textLength);
														   return nl2br($extracted_plaintext);
													}
												}   
											}
										?>
										
										<!-- F-CIJA ZA CITANJE NA TEXT OD MicrosoftWord FILE .DOCX -->
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
						
						
						<?php
									$txtHere = False;
									$pdfHere = False;
									$docxHere = False;
									
							      if($_FILES["file1"]["error"] > 0){
									echo "<h3>Error: </h3>" . $_FILES["file1"]["error"] . "<br />";
								  } 
								  else {
		// FILE 1
									if($_FILES["file1"]["type"] == "application/pdf"){	
										$filename = $_FILES["file1"]["tmp_name"];
										$handle = fopen($filename, "r");
										$contents = fread($handle, filesize($filename));
										$contents = pdf2text($filename);
										check_file_font($contents);
										$pdfHere = True;
									}
									else if($_FILES["file1"]["type"] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"){
										$filename = $_FILES["file1"]["tmp_name"];
										$handle = fopen($filename, "r"); 
										$contents = fread($handle, filesize($filename));
										$contents = read_docx($filename);
										check_file_font($contents);
										$docxHere = True;
									}
									else if($_FILES["file1"]["type"] == "text/plain"){
										$filename = $_FILES["file1"]["tmp_name"]; 
										$handle = fopen($filename, "r"); 
										$contents = fread($handle, filesize($filename));
										check_file_font($contents);										
										$txtHere = True;
									}
									else{
										echo "<script> alert('There is no functionality provided for this kind of files! Please insert another type of file.');
										   window.location ='compare_two_files.php'; </script>";
									}
								  }
								  function contains($needle, $haystack){
										return strpos($haystack, $needle) !== false;
									}
									
									function check_file_font($contents){
										$cont = trim($contents);
										$sodrzina = preg_split('/[\s,]+/', $cont);
										$sodrzina = preg_replace('#[[:punct:]]#', ' ', $sodrzina);
										$flag_font1 = False;
										
										foreach($sodrzina as $d){
											
											$posebno_zbor = str_split($d);
												foreach($posebno_zbor as $p){
													if(ord($p) >= 65 && ord($p) <= 122)
														$flag_font1 = True;
													else if(is_numeric($p)){
														continue;
													}
													else if(ord($p) == 32)
														continue;
												}
										}
										
										if($flag_font1 == True){
											echo "<script> alert('Upload a file in the special encoding!');
												window.location ='compare_two_files.php'; </script>";
										}
									}
							?>
		<!--FILE 2-->
						
                            <?php
								$txtHere2 = False;
								$pdfHere2 = False;
								$docxHere2 = False;
								
								if ($_FILES["file2"]["error"] > 0){
									echo "<h3>Error: </h3>" . $_FILES["file2"]["error"] . "<br />";
								} 
								else {
									
									if($_FILES["file2"]["type"] == "application/pdf"){	
										$filename2 = $_FILES["file2"]["tmp_name"];
										$handle2 = fopen($filename2, "r");
										$contents2 = fread($handle2, filesize($filename2));
										$contents2 = pdf2text($filename2);
										check_file_font($contents2);
										$pdfHere2 = True;
									}
									else if($_FILES["file2"]["type"] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"){
										$filename2 = $_FILES["file2"]["tmp_name"];
										$handle2 = fopen($filename2, "r"); 
										$contents2 = fread($handle2, filesize($filename2));
										$contents2 = read_docx($filename2);
										check_file_font($contents2);
										$docxHere2 = True;
									}
									else if($_FILES["file2"]["type"] == "text/plain"){
										$filename2 = $_FILES["file2"]["tmp_name"]; 
										$handle2 = fopen($filename2, "r"); 
										$contents2 = fread($handle2, filesize($filename2));
										check_file_font($contents2);
										$txtHere2 = True;
									}
									else{
										echo "<script> alert('There is no functionality provided for this kind of files! Please insert another type of file.');
										   window.location ='compare_two_files.php'; </script>";
									}
								  }
							?>
					<tr style="background-color: #989898;">
					    <th></th>
					    <th class="text-center"><h4><b>File 1</b></h4></th>
					    <th class="text-center"><h4><b>File 2</b></h4></th>
				    </tr>
					<tr style="background-color: #A0A0A0;">
                        <td><b>Type</b></td>
                        <td>
							<?php
								echo $_FILES["file1"]["type"];
							?>
                        </td>
						<td>
							<?php
								echo $_FILES["file2"]["type"];
							?>
                        </td>
                    </tr>
					
		<!--FILE 1-->
					<tr style="background-color: #A8A8A8;">
                        <td><b>Size</b></td>
                        <td>
                            <?php $size = ($_FILES["file1"]["size"] / 1024);
								echo round($size, 2) . " Kb<br />";	?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php $size2 = ($_FILES["file2"]["size"] / 1024);
								echo round($size2, 2) . " Kb<br />";	?>
                        </td>
                    </tr>
					
		<!--FILE 1-->
					<tr style="background-color: #B0B0B0;">
                        <td><b>Words</b></td>
                        <td>
                            <?php
							$line = $contents;
							if($txtHere == True){
								$brisi_interpuncii = preg_replace('#[[:punct:]]#', ' ', $contents);
								$split_strings = preg_split('/[\ \s\,]+/', $brisi_interpuncii);
								$counter1 = 0;
								
								foreach($split_strings as $el){
									if(!is_numeric($el)){
										$counter1++;
									}
								}
								echo $counter1;
							}
							else if(($pdfHere == True) || ($docxHere == True)){
									$line = str_replace("\n", " ", $line);
									$str = explode(" ", $line);

									function my_word_count($str) {
									  $mystr = str_replace("\xC2\xAD",'', $str);
									  return preg_match_all('~[\p{L}\'\-]+~u', $mystr);
									}
									$count_words = my_word_count($contents);
									echo $count_words;
							}
						?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php 
								$line2 = $contents2;
								if($txtHere2 == True){
									$brisi_interpuncii2 = preg_replace('#[[:punct:]]#', ' ', $contents2);
									$split_strings2 = preg_split('/[\ \s\,]+/', $brisi_interpuncii2);
									$counter_file2 = 0;
									
									foreach($split_strings2 as $el){
										if(!is_numeric($el)){
											$counter_file2++;
										}
									}
									echo $counter_file2;
								}
								else if(($pdfHere2 == True) || ($docxHere2 == True)){
										$line2 = str_replace("\n", " ", $line2);
										$str2 = explode(" ", $line2);

										$count_words2 = my_word_count($contents2);
										echo $count_words2;
								}
							?>
                        </td>
                    </tr>
                   <tr style="background-color: #B8B8B8;">
		<!--FILE 1-->
                        <td><b>Numbers</b></td>
                        <td>
                            <?php
								$counter2 = 0;
								$brisi_interpuncii = preg_replace('#[[:punct:]]#', ' ', $contents);
								$split_strings = preg_split('/[\ \s\,]+/', $brisi_interpuncii);
								
								foreach($split_strings as $el){
									if(is_numeric($el))
										$counter2++;
								}
								echo $counter2 . "\n";
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$counter_numbers2 = 0;
								$brisi_interpuncii2 = preg_replace('#[[:punct:]]#', ' ', $contents2);
								$split_strings2 = preg_split('/[\ \s\,]+/', $brisi_interpuncii2);
								
								foreach($split_strings2 as $el2){
									if(is_numeric($el2))
										$counter_numbers2 ++;
								}
								echo $counter_numbers2;
							?>
                        </td>
                    </tr>
		<!--FILE 1-->
                    <tr style="background-color: #C0C0C0;">
                        <td><b>Reading time</b></td>
                        <td>
                            <?php
								$contents = preg_replace("#[[:punct:]]#", "", $contents);
								$str_bez_interp = explode(" ", $contents);
								if(count($str_bez_interp)<275){
									echo "Less than a minute";	
								}
								else if(count($str_bez_interp)==275){
									echo "For a minute";
								}
									else{
								$temp = count($str_bez_interp)/275;
									echo round($temp, 1) . " min";
								}
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$contents2 = preg_replace("#[[:punct:]]#", "", $contents2);
								$str_bez_interp2 = explode(" ", $contents2);
								if(count($str_bez_interp2)<275){
									echo "Less than a minute";	
								}
								else if(count($str_bez_interp2)==275){
									echo "For a minute";
								}
									else{
								$temp2 = count($str_bez_interp2)/275;
									echo round($temp2, 1) . " min";
								}
							?>
                        </td>
                    </tr>
		<!--FILE 1-->
					<tr style="background-color: #C8C8C8;">
                        <td><b>Speaking time</b></td>
                        <td>
                            <?php
								$contents = preg_replace("#[[:punct:]]#", "", $contents);
								$str_bez_interp = explode(" ", $contents);
								if(count($str_bez_interp)<180){
									echo "Less than a minute";	
								}
								else if(count($str_bez_interp)==180){
									echo "For a minute";	
								}
								else{
									$temp = count($str_bez_interp)/180;
									echo round($temp, 1) . " min";
								}
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$contents2 = preg_replace("#[[:punct:]]#", "", $contents2);
								$str_bez_interp2 = explode(" ", $contents2);
								if(count($str_bez_interp2)<180){
									echo "Less than a minute";	
								}
								else if(count($str_bez_interp2)==180){
									echo "For a minute";	
								}
								else{
									$temp2 = count($str_bez_interp2)/180;
									echo round($temp2, 1) . " min";
								}
							?>
                        </td>
                    </tr>
					<tr style="background-color: #D0D0D0;">
		<!--FILE 1-->
                        <td><b>Long words</b></td>
                        <td>
                            <?php
								$counter5 = 0;
								$str_i = preg_replace('#[[:punct:]]#', '', $split_strings);
								
								foreach($str_i as $s){
									if(is_numeric($s))
										continue;
									if(strlen(utf8_decode($s))>=7)
									{
										$counter5 += 1;
									}
								}
								echo $counter5;
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$counter_longWords2 = 0;
								$str_i2 = preg_replace('#[[:punct:]]#', '', $split_strings2);
								
								foreach($str_i2 as $s2){
									if(is_numeric($s2))
										continue;
									if(strlen(utf8_decode($s2))>=7)
									{
										$counter_longWords2 += 1;
									}
								}
								echo $counter_longWords2;
							?>
                        </td>
                    </tr>
                    <tr style="background-color: #D8D8D8;">
		<!--FILE 1-->
                        <td><b>Short words</b></td>
                        <td>
                            <?php
								$counter4 = 0;
								$str_i = preg_replace('#[[:punct:]]#', '', $split_strings);
								foreach($str_i as $s){
									if(is_numeric($s))
										continue;
									if(strlen(utf8_decode($s))>=1 && strlen(utf8_decode($s))<=3)
									{
										$counter4 += 1;
									}
								}
								echo $counter4;
							?>
                        </td>
						<td>
		<!--FILE 2-->
                            <?php
								$counter_shortWords2 = 0;
								$str_i2 = preg_replace('#[[:punct:]]#', '', $split_strings2);
								foreach($str_i2 as $s2){
									if(is_numeric($s2))
										continue;
									if(strlen(utf8_decode($s2))>=1 && strlen(utf8_decode($s2))<=3)
									{
										$counter_shortWords2 += 1;
									}
								}
								echo $counter_shortWords2;
							?>
                        </td>
                    </tr>
					
		<!--FILE 1-->
                    <tr style="background-color: #E0E0E0;">
                        <td><b>Sentences</b></td>
                        <td>
                            <?php
								$counter3 = 0;
								foreach($split_strings as $s){
									if(preg_match('/[.!?;]/u', $s)){
										$counter3 += 1;
									}
								}
								echo $counter3; 
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php			
								$counter_sentences2 = 0;
								foreach($split_strings2 as $s2){
									if(preg_match('/[.!?;]/u', $s2)){
										$counter_sentences2 += 1;
									}
								}
								echo $counter_sentences2;
							?>
                        </td>
                    </tr>
		<!--FILE 1-->
					<tr style="background-color: #E8E8E8;">
                        <td><b>Whitespaces</b></td>
                        <td>
                            <?php
								if($txtHere == True){
									$count_whitespaces = substr_count($contents, " ");
									$count_newline = substr_count($contents, "\n");
									$whitespaces = $count_whitespaces + $count_newline;
									echo $whitespaces; 
								}
								else if($pdfHere == True){
									$count_whitespaces = substr_count($contents, " ");
									$count_newline = mb_substr($contents, 0, "\n", 'UTF-8');
									$whitespaces = $count_whitespaces + $count_newline;
									$wh = $whitespaces-2;
									echo $wh;
								}
								else if($docxHere == True){
									$count_whitespaces = substr_count($contents, " ");
									$count_newline = mb_substr($contents, 0, "\n", 'UTF-8');
									$whitespaces = $count_whitespaces + $count_newline;
									echo $whitespaces;
								}
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php			
								if($txtHere2 == True){
									$count_whitespaces2 = substr_count($contents2, " ");
									$count_newline2 = substr_count($contents2, "\n");
									$whitespaces2 = $count_whitespaces2 + $count_newline2;
									echo $whitespaces2; 
								}
								else if($pdfHere2 == True){
									$count_whitespaces2 = substr_count($contents2, " ");
									$count_newline2 = mb_substr($contents2, 0, "\n", 'UTF-8');
									$whitespaces2 = $count_whitespaces2 + $count_newline2;
									$wh2 = $whitespaces2-2;
									echo $wh2;
								}
								else if($docxHere2 == True){
									$count_whitespaces2 = substr_count($contents2, " ");
									$count_newline2 = mb_substr($contents, 0, "\n", 'UTF-8');
									$whitespaces2 = $count_whitespaces2 + $count_newline2;
									echo $whitespaces2;
								}
							?>
                        </td>
                    </tr>
                    <tr style="background-color: #F0F0F0;">
		<!--FILE 1-->
                        <td><b>Characters (with spaces)</b></td>
                        <td>
                            <?php 
								$brisi_nov_red = str_replace("\n", "", $contents);
								$no_spaces = mb_strlen(utf8_decode($brisi_nov_red)) - $whitespaces;
								$pom = $no_spaces + $whitespaces;
								echo ($pom+1);
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$brisi_nov_red2 = str_replace("\n", "", $contents2);
								$no_spaces2 = mb_strlen(utf8_decode($brisi_nov_red2)) - $whitespaces2;
								$pom2 = $no_spaces2 + $whitespaces2;
								echo ($pom2+1);
							?>
                        </td>
                    </tr>
                    
		<!--FILE 1-->
					<tr style="background-color: #F8F8F8;">
                        <td><b>Characters (no spaces)</b></td>
                        <td>
                            <?php
								$brisi_nov_red = str_replace("\n", "", $contents);
								$no_spaces = mb_strlen(utf8_decode($contents)) - $whitespaces;
								echo ($no_spaces+1);
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$brisi_nov_red2 = str_replace("\n", "", $contents2);
								$no_spaces2 = mb_strlen(utf8_decode($contents2)) - $whitespaces2;
								echo ($no_spaces2+1);
							?>
                        </td>
                    </tr>
                    <tr style="background-color: #F8F8F8;">
		<!--FILE 1-->
                        <td><b>Length of longest sentence</b></td>
                        <td>
                            <?php
								$sobiraj_zborovi = 0;
								$niza = array(); 
								$max_niza = 0;
								
								for($i = 0; $i < sizeof($split_strings); $i++)
								{									
									if(strpos($split_strings[$i], '.')){
										$sobiraj_zborovi += mb_strlen($split_strings[$i]);
										array_push($niza, $sobiraj_zborovi);
										$sobiraj_zborovi = 0;
									}
									else{
										$sobiraj_zborovi += mb_strlen($split_strings[$i]);
									}
								}
									
								for($j = 0; $j < sizeof($niza); $j++){
									$max_niza = $niza[0];
									
									if($niza[$j] > $max_niza)
										$max_niza = $niza[$j];
								}
								echo $max_niza;
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php
								$sobiraj_zborovi2 = 0;
								$niza2 = array(); 
								$max_niza2 = 0;
								
								for($i = 0; $i < sizeof($split_strings2); $i++)
								{									
									if(strpos($split_strings2[$i], '.')){
										$sobiraj_zborovi2 += mb_strlen($split_strings2[$i]);
										array_push($niza2, $sobiraj_zborovi2);
										$sobiraj_zborovi2 = 0;
									}
									else{
										$sobiraj_zborovi2 += mb_strlen($split_strings2[$i]);
									}
								}
									
								for($j = 0; $j < sizeof($niza2); $j++){
									$max_niza2 = $niza2[0];
									
									if($niza2[$j] > $max_niza2)
										$max_niza2 = $niza2[$j];
								}
								echo $max_niza2;
							?>
                        </td>
                    </tr>
					
		<!--FILE 1-->
                    <tr style="background-color: #FFFFFF;">
                        <td><b>Length of shortest sentence</b></td>
                        <td>
                            <?php			
								$sobiraj_zborovi = 0;
								$niza = array(); 
								$min_niza = 0;
								
								for($i = 0; $i < sizeof($split_strings); $i++)
								{									
									if(strpos($split_strings[$i], '.')){
										$sobiraj_zborovi += mb_strlen($split_strings[$i]);
										array_push($niza, $sobiraj_zborovi);
										$sobiraj_zborovi = 0;
									}
									else{
										$sobiraj_zborovi += mb_strlen($split_strings[$i]);
									}
								}
									
								for($j = 0; $j < sizeof($niza); $j++){
									$min_niza = $niza[0];
									
									if($niza[$j] < $min_niza)
										$min_niza = $niza[$j];
								}
								echo $min_niza;
							?>
                        </td>
		<!--FILE 2-->
						<td>
                            <?php			
								$sobiraj_zborovi2 = 0;
								$niza2 = array(); 
								$min_niza2 = 0;
								
								for($i = 0; $i < sizeof($split_strings2); $i++)
								{									
									if(strpos($split_strings2[$i], '.')){
										$sobiraj_zborovi2 += mb_strlen($split_strings2[$i]);
										array_push($niza2, $sobiraj_zborovi2);
										$sobiraj_zborovi2 = 0;
									}
									else{
										$sobiraj_zborovi2 += mb_strlen($split_strings2[$i]);
									}
								}
									
								for($j = 0; $j < sizeof($niza2); $j++){
									$min_niza2 = $niza2[0];
									
									if($niza2[$j] < $min_niza2)
										$min_niza2 = $niza2[$j];
								}
								echo $min_niza2;
							?>
                        </td>
                    </tr>
					
		<!--FILE 1-->
                    <tr style="background-color: #FFFFFF;">
                        <td><b>Average word length</b></td>
                        <td>
                            <?php
								$sum = 0;
								$str_i = preg_replace('#[[:punct:]]#', '', $split_strings);
								
								foreach($str_i as $s){
									$sum += mb_strlen(utf8_decode($s));
								}
								
								$pom1 = $sum/count($str_i);
								echo number_format((float)$pom1, 2, '.', '');
							?>
                        </td>
						<td>
                            <?php
								$sum2 = 0;
								$str_i2 = preg_replace('#[[:punct:]]#', '', $split_strings2);
								
								foreach($str_i2 as $s2){
									$sum2 += mb_strlen(utf8_decode($s2));
								}
								
								$pom12 = $sum2/count($str_i2);
								echo number_format((float)$pom12, 2, '.', '');
							?>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        
    </div><br><br><br>
    <br><br>

    <div class="navbar" style="padding-top: 15px; color: dimgray; margin-bottom: 0px; background-color: black; border-radius: 0px; opacity: 0.8; margin-top: 40px;">
        <p class="text-center">&copy; Copyrights FINKI</p>
    </div>
</body>
</html>