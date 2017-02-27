<?php
	//data is pulled from command line
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	//Set your log folder here - each log will be recorded as DATE_TIME.txt with the current date and time
	$fileName = "C:\\Users\\awaldman\\desktop\\" . $_GET['date'] . "_" . $_GET['time'] . ".txt";
    $item = $_GET['item_id'];
	
	session_start();
	
	//uses phpish/shopify - available on github, search for it and follow instructions for installation
	// autoloaded for ease of use
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	
	//connect to shopify
	//use your shops information for arguments here
	//SHOPIFY_SHOP is admin address - e.g.: 'store-name.myshopify.com'
	//SHOPIFY_APP_API_KEY and SHOPIFY_APP_PASSWORD are generated when you create a private app in your store
	$shopify = shopify\client('SHOPIFY_SHOP','SHOPIFY_APP_API_KEY','SHOPIFY_APP_PASSWORD',true);
	//create array for variants that will be removed
	$deathRay = array();
	
	// various counters
	$errCnt = 0;
	$dltCnt = 0;
	$variantsInUse = 0;
	$variantsAvailable = 0;
	
	//set dates
	//this app deletes variants that were purchased or in an abandoned cart in the last 4 weeks
	//the time frame can be adjusted with the $d variable
	date_default_timezone_set("America/New_York");	
	$d=strtotime("-4 week");
	$date=date("Y-m-d", $d);
	$todaysDate=date("Y-m-d");
	
	//create a horizontal rule to be used for design purposes in log fileName
	$lineBreak = '';
	for($i = 0; $i < 23; $i++){
		$lineBreak .= '=';
	}
	
	try{
		$file = fopen($fileName,"a");
		//Edit the following to customize the log file header
		fwrite($file, "Item Cleaning - " . $item . PHP_EOL);
		try{
			//pull in items in orders from the time frame to test against
			$orders = $shopify('GET /admin/orders.json?created_at_min=' . $date);
			$ordersArray = array();
			foreach($orders as $o){
				foreach($o['line_items'] as $li){
					array_push($ordersArray, $li['variant_id']);
				}
			}
		}catch(shopify\ApiException $e){
			writeErrorToFile($e,$file);
		}catch(shopify\CurlException $e){
			writeErrorToFile($e,$file);
		}
		
		try{
			//pull in items in abandoned carts from the time frame to test against
			$orders = $shopify('GET /admin/checkouts.json?created_at_min=' . $date);
			$abandonedArray = array();
			foreach($orders as $o){
			 foreach($o['line_items'] as $li){
				 array_push($abandonedArray, $li['variant_id']);
			 }
			}
		}catch(shopify\ApiException $e){
			writeErrorToFile($e,$file);
		}catch(shopify\CurlException $e){
			writeErrorToFile($e,$file);
		}
		
		try{
			// Get your items variants to test for deletion
			$product = $shopify('GET /admin/products/' . $item . '.json');
			$variantsArray = $product['variants'];
			foreach($variantsArray as $v){
				if(testVariant($v['id'],$ordersArray)){
				}elseif(testVariant($v['id'],$abandonedArray)){
				}elseif(substr($v['created_at'],0,10)==$todaysDate){
				}elseif(substr($v['updated_at'],0,10)==$todaysDate){
				}else{
					array_push($deathRay,$v['id']);
				}
			}
		}catch(shopify\ApiException $e){
			writeErrorToFile($e,$file);
		}catch(shopify\CurlException $e){
			writeErrorToFile($e,$file);
		}
		
		fwrite($file,$lineBreak . PHP_EOL);
		
		//if there is nothing to be deleted print message
		if(count($deathRay)==0){
			echo 'No Items To Be Deleted' . PHP_EOL;
			fwrite($file, 'No Items To Be Deleted' . PHP_EOL);
		}
		
		//cycle through and deleted all old variants
		foreach($deathRay as $d){
			$url = 'DELETE /admin/products/' . $item . '/variants/' . $d . '.json';
			try{
				$shopify($url);
				echo $d . ' deleted' . PHP_EOL;
				fwrite($file, $d . ' deleted' . PHP_EOL);
				$dltCnt++;
			}catch(shopify\ApiException $e){
				writeErrorToFile($e,$file);
			}catch(shopify\CurlException $e){
				writeErrorToFile($e,$file);
			}
		}
		
		//print information
		echo 'complete' . PHP_EOL;
		fwrite($file, 'complete' . PHP_EOL);
		$inUse = count($variantsArray) - count($deathRay);
		$available = 100 - $inUse;
		fwrite($file, 'Variants in use: ' . $inUse . PHP_EOL);
		fwrite($file, 'Variants available: ' . $available . PHP_EOL);
		fwrite($file, $lineBreak . PHP_EOL);
		echo 'Variants in use: ' . $inUse . PHP_EOL;
		echo 'Variants available: ' . $available . PHP_EOL;
	}catch(exception $exc){
		echo $exc;
	}
	
	//writes catch errors to file
	// $e - the exception
	// $file - the file to be written to
	function writeErrorToFile($e, $file){
		echo $e;
		fwrite($file,$e . PHP_EOL);
		$GLOBALS['errCnt']++;
	}
	
	//test if variant has been used over the time frame
	// $id - id of the variant to be tested
	// $ray - test type: orders, abandoned carts, etc...
	function testVariant($id,$ray){
		foreach($ray as $i){
			if($i == $id) return true;
		}
		return false;
	}
?>