<?php

  /**
   * BitWire - Bitcoin Version Message
   * Copyright (C) 2017-2021 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  use \quarxConnect\Events;
  
  // Check Integer-Size
  if (PHP_INT_MAX <= 0x7FFFFFFF)
    trigger_error ('BitWire requires a 64-Bit PHP to work properly and reliable');
  
  class Version extends Payload {
    protected const PAYLOAD_COMMAND = 'version';
    
    /* Well-known services */
    public const SERVICE_NONE    = 0x00; // "Unnamed" on Bitcoin-reference
    public const SERVICE_NETWORK = 0x01;
    
    /* Supported version */
    private $Version = 70015;
    
    /* Supported services */
    private $SupportedServices = Version::SERVICE_NONE;
    
    /* Current timestamp */
    private $Timestamp = null;
    
    /* Services expected to be supported at peer (since 106) */
    private $PeerServices = Version::SERVICE_NETWORK;
    
    /* Expected address of our peer (since 106) */
    private $PeerAddress = '::ffff:7f00:0001';
    private $PeerPort = 8333;
    
    /* Actual announced supported services */
    private $Services = Version::SERVICE_NONE;
    
    /* Expected local address */
    private $Address = '::ffff:7f00:0001';
    private $Port = 0x000;
    
    /* Nonce for this version */
    private $Nonce = 0x0000000000000000;
    
    /* Connecting User-Agent (merely since 60000) */
    private $UserAgent = '/BitWire:0.2/';
    
    /* Known height of block-chain */
    private $StartHeight = 0x00000000;
    
    /* Relaying is supported (since 70001 / BIP37) */
    private $Relay = null;
    
    // {{{ __construct
    /**
     * Create a new version-message
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version of this message
     * 
     * @access public
     * @return int
     **/
    public function getVersion () : int {
      return $this->Version;
    }
    // }}}
    
    // {{{ setVersion
    /**
     * Set the version of this message
     * 
     * @param int $Version
     * 
     * @access public
     * @return void
     **/
    public function setVersion (int $Version) : void {
      $this->Version = $Version;
    }
    // }}}
    
    // {{{ setAddress
    /**
     * Set our local address as human-readable string
     * 
     * @param string $Address
     * 
     * @access public
     * @return void
     **/
    public function setAddress (string $Address) : void {
      $this->Address = $Address;
    }
    // }}}
    
    // {{{ setPort
    /**
     * Set our local port
     * 
     * @param int $Port
     * 
     * @access public
     * @return void
     **/
    public function setPort (int $Port) : void {
      $this->Port = $Port;
    }
    // }}}
    
    // {{{ setPeerAddress
    /**
     * Set expected peer-address as human-readable string
     * 
     * @param stirng $Address
     * 
     * @access public
     * @return void
     **/
    public function setPeerAddress (string $Address) : void {
      $this->PeerAddress = $Address;
    }
    // }}}
    
    // {{{ setPeerPort
    /**
     * Set the expected peer-port
     * 
     * @param int $Port
     * 
     * @access public
     * @return void
     **/
    public function setPeerPort (int $Port) : void {
      $this->PeerPort = $Port;
    }
    // }}}
    
    // {{{ setStartHeight
    /**
     * Set height of known block-chain
     * 
     * @param int $Height
     * 
     * @access public
     * @return void
     **/
    public function setStartHeight (int $Height) : void {
      $this->StartHeight = $Height;
    }
    // }}}
    
    // {{{ getSerivceMask
    /**
     * Retrive mask of supported services
     * 
     * @access public
     * @return int
     **/
    public function getServiceMask () : int {
      return $this->Services;
    }
    // }}}
    
    // {{{ getUserAgent
    /**
     * Retrive the useragent
     * 
     * @access public
     * @return string
     **/
    public function getUserAgent () : string {
      return $this->UserAgent;
    }
    // }}}
    
    // {{{ setUserAgent
    /**
     * Set a user-agent
     * 
     * @param string $Agent
     * 
     * @access public
     * @return void
     **/
    public function setUserAgent (string $Agent) : void {
      $this->UserAgent = $Agent;
    }
    // }}}
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data) : void {
      // Retrive length of data
      $Length = strlen ($Data);
      $Offset = 0;
      
      // Make sure the min length is met
      if ($Length < 59)
        throw new \LengthException ('Version-Message too short');
      
      // Parse first bits
      $Version = self::readUInt32 ($Data, $Offset, $Length);
      $SupportedServices = self::readUInt64 ($Data, $Offset, $Length);
      $Timestamp = self::readUInt64 ($Data, $Offset, $Length);
      
      $this->Version = $Version;
      $this->SupportedServices = $SupportedServices;
      $this->Timestamp = $Timestamp;
      
      // Parse additional peer-addresses
      if ($this->Version >= 106) {
        // Re-Check the length
        if ($Length < 85)
          throw new \LengthException ('Extended version-message too short');
        
        $PeerServices = self::readUInt64 ($Data, $Offset, $Length);
        $PeerAddress = self::readChar ($Data, $Offset, 16, $Length);
        $PeerPort = self::readUInt16 ($Data, $Offset, $Length);
        
        $this->PeerServices = $PeerServices;
        $this->PeerAddress = Events\Socket::ip6fromBinary ($PeerAddress);
        $this->PeerPort = $PeerPort;
      }
      
      // Parse additional standard-fields
      $Services = self::readUInt64 ($Data, $Offset, $Length);
      $Address = self::readChar ($Data, $Offset, 16, $Length);
      $Port = self::readUInt16 ($Data, $Offset, $Length);
      $Nonce = self::readUInt64 ($Data, $Offset, $Length);
      
      $this->Services = $Services;
      $this->Address = Events\Socket::ip6fromBinary ($Address);
      $this->Port = $Port;
      $this->Nonce = $Nonce;
      
      $this->UserAgent = $this::readCompactString ($Data, $Offset, $Length);
      
      // Truncate the buffer and recheck the length
      if ($Length - $Offset < 4)
        throw new \LengthException ('version-message too short');
      
      if ($Length - $Offset > 4) {
        $Values = unpack ('Vheight/Crelay', substr ($Data, $Offset, 5));
        $this->Relay = ($Values ['relay'] != 0x00);
      } else {
        $Values = unpack ('Vheight', substr ($Data, $Offset, 4));
        $this->Relay = null;
      }
      
      $this->StartHeight = $Values ['height'];
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      # TODO: We use unsigned integers here because of speed, but spec is different
      return
        pack ('VPP', $this->Version, $this->SupportedServices, ($this->Timestamp !== null ? $this->Timestamp : time ())) .
        ($this->Version >= 106 ? pack ('Pa16n', $this->PeerServices, Events\Socket::ip6toBinary ($this->PeerAddress), $this->PeerPort) : '') .
        pack ('Pa16nP', $this->Services, Events\Socket::ip6toBinary ($this->Address), $this->Port, $this->Nonce) .
        $this::toCompactSize (strlen ($this->UserAgent)) . $this->UserAgent .
        pack ('V', $this->StartHeight) .
        (($this->Version >= 70001) && ($this->Relay !== null) ? pack ('C', $this->Relay ? 0x01 : 0x00) : '');
    }
    // }}}
  }
