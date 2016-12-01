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
    </style>
	
	<script type="text/javascript">
		$(document).ready(function() {
			$('#upload').bind("click",function() 
			{ 
				var val = $('#file1').val(); 
				var val2 = $('#file2').val(); 
				
				if(val=='' && val2=='') 
				{ 
					alert("Empty input FILE 1 and FILE 2"); 
				} 
				else if(val=='' && val2!='') 
				{ 
					alert("Empty input FILE 1"); 
				}
				else if(val!='' && val2=='') 
				{ 
					alert("Empty input FILE 2"); 
				}
				else{
					$('#upload').post("upload_a_file_analysis.php");
				}
				return false; 
			}); 
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
        </div>
    </nav>

    <div class="container" style="margin-top: 110px;">
        <div class="row">
		<form action="compare_two_files_analysis.php" method="post" enctype="multipart/form-data">
            <div class="col-sm-12">
                <h2><b>WORD STATISTICS of compared two files</b></h2><br><br>

				
					<div class="col-sm-5" style="background-color: white; padding-top: 30px; padding-bottom: 40px; border: 1px solid gray; border-radius: 8px;">
						<div class="col-sm-3">
							<img src="img/file1.png" class="img-responsive">
						</div>
						<h4>Choose first file:</h4>
						<input type="file" name="file1" id="file1" />
					</div>

					<div class="col-sm-5 col-lg-offset-1" style="background-color: white; padding-top: 30px; padding-bottom: 40px; border: 1px solid gray; border-radius: 8px;">
						<div class="col-sm-3">
							<img src="img/file1.png" class="img-responsive">
						</div>
						<h4>Choose second file:</h4>
						<input type="file" name="file2" id="file2" />
					</div>
				
            </div>

			<div class="col-sm-12 text-center"><br><br><br>
				<!--<button class="btn-info btn-lg" id="upload" type="submit" name="submit">C O M P A R E &thinsp;&thinsp; F I L E S</button>-->
				<input type="submit" name="submit" value="C O M P A R E &thinsp;&thinsp; F I L E S" id="upload"/>
			</div>
		</form>
		</div>
    </div><br><br><br>
    <br><br>

    <div class="navbar navbar-fixed-bottom" style="padding-top: 15px; color: dimgray; margin-bottom: 0px; background-color: black; border-radius: 0px; opacity: 0.8; margin-top: 40px;">
        <p class="text-center">&copy; Copyrights FINKI</p>
    </div>
</body>
</html>
