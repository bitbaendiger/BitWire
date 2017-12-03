<?PHP

  /**
   * BitWire - Bitcoin Controller Address
   * Copyright (C) 2017 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('qcEvents/Hookable.php');
  
  class BitWire_Controller_Address extends qcEvents_Hookable {
    /* IPv6-Address of node (may be mapped IPv4) */
    private $ipAddress = null;
    
    /* IP-Port of node */
    private $ipPort = null;
    
    /* Services advertised by this node */
    private $Services = 0x00;
    
    /* Time on node itself or time of last connection */
    private $Timestamp = null;
    
    /* Peer-Instance assigned to this address */
    private $Peer = null;
    
    /* Connection-state */
    private $Socket = null;
    
    // {{{ __construct
    /**
     * Create a new controller-address
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($ipAddress = '::', $ipPort = 8333, $Services = 0x00, $Timestamp = null) {
      $this->ipAddress = $ipAddress;
      $this->ipPort = $ipPort & 0xFFFF;
      $this->Services = $Services;
      $this->Timestamp = $Timestamp;
    }
    // }}}
    
    // {{{ __toString
    /**
     * Cast this object into a string
     * 
     * @access friendly
     * @return string
     **/
    function __toString () {
      return $this->getAddress ();
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Retrive the full address-string of this address
     * 
     * @access public
     * @return string
     **/
    public function getAddress () {
      return '[' . $this->ipAddress . ']:' . $this->ipPort;
    }
    // }}}
    
    // {{{ getIPAddress
    /**
     * @access public
     * @return string
     **/
    public function getIPAddress () {
      return '[' . $this->ipAddress . ']';
    }
    // }}}
    
    // {{{ getPort
    /**
     * @access public
     * @return int
     **/
    public function getPort () {
      return $this->ipPort;
    }
    // }}}
    
    // {{{ unsetTimestamp
    /**
     * Remove last connection-attemp-timestamp
     * 
     * @access public
     * @return void
     **/
    public function unsetTimestamp () {
      $this->Timestamp = null;
    }
    // }}}
    
    // {{{ hasConnection
    /**
     * Check if there is a connection pending or established
     * 
     * @access public
     * @return bool
     **/
    public function hasConnection () {
      return ($this->Socket !== null) || ($this->Peer !== null) || ($this->Timestamp > time () - 600);
    }
    // }}}
    
    // {{{ hasPeer
    /**
     * Check if this address has a peer assigned
     * 
     * @access public
     * @return bool
     **/
    public function hasPeer () {
      return ($this->Peer !== null);
    }
    // }}}
    
    // {{{ setPeer
    /**
     * Assign a peer-connection to this address
     * 
     * @param BitWire_Peer $Peer
     * 
     * @access public
     * @return void
     **/
    public function setPeer (BitWire_Peer $Peer) {
      $this->Peer = $Peer;
      $this->Timestamp = time ();
    }
    // }}}
    
    // {{{ connect
    /**
     * Try to connect to this address
     * 
     * @param qcEvents_Base $Base Event-Base for client-socket
     * @param BitWire_Controller $Controller (optional) Use this controller for the new peer
     * @param callable $Callback A callback to forward the new peer to
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised in the form of
     * 
     *   function (BitWire_Controller_Address $Self, BitWire_Peer $Peer = null, mixed $Private) { }
     * 
     * @access public
     * @return void
     **/
    public function connect (qcEvents_Base $Base, BitWire_Controller $Controller = null, callable $Callback, $Private = null) {
      // Create a new socket
      $this->Socket = new qcEvents_Socket ($Base);
      $this->Timestamp = time ();
      
      // Try to connect to peer
      return $this->Socket->connect (
        $this->ipAddress,
        $this->ipPort,
        $this->Socket::TYPE_TCP,
        false,
        function (qcEvents_Socket $Socket, $Status) use ($Controller, $Callback, $Private) {
          // Check if the connection was successfull
          if (!$Status) {
            $this->Socket = null;
            
            // Forward the callback
            return $this->___raiseCallback ($Callback, null, $Private);
          }
          
          // Create a new peer
          $Peer = new BitWire_Peer ($Controller, null, ($Controller ? $Controller->getUserAgent () : null));
          
          return $Socket->pipeStream (
            $Peer,
            true,
            function (qcEvents_Socket $Socket, $Status) use ($Peer, $Callback, $Private) {
              if ($Status) {
                $this->Peer = $Peer;
                
                $this->Socket->addHook ('eventClosed', function () {
                  $this->Socket = null;
                  $this->Peer = null;
                }, null, true);
              } else
                $this->Socket = null;
              
              return $this->___raiseCallback ($Callback, ($Status ? $Peer : null), $Private);
            }
          );
        }
      );
    }
    // }}}
  }

?>