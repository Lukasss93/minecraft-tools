<?php
namespace MinecraftTools;

class mcpremium
{
	const gioco="Minecraft";
	const versione=12;
	const sito="https://authserver.mojang.com/authenticate";
	
	private $premium=false;
	private $correct_username=null;
	private $uuid=null;
	private $error=null;
	private $raw=null;
	
	public function __construct($username,$password){
	
		$data = sprintf('{"agent": {"name": "'.self::gioco.'","version": '.self::versione.'},"username": "%s","password": "%s"}', $username, $password);
		$ch = curl_init(self::sito);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if(!($result = curl_exec($ch)))
		{
			echo('CURL ERROR: '.curl_error($ch));
		}
		curl_close($ch);
		
		//raw output
		$this->raw=@$result;
	
		//json decode output
		$json = json_decode($result,true);
	
		//errors
		$this->error = @$json['errorMessage'];
		if($this->error==null)
		{
			$this->error=null;
		}
		
		//registered user but not premium
		if(array_key_exists("selectedProfile",$json)==false && $this->error==null)
		{
			$this->error = "Your account is not premium.";
			$this->premium=false;
		}
		else
		{
			//correct username
			$this->correct_username=@$json['selectedProfile']['name'];
			if($this->correct_username==null)
			{
				$this->correct_username=null;
			}
			//user uuid
			$this->uuid=@$json['selectedProfile']['id'];
			if($this->uuid==null)
			{
				$this->uuid=null;
			}
			
			//check premium
			if($this->uuid==null && $this->correct_username==null)
			{
				$this->premium=false;
			}
			else
			{
				$this->premium=true;
			}
		
		}
	}

	public function isPremium()
	{
		return $this->premium;
	}
	
	public function getCorrectUsername()
	{
		return $this->correct_username;
	}
	
	public function getUUID()
	{
		return $this->uuid;
	}
	
	public function getError()
	{
		return $this->error;
	}
	
	public function getRaw()
	{
		return $this->raw;
	}
}
?> 