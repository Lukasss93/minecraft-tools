<?php
class mcquery
{
	const STATISTIC = 0x00;
	const HANDSHAKE = 0x09;

	private $Socket;
	private $Players;
	private $Info;
	private $error;
	
	public function __construct($ip,$port=25565,$timeout=3)
	{
		if( !is_int( $timeout ) || $timeout < 0 )
		{
			$this->error="Invalid timeout";
		}
		
		$this->Socket = @FSockOpen( 'udp://' . $ip, (int)$port, $ErrNo, $ErrStr, $timeout );

		if( $ErrNo || $this->Socket === false )
		{
			$this->error="Socket error.";
		}

		@Stream_Set_Timeout( $this->Socket, $timeout );
		@Stream_Set_Blocking( $this->Socket, true );

		$Challenge = $this->GetChallenge( );
		$this->GetStatus( $Challenge );

		@FClose( $this->Socket );
	}
	
	//public function
	
	public function isOnline()
	{
		return $this->error!=null?false:true;
	}
	
	public function getMOTD()
	{
		return isset($this->Info["HostName"])?$this->Info["HostName"]:null;
	}
	
	public function getGameType()
	{
		return isset($this->Info["GameType"])?$this->Info["GameType"]:null;
	}
	
	public function getVersion()
	{
		return isset($this->Info["Version"])?$this->Info["Version"]:null;
	}
	
	public function getPlugins()
	{
		if(isset($this->Info["Plugins"]))
		{
			if($this->Info["Plugins"]==$this->getSoftware() || $this->Info["Plugins"]=="")
			{
				return array();
			}
			else
			{
				return $this->Info["Plugins"];
			}
		}
		else
		{
			return array();
		}
	}
	
	public function getMap()
	{
		return isset($this->Info["Map"])?$this->Info["Map"]:null;
	}
	
	public function getOnlinePlayer()
	{
		return isset($this->Info["Players"])?$this->Info["Players"]:null;
	}
	
	public function getMaxPlayer()
	{
		return isset($this->Info["MaxPlayers"])?$this->Info["MaxPlayers"]:null;
	}
	
	public function getHostIp()
	{
		return isset($this->Info["HostIp"])?$this->Info["HostIp"]:null;
	}
	
	public function getHostPort()
	{
		return isset($this->Info["HostPort"])?$this->Info["HostPort"]:null;
	}
	
	public function getGameName()
	{
		return isset($this->Info["GameName"])?$this->Info["GameName"]:null;
	}
	
	public function getSoftware()
	{
		if($this->error!=null)
		{
			return null;
		}
		else
		{		
			return $this->Info["Software"];
		}
	}
	
	public function getPlayers()
	{
		if(isset($this->Players))
		{
			return $this->Players;
		}
		else
		{
			return array();
		}
	}
	
	public function getErrors()
	{
		return isset($this->error)?$this->error:null;
	}
	
	public function getRaw()
	{
		$raw=array();
		$raw["online"]=$this->isOnline();
		$raw["motd"]=$this->getMOTD();
		$raw["gametype"]=$this->getGameType();
		$raw["version"]=$this->getVersion();
		$raw["plugins"]=$this->getPlugins();
		$raw["map"]=$this->getMap();
		$raw["online_player"]=$this->getOnlinePlayer();
		$raw["max_player"]=$this->getMaxPlayer();
		$raw["host_ip"]=$this->getHostIp();
		$raw["host_port"]=$this->getHostPort();
		$raw["gamename"]=$this->getGameName();
		$raw["software"]=$this->getSoftware();
		$raw["players"]=$this->getPlayers();
		$raw["errors"]=$this->getErrors();
		
		return $this->isOnline()?$raw:array();
	}
	
	
	
	
	//private function
	
	private function GetInfo()
	{
		return isset( $this->Info ) ? $this->Info : false;
	}
	
	private function GetChallenge( )
	{
		$Data = $this->WriteData( self :: HANDSHAKE );

		if( $Data === false )
		{
			$this->error="data false.";
		}

		return Pack( 'N', $Data );
	}
	
	private function GetStatus( $Challenge )
	{
		$Data = $this->WriteData( self :: STATISTIC, $Challenge . Pack( 'c*', 0x00, 0x00, 0x00, 0x00 ) );

		if( !$Data )
		{
			$this->error="data false.";
		}

		$Last = '';
		$Info = Array( );

		$Data    = SubStr( $Data, 11 ); // splitnum + 2 int
		$Data    = Explode( "\x00\x00\x01player_\x00\x00", $Data );

		if( Count( $Data ) !== 2 )
		{
			$this->error="server offline or port is not opened";
		}

		$Players = @SubStr( $Data[ 1 ], 0, -2 );
		$Data    = Explode( "\x00", $Data[ 0 ] );

		// Array with known keys in order to validate the result
		// It can happen that server sends custom strings containing bad things (who can know!)
		$Keys = Array(
			'hostname'   => 'HostName',
			'gametype'   => 'GameType',
			'version'    => 'Version',
			'plugins'    => 'Plugins',
			'map'        => 'Map',
			'numplayers' => 'Players',
			'maxplayers' => 'MaxPlayers',
			'hostport'   => 'HostPort',
			'hostip'     => 'HostIp',
			'game_id'    => 'GameName'
		);

		foreach( $Data as $Key => $Value )
		{
			if( ~$Key & 1 )
			{
				if( !Array_Key_Exists( $Value, $Keys ) )
				{
					$Last = false;
					continue;
				}

				$Last = $Keys[ $Value ];
				$Info[ $Last ] = '';
			}
			else if( $Last != false )
			{
				$Info[ $Last ] = $Value;
			}
		}

		// Ints
		$Info[ 'Players' ]    = @IntVal( $Info[ 'Players' ] );
		$Info[ 'MaxPlayers' ] = @IntVal( $Info[ 'MaxPlayers' ] );
		$Info[ 'HostPort' ]   = @IntVal( $Info[ 'HostPort' ] );

		// Parse "plugins", if any
		if( @$Info[ 'Plugins' ] )
		{
			$Data = Explode( ": ", $Info[ 'Plugins' ], 2 );

			$Info[ 'RawPlugins' ] = $Info[ 'Plugins' ];
			$Info[ 'Software' ]   = $Data[ 0 ];

			if( Count( $Data ) == 2 )
			{
				$Info[ 'Plugins' ] = Explode( "; ", $Data[ 1 ] );
			}
		}
		else
		{
			$Info[ 'Software' ] = 'Vanilla';
		}

		$this->Info = $Info;

		if( $Players )
		{
			$this->Players = Explode( "\x00", $Players );
		}
	}

	private function WriteData( $Command, $Append = "" )
	{
		$Command = Pack( 'c*', 0xFE, 0xFD, $Command, 0x01, 0x02, 0x03, 0x04 ) . $Append;
		$Length  = StrLen( $Command );

		if( $Length !== @FWrite( $this->Socket, $Command, $Length ) )
		{
			$this->error="lunghezza fwrite.";
		}

		$Data = @FRead( $this->Socket, 4096 );

		if( $Data === false )
		{
			$this->error="data false.";
		}

		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Command[ 2 ] )
		{
			$this->error="Strlen <.";
		}

		return SubStr( $Data, 5 );
	}
}

?>