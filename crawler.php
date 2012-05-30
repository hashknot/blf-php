<?php

/****** Initialize and Includes *******/
set_time_limit(0);
ob_start();
require('fpdf/fpdf.php');
require('mysqldb.php');
define('MAX',100);				//Maximum Number of Pages to be Crawled
/*************************************/


/******** MAIN **********/

	/****** Read from the Post Request *****/
		$url=$_POST['url'];
		$email=$_POST['email']; 
		$depth = $_POST['depthlevel'];
		$type = $_POST['type'];
		$format = $_POST['forma'];
	/***************************************/


	//Create an oject of class crawler
	$obj = new crawler
	(
	$url		//URL to be crawled
	,$email		//Email
	,$format	//Just put the initials of each Status COde you want to be considered broken i.e 3 for 30X, '45' for 40X,50X and so on
	,$depth		//MaxDepth Level			
	,$type		//Output Format. 1 - PDF, 2 - HTML, 3 - CSV
	);

	//Configure MySQL. Use configdb.sql file for setting up the Database and Table.
	$obj->config(
	'localhost'				//Server
	,'root'					//Username		
	,''						//Password
	,'brokenlinksfinder'	//Database
	,'requests'				//Table
	);


	//Start the Crawling
	$obj->start();
	
/****** END OF MAIN ******/


/*************** FUNCTIONs and CLASS Definitions **************/
function displayMsg($endMessage)
{
	ignore_user_abort(true); 
	set_time_limit(0); 
	header("Connection: close"); 
	header("Content-Length: ".strlen($endMessage)); 
	echo $endMessage; 
	force_flush();
}

function force_flush() 
{
    echo "\n\n<!-- Deal with browser-related buffering by sending some incompressible strings -->\n\n";

    for ( $i = 0; $i < 5; $i++ )
        echo "<!-- abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono -->\n\n";

    while ( ob_get_level() )
        ob_end_flush();

    @ob_flush();
    @flush();
}


class crawler
{
	private $BrokenLinks = array();
	private $URL,$BrokenCount;
	private $BrokenLinkCodes;
	private $Email;
	private $Domain;
	private $visited = array();
	private $rel_links = array();
	private $extrn_links = array();					//Maintains a list of links pointing to external domain.
	private $pdf;
	private $MyCon;
	private $TransID;
	private $ErrCode;
	private $PDFPath;
	private $MaxDepth;
	private $OpFmt;
	private $FileName;
	private $CrawlCount;
	
	function __construct($url,$email,$brokencodes='45',$maxd=5,$format=1)
	{
		$this->progress = 0;
		$this->BrokenCount = 0;
		$this->successful = false;
		$this->ErrCode = 0;
		$this->URL = $url;
		$this->Email = $email;
		$this->Domain = parse_url($url, PHP_URL_HOST);
		$this->pdf = new PDF();
		$this->pdf->AliasNbPages();
		$this->BrokenLinkCodes = empty($brokencodes)?'45':$brokencodes;
		$this->MaxDepth = $maxd;
		$this->OpFmt = $format;
		$this->MyCon = new mysqldb();
	}
	

	public function start()
	{
		displayMsg('<div style="line-height:35px;width:inherit;text-align:center">An email will be sent to </div><b style="line-height:40px;display:block;font-size:18px;width:inherit;overflow:hidden;text-align:center">'.$this->Email.'</b>');	//Display message but continue processing in the background.
		
		$this->storeBefore();
		switch($this->OpFmt)
		{
			case 1:
				$this->FileName = 'results/'.$this->TransID.'.pdf';
				$this->pdf->AddPage();
				$this->pdf->SetY(50);
				break;
				
			case 2:
				$this->FileName = 'results/'.$this->TransID.'.htm';
				$fp = @fopen($this->FileName,'w');
				if($fp===false)
					return;
{//Writing in file over here.Dont make *ANY* changes in the HTML. The BYTES have been calibrated.
fwrite($fp,
'<html>
<head>
<title>Broken Link Finder | Report</title>
<meta charset="utf-8">
<meta name="authors" content="JAKWorks">
<meta name="keywords" content="Broken Link Finder">
<style type="text/css">
.page td{padding: 5px;font-size:17px;font-weight:bold;}
.working td{font-size:14px;font-weight:bold;padding: 10px 0 10px 10px;}
.broken td{padding: 15px 0 10px 10px;font-size:14px;font-weight:bold;}
.col1{width:100px;text-align:center;font-size:13px;padding:4px}
.col2{font-size:13px;}
table{margin:30px 0 30px 0}
#container{text-align:left;background:#fff;border:1px solid #ccc;-webkit-box-shadow:rgba(0,0,0,0.2) 0px 0px 5px;-moz-box-shadow:rgba(0,0,0,0.2) 0px 0px 5px;-o-box-shadow:rgba(0,0,0,0.2) 0px 0px 5px;box-shadow:rgba(0,0,0,0.2) 0px 0px 5px}#container{margin:30px auto 30px auto;width:440px}
.content{margin:40px 10px 40px 60px;padding:0 0 20px 0;position:relative;font-family:"Lucida Grande","Lucida Sans Unicode", Tahoma, sans-serif;letter-spacing:.01em}
</style>
</head>
<body id="public" >
	<div id="container" class="ltr" style="width:90%">
		<div class="content" >
		
			<h2>Broken Links Finder | Report</h2>
						
			<h3>Statistics</h3>
			<table>
			<tr><td><h4>Pages Crawled</h4></td><td><h4>:&nbsp;&nbsp;   </h4></td></tr>
			<tr><td><h4>Broken Links Found&nbsp;&nbsp;</h4></td><td><h4>:&nbsp;&nbsp;     </h4></td></tr>
			</table>');
			fclose($fp);
}

				break;
				
			case 3:
				$this->FileName = 'results/'.$this->TransID.'.csv';
				$fp = @fopen($this->FileName,'w');
				if($fp===false)
					return;
				fputcsv($fp,array('','Broken Links Finder'));
				fputcsv($fp,array('',''));
				fputcsv($fp,array('','Statistics'));
				fputcsv($fp,array('Pages Crawled','    '));//60-4+1(for ")
				fputcsv($fp,array('Broken Links','      '));//83-5+1(for ")
				break;
				
			default:
				return;
		}			
		$this->crawl($this->URL);
		
		$this->storeAfter();
		
		
		
		$this->genReport();
		//$this->pdf->Output();  //Displays the output on the screen if PDF
		
		
		$this->sendMail();
	}
	
	private function genReport()
	{
		switch($this->OpFmt)
		{
			case 1:
				if($this->BrokenCount)
				{
					$this->pdf->Ln(12);
					$this->pdf->SetFont('Arial','BI',13);
					$this->pdf->Cell(0,11,'A Complete list of Broken Links : ',0,1);
					$this->pdf->SetFont('Arial','B',10);
					$this->pdf->Ln(1.5);
					$this->pdf->Cell(20,7,'HTTP',0,0,'C');
					$this->pdf->SetFont('Arial','B',10);
					$this->pdf->Cell(0,7,'   Link',0,1);
					$this->pdf->SetFont('Times','',10);
					foreach($this->BrokenLinks as $temp)
					{
						$this->pdf->Cell(20,4,$temp[1],0,0,'C');
						$this->pdf->Cell(0,4,$temp[0],0,1);
					}
					$last = $this->pdf->PageNo();
				}
				$this->pdf->page = 1;
				$this->pdf->SetY(22);
				$this->pdf->SetFont('Arial','BI',13);
				$this->pdf->Cell(0,11,'Statistics : ',0,1);
				
				$this->pdf->SetFont('Arial','B',10);
				$this->pdf->Cell(40,5,"Pages Crawled");
				$this->pdf->SetFont('Arial','I',10);
				$this->pdf->Cell(40,5,' :-   '.$this->CrawlCount,0,1);
					
				$this->pdf->SetFont('Arial','B',10);
				$this->pdf->Cell(40,5,"Broken Links Found");
				$this->pdf->SetFont('Arial','I',10);
				$this->pdf->Cell(40,5,' :-   '.$this->BrokenCount,0,1);
				
				$this->pdf->page = $last;
					
				//$this->TransID = mt_rand(100000,999999);
				$this->pdf->Output('results/'.$this->TransID.'.pdf','F');
				break;
					
			case 2:
				$fp = fopen($this->FileName,'a');
				if($this->BrokenCount)
				{
					fwrite($fp,'
			<table>
				<tbody>
					<tr class="page"><td colspan=2>Complete List of Broken Links</td></tr>
						<tr><td class="col1"><b>HTTP<b></td><td class="col2"><b>Link</b></td></tr>
');
					
					foreach($this->BrokenLinks as $temp)
					{
						fwrite($fp,'
						<tr><td class="col1">'.$temp[1].'</td><td class="col2">'.$temp[0].'</td></tr>');
					}	
				}
				fwrite($fp,'
				</tbody>
			</table>
		</div> 
	</div>
</body>
</html>');
				fclose($fp);
				$fp = fopen($this->FileName,'r+');
				rewind($fp);
				fseek($fp,1247);
				fwrite($fp,$this->CrawlCount,3);
				fseek($fp,1343);
				fwrite($fp,$this->BrokenCount,5);
				fclose($fp);
				break;
					
			case 3:
				$fp = fopen($this->FileName,'a');
				if($this->BrokenCount)
				{
					fputcsv($fp,array('',''));
					fputcsv($fp,array('',''));
					fputcsv($fp,array('',''));
					fputcsv($fp,array('',''));
					fputcsv($fp,array('',''));
					fputcsv($fp,array('',''));
					fputcsv($fp,array('','All Broken Links'));
					fputcsv($fp,array('HTTP','Link'));
					foreach($this->BrokenLinks as $temp)
						fputcsv($fp,array($temp[1],$temp[0]));
				}
				fclose($fp);
				$fp = fopen($this->FileName,'r+');
				rewind($fp);
				fseek($fp,54);
				fwrite($fp,$this->CrawlCount,3);
				fseek($fp,76);
				fwrite($fp,$this->BrokenCount,5);
				fclose($fp);
				break;
					
			default:
				return;
		}
	}
	
	private function crawl()
	{
		
		if((substr($this->URL,strlen($this->URL)-1)!='/') && (strpos(substr($this->URL,strrpos($this->URL,'/')),'.') === false))	//Handle trailing / problem
					$this->URL.='/';
		
		$docurl = $this->URL;
		
		switch($this->OpFmt)
		{
			case 1:break;
						
			case 2:
			case 3:
				$fp = fopen($this->FileName,'a');
				break;
			
			default:
				return;
		}
		
		$i=0;
		$j=1;
		$end=$j;
		$level=0;
		$this->visited[$docurl] = 0;
		while($level<$this->MaxDepth && $i<$end && $i<=MAX)
		{
			while($i<$j && $i<=MAX)
			{
				$working = array();
				$notworking=array();
				$docurl = key(array_slice($this->visited,$i,1,true));
				$dom = new DOMDocument;
				libxml_use_internal_errors(true);
				$load = @$dom->loadHTMLFile($docurl);
				
				if($load == false)		//Return if the current URL couldnt be opened.
					return;
				
				if(!($loc = strrpos($docurl,'/',7)))	//Find the last occurence of '/'
					$root = $docurl;					//If '/' not found after 'http://' take the current domain as root
				else
					$root = substr($docurl,0,$loc);		//Else take the substring uptil(not including) the last occurence of '/' as root
				
				$xpath = new DOMXPath($dom);
				$aTag = $xpath->query('//a[@href]');
				
				$this->visited[$docurl] = 1;		//Maintains a list of Crawled pages.
				
				$dir = $root;//maintain a backup of root as it may change in recursive exen
				
				switch($this->OpFmt)
				{
					case 1:
						$this->pdf->Ln(12);
						$this->pdf->SetFont('Arial','BI',13);
						$this->pdf->Cell(0,11,'Page : '.$docurl,0,1);
						break;
						
					case 2:
						fwrite($fp,'
			<table>
				<tbody>
					<tr class="page"><td>Page :	</td><td>'.$docurl.'</td></tr>');
						break;
						
					case 3:
						fputcsv($fp,array('',''));
						fputcsv($fp,array('',''));
						fputcsv($fp,array('',''));
						fputcsv($fp,array('Page',$docurl));
						break;
				}
				//$this->pdf->Ln(25);
				
				foreach($aTag as $url)
				{
					$orglink = $link = $url->getAttribute('href');
					
					if(substr($link,0,1) == '#')		//If the link starts with #, ignore and get the next link
						continue;
					
					$root=$dir;							//Restore the root of the page being crawled
					
					if(($domain = parse_url($link, PHP_URL_HOST))=='')	//Check if the link is relative. If yes the next line
					{
						if(strcasecmp(substr($link,0,7),'mailto:') == 0)	//exclude links containing 'mailto:*'
							continue;
						if(substr($link,0,1) == '/')	//If the link starts with '/',
							$root = 'http://'.$this->Domain; //THEN set root = current domain 
						else if(substr($link,0,2) == '..')	//Else handle the '../' paths(if present)
						{
							//$link = parse_url($link, PHP_URL_PATH);
							while(substr($link,0,2) == '..')	//For every ../ occurence at the start
							{
								//echo $link.'<br />'.$root.'<br />';
								$link=preg_replace("/^(..\/)/","",$link,1);	//Remove ../ at the start
								
								$root = str_split($root,strrpos($root,'/'));	//Move root one level 
								$root = $root[0];								//up
								
								
								//echo $link.'<br />'.$root.'<br />';
								//exit;
							}
							if(substr_count($root,'/',4) <= 1)					//Incase of excessive '../',ignore and get the next link
								continue;
							$root.='/';
						}
						else
							$root.='/';
							
						if((substr($link,strlen($link)-1)!='/') && (strpos(substr($link,strrpos($link,'/')),'.') === false))
							$link.='/';
					}
					else if($domain==$this->Domain)					//if complete path of the same domain is given
					{
						$root='';											//no need to consider the root as we have the complete path
						$rel_link = parse_url($link,PHP_URL_PATH);
						if((substr($link,strlen($link)-1)!='/') && (strpos(substr($rel_link,strrpos($rel_link,'/')),'.') === false))
								$link.='/';
					}	
					else													//the path is from another domain.
					{
						if(!array_key_exists($link,$this->extrn_links))
						{
							$result = $this->extrn_links[$link] = $this->check($link);
							if(!$result[0])
							{
								$this->BrokenLinks[$this->BrokenCount++] = array($link,$result[1]);
								$notworking[$link]=$result[1];
							}
							else
								$working[$link]=$result[1];					
						}
						else
						{
							$result=$this->extrn_links[$link];
							if($result[0])
								$working[$link]=$result[1];
							else
								$notworking[$link]=$result[1];
						}
						continue;
					}
					
					if(!(($loc=strpos($link,'#')) === false))				//To handle '#' in the link
					{
						$link = str_split($link,$loc);		//to truncate the path starting from #
						$link=$link[0];
					}
					
					if(!array_key_exists($root.$link,$this->rel_links))	//If the URL hasnt been checked then
					{
						$result = $this->rel_links[$root.$link]=$this->check($root.$link);	//Add to the list of local_links,global href and store the link's working status
						if($result[0])
						{
							$working[$orglink]=$result[1];
							$this->visited[$root.$link]=0;	//add to global visited array along with its state to NOT_CRAWLED i.e. 0
							$end++;
						}
						else
						{
							$notworking[$orglink]=$result[1];
							$this->BrokenLinks[$this->BrokenCount++] = array($root.$link,$result[1]);
						}
					}
					else
					{
						if( ($result = $this->rel_links[$root.$link]) && $result[0])				//If the link is working then add it to the 
							$working[$orglink]=$result[1];
						else
							$notworking[$orglink]=$result[1];
					}
				}
				
				switch($this->OpFmt)
				{
					case 1:
						$this->pdf->SetFont('Arial','B',12);
						$this->pdf->Cell(0,5,'Working Links :- ',0,1);
						$this->pdf->SetFont('Times','',10);
						break;
						
					case 2:
						fwrite($fp,'
						<tr class="working"><td colspan=2>Working Links:-</td></tr>');
						break;
						
					case 3:
						fputcsv($fp,array('',''));
						fputcsv($fp,array('Working Links',''));
						break;
				}
				
				
				if(count($working))
				{
					switch($this->OpFmt)
					{
						case 1:
							$this->pdf->SetFont('Arial','B',10);
							$this->pdf->Ln(1.5);
							$this->pdf->Cell(20,7,'HTTP',0,0,'C');
							$this->pdf->SetFont('Arial','B',10);
							$this->pdf->Cell(0,7,'   Link',0,1);
							$this->pdf->SetFont('Times','',10);
							foreach($working as $key => $temp)
							{
								$this->pdf->Cell(20,4,$temp,0,0,'C');
								$this->pdf->Cell(0,4,$key,0,1);
							}
							break;
							
						case 2:
							fwrite($fp,'
							<tr><td class="col1"><b>HTTP<b></td><td class="col2"><b>Link</b></td></tr>');
							foreach($working as $key => $temp)
							{
								fwrite($fp,'
							<tr><td class="col1">'.$temp.'</td><td class="col2">'.$key.'</td></tr>');
							}
							break;
							
						case 3:
							fputcsv($fp,array('HTTP','Link'));
							foreach($working as $key => $temp)
							{
								fputcsv($fp,array($temp,$key));
							}
							break;
					}
					
					
				}
				else
					switch($this->OpFmt)
					{
						case 1:
							$this->pdf->Cell(0,4,'None',0,1);
							break;
							
						case 2:
							fwrite($fp,'
						<tr><td class="col1"></td><td class="col2">None</td></tr>');
							break;
							
						case 3:
							fputcsv($fp,array('None',''));
							break;
					}
				
				switch($this->OpFmt)
				{
					case 1:
						$this->pdf->Ln(7);
						$this->pdf->SetFont('Arial','B',12);
						$this->pdf->Cell(0,5,'Broken Links :- ',0,1);
						$this->pdf->SetFont('Times','',10);
						break;
						
					case 2:
						fwrite($fp,'
						<tr class="broken"><td colspan=2>Broken Links:-</td></tr>');
						break;
						
					case 3:
						fputcsv($fp,array('',''));
						fputcsv($fp,array('Broken Links',''));
						break;
				}
				
				
				if(count($notworking))
				{
					switch($this->OpFmt)
					{
						case 1:
							$this->pdf->SetFont('Arial','B',10);
							$this->pdf->Ln(1.5);
							$this->pdf->Cell(20,7,'HTTP',0,0,'C');
							$this->pdf->SetFont('Arial','B',10);
							$this->pdf->Cell(0,7,'   Link',0,1);
							$this->pdf->SetFont('Times','',10);
							foreach($notworking as $key => $temp)
							{
								$this->pdf->Cell(20,4,$temp,0,0,'C');
								$this->pdf->Cell(0,4,$key,0,1);
							}
							break;
							
						case 2:
							fwrite($fp,'
							<tr><td class="col1"><b>HTTP<b></td><td class="col2"><b>Link</b></td></tr>');
							foreach($notworking as $key => $temp)
							{
								fwrite($fp,'
							<tr><td class="col1">'.$temp.'</td><td class="col2">'.$key.'</td></tr>');
							}
							break;
							
						case 3:
							fputcsv($fp,array('HTTP','Link'));
							foreach($notworking as $key => $temp)
							{
								fputcsv($fp,array($temp,$key));
							}
							break;
					}
				}
				else
					switch($this->OpFmt)
					{
						case 1:
							$this->pdf->Cell(0,4,'None',0,1);
							break;
							
						case 2:
							fwrite($fp,'
						<tr><td class="col1"></td><td class="col2">None</td></tr>');
							break;
							
						case 3:
							fputcsv($fp,array('None',''));
							break;
					}
				$i++;
				switch($this->OpFmt)
				{
					case 2:
						fwrite($fp,'
				</tbody>
			</table>');
						break;
				}
				unset($working);
				unset($notworking);
			}
			
			$level++;
			$j=$end;
		}
		$this->CrawlCount = $i;
		switch($this->OpFmt)
		{
			case 2:
			case 3:
				fclose($fp);
		}
	}

	
	private function check($url)
	{

		$host = parse_url($url, PHP_URL_HOST);
		$path = parse_url($url, PHP_URL_PATH);
		
		if(empty($host))
			$host=$this->Domain;
		if (empty($path)) 
			$path = "/";
		
		if(substr($path,0,1) != '/')
			$path = '/'.$path;
		
		$header  = "HEAD $path HTTP/1.1\r\n"
				  ."Host: $host\r\n\r\n";
		
		$req = @fsockopen($host, 80, $errno, $errmsg, 20);
		
		if (!$req)
			return false;
	   
		fwrite($req, $header);
		$response = @fgets($req, 4096);
		fclose($req);
		
		sscanf(substr($response,9,3),"%d",$status);
		
		$result = array();
		$result[1] = $status;
		$result[0] = (strstr($this->BrokenLinkCodes,substr($status,0,1))===false)?true:false;
		return $result;
	}
	
	public function config($server,$user,$pass,$db,$table)
	{
		$this->MyCon->server = $server;
		$this->MyCon->username = $user;
		$this->MyCon->password = $pass;
		$this->MyCon->dbname = $db;
		$this->MyCon->table = $table;
	}

	private function storeBefore()
	{
		$StartTime = date('Y-m-d H:i:s');
		$IP = $_SERVER['REMOTE_ADDR'];
		$this->MyCon->connect();
		$this->MyCon->values = "'','".$IP."', '".$this->URL."', '".$this->Email."', '".$StartTime."', ''";
		$this->MyCon->insert();
		$this->TransID = mysql_insert_id($this->MyCon->link);
	}
	
	private function storeAfter()
	{
		$EndTime = date('Y-m-d H:i:s');
		$this->MyCon->fields = "Endtime = '".$EndTime."'";
		$this->MyCon->where = "Id = '".$this->TransID."'";
		$this->MyCon->update();
	}
	
	private function sendMail($error = false,$errmsg = '')
	{
		$fileatt = $this->FileName; // Path to the file 
		switch($this->OpFmt)
		{
			case 1:
				$fileatt_type = "application/pdf"; // File Type 
				$fileatt_name = "BrokenLinksReport.pdf"; 
				break;
				
			case 2:
				$fileatt_type = "text/html"; // File Type 
				$fileatt_name = "BrokenLinksReport.htm";
				break;
			
			case 3:
				$fileatt_type = "text/csv"; // File Type 
				$fileatt_name = "BrokenLinksReport.csv";
				break;
				
			default:
				return;
		}
		
		$email_from = "Report@BrokenLinksFinder.com"; // Who the email is from 
		$email_subject = "Broken Links Finder"; // The Subject of the email 
		$email_message = "Thanks for visiting BrokenLinksFinder.com!\r\nHere is the Report Generated.\r\n";
		$email_message .= "Thanks for visiting."; // Message that the email has in it
		
		$email_to = $this->Email; // Who the email is to
	
		$headers = "From: ".$email_from;
	
		$file = fopen($fileatt,'rb'); 
		$data = fread($file,filesize($fileatt)); 
		fclose($file);
		
		$semi_rand = md5(time()); 
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

		$headers .= "\nMIME-Version: 1.0\n" . 
		"Content-Type: multipart/mixed;\n" . 
		" boundary=\"{$mime_boundary}\"";

		$email_message .= "This is a multi-part message in MIME format.\n\n" . 
		"--{$mime_boundary}\n" . 
		"Content-Type:text/html; charset=\"iso-8859-1\"\n" . 
		"Content-Transfer-Encoding: 7bit\n\n" . 
		$email_message .= "\n\n";

		$data = chunk_split(base64_encode($data));

		$email_message .= "--{$mime_boundary}\n" . 
		"Content-Type: {$fileatt_type};\n" . 
		" name=\"{$fileatt_name}\"\n" . 
		//"Content-Disposition: attachment;\n" . 
		//" filename=\"{$fileatt_name}\"\n" . 
		"Content-Transfer-Encoding: base64\n\n" . 
		$data .= "\n\n"."--{$mime_boundary}--\n";

		$ok = @mail($email_to, $email_subject, $email_message, $headers);
	}
}
?>