<?php

require_once 'dbconfig.php';
require 'conf.inc.php';
class USER
{	

	private $dbconnect;
	
	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
    }
	
	public function runQuery($sql)
	{
		$stmt = $this->conn->prepare($sql);
		return $stmt;
	}
	
	public function lasdID()
	{
		$stmt = $this->conn->lastInsertId();
		return $stmt;
	}
	
	public function register($uname,$email,$upass,$code)
	{
		try
		{							
			$password = md5($upass);
			$stmt = $this->conn->prepare("INSERT INTO users(userName,userEmail,userPass,tokenCode) 
			                                             VALUES(:user_name, :user_mail, :user_pass, :active_code)");
			$stmt->bindparam(":user_name",$uname);
			$stmt->bindparam(":user_mail",$email);
			$stmt->bindparam(":user_pass",$password);
			$stmt->bindparam(":active_code",$code);
			$stmt->execute();	
			return $stmt;
		}
		catch(PDOException $ex)
		{
			echo $ex->getMessage();
		}
	}
	
	// public function login($email,$upass)
	// {
	// 	try
	// 	{
	// 		$stmt = $this->conn->prepare("SELECT * FROM users WHERE userEmail=:email_id");
	// 		$stmt->execute(array(":email_id"=>$email));
	// 		$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
			
	// 		if($stmt->rowCount() == 1)
	// 		{
	// 			if($userRow['userStatus']=="Y")
	// 			{
	// 				if($userRow['userPass']==md5($upass))
	// 				{
	// 					$_SESSION['userSession'] = $userRow['userID'];
	// 					return true;
	// 				}
	// 				else
	// 				{
	// 					header("Location: login.php?error");
	// 					exit;
	// 				}
	// 			}
	// 			else
	// 			{
	// 				header("Location: login.php?inactive");
	// 				exit;
	// 			}	
	// 		}
	// 		else
	// 		{
	// 			header("Location: login.php?error");
	// 			exit;
	// 		}		
	// 	}
	// 	catch(PDOException $ex)
	// 	{
	// 		echo $ex->getMessage();
	// 	}
	// }

	public function login($email, $upass)
{
    try
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE userEmail=:email_id");
        $stmt->execute(array(":email_id" => $email));
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() == 1)
        {
            if ($userRow['userStatus'] == "Y")
            {
                if ($userRow['userPass'] == md5($upass))
                {
                    $_SESSION['userSession'] = $userRow['userID'];
                    return true;
                }
                else
                {
                    return false; // Return false for incorrect password
                }
            }
            else
            {
                return false; // Return false for inactive user
            }
        }
        else
        {
            return false; // Return false for user not found
        }
    }
    catch (PDOException $ex)
    {
        echo $ex->getMessage();
    }
}

	
	
	public function is_logged_in()
	{
		if(isset($_SESSION['userSession']))
		{
			return true;
		}
	}
	
	
	public function redirect($url)
	{
		header("Location: $url");
	}
	
	public function logout()
	{
		session_destroy();
		$_SESSION['userSession'] = false;
	}
	
	function send_mail($email,$message,$subject)
    {                       
        require_once('mailer/class.phpmailer.php');
        $mail = new PHPMailer();
        $mail->IsSMTP(); 
        $mail->SMTPDebug = 0; 
        $mail->SMTPAuth = true; 
        $mail->SMTPSecure = "ssl"; 
        $mail->Host = "mail.manliusracingleague.com"; 
        $mail->Port = 465;             
        $mail->AddAddress($email);
        $mail->Username="manliusracingleague@manliusracingleague.com";  
        $mail->Password="XR%15Jvz;LSf";
        $mail->SetFrom('manliusracingleague@manliusracingleague.com','Manlius Racing League');
        $mail->AddReplyTo("manliusracingleague@manliusracingleague.com","Manlius Racing League");
		
		$mail->addBCC("manliusracingleague@gmail.com");
		$mail->addBCC("manliusracingleague@manliusracingleague.com");


		$mail->isHTML(true);
		
        $mail->Subject    = $subject;
        $mail->MsgHTML($message);
        $mail->Send();
    } 
}

// this is to determine if current user is setup as an Admin in the database (yes/no for userAdmin in 'users' table)
function isAdmin($userID) {
  $user_home = new USER();
  $stmt = $user_home->runQuery("SELECT userAdmin FROM users WHERE userID=:uid");
  $stmt->execute(array(":uid" => $userID));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row['userAdmin'] == 'Y';
}
