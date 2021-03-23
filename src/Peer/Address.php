<?php

  /**
   * BitWire - Peer Address (a.k.a. CAddress)
   * Copyright (C) 2019-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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

  namespace BitBaendiger\BitWire\Peer;
  use BitBaendiger\BitWire;
  
  class Address {
    /* IP-Address of this peer */
    private $Address = '::';
    
    /* Port of service on peer */
    private $Port = 8333;
    
    // {{{ fromString
    /**
     * Parse an address from string
     * 
     * @param string $nodeAddress
     * 
     * @access public
     * @return Address
     **/
    public static function fromString (string $nodeAddress) : Address {
      // Retrive the length of the address
      if (($addressLength = strlen ($nodeAddress)) == 0)
        return new static ();
      
      $nodePort = 8333;
      
      // Check for IPv6
      if ($nodeAddress [0] == '[') {
        if (($p = strpos ($nodeAddress, ']', 1)) === false)
          throw new \Exception ('Invalid IPv6');
        
        $nodeIP = substr ($nodeAddress, 1, $p - 1);
        
        if (($p < $addressLength - 1) && ($nodeAddress [$p + 1] == ':'))
          $nodePort = (int)substr ($nodeAddress, $p + 2);
      } elseif (($p = strpos ($nodeAddress, ':')) !== false) {
        $nodeIP = substr ($nodeAddress, 0, $p);
        $nodePort = (int)substr ($nodeAddress, $p + 1);
      } else
        $nodeIP = $nodeAddress;
      
      return new static ($nodeIP, $nodePort);
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new controller-address
     * 
     * @param string $Addres
     * @param int $Port
     * 
     * @access friendly
     * @return void
     **/
    function __construct (string $Address = '::', int $Port = 8333) {
      $this->Address = $Address;
      $this->Port = $Port;
    }
    // }}}
    
    // {{{ getFullAddress
    /**
     * Retrive the full address-string of this address
     * 
     * @access public
     * @return string
     **/
    public function getFullAddress () : string {
      return '[' . $this->Address . ']:' . $this->Port;
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Retrive the IP-Address of this peer
     * 
     * @param bool $unmappIPv4 (optional)
     * 
     * @access public
     * @return string
     **/
    public function getAddress (bool $unmappIPv4 = false) : string {
      // Convert address to binary
      if ($unmappIPv4) {
        $Binary = unpack ('n8', \quarxConnect\Events\Socket::ip6toBinary ($this->Address));
        
        // Check for IPv4
        if (($Binary [1] == $Binary [2]) && ($Binary [1] == $Binary [3]) && ($Binary [1] == $Binary [4]) && ($Binary [1] == $Binary [5]) && ($Binary [1] == 0) && ($Binary [6] == 0xffff))
          return sprintf ('%u.%u.%u.%u', ($Binary [7] >> 8) & 0xFF, $Binary [7] & 0xFF, ($Binary [8] >> 8) & 0xFF, $Binary [8] & 0xFF);
      }
      
      if ($this->Address [0] != '[')
        return '[' . $this->Address . ']';
      
      return $this->Address;
    }
    // }}}
    
    // {{{ getPort
    /**
     * @access public
     * @return int
     **/
    public function getPort () : int {
      return $this->Port;
    }
    // }}}
    
    // {{{ toString
    /**
     * See CNetAddr::ToString()
     * 
     * @access public
     * @return string
     **/
    public function toString () : string {
      if ($this->Port)
        return $this->getAddress (true) . ':' . $this->Port;
      
      return $this->getAddress (true);
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse contents from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return void
     **/
    public function parse (string &$Data, int &$Offset, int $Length = null) : void {
      // Make sure we know the length
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Check available size
      if ($Length - $Offset < 18)
        throw new \LengthException ('Available data too short');
      
      // Get the relevant data from the input-buffer
      $this->Address = \quarxConnect\Events\Socket::ip6fromBinary (substr ($Data, $Offset, 16));
      $Offset += 16;
      $this->Port = BitWire\Message\Payload::readUInt16 ($Data, $Offset, $Length);
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this address into binary format
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      return
        \quarxConnect\Events\Socket::ip6toBinary ($this->Address) .
        BitWire\Message\Payload::writeUInt16 ($this->Port);
    }
    // }}}
  }
