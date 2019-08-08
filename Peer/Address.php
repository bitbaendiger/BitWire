<?PHP

  /**
   * BitWire - Peer Address (a.k.a. CAddress)
   * Copyright (C) 2019 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('qcEvents/Socket.php');
  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Peer_Address {
    /* IP-Address of this peer */
    private $Address = '::';
    
    /* Port of service on peer */
    private $Port = 8333;
    
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
    function __construct ($Address = '::', $Port = 8333) {
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
    public function getFullAddress () {
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
    public function getAddress ($unmappIPv4 = false) {
      if (!$unmappIPv4)
        return '[' . $this->Address . ']';
      
      // Convert address to binary
      $Binary = unpack ('n8', qcEvents_Socket::ip6toBinary ($this->Address));
      
      // Check for IPv4
      if (($Binary [1] == $Binary [2]) && ($Binary [1] == $Binary [3]) && ($Binary [1] == $Binary [4]) && ($Binary [1] == $Binary [5]) && ($Binary [1] == 0) && ($Binary [6] == 0xffff))
        return sprintf ('%u.%u.%u.%u', ($Binary [7] >> 8) & 0xFF, $Binary [7] & 0xFF, ($Binary [8] >> 8) & 0xFF, $Binary [8] & 0xFF);
      
      return '[' . $this->Address . ']';
    }
    // }}}
    
    // {{{ getPort
    /**
     * @access public
     * @return int
     **/
    public function getPort () {
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
    public function toString () {
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
     * @return bool
     **/
    public function parse (&$Data, &$Offset, $Length = null) {
      // Make sure we know the length
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Check available size
      if ($Length - $Offset < 18)
        return null;
      
      // Get the relevant data from the input-buffer
      $this->Address = qcEvents_Socket::ip6fromBinary (substr ($Data, $Offset, 16));
      $Offset += 16;
      $this->Port = BitWire_Message_Payload::readUInt16 ($Data, $Offset, $Length);
      
      return true;
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
        qcEvents_Socket::ip6toBinary ($this->Address) .
        BitWire_Message_Payload::writeUInt16 ($this->Port);
    }
    // }}}
  }