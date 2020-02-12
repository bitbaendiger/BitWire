<?PHP

  /**
   * BitWire - Bitcoin Controller Address
   * Copyright (C) 2017-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  require_once ('qcEvents/Socket.php');
  
  require_once ('BitWire/Peer.php');
  
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
    private $connectedPeer = null;
    
    /* Time of last connection to peer */
    private $lastConnectionTime = null;
    
    /* Connection-state */
    private $networkSocket = null;
    
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
    
    // {{{ getTimestamp
    /**
     * Retrive the timestamp of last connection to this address
     * 
     * @access public
     * @return int
     **/
    public function getTimestamp () {
      return $this->Timestamp;
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
    
    public function getServices () {
      return $this->Services;
    }
    
    // {{{ hasConnection
    /**
     * Check if there is a connection pending or established
     * 
     * @access public
     * @return bool
     **/
    public function hasConnection () {
      return ($this->networkSocket !== null) || ($this->connectedPeer !== null);
    }
    // }}}
    
    // {{{ hadConnection
    /**
     * Check if this peer was recently connected
     * 
     * @access public
     * @return bool
     **/
   public function hadConnection () {
     return (time () - $this->lastConnectionTime < 600);
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
      return ($this->connectedPeer !== null);
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
      $this->connectedPeer = $Peer;
      $this->lastConnectionTime = time ();
    }
    // }}}
    
    // {{{ removePeer
    /**
     * Remove a peer-instance from this address
     * 
     * @param BitWire_Peer $removePeer
     * @access public
     * @return void
     **/
    public function removePeer (BitWire_Peer $removePeer) {
      if ($this->connectedPeer !== $removePeer)
        return;
      
      $this->connectedPeer = null;
      $this->lastConnectionTime = time ();
    }
    // }}}
    
    // {{{ connect
    /**
     * Try to connect to this address
     * 
     * @param qcEvents_Base $eventBase Event-Base for client-socket
     * @param BitWire_Controller $bitWireController (optional) Use this controller for the new peer
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function connect (qcEvents_Base $eventBase, BitWire_Controller $bitWireController = null) : qcEvents_Promise {
      // Create a new socket
      $this->networkSocket = new qcEvents_Socket ($eventBase);
      $this->lastConnectionTime = time ();
      
      // Try to connect to peer
      return $this->networkSocket->connect (
        $this->ipAddress,
        $this->ipPort,
        $this->networkSocket::TYPE_TCP,
      )->then (
        function () use ($bitWireController) {
          // Sanity-Check if we still have a network-stream
          if (!$this->networkSocket)
            throw new exception ('Race-condition: Network-socket vanished');
          
          if ($bitWireController)
            return $bitWireController->newPeer ($this->networkSocket)->then (
              function (BitWire_Peer $newPeer) {
                $this->connectedPeer = $newPeer;
                
                return $newPeer;
              }
            );
          
          // Create a new peer
          $newPeer = new BitWire_Peer ();
          
          return $this->networkSocket->pipeStream (
            $newPeer,
            true
          )->then (
            function () use ($newPeer) {
              $this->connectedPeer = $newPeer;
              
              if ($this->networkSocket)
                $this->networkSocket->once ('eventClosed')->then (
                  function () {
                    $this->networkSocket = null;
                    $this->connectedPeer = null;
                  }
                );
                
              return $newPeer;
            }
          );
        }
      )->finally (
        function () {
          // Release the socket
          $this->Socket = null;
        }
      );
    }
    // }}}
  }

?>