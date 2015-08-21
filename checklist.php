<?php

$dir = "F:\\recruitement_site\\Output\\";
$solr_service = "http://localhost:8983/";

// Open a directory, and read its contents
$nowTime = (date("F d Y H:i:s A"));
echo "<br><b>Today : $nowTime</b><br>";

if (is_dir($dir)){
  if ($dh = opendir($dir)){
	  $d = 1;
    while (($filename = readdir($dh)) !== false){
		if($filename != "." && $filename != ".." && $filename != "StartUrls")
		{
		echo "<b>".$d.") Filename:" . $dir.$filename . "<br></b>";
		echo "<br>$filename was last modified: " . date ("F d Y H:i:s A", filemtime($dir.$filename))."<br>";
		
		$fileModifiedAt =  date ("F d Y H:i:s.", filemtime($dir.$filename));
		$diffTime = (strtotime($nowTime) - strtotime($fileModifiedAt));
		$diffTimeInDays = $diffTime/(60*60*24);
		//echo "<br>File Modified At: $fileModifiedAt and Diff :".$diffTime." in days: $diffTimeInDays<br>";
		if($diffTimeInDays > 1)
		{
			echo "<br><b><font color='red'>CSV file ".$filename." was not modified yesterday </font></b></br>";
		}
		$d++;

$file = fopen($dir.$filename,"r");
	$company = '';
    $header = NULL;
    $data = array();
	$i = 0;
	$companyNameExists = false;
	
	$columArray = ['JobTitle','JobDescription','CompanyName','DatePosted','JobCategory','City','State','JobDetailUrl','ApplyUrl','Skills'];
	$columExistsArray = [false,false,false,false,false,false,false,false,false,false];
   while (($row = fgetcsv($file)) !== FALSE)
        {			
						
			if($i == 0)
			{	
		
		
				for($c = 0; $c<count($columArray);$c++)
				{
					for($k = 0; $k<count($row);$k++)
					{
						//var_dump($row[$k]);
						if($row[$k] == $columArray[$c])
						{
							$columExistsArray[$c] = $k;
							
							//echo "$columArray[$c] column present in ".$filename." at column ".$k."<br>";
							if($columArray[$c] == 'CompanyName')
							{
									$companyNameExists = true;
									$columnExistsAt = $k;
							}
							break;
						}
					}
				}
			}
				//var_dump($columnExistsAt);					
				$company = $row[$columnExistsAt];
				//echo "<br>Company name: ".$company."<br>";					
			//$company = $row[$columExistsArray[$c]];
			$i++;
		}
		
		fclose($file);
		if($i != 0)
		{
			for($h = 0;$h<count($columExistsArray);$h++)
			{
				if(!$columExistsArray[$h])
				{
					echo "<br><b><font color='red'>column $columArray[$h] is not present in ".$filename."</font></b></br>";
				}
			}
		}															
		if($i == 0)
		{
			echo "<br><b><font color='red'>Data not added in".$filename."</font></b></br>";
		}else if($companyNameExists == false)
			{
				echo "<br><b><font color='red'>CompanyName column not present for ".$filename."</font></b></br>";
			}
			else
			{
				echo "No. of rows in ".$company.": ".($i - 1). "<br>";
				$csv_num_rows = ($i - 1);
				//read solr data
				$company = str_replace(" ","+",$company);
				$company = str_replace("&","%26",$company);
				$response = file_get_contents($solr_service."solr/SynerzipRecruitment/select?q=CompanyName%3A%22".@$company."%22&wt=json&indent=true");
				$res = json_decode($response,true);
				
				echo("Solr service: ".$solr_service."solr/SynerzipRecruitment/select?q=CompanyName%3A%22".@$company."%22&wt=json&indent=true"."<br>");
				//var_dump(($res['response']['numFound']));
				//var_dump($csv_num_rows);
				if(($res['response']['numFound']) == 0)
				{
					echo "<br><b><font color='red'>Records do not exists for ". $filename.". Csv file is not indexed.</font></b></br>";
				}else if(($res['response']['numFound']) != $csv_num_rows)
				{
					echo "<br><b><font color='red'>Records do not match in csv and solr index for ". $filename."</font></b></br>";
				}
			}
			
			$i++;

}
	}
    closedir($dh);
  }
}


?>