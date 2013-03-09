<?php

include('scripts.php');
include('versions.php');
include('reviews.php');


function connect()
{
	$mysqli = new mysqli("localhost", "favedcou", "Ethandylan1!", "favedcou_api");
	if ($mysqli->connect_errno) {
	    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	}
	return $mysqli;
}


function getscripts()
{
	//TODO change this query so it gets the most recent version number and average review score
	$sql = "select * from Scripts";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$a = array();
	while($row = mysqli_fetch_assoc($res))
	{
		$s = new Scripts;
		$s->Name = $row['name'];
		array_push($a, $s);
	}
	return $a;
}
function getReviews()
{

	$sql = "select * from Scripts s left join Reviews r on s.scriptid = r.scriptid";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$a = array();
	while($row = mysqli_fetch_assoc($res))
	{
		if(!is_null($row['reviewid']))
		{
			$r = new Reviews;
			$r->ReviewId = $row['reviewid'];
			$r->ScriptName = $row['name'];
			$r->Review = $row['review'];
			$r->Score = $row['score'];
			array_push($a, $r);
		}
		
	}

	return $a;
}

function GetScriptByName($name)
{
	$sql = "select v.code from versions v left join Scripts s on v.scriptid = s.scriptid where s.name = '".$name."' ";
	$con = connect();
	$result = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($result);
	if($row)
		return $row['code'];
	else 
		return "null";

}

function AddScriptToDb($data)
{
	//first need to do some sanity checks lets make sure that th
	$scriptName = $data->name;
	$scriptCode = $data->code;
	$versionInfo = $data->versionInfo;
	$versionNo = $data->versionNumber;

	//first going to check to see if there is a script with this name
	$sql = 'select * from Scripts where name="'.$scriptName.'"';
	
	$result = mysqli_query(connect(),$sql);
	
	$row = mysqli_fetch_assoc($result);
	if($row)
		return "error";
	else
	{
		//if the script doesnt exisit then we need to insert it  this iwll give it a version number of 1.0 also requires some comments!
		$sql = "INSERT INTO Scripts (name) values ('".$scriptName."')";
		$mycon = connect();
		mysqli_query($mycon,$sql);
		$id = mysqli_insert_id($mycon);
		//now to insert the rest of the data to the version table
		$sql = "INSERT INTO versions (scriptid,versioncode,versioninfo,code) values (".$id.",".$versionNo.",'".$versionInfo."','".$scriptCode."')";
		if(mysqli_query($mycon,$sql))
			return "success";
		else 
			return "not inserted";
	}
}
function addScriptToDbByName($data,$name)
{
	//first need to do some sanity checks lets make sure that th
	$scriptName = $name;
	$scriptCode = $data->code;
	$versionInfo = $data->versionInfo;
	$versionNo = $data->versionNumber;

	//first going to check to see if there is a script with this name
	$sql = 'select * from Scripts where name="'.$scriptName.'"';
	
	$result = mysqli_query(connect(),$sql);
	
	$row = mysqli_fetch_assoc($result);
	if($row)
		return "error";
	else
	{
		//if the script doesnt exisit then we need to insert it  this iwll give it a version number of 1.0 also requires some comments!
		$sql = "INSERT INTO Scripts (name) values ('".$scriptName."')";
		$mycon = connect();
		mysqli_query($mycon,$sql);
		$id = mysqli_insert_id($mycon);
		//now to insert the rest of the data to the version table
		$sql = "INSERT INTO versions (scriptid,versioncode,versioninfo,code) values (".$id.",".$versionNo.",'".$versionInfo."','".$scriptCode."')";
		if(mysqli_query($mycon,$sql))
			return "success";
		else
			return "not inserted";
	}
}
function deleteVersion($name,$vno)
{
	//first we need to get the id of the script
	$sql = "select scriptid from Scripts where name = '".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$id = $row['scriptid'];

	//now the version id of the version number
	$sql ="select versionid from versions where versioncode =".$versionNo." and scriptid = ".$scriptid;
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		return "no version id found";
	$versionid = $row['versionid'];

	//now to delete!
	$sql = "DELETE FROM versions where scriptid=".$id." and versionid = ".$versionid;
	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';

}
function deleteScript($name)
{
	//first we need to get the id of the script
	$sql = "select scriptid from Scripts where name = '".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$id = $row['scriptid'];

	//now we have the id we can go ahead and remove all traces from the database, this includes reviews and versions.
	//versions.
	$sql = "DELETE FROM versions where scriptid = ".$id;
	if(!mysqli_query($con,$sql))
		return 'error';
	//review table
	$sql = "DELETE FROM Reviews where scriptid = ".$id;
	if(!mysqli_query($con,$sql))
		return 'error';
	//scripts table
	$sql = "DELETE FROM Scripts where scriptid = ".$id;
	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';


}
function updateScript($data,$name)
{
	$scriptName = $name;
	$scriptCode = $data->code;
	$versionInfo = $data->versionInfo;
	$versionNo = $data->versionNumber;
	//first get the id of the script

	$sql = "select scriptid from Scripts where name ='".$scriptName."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		return "no script id found";
	$scriptid = $row['scriptid'];

	//now we need to up date the script where the version id is correct and also the script id.

	$sql ="select versionid from versions where versioncode =".$versionNo." and scriptid = ".$scriptid;
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		return "no  version id found";
	$versionid = $row['versionid'];

	// now we check to see if the versioninfo has been updated or not, or wether it is just the code itself.
	if($versionInfo != '')
	{
		$sql = 'UPDATE versions set code = "'.$scriptCode.'", versioninfo = "'.$versionInfo.'" where versionid = '.$versionid;
		
	}
	else
	{
		$sql = 'UPDATE versions set (code = "'.$scriptCode.'") where versionid = '.$versionid;	
	}

	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';


}
function getVersions()
{

	$sql = "select * from Scripts s left join versions v on v.scriptid = s.scriptid";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$a = array();
	while($row = mysqli_fetch_assoc($res))
	{
		$v = new Versions;
		$v->ScriptName = $row['name'];
		$v->ScriptCode = $row['code'];
		$v->VersionNumber = $row['versioncode'];
		$v->VersionInfo = $row['versioninfo'];
		array_push($a, $v);
	}

	return $a;
}
function getScriptByVersions($name,$vno)
{
	//get script id first
	$sql = "select scriptid from Scripts where name = '".$name."'";
	
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$scriptid = $row['scriptid'];

	$sql = "select code from versions where versioncode = ".$vno." and scriptid = ".$scriptid;
	$result = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($result);
	if($row)
		return $row['code'];
	else 
		return "null";
}
function getReviewById($name,$reviewid)
{

	$sql = "select * from Reviews where reviewid = ".$reviewid;
	$con = connect();
	$result = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($result);
	if($row)
		return $row['review'].' Score = '.$row['score'];
	else 
		return "null";
}

function getVersionsOfScripts($name)
{

	$sql = "select * from Scripts s left join versions v on v.scriptid = s.scriptid where s.name ='".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$a = array();
	while($row = mysqli_fetch_assoc($res))
	{
		$v = new Versions;
		$v->ScriptName = $row['name'];
		$v->ScriptCode = $row['code'];
		$v->VersionNumber = $row['versioncode'];
		$v->VersionInfo = $row['versioninfo'];
		array_push($a, $v);
	}

	return $a;

}
function getReviewsOfScript($scriptName)
{

	$sql = "select * from Scripts s left join Reviews r on s.scriptid = r.scriptid where s.name='".$scriptName."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$a = array();
	while($row = mysqli_fetch_assoc($res))
	{
		$r = new Reviews;
		$r->ReviewId = $row['reviewid'];
		$r->ScriptName = $row['name'];
		$r->Review = $row['review'];
		$r->Score = $row['score'];
		array_push($a, $r);
	}

	return $a;

}

function addVersionToDatabase($data,$name)
{
	//first need to do some sanity checks lets make sure that th
	$scriptName = $name;
	$scriptCode = $data->code;
	$versionInfo = $data->versionInfo;
	$versionNo = $data->versionNumber;

	//first need the script id for the name
	$sql = "select scriptid from Scripts where name = '".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$scriptid = $row['scriptid'];

	//first lets get the current max version id to see if it is smaller than new one

	$sql = "select max(versioncode) as versioncode from versions where scriptid = ".$scriptid;
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		$maxvno = 0;
	else
		$maxvno = $row['versioncode'];

	if($versionNo <= $maxvno)
		return 'invalid version number';

	//now to insert the rest of the data to the version table
	$sql = "INSERT INTO versions (scriptid,versioncode,versioninfo,code) values (".$scriptid.",".$versionNo.",'".$versionInfo."','".$scriptCode."')";
	if(mysqli_query($con,$sql))
		return "success";
	else
		return "not inserted";
	
}
function addReviewToDatabase($data,$name)
{
	//first need to do some sanity checks lets make sure that th
	$scriptName = $name;
	$review = $data->review;
	$score = $data->score;

	//first need the script id for the name
	$sql = "select scriptid from Scripts where name = '".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$scriptid = $row['scriptid'];


	//now to insert the rest of the data to the version table
	$sql = "INSERT INTO Reviews (scriptid,review,score) values (".$scriptid.",'".$review."',".$score.")";
	if(mysqli_query($mycon,$sql))
		return "success";
	else
		return "not inserted";
}
function updateVersionOfScript($data,$name,$vno)
{
	$scriptName = $name;
	$scriptCode = $data->code;
	$versionInfo = $data->versionInfo;
	$versionNo = $vno;

	//again first lets get the scriptid
	$sql = "select scriptid from Scripts where name = '".$name."'";
	$con = connect();
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row['scriptid'])
		return "not found";
	$scriptid = $row['scriptid'];

	//now the version id

	$sql ="select versionid from versions where versioncode =".$versionNo." and scriptid = ".$scriptid;
	$res = mysqli_query($con,$sql);
	$row = mysqli_fetch_assoc($res);
	if(!$row)
		return "no  version id found";
	$versionid = $row['versionid'];

		// now we check to see if the versioninfo has been updated or not, or wether it is just the code itself.
	if($versionInfo != '')
	{
		$sql = 'UPDATE versions set code = "'.$scriptCode.'", versioninfo = "'.$versionInfo.'" where versionid = '.$versionid;
		
	}
	else
	{
		$sql = 'UPDATE versions set (code = "'.$scriptCode.'") where versionid = '.$versionid;	
	}

	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';

}
function updateReview($data,$reviewid)
{
	$review = $data->review;
	$score = $data->score;

	$sql = "UPDATE Reviews SET (review = '".$review."', score=".$score.") where reviewid = ".$reviewid;
	$con = connect();
	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';
}
function deleteReview($reviewid)
{
	$sql = "DELETE FROM Reviews WHERE reviewid = ".$reviewid;
	$con = connect();
	if(!mysqli_query($con,$sql))
		return 'error';

	return 'success';
}
?>