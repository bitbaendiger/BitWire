<?php

  /**
   * BitWire - Bitcoin Controller Address
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

  namespace BitBaendiger\BitWire\Controller;
  use \BitBaendiger\BitWire;
  use \quarxConnect\Events;
  
  class Address extends Events\Hookable {
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
     * @param string $ipAddress (optional)
     * @param int $ipPort (optional)
     * @param int $Services (optional)
     * @param int $Timestamp (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (string $ipAddress = '::', int $ipPort = 8333, int $Services = 0x00, int $Timestamp = null) {
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
    public function getAddress () : string {
      return '[' . $this->ipAddress . ']:' . $this->ipPort;
    }
    // }}}
    
    // {{{ getIPAddress
    /**
     * @access public
     * @return string
     **/
    public function getIPAddress () : string {
      return '[' . $this->ipAddress . ']';
    }
    // }}}
    
    // {{{ getPort
    /**
     * @access public
     * @return int
     **/
    public function getPort () : int {
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
    public function getTimestamp () : ?int {
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
    public function unsetTimestamp () : void {
      $this->Timestamp = null;
    }
    // }}}
    
    public function getServices () : int {
      return $this->Services;
    }
    
    // {{{ hasConnection
    /**
     * Check if there is a connection pending or established
     * 
     * @access public
     * @return bool
     **/
    public function hasConnection () : bool {
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
   public function hadConnection () : bool {
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
    public function hasPeer () : bool {
      return ($this->connectedPeer !== null);
    }
    // }}}
    
    // {{{ setPeer
    /**
     * Assign a peer-connection to this address
     * 
     * @param BitWire\Peer $Peer
     * 
     * @access public
     * @return void
     **/
    public function setPeer (BitWire\Peer $Peer) : void {
      $this->connectedPeer = $Peer;
      $this->lastConnectionTime = time ();
    }
    // }}}
    
    // {{{ removePeer
    /**
     * Remove a peer-instance from this address
     * 
     * @param BitWire\Peer $removePeer
     * 
     * @access public
     * @return void
     **/
    public function removePeer (BitWire\Peer $removePeer) : void {
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
     * @param Events\Base $eventBase Event-Base for client-socket
     * @param BitWire\Controller $bitWireController (optional) Use this controller for the new peer
     * 
     * @access public
     * @return Events\Promise
     **/
    public function connect (Events\Base $eventBase, BitWire\Controller $bitWireController = null) : Events\Promise {
      // Create a new socket
      $this->networkSocket = new Events\Socket ($eventBase);
      $this->lastConnectionTime = time ();
      
      // Try to connect to peer
      return $this->networkSocket->connect (
        $this->ipAddress,
        $this->ipPort,
        $this->networkSocket::TYPE_TCP
      )->then (
        function () use ($bitWireController) {
          // Sanity-Check if we still have a network-stream
          if (!$this->networkSocket)
            throw new \Exception ('Race-condition: Network-socket vanished');
          
          if ($bitWireController)
            return $bitWireController->newPeer ($this->networkSocket)->then (
              function (BitWire\Peer $newPeer) {
                $this->connectedPeer = $newPeer;
                
                return $newPeer;
              }
            );
          
          // Create a new peer
          $newPeer = new BitWire\Peer ();
          
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
