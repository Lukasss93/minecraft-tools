<?php

class mcping
{
	//utils
	private $Socket;
	private $ServerAddress;
	private $ServerPort;
	private $Timeout;
	
	
	//output
	private $description=null;	
	private $online_player=null;
	private $max_player=null;
	private $sample_player=null;
	private $version_name=null;
	private $version_protocol=null;
	private $favicon=null;
	private $error=null;

	public function __construct( $Address, $Port = 25565, $Pre17=false, $Timeout = 2 )
	{
		$this->ServerAddress = $Address;
		$this->ServerPort = (int)$Port;
		$this->Timeout = (int)$Timeout;

		//connect*************************************************
		$connectTimeout = $this->Timeout;
		$this->Socket = @fsockopen( $this->ServerAddress, $this->ServerPort, $errno, $errstr, $connectTimeout );

		if( !$this->Socket )
		{
			$this->error="Failed to connect or create a socket: $errno ($errstr)";
		}

		stream_set_timeout( $this->Socket, $this->Timeout );
		
		//query***************************************************
		if($Pre17)
		{
			$this->Build($this->QueryOldPre17(),true);
		}
		else
		{
			$this->Build($this->Query(),false);
		}
		
		//close***************************************************
		$this->Close();
	}
		
	private function Build($array,$pre17)
	{
		if($pre17)
		{	
			$this->online_player=$array["Players"];
			$this->max_player=$array["MaxPlayers"];
			$this->version_name=$array["Version"];
			$this->version_protocol=$array["Protocol"];
		}
		else
		{
			$this->description=$array["description"];
		    $this->online_player=$array["players"]["online"];
		    $this->max_player=$array["players"]["max"];
		    $this->sample_player=$array["players"]["sample"];
		    $this->version_name=$array["version"]["name"];
		    $this->version_protocol=$array["version"]["protocol"];
		    $this->favicon=$array["favicon"];
		}
	}

	private function Close()
	{
		if( $this->Socket !== null )
		{
			fclose( $this->Socket );

			$this->Socket = null;
		}
	}
	
	private function Query()
	{
		$TimeStart = microtime(true); // for read timeout purposes

		// See http://wiki.vg/Protocol (Status Ping)
		$Data = "\x00"; // packet ID = 0 (varint)

		$Data .= "\x04"; // Protocol version (varint)
		$Data .= Pack( 'c', StrLen( $this->ServerAddress ) ) . $this->ServerAddress; // Server (varint len + UTF-8 addr)
		$Data .= Pack( 'n', $this->ServerPort ); // Server port (unsigned short)
		$Data .= "\x01"; // Next state: status (varint)

		$Data = Pack( 'c', StrLen( $Data ) ) . $Data; // prepend length of packet ID + data

		fwrite( $this->Socket, $Data ); // handshake
		fwrite( $this->Socket, "\x01\x00" ); // status ping

		$Length = $this->ReadVarInt( ); // full packet length

		if( $Length < 10 )
		{
			return FALSE;
		}

		fgetc( $this->Socket ); // packet type, in server ping it's 0

		$Length = $this->ReadVarInt( ); // string length

		$Data = "";
		do
		{
			if (microtime(true) - $TimeStart > $this->Timeout)
			{
				$this->error='Server read timed out' ;
			}

			$Remainder = $Length - StrLen( $Data );
			$block = fread( $this->Socket, $Remainder ); // and finally the json string
			// abort if there is no progress
			if (!$block)
			{
				$this->error='Server returned too few data';
			}

			$Data .= $block;
		} while( StrLen($Data) < $Length );

		if( $Data === FALSE )
		{
			$this->error='Server didn\'t return any data';
		}

		$Data = JSON_Decode( $Data, true );

		if( JSON_Last_Error( ) !== JSON_ERROR_NONE )
		{
			if( Function_Exists( 'json_last_error_msg' ) )
			{
				$this->error=JSON_Last_Error_Msg( );
			}
			else
			{
				$this->error='JSON parsing failed';
			}

			return FALSE;
		}

		return $Data;
	}

	private function QueryOldPre17()
	{
		fwrite( $this->Socket, "\xFE\x01" );
		$Data = fread( $this->Socket, 512 );
		$Len = StrLen( $Data );

		if( $Len < 4 || $Data[ 0 ] !== "\xFF" )
		{
			return FALSE;
		}

		$Data = SubStr( $Data, 3 ); // Strip packet header (kick message packet and short length)
		$Data = iconv( 'UTF-16BE', 'UTF-8', $Data );

		// Are we dealing with Minecraft 1.4+ server?
		if( $Data[ 1 ] === "\xA7" && $Data[ 2 ] === "\x31" )
		{
			$Data = Explode( "\x00", $Data );

			return Array(
				'HostName'   => $Data[ 3 ],
				'Players'    => IntVal( $Data[ 4 ] ),
				'MaxPlayers' => IntVal( $Data[ 5 ] ),
				'Protocol'   => IntVal( $Data[ 1 ] ),
				'Version'    => $Data[ 2 ]
			);
		}

		$Data = Explode( "\xA7", $Data );

		return Array(
			'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
			'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
			'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0,
			'Protocol'   => 0,
			'Version'    => '1.3'
		);
	}

	private function ReadVarInt()
	{
		$i = 0;
		$j = 0;

		while( true )
		{
			$k = @fgetc( $this->Socket );

			if( $k === FALSE )
			{
				return 0;
			}

			$k = Ord( $k );

			$i |= ( $k & 0x7F ) << $j++ * 7;

			if( $j > 5 )
			{
				$this->error='VarInt too big';
			}

			if( ( $k & 0x80 ) != 128 )
			{
				break;
			}
		}

		return $i;
	}

	public function isOnline()
	{
		return $this->error!=null?false:true;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function getOnlinePlayer()
	{
		return $this->online_player;
	}
	
	public function getMaxPlayer()
	{
		return $this->max_player;
	}
	
	public function getSamplePlayer()
	{
		return $this->sample_player==null?array():$this->sample_player;
	}
	
	public function getVersionName()
	{
		return $this->version_name;
	}
	
	public function getVersionProtocol()
	{
		return $this->version_protocol;
	}
	
	public function getFavicon()
	{
		$icona=str_replace("\n","",$this->favicon);
		return $icona==""?null:$icona;
	}
	
	public function getError()
	{
		return $this->error;
	}
	
	public function getRaw()
	{
		$raw=array();
		$raw["online"]=$this->isOnline();
		$raw["description"]=$this->getDescription();
		$raw["online_player"]=$this->getOnlinePlayer();
		$raw["max_player"]=$this->getMaxPlayer();
		$raw["sample_player"]=$this->getSamplePlayer();
		$raw["version_name"]=$this->getVersionName();
		$raw["version_protocol"]=$this->getVersionProtocol();
		$raw["favicon"]=$this->getFavicon();
		$raw["error"]=$this->getError();
		
		return $raw;
	}
	
	
	
}

?>