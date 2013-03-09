<?php
 error_reporting(E_ALL);
 ini_set("display_errors", 1);

 define("CODE_404","HTTP/1.1 404 Page Not Available");
 define("CODE_400",'HTTP/1.1 400 Bad Request');
 define("CODE_405","HTTP/1.1 405 Method Not Allowed");
 define("CODE_409","HTTP/1.1 409 Conflict");
 define("CODE_200","HTTP/1.1 200 OK");
 define("CODE_201","HTTP/1.1 201 Created");
 define("CODE_204","HTTP/1.1 204 No Content");



 
 include('functions.php');

class Api{

	var $uri;
	var $mehod;
	var $paths;
	

	public function serve()
	{
		$this->uri = $_SERVER['REQUEST_URI'];

	    $this->method = $_SERVER['REQUEST_METHOD'];
		$uris = parse_url($this->uri);
		

	   	$this->paths = explode('/', $uris['path']);


	   	
	   	array_shift($this->paths); // Hack; get rid of initials empty strin

	   	if($this->paths[count($this->paths)-1]=="")
	   	{
	   		array_pop($this->paths);
	   	}

	   	//lets remove the first element as we dont need that, its the preamble to the system

	   	array_shift($this->paths);


	   	//make it all lowercase

	   	for($i =0; $i< count($this->paths);++$i)
	   	{
	   		$this->paths[$i] = strtolower($this->paths[$i]);
		}
		

		switch($this->paths[0])
		{
			case "scripts":
				//now we need to do something based on the number of things in uri
				$this->ScriptUriHandler();
				break;
			case "reviews":
				//do something
				$this->ReviewUriHandler();
				break;
			case "versions":
				$this->VersionUriHandler();
				//do soemthing
				break;
			default:				
				$this->returnErrorHeader(CODE_404);
				die;
				break;
		}
	}
	private function returnErrorHeader($code)
   	{
   		header($code);
   		die;
   	}
	private function VersionUriHandler()
   	{
   		switch(count($this->paths))
		{
			//Example /api/Versions/
			case 1:
				//deal with the type
				switch($this->method)
				{
					case "GET":
						$data = getVersions();
						header('Content-type: application/json');
						echo json_encode($data);
						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);				
						break;
				}
				break;
			//example api/Versions/scriptname
			case 2:
				switch($this->method)
				{
					case "GET":
						//return script if possible
						$scriptName = urldecode($this->paths[1]);
						$data = getVersionsOfScripts($scriptName);
						if($data =='null')
						{
							$this->returnErrorHeader(CODE_404.' Script not found');
						}
						else
						{
							//header('Content-type: application/json');
							echo json_encode($data);
						}
						break;
					case "POST":
						//add the script if possible
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' Data missing');
						//check for data, Name is grabbed from the url.
						if(!isset($data->code) || !isset($data->versionNumber) || !isset($data->versionInfo))
							$this->ReturnErrorHeader(CODE_400.' not all data passed');
						$name = urldecode($this->paths[1]);
						//now we know that it is set we can send the data to the function to add it to the database.
						switch(addVersionToDatabase($data,$name))
						{
							case "no found":
								$this->returnErrorHeader(CODE_404.' Script with name doesnt exist');
								break;
							case "invalid version number":
								$this->returnErrorHeader(CODE_400.' version number invalid/not higher than current');
								break;
							case "success":
								$this->returnErrorHeader(CODE_201);
								break;
							case "not inserted":
								$this->returnErrorHeader(CODE_400.' Could not insert, check data types');
								break;
							default:
								break;
						}
						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);
						break;
				}
				break;
			//example /api/versions/scriptname/versionNum
			case 3:
				switch($this->method)
				{
					case "GET":
						//return script if possible
						$name = urldecode($this->paths[1]);
						$vno = urldecode($this->paths[2]);
						
						$data = getScriptByVersions($name,$vno);
						if($data =='null')
						{
							$this->returnErrorHeader(CODE_404.' Script not found');
						}
						else
						{
							header('Content-type: application/json');
							echo json_encode($data);
						}
						break;
					case "PUT":
						//get the name and vno
						$name = urldecode($this->paths[1]);
						$vno = urldecode($this->paths[2]);
						//get the data
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' Data missing');
						//check for data, Name is grabbed from the url.
						if(!isset($data->code)  || !isset($data->versionInfo))
							$this->ReturnErrorHeader(CODE_400.' not all data passed');
						$name = urldecode($this->paths[1]);
						//now we know that it is set we can send the data to the function to add it to the database.
						switch(updateVersionOfScript($data,$name,$vno))
						{
							case "no script id found":
								$this->returnErrorHeader(CODE_404.' Script with name not found');
								break;
							case "no  version id found":
								$this->returnErrorHeader(CODE_404.' No versions found');
								break;
							case "error":
								$this->returnErrorHeader(CODE_400.' Could not update, check data types');
								break;
							case "success":
								$this->returnErrorHeader(CODE_200.' Updated');
								break;
							default:
								break;
						}
						break;
					case "DELETE":
						//get name and version number
						$name = urldecode($this->paths[1]);
						$vno = urldecode($this->paths[2]);
						switch(deleteVersion($name,$vno))
						{
							case "no version id found":
								$this->returnErrorHeader(CODE_404.' No versions found');
								break;
							case "error":
								$this->returnErrorHeader(CODE_400.' Could not delete');
								break;
							case "success":
								$this->returnErrorHeader(CODE_204.' Deleted');
								break;
							case "not found":
								$this->returnErrorHeader(CODE_404.' Script Not Found');
								break;
							default:
								break;
						}

						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);
						break;
				}
				break;
			default:
				break;
			}

   	}

  

   	private function ReviewUriHandler()
   	{
   		switch(count($this->paths))
		{
			//Example /api/Reviews/
			case 1:
				//deal with the type
				switch($this->method)
				{
					case "GET":
						$data = getReviews();
						header('Content-type: application/json');
						echo json_encode($data);
						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);				
						break;
				}
				break;
			//example api/Reviews/scriptname
			case 2:
				switch($this->method)
				{
					case "GET":
						//return script if possible
						$scriptName = urldecode($this->paths[1]);
						$data = getReviewsOfScript($scriptName);
						if($data =='null')
						{
							$this->returnErrorHeader(CODE_404.' Script not found');
						}
						else
						{
							header('Content-type: application/json');
							echo json_encode($data);
						}
						break;
					case "POST":
						//add the script if possible
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' Data missing');
						//check for data, Name is grabbed from the url.
						if(!isset($data->review) || !isset($data->score) )
							$this->ReturnErrorHeader(CODE_400.' not all data passed');
						if($data->score > 5)
							$this->returnErrorHeader(CODE_400.' score must be between 1 & 5');

						//now we know that it is set we can send the data to the function to add it to the database.
						switch(addReviewToDatabase($data,$name))
						{
							case "no found":
								$this->returnErrorHeader(CODE_404.' Script with name doesnt exist');
								break;
							case "success":
								$this->returnErrorHeader(CODE_201);
								break;
							case "not inserted":
								$this->returnErrorHeader(CODE_400.' Could not insert, check data types');
								break;
							default:
								break;
						}
						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);
						break;
				}
				break;
			//example /api/Reviews/scriptname/reviewid
			case 3:
				switch($this->method)
				{
					case "GET":
						//return script if possible
						$name = urldecode($this->paths[1]);
						$reviewId = urldecode($this->paths[2]);
						$data = getReviewById($name,$reviewId);
						if($data =='null')
						{
							$this->returnErrorHeader(CODE_404.' Script not found');
						}
						else
						{
							header('Content-type: application/json');
							echo json_encode($data);
						}
						break;
					case "PUT":
						//get the name and vno
						$name = urldecode($this->paths[1]);
						$reviewid = urldecode($this->paths[2]);

						//get the data
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' Data missing');
						//check for data, Name is grabbed from the url.
						if(!isset($data->review)  || !isset($data->score))
							$this->ReturnErrorHeader(CODE_400.' not all data passed');
						if($data->score > 5)
							$this->returnErrorHeader(CODE_400.' score must be between 1 & 5');
					
						//now we know that it is set we can send the data to the function to add it to the database.
						switch(updateReview($data,$reviewid))
						{
							case "error":
								$this->returnErrorHeader(CODE_400.' Could not update, check data types');
								break;
							case "success":
								$this->returnErrorHeader(CODE_200.' Updated');
								break;
							default:
								break;
						}
						break;
					case "DELETE":
						//get name and version number
						$name = urldecode($this->paths[1]);
						$reviewid = urldecode($this->paths[2]);
						switch(deleteReview($reviewid))
						{

							case "error":
								$this->returnErrorHeader(CODE_400.' Could not delete');
								break;
							case "success":
								$this->returnErrorHeader(CODE_204.' Deleted');
								break;
							default:
								break;
						}

						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);
						break;
				}
				break;
			default:
				break;
			}
   	}
	private function ScriptUriHandler()
	{

		switch(count($this->paths))
		{
			//Example /api/Scripts/
			case 1:
				//deal with the type
				switch($this->method)
				{
					case "GET":
						
						$data = getscripts();
						header('Content-type: application/json');
						echo json_encode($data);
						break;
					case "POST":
						//put the script if possible
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->returnErrorHeader(CODE_400.' Data not passed');
						//check for name
						if(!isset($data->name) || !isset($data->code) || !isset($data->versionNumber) || !isset($data->versionInfo))
							$this->returnErrorHeader(CODE_400.' Not all data passed');
						//check to make sure v
						//now we know that it is set we can send the data to the function to add it to the database.
						switch(AddScriptToDb($data))
						{
							case "error":
								$this->returnErrorHeader(CODE_409.' Script with that name exists');
								break;
							case "success":
								$this->returnErrorHeader(CODE_201);
								break;
							case "not inserted":
								$this->returnErrorHeader(CODE_400.' Could not insert, check data types, version number must be double');
								break;
							default:
								break;
						}
						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);				
						break;
				}
				break;
			//example api/scripts/scriptname
			case 2:
				switch($this->method)
				{
					case "GET":
						//return script if possible
						$scriptName = urldecode($this->paths[1]);
						$code = GetScriptByName($scriptName);
						if($code =='null')
						{
							$this->returnErrorHeader(CODE_404.' Script not found');
						}
						else
						{
							header('Content-type: application/json');
							echo json_encode($code);
						}
						break;
					case "POST":
						//add the script if possible
						$data = json_decode(file_get_contents('php://input'));
						//we need to do some sanity checks to make sure that the user has added the correct information.
						//data check to make sure there is some....
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' Data missing');
						//check for data, Name is grabbed from the url.
						if(!isset($data->code) || !isset($data->versionNumber) || !isset($data->versionInfo))
							$this->ReturnErrorHeader(CODE_400.' not all data passed');
						$name = urldecode($this->paths[1]);
						//now we know that it is set we can send the data to the function to add it to the database.
						switch(addScriptToDbByName($data,$name))
						{
							case "error":
								$this->returnErrorHeader(CODE_409.' Script with that name exists');
								break;
							case "success":
								$this->returnErrorHeader(CODE_201);
								break;
							case "not inserted":
								$this->returnErrorHeader(CODE_400.' Could not insert, check data types, version number must be double');
								break;
							default:
								break;
						}
						break;
					case "DELETE":
						//delete the script
						//lets get the name of the script
						$name = urldecode($this->paths[1]);
						switch(deleteScript($name))
						{
							case "error":
								$this->returnErrorHeader(CODE_400.' Could not delete');
								break;
							case "success":
								$this->returnErrorHeader(CODE_204.' Deleted');
								break;
							case "not found":
								$this->returnErrorHeader(CODE_404.' Script Not Found');
								break;
							default:
								break;
						}
						break;
					case "PUT":
						//update latest script
						$data = json_decode(file_get_contents('php://input'));
						$name = urldecode($this->paths[1]);
						//sanity checks
						if(is_null($data))
							$this->ReturnErrorHeader(CODE_400.' No data passed');
						//check the contents of the $data
						if(!isset($data->code) || !isset($data->versionNumber) || !isset($data->versionInfo))
							$this->ReturnErrorHeader(CODE_400.' not enough data passed');
						switch(updateScript($data,$name))
						{
							case "no script id found":
								$this->returnErrorHeader(CODE_404.' Script with name not found');
								break;
							case "no  version id found":
								$this->returnErrorHeader(CODE_404.' No versions found');
								break;
							case "error":
								$this->returnErrorHeader(CODE_400.' Could not update, check data types');
								break;
							case "success":
								$this->returnErrorHeader(CODE_200.' Updated');
								break;
							default:
								break;
						}

						break;
					default:
						$this->returnErrorHeader(CODE_405.' The Method '.$this->method.' is not allowed on '.$this->uri);
						break;
				}
				break;
			default:
				break;
			}

	}
}
$server = new Api;
$server->serve();


?>