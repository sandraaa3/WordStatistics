
	<?php
	//prviot file
	echo "<h1>File1:<h1>";
	if ($_FILES["file1"]["error"] > 0){
		echo "<h3>Error: </h3>" . $_FILES["file1"]["error"] . "<br />";
	  } else {
		echo "<h3>Upload: </h3>" . $_FILES["file1"]["name"] . "<br />";
		echo "<h3>Type: </h3>" . $_FILES["file1"]["type"] . "<br />";
		echo "<h3>Size: </h3>" . ($_FILES["file1"]["size"] / 1024) . " Kb<br />";
		echo "<h3>Stored in: </h3>" . $_FILES["file1"]["tmp_name"];
		
		
		echo "PROVERKI:" . "<br />"; //ovie mozam so SWITCH da gi napravam i treba da gi namestam za info kako da se prikazuva
		if($_FILES["file1"]["type"] == "application/pdf"){	
			echo "PDF E";
			$filename = $_FILES["file1"]["tmp_name"]; //ja naoga patekata kaj so e socuvan fajlot
			$handle = fopen($filename, "r"); //go otvara fajlot za da ima pristap do sodrzinata
			$contents = fread($handle, filesize($filename)); //ja zacuvuva sodrzinata vo promenliva
		}
		else if(contains("document", $_FILES["file1"]["type"])){
			echo "DOC E";
			
			$filename = $_FILES["file1"]["tmp_name"];
			$handle = fopen($filename, "r"); 
			$contents = fread($handle, filesize($filename));
            $contents = read_docx($filename); //preprocesiranje na docx fajlot da se vmetre readable sodrzina vo promenliva		
		}
		else if($_FILES["file1"]["type"] == "text/plain"){
			echo "TXT E";
			$filename = $_FILES["file1"]["tmp_name"]; 
			$handle = fopen($filename, "r"); 
			$contents = fread($handle, filesize($filename)); 
		}
		else{
			echo "NEMAME FUNKCIONALNOST ZA DRUGI TIPOVI NA FAJLOVI";
		}
		
		echo "<br>" . "<h3>File content: </h3>";
		print $contents;

	  }
	  
	  //vtoriot file
	  echo "<h1>File2:<h1>";
	  if ($_FILES["file2"]["error"] > 0){
		echo "<h3>Error: </h3>" . $_FILES["file2"]["error"] . "<br />";
	  } else {
		echo "<h3>Upload: </h3>" . $_FILES["file2"]["name"] . "<br />";
		echo "<h3>Type: </h3>" . $_FILES["file2"]["type"] . "<br />";
		echo "<h3>Size: </h3>" . ($_FILES["file2"]["size"] / 1024) . " Kb<br />";
		echo "<h3>Stored in: </h3>" . $_FILES["file2"]["tmp_name"];
		
		
		echo "PROVERKI:" . "<br />"; //ovie mozam so SWITCH da gi napravam i treba da gi namestam za info kako da se prikazuva
		if($_FILES["file2"]["type"] == "application/pdf"){	
			echo "PDF E";
			$filename2 = $_FILES["file2"]["tmp_name"]; //ja naoga patekata kaj so e socuvan fajlot
			$handle2 = fopen($filename2, "r"); //go otvara fajlot za da ima pristap do sodrzinata
			$contents2 = fread($handle2, filesize($filename2)); //ja zacuvuva sodrzinata vo promenliva
		}
		else if(contains("document", $_FILES["file2"]["type"])){
			echo "DOC E";
			
			$filename2 = $_FILES["file2"]["tmp_name"];
			$handle2 = fopen($filename2, "r"); 
			$contents2 = fread($handle2, filesize($filename2));
            $contents2 = read_docx($filename2); //preprocesiranje na docx fajlot da se vmetre readable sodrzina vo promenliva		
		}
		else if($_FILES["file2"]["type"] == "text/plain"){
			echo "TXT E";
			$filename2 = $_FILES["file2"]["tmp_name"]; 
			$handle2 = fopen($filename2, "r"); 
			$contents2 = fread($handle2, filesize($filename2)); 
		}
		else{
			echo "NEMAME FUNKCIONALNOST ZA DRUGI TIPOVI NA FAJLOVI";
		}
		
		echo "<br>" . "<h3>File content: </h3>";
		print $contents2;

	  }
	  
		function contains($needle, $haystack){ //da se najde document vo toa ogromnoto ime kaj type 
			return strpos($haystack, $needle) !== false;
		}
		
		function read_docx($filename){ // ja najdov na http://stackoverflow.com/questions/10646445/read-word-document-in-php

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
		echo "<h1>Word statistics of the 2 files and comparing them:<h1>";
		//funkciite moze tuka kako eksterni da gi postavime i za 2ta fajla da gi povikuvame
	?>
	
	<h3>Number of words, no numbers: </h3> 
		<h4 style="color:red;">File1:</h4>
		<?php echo str_word_count($contents); ?>
		<h4 style="color:red;">File2:</h4>
		<?php echo str_word_count($contents2); ?>
	
    <h3>Number of numbers found in the file: </h3> 
		<h4 style="color:red;">File1:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$count_num = 0;
			foreach($split_strings as $str){
				if(is_numeric($str)){
					$count_num += 1;
				}
			}
			echo $count_num;	
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$count_num = 0;
			foreach($split_strings as $str){
				if(is_numeric($str)){
					$count_num += 1;
				}
			}
			echo $count_num;	
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
		
	<h3>Number of words, with numbers(Number of characters): </h3> 
		<h4 style="color:red;">File1:</h4>
		<?php
		    $resultNumAndWords = str_word_count($contents) + $count_num;
			echo $resultNumAndWords;
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
		    $resultNumAndWords = str_word_count($contents2) + $count_num;
			echo $resultNumAndWords;
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
		
	<h3>Reading time:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			if(count($split_strings)<275){
				echo "The file will be read for less than a minute";	
			}
			else if(count($split_strings)==275){
				echo "The file will be read for exactly a minute";	
			}
			else{
				$temp = count($split_strings)/275;
				echo "The file will be read for approximately " . round($temp, 1) . " min";
			}
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			if(count($split_strings)<275){
				echo "The file will be read for less than a minute";	
			}
			else if(count($split_strings)==275){
				echo "The file will be read for exactly a minute";	
			}
			else{
				$temp = count($split_strings)/275;
				echo "The file will be read for approximately " . round($temp, 1) . " min";
			}
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Speaking time:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
				$split_strings = preg_split('/[\ \n\,]+/', $contents);
			if(count($split_strings)<180){
				echo "The file will be spoken for less than a minute";	
			}
			else if(count($split_strings)==180){
				echo "The file will be spoken for exactly a minute";	
			}
			else{
				$temp = count($split_strings)/180;
				echo "The file will be spoken for approximately " . round($temp, 1) . " min";
			}
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
				$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			if(count($split_strings)<180){
				echo "The file will be spoken for less than a minute";	
			}
			else if(count($split_strings)==180){
				echo "The file will be spoken for exactly a minute";	
			}
			else{
				$temp = count($split_strings)/180;
				echo "The file will be spoken for approximately " . round($temp, 1) . " min";
			}
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Short words:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$brojac = 0;
			foreach($split_strings as $el){
				if(strlen($el)<=3)
					$brojac++;
			}
			echo "The number of shorstest words in the file is  " . $brojac;
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$brojac = 0;
			foreach($split_strings as $el){
				if(strlen($el)<=3)
					$brojac++;
			}
			echo "The number of shorstest words in the file is  " . $brojac;
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Long words:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$brojac = 0;
			foreach($split_strings as $el){
				if(strlen($el)>=7)
					$brojac++;
			}
			echo "The number of longest words in the file is " . $brojac;
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$brojac = 0;
			foreach($split_strings as $el){
				if(strlen($el)>=7)
					$brojac++;
			}
			echo "The number of longest words in the file is " . $brojac;
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Number of sentences:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
		
			function countSentences($str){
				return preg_match_all('/[^\s](\.|\!|\?)(?!\w)/',$str,$match);
			}
			
			$res = countSentences($contents);
			echo "The number of sentences in the file is " . $res; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php			
			$res = countSentences($contents2);
			echo "The number of sentences in the file is " . $res; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Number of whitespaces:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$br = count($split_strings) - 1;
			echo "The number of whitespaces in the file is " . $br; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$br = count($split_strings) - 1;
			echo "The number of whitespaces in the file is " . $br; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Number of characters(with whitespaces):</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$strArray = count_chars($contents,0);
			$brojacFile1 = 0;
			foreach ($strArray as $key=>$value){
				$brojacFile1 += $value;
		   }
			
			echo "The number of characters(with whitespaces) in the file is " . $brojacFile1; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
		
			$strArray = count_chars($contents2,0);
			$brojacFile2 = 0;
			foreach ($strArray as $key=>$value){
				$brojacFile2 += $value;
		   }
			
			echo "The number of characters(with whitespaces) in the file is " . $brojacFile2; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Number of characters(without whitespaces):</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$resultNoWhitespacesChars = $brojacFile1 - $br;
			echo "The number of characters(without whitespaces) in the file is " . $resultNoWhitespacesChars; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			
			$resultNoWhitespacesChars = $brojacFile2 - $br;
			echo "The number of characters(without whitespaces) in the file is " . $resultNoWhitespacesChars; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Longest sentence:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$max = strlen($split_strings[0]);
			foreach($split_strings as $el){
					if(strlen($el) > $max)
						$max = strlen($el);
			}
			
			echo "The length of the longest sentence of the file is " . $max; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$max = strlen($split_strings[0]);
			foreach($split_strings as $el){
					if(strlen($el) > $max)
						$max = strlen($el);
			}
			
			echo "The length of the longest sentence of the file is " . $max; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Shortest sentence:</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$min = strlen($split_strings[0]);
			foreach($split_strings as $el){
					if(strlen($el) < $min)
						$min = strlen($el);
			}
			
			echo "The length of the shortest sentence of the file is " . $min; 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$min = strlen($split_strings[0]);
			foreach($split_strings as $el){
					if(strlen($el) < $min)
						$min = strlen($el);
			}
			
			echo "The length of the shortest sentence of the file is " . $min; 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>
	<h3>Average word length: (mislam deka i whitespaces mi broi ovde)</h3>
		<h4 style="color:red;">File1:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents);
			$sum = 0;
			foreach($split_strings as $str){
				$sum += strlen($str);
			}
			$result = $sum/count($split_strings);
			
			echo "The average length of the words in the file is " . round($result, 2); 
		?>
		<h4 style="color:red;">File2:</h4>
		<?php
			
			$split_strings = preg_split('/[\ \n\,]+/', $contents2);
			$sum = 0;
			foreach($split_strings as $str){
				$sum += strlen($str);
			}
			$result = $sum/count($split_strings);
			
			echo "The average length of the words in the file is " . round($result, 2); 
		?>
		<h4 style="color:red; font-weight: bold;">CONCLUSION:</h4>