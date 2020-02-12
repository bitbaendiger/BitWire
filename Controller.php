<?PHP

  /**
   * BitWire - Bitcoin Controller
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
  
  require_once ('qcEvents/Socket.php');
  require_once ('qcEvents/Hookable.php');
  require_once ('qcEvents/Promise.php';
  
  require_once ('BitWire/Peer.php');
  require_once ('BitWire/Controller/Address.php');
  require_once ('BitWire/Message/GetAddresses.php');
  
  class BitWire_Controller extends qcEvents_Hookable {
    /* Event-Base for our sockets */
    private $eventBase = null;
    
    /* Default protocol-version to use */
    private $protocolVersion = null;
    
    /* Magic bytes fpr messages */
    private $messageMagic = null;
    
    /* User-Agent */
    private $userAgent = null;
    
    /* List of known addresses */
    private $peerAddresses = array ();
    
    /* List of connected peers */
    private $connectedPeers = array ();
    
    /* Maximum number of connected peers */
    private $connectedPeersMax = 32;
    
    /* List of pending peers */
    private $pendingPeers = array ();
    
    /* Maximum number of connected peers */
    private $pendingPeersMax = 16;
    
    /* Inventories */
    private $typeInventory = array ();
    
    // {{{ __construct
    /**
     * Create a new BitWire-Controller
     * 
     * @param qcEvents_Base $eventBase
     * 
     * @access friendly
     * @return void
     **/
    function __construct (qcEvents_Base $eventBase, $protocolVersion = null, $messageMagic = null, $userAgent = null) {
      $this->eventBase = $eventBase;
      $this->protocolVersion = $protocolVersion;
      $this->messageMagic = $messageMagic;
      $this->userAgent = $userAgent;
    }
    // }}}
    
    // {{{ setMaxPeers
    /**
     * Set maximum number of peer-connections
     * 
     * @param int $maxPeers
     * 
     * @access public
     * @return bool
     **/
    public function setMaxPeers ($maxPeers) {
      if ($maxPeers < 1)
        $this->connectedPeersMax = null;
      else
        $this->connectedPeersMax = (int)$maxPeers;
      
      return true;
    }
    // }}}
    
    // {{{ getProtocolVersion
    /**
     * Retrive the default protocol-version to use on this controller
     * 
     * @access public
     * @return int
     **/
    public function getProtocolVersion () {
      return $this->protocolVersion;
    }
    // }}}
    
    // {{{ getMessageMagic
    /**
     * Retrive magic number/bytes for messages on the network
     * 
     * @access public
     * @return int
     **/
    public function getMessageMagic () {
      return $this->messageMagic;
    }
    // }}}
    
    // {{{ getUserAgent
    /**
     * Retrive the user-agent for this controller
     * 
     * @access public
     * @return string
     **/
    public function getUserAgent () {
      return $this->userAgent;
    }
    // }}}
    
    // {{{ connectPeer
    /**
     * Try to add a new peer-connection to this controller
     * 
     * @param string $destinationHost Hostname or IP-Address to connect to
     * @param int $destinationPort (optional) Destination Port to connect to
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function connectPeer ($destinationHost, $destinationPort = 8333) : qcEvents_Promise {
      // Create a new socket
      $networkSocket = new qcEvents_Socket ($this->eventBase);
      
      // Try to connect to peer
      return $networkSocket->connect (
        $destinationHost,
        $destinationPort,
        $networkSocket::TYPE_TCP
      )->then (
        function () use ($networkSocket) {
          return $this->newPeer ($networkSocket);
        }
      );
    }
    // }}}
    
    // {{{ newPeer
    /**
     * Create a new peer on a given stream
     * 
     * @param qcEvents_Interface_Stream $parentStream
     * @param bool $addAsPeer (optional)
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function newPeer (qcEvents_Interface_Stream $parentStream, $addAsPeer = true) : qcEvents_Promise {
      // Create a new peer
      $newPeer = new BitWire_Peer ($this, $this->getProtocolVersion (), $this->getMessageMagic (), $this->getUserAgent ());
      
      // Bridge stream and peer together
      return $parentStream->pipeStream ($newPeer, true)->then (
        function () use ($newPeer, $addAsPeer) {
          if ($addAsPeer)
            $this->addPeer ($newPeer);
          
          return $newPeer;
        }
      );
    }
    // }}}
    
    // {{{ addPeer
    /**
     * Register a new peer-instance for this controller
     * 
     * @param BitWire_Peer $newPeer
     * 
     * @access public
     * @return void
     **/
    public function addPeer (BitWire_Peer $newPeer) {
      // Don't add peer twice
      if (in_array ($newPeer, $this->connectedPeers, true))
        return;
      
      // Make sure we have an address for this
      $peerKey = $newPeer->getPeerAddress ();
      
      if (!isset ($this->peerAddresses [$peerKey])) {
        $peerPort = substr ($peerKey, strrpos ($peerKey, ':') + 1);
        $peerIP = substr ($peerKey, 0, strrpos ($peerKey, ':'));
        $peerVersion = $newPeer->getPeerVersion ();
        
        $peerAddress = new BitWire_Controller_Address (
          qcEvents_Socket::ip6fromBinary (qcEvents_Socket::ip6toBinary ($peerIP)),
          $peerPort,
          ($peerVersion ? $peerVersion->getServiceMask () : 0x00),
          time ()
        );
        
        if ($peerKey !== $peerAddress->getAddress ())
          $peerKey = $peerAddress->getAddress ();
        
        $this->peerAddresses [$peerKey] = $peerAddress;
      }
      
      // Add peer to collection
      $this->peerAddresses [$peerKey]->setPeer ($newPeer);
      $this->connectedPeers [$peerKey] = $newPeer;
      
      // Request known addresses from the peer
      $newPeer->sendPayload (new BitWire_Message_GetAddresses);
      
      // Register callbacks on peer
      $newPeer->addHook (
        'payloadReceived',
        function (BitWire_Peer $receivingPeer, BitWire_Message_Payload $receivedPayload) {
          // Raise a callback for the incoming payload
          if ($this->___callback ('bitWirePayloadReceived', $receivedPayload, $receivingPeer) === false)
            return;
          
          // Learn new addresses from this peer
          if ($receivedPayload instanceof BitWire_Message_Addresses)
            return $this->learnAddresses ($receivedPayload->getAddresses (), $receivingPeer);
        }
      );
      
      $newPeer->addHook (
        'eventClosed',
        function (BitWire_Peer $closedPeer) {
          // Try to find the peer
          if (($peerKey = array_search ($closedPeer, $this->connectedPeers, true)) === false)
            return;
          
          // Remove references to this peer
          if (isset ($this->peerAddresses [$peerKey]))
            $this->peerAddresses [$peerKey]->removePeer ($closedPeer);
          
          unset ($this->connectedPeers [$peerKey]);
          
          // Raise callback for this
          $this->___callback ('bitWirePeerDisconnected', $closedPeer);
          
          // Check wheter to connect to a different peer
          $this->checkPeerConnections ();
        }
      );
      
      // Fire callbacks
      $this->___callback ('bitWirePeerAdded', $newPeer);
    }
    // }}}
    
    // {{{ getPeerCount
    /**
     * Retrive the number of connected peers
     * 
     * @access public
     * @return int
     **/
    public function getPeerCount ($Backlog = false) {
      return count ($this->connectedPeers) + ($Backlog ? count ($this->pendingPeers) : 0);
    }
    // }}}
    
    // {{{ learnAddresses
    /**
     * Learn addresses from one of our peers
     * 
     * @param array $newAddresses
     * @param BitWire_Peer $fromPeer
     * 
     * @access private
     * @return void
     **/
    private function learnAddresses (array $newAddresses, BitWire_Peer $fromPeer) {
      // Collect new addresses
      foreach ($newAddresses as $addressIndex=>$newAddress) {
        // Make sure the address is known
        $addressKey = $newAddress->getAddress ();
        
        if (isset ($this->peerAddresses [$addressKey])) {
          unset ($newAddresses [$addressIndex]);
          
          continue;
        }
        
        // Push address to our collection
        $newAddress->unsetTimestamp ();
        
        $this->peerAddresses [$addressKey] = $newAddress;  
        
        // Raise a callback for this
        if ($this->___callback ('bitWirePeerAddressLearned', $newAddress) === false)
          unset ($this->peerAddresses [$addressKey], $newAddresses [$addressIndex]);
      }
      
      // Try to connect to newly learned peers
      if (count ($newAddresses) > 0)
        $this->checkPeerConnections ();
    }
    // }}}
    
    // {{{ checkPeerConnections
    /**
     * Check wheter to connect to new/other peers
     * 
     * @access private
     * @return void
     **/
    private function checkPeerConnections () {
      // Check if we have enough peers
      if ((count ($this->pendingPeers) >= $this->pendingPeersMax) ||
          (($this->connectedPeersMax !== null) && (count ($this->connectedPeers) + count ($this->pendingPeers) >= $this->connectedPeersMax)))
        return;
      
      // Resort all peers
      $tryPeers = $this->peerAddresses;
      
      usort (
        $tryPeers,
        function ($peerA, $peerB) {
          return ($peerA->getTimestamp () <= $peerB->getTimestamp () ? -1 : 1);
        }
      );
      
      // Try to connect to more peers
      foreach ($tryPeers as $addressKey=>$peerAddress) {
        // Check if there is already a peer-connection (pending)
        if ($peerAddress->hasConnection () ||
            ((count ($this->connectedPeers) > 0) && $peerAddress->hadConnection ()))
          continue;
        
        // Try to connect to this address
        /**
         * We have to limit the number of pending connections
         * to a reasonable limit because we might outperform
         * the application we new connection-requests
         **/
        if ($this->___callback ('bitWirePeerTryConnect', $peerAddress) === false)
          continue;
        
        $this->pendingPeers [$addressKey] = $peerAddress->connect (
          $this->eventBase,
          $this
        )->then (
          function () use ($addressKey) {
            // Remove from pending peers
            unset ($this->pendingPeers [$addressKey]);
          },
          function () use ($addressKey) {
            // Remove from pending peers
            unset ($this->pendingPeers [$addressKey]);
            
            // Try to connect to other peers pn failure
            $this->checkPeerConnections ();
            
            // Forward the failure
            throw new qcEvents_Promise_Solution (func_get_args ());
          }
        );
        
        // Check if we should try another peer
        if ((count ($this->pendingPeers) >= $this->pendingPeersMax) ||
            (($this->connectedPeersMax !== null) && (count ($this->connectedPeers) + count ($this->pendingPeers) >= $this->connectedPeersMax)))
          break;
      }
    }
    // }}}
    
    
    // {{{ bitWirePeerAddressLearned
    /**
     * A new peer-address was learned
     * 
     * @param BitWire_Controller_Address $newAddress
     * 
     * @access protected
     * @return void
     **/
    protected function bitWirePeerAddressLearned (BitWire_Controller_Address $newAddress) { }
    // }}}
    
    // {{{ bitWirePeerTryConnect
    /**
     * Callback: Controller is about to try a connection to a peer
     * 
     * @param BitWire_Controller_Address $connectionAddress
     * 
     * @access protected
     * @return bool
     **/
    protected function bitWirePeerTryConnect (BitWire_Controller_Address $connectionAddress) { }
    // }}}
    
    // {{{ bitWirePeerAdded
    /**
     * Callback: A new peer was added to this controller
     * 
     * @param BitWire_Peer $Peer
     * 
     * @access protected
     * @return void
     **/
    protected function bitWirePeerAdded (BitWire_Peer $Peer) { }
    // }}}
    
    // {{{ bitWirePeerDisconnected
    /**
     * Callback: A peer was removed/disconnected from this controller
     * 
     * @param BitWire_Peer $Peer
     * 
     * @access protected
     * @return void
     **/
    protected function bitWirePeerDisconnected (BitWire_Peer $Peer) { }
    // }}}
    
    // {{{ bitWirePayloadReceived
    /**
     * Callback: A payload was received from a peer
     * 
     * @param BitWire_Message_Payload $receivedPayload
     * @param BitWire_Peer $receivingPeer
     * 
     * @access protected
     * @return bool
     **/
    protected function bitWirePayloadReceived (BitWire_Message_Payload $receivedPayload, BitWire_Peer $receivingPeer) { }
    // }}}
  }

?>