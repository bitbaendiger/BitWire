<?php

  /**
   * BitWire - Bitcoin Peer
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

  namespace BitBaendiger\BitWire;
  use \quarxConnect\Events;
  
  class Peer extends Events\Hookable implements Events\Interface\Stream\Consumer {
    /* Publish-Methods */
    private const PUBLISH_INVENTORY = 0;
    private const PUBLISH_HEADERS = 1;
    
    private $publishMethod = Peer::PUBLISH_INVENTORY;
    
    /* Minimum fee for transactions to be relayed */
    private $minFee = 0;
    
    /* Protocol-Version to negotiate with the peer */
    private $protocolVersion = 70015;
    
    /* Network for this peer (see BitWire_Message) */
    private $Network = null;
    
    /* Custom user-agent to use */
    private $UserAgent = null;
    
    /* Stream-Interface to peer */
    private $Peer = null;
    
    /* Protocol-Version used at peer */
    private $peerVersion = null;
    
    /* Peer has accepted out version */
    private $peerInit = false;
    
    /* Callback to raise once the peer-connection was initialized */
    private $peerInitCallback = null;
    
    /* Stored callback registered at peer for socketConnected-Events */
    private $peerCallbackConnected = null;
    
    /* Next pending message */
    private $pendingMessage = null;
    
    // {{{ __construct
    /**
     * Create a new BitWire-Peer
     * 
     * @param Controller $Controller (optional)
     * @param int $protocolVersion (optional) Negotiate this protocol-version
     * @param int $Network (optional) Magic bytes identifying this network
     * @param string $UserAgent (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (Controller $Controller = null, int $protocolVersion = null, int $Network = null, string $UserAgent = null) {
      // Store controller and version
      $this->Controller = $Controller;
      $this->protocolVersion = ($protocolVersion === null ? 70015 : $protocolVersion);
      $this->Network = ($Network === null ? Message::BITCOIN_MAIN : $Network);
      $this->UserAgent = $UserAgent;
    }
    // }}}
    
    // {{{ isConnected
    /**
     * Check if this peer is conntected to Bitcoin-Network
     * 
     * @access public
     * @return bool
     **/
    public function isConnected () : bool {
      return ($this->peerInit && ($this->peerVersion !== null) && $this->Peer && $this->Peer->isConnected ());
    }
    // }}}
    
    // {{{ validatePeer
    /**
     * Check if a given peer is valid for this stream
     * 
     * @param Events\Interface\Stream $Peer
     * 
     * @access private
     * @return bool
     **/
    private function validatePeer (Events\Interface\Stream $Peer) : bool {
      return ($Peer === $this->Peer);
    }
    // }}}
    
    // {{{ getPeerSocket
    /**
     * Retrive the underlying socket-connection of this peer
     * 
     * @access public
     * @return Events\Socket
     **/
    public function getPeerSocket () : ?Events\Socket {
      if ($this->Peer instanceof Events\Socket)
        return $this->Peer;
      
      return null;
    }
    // }}}
    
    // {{{ getPeerAddress
    /**
     * Retrive the full address to this peer
     * 
     * @access public
     * @return string
     **/
    public function getPeerAddress () : ?string {
      if (!($this->Peer instanceof Events\Socket))
        return null;
      
      $Address = $this->Peer->getRemoteAddress ();
      
      if ($this->Peer::isIPv4 ($Address))
        $Address = '[' . $this->Peer::ip6fromBinary ($this->Peer::ip6toBinary ($Address)) . ']';
      
      return $Address . ':' . $this->Peer->getRemotePort ();
    }
    // }}}
    
    // {{{ getPeerVersion
    /**
     * Retrive the version-message of this peer
     * 
     * @access public
     * @return Message\Version
     **/
    public function getPeerVersion () : ?Message\Version {
      return $this->peerVersion;
    }
    // }}}
    
    // {{{ getPeerUserAgent
    /**
     * Retrive the user-agent used at the peer
     * 
     * @access public
     * @return string
     **/
    public function getPeerUserAgent () : ?string {
      if ($this->peerVersion)
        return $this->peerVersion->getUserAgent ();
      
      return null;
    }
    // }}}
    
    // {{{ getProtocolVersion
    /**
     * Retrive the protocol-version configured on this peer
     * 
     * @access public
     * @return int
     **/
    public function getProtocolVersion () : ?int {
      return $this->protocolVersion;
    }
    // }}}
    
    // {{{ consume
    /**
     * Consume a set of data
     * 
     * @param mixed $Data
     * @param Events\Interface\Source $Source
     * 
     * @access public
     * @return void
     **/
    public function consume ($Data, Events\Interface\Source $Source) : void {
      // Make sure we receive from a well-known peer
      if (!$this->validatePeer ($Source))
        return;
      
      $Length = strlen ($Data);
      
      while ($Length > 0) {
        // Make sure we have a pending message
        if (!$this->pendingMessage)
          $this->pendingMessage = new Message (null, ($this->peerVersion ? $this->peerVersion->getVersion () : null), $this->Network);
        
        // Forward the data to next message
        if (($consumedLength = $this->pendingMessage->consume ($Data)) === false) {
          $this->pendingMessage = null;
          
          return;
        }
        
        // Check if the message is ready
        if ($this->pendingMessage->isReady ()) {
          $Message = $this->pendingMessage;
          $this->pendingMessage = null;
          
          $this->processMessage ($Message);
        } elseif ($consumedLength < $Length) {
          trigger_error ('Message did not consume all bytes but is still not ready - discarding');
          
          $this->pendingMessage = null;
          
          return;
        }
        
        // Move forward
        $Length -= $consumedLength;
        $Data = substr ($Data, $consumedLength);
      }
    }
    // }}}
    
    // {{{ processMessage
    /**
     * Process an incoming message
     * 
     * @param Message $Message
     * 
     * @access private
     * @return void
     **/
    private function processMessage (Message $Message) : void {
      // Retrive Payload from message
      if (!is_object ($Payload = $Message->getPayload ()))
        return;
      
      // Check if we are negotiating
      if (!$this->peerInit || ($this->peerVersion === null)) {
        // Check if we learned peer's version
        if ($Payload instanceof Message\Version) {
          // Store the version of the peer
          $this->peerVersion = $Payload;
        
          // Respond
          $this->sendPayload (new Message\Version\Acknowledgement);
        
        // Check if the peer accepted our version
        } elseif ($Payload instanceof Message\Version\Acknowledgement) {
          $this->peerInit = true;
        
        // Check if the peer rejected something
        } elseif ($Payload instanceof BitWire_Message_Reject) {
          // Check for a registered callback
          if ($this->peerInitCallback) {
            call_user_func ($this->peerInitCallback [1], 'Rejected: ' . $Payload->getReason ());
            $this->peerInitCallback = null;
          }
        
        // Anything else is unwanted here (except "getsporks" which is a known bug on PIVX-based coins)
        } elseif ($Payload->getCommand () != 'getsporks')
          trigger_error ('Invalid message during negotiation (' . get_class ($Payload) . '/' . $Payload->getCommand () . ')');
        
        // Check wheter to run callbacks
        if ($this->peerInit && ($this->peerVersion !== null)) {
          // Check for a registered callback
          if ($this->peerInitCallback) {
            call_user_func ($this->peerInitCallback [0]);
            $this->peerInitCallback = null;
          }
          
          // Run the generic callback
          $this->___callback ('eventPipedStream', $this->Peer);
          $this->___callback ('bitwireConnected', $this->peerVersion);
        }
        
        return;
      }
      
      // Check wheter to change peer's properties
      if ($Payload instanceof Message\SendHeaders)
        $this->publishMethod = $this::PUBLISH_HEADERS;
      
      elseif ($Payload instanceof Message\FeeFilter)
        $this->minFee = $Payload->getFee ();
      
      // Reply to pings automatically
      elseif ($Payload instanceof Message\Ping) {
        if ($this->___callback ('peerPingReceived', $Payload) !== false)
          $this->sendPayload (new Message\Pong ($Payload));
        
        return;
      }
      
      $this->___callback ('messageReceived', $Message);
      $this->___callback ('payloadReceived', $Payload);
    }
    // }}}
    
    // {{{ sendPayload
    /**
     * Write out payload to peer
     * 
     * @param Message\Payload $Payload
     * 
     * @access public
     * @return Events\Promise
     **/
    public function sendPayload (Message\Payload $Payload) : Events\Promise {
      // Create envelope
      $Message = new Message ($Payload, $this->protocolVersion, $this->Network);
      
      // Write out to peer
      return $this->Peer->write (
        $Message->toBinary ()
      )->then (
        function () use ($Message, $Payload) {
          // Raise unspecific callbacks
          $this->___callback ('payloadSent', $Payload);
          $this->___callback ('messageSent', $Message);
        }
      );
    }
    // }}}
    
    // {{{ close
    /**
     * Close this event-interface
     * 
     * @access public
     * @return Events\Promise
     **/
    public function close () : Events\Promise {
      $this->___callback ('eventClosed');
      
      return Events\Promise::resolve ();
    }
    // }}}
    
    // {{{ initStreamConsumer
    /**
     * Setup ourself to consume data from a stream
     * 
     * @param Events\Interface\Source $Source
     * 
     * @access public
     * @return Events\Promise
     **/
    public function initStreamConsumer (Events\Interface\Stream $Peer) : Events\Promise {
      return new Events\Promise (
        function (callable $Resolve, callable $Reject) use ($Peer) {
          // Store our new peer
          $this->Peer = $Peer;
          $this->peerInit = false;
          $this->peerVersion = null;
          
          // Register callbacks
          $this->peerInitCallback = [ $Resolve, $Reject ];
          
          ($Peer->isConnected () ? Events\Promise::resolve () : $this->Peer->once ('socketConnected'))->then (
            function () use ($Peer) {
              // Make sure the peer is still valid
              if ($Peer !== $this->Peer)
                return trigger_error ('Connect-Event for invalid peer received');
              
              // Send out version
              $Version = new Message\Version;
              $Version->setVersion ($this->protocolVersion);
              $Version->setAddress ($Peer->getLocalAddress ());
              $Version->setPort ($Peer->getLocalPort ());
              $Version->setPeerAddress ($Peer->getRemoteAddress ());
              $Version->setPeerPort ($Peer->getRemotePort ());
              
              if ($this->UserAgent !== null)
                $Version->setUserAgent ($this->UserAgent);
              
              $this->sendPayload ($Version);
            }
          );
          
          $Peer->getEventBase ()->addTimeout (5)->then (
            function () use ($Peer) {
              // Check if we are connected by now
              if ($this->isConnected () || !$this->peerInitCallback || ($Peer !== $this->Peer))
                return;
              
              // Fail to process
              call_user_func ($this->peerInitCallback [1], 'Timeout reached');
              $this->peerInitCallback = null;
            }
          );
        }
      );
    }
    // }}}
    
    // {{{ deinitConsumer
    /**
     * Callback: A source was removed from this consumer
     * 
     * @param Events\Interface\Source $Source
     * 
     * @access public
     * @return Events\Promise
     **/
    public function deinitConsumer (Events\Interface\Source $Source) : Events\Promise {
      return Events\Promise::resolve ();
    }
    // }}}
    
    // {{{ requestInventory
    /**
     * Request inventory from this peer
     * 
     * @param array $Inventory
     * 
     * @access public
     * @return void
     **/
    public function requestInventory (array $Inventory) {
      $this->sendPayload (new Message\GetData ($Inventory));
    }
    // }}}
    
    protected function bitwireConnected (Message\Version $PeerVersion) { }
    protected function messageReceived (Message $Message) { }
    protected function payloadReceived (Message\Payload $Payload) { }
    protected function peerPingReceived (Message\Payload $Ping) { }
    protected function messageSent (Message $Message) { }
    protected function payloadSent (Message\Payload $Payload) { }
    protected function eventReadable () { }
    protected function eventClosed () { }
    
    // {{{ eventPipedStream
    /**
     * Callback: A stream was attached to this consumer
     * 
     * @param Events\Interface\Stream $Source
     * 
     * @access protected
     * @return void
     **/
    protected function eventPipedStream (Events\Interface\Stream $Source) { }
    // }}}
    
    // {{{ eventUnpiped
    /**
     * Callback: A source was removed from this consumer
     * 
     * @param Events\Interface\Source $Source
     * 
     * @access protected
     * @return void
     **/
    protected function eventUnpiped (Events\Interface\Source $Source) { }
    // }}}
  }
