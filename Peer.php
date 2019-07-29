<?PHP

  require_once ('qcEvents/Interface/Stream/Consumer.php');
  require_once ('qcEvents/Hookable.php');
  require_once ('qcEvents/Promise.php');
  
  // BitWire Messages / Payloads
  require_once ('BitWire/Message.php');
  require_once ('BitWire/Message/Version.php');
  require_once ('BitWire/Message/Version/Acknowledgement.php');
  require_once ('BitWire/Message/Ping.php');
  require_once ('BitWire/Message/Pong.php');
  require_once ('BitWire/Message/Reject.php');
  require_once ('BitWire/Message/SendHeaders.php');
  require_once ('BitWire/Message/SendCompact.php');
  require_once ('BitWire/Message/FeeFilter.php');
  require_once ('BitWire/Message/GetBlocks.php');
  require_once ('BitWire/Message/GetHeaders.php');
  require_once ('BitWire/Message/GetData.php');
  require_once ('BitWire/Message/GetAddresses.php');
  require_once ('BitWire/Message/Inventory.php');
  require_once ('BitWire/Message/Addresses.php');
  require_once ('BitWire/Message/Transaction.php');
  require_once ('BitWire/Message/Block.php');
  require_once ('BitWire/Message/Headers.php');
  require_once ('BitWire/Message/NotFound.php');
  
  class BitWire_Peer extends qcEvents_Hookable implements qcEvents_Interface_Stream_Consumer {
    /* Publish-Methods */
    const PUBLISH_INVENTORY = 0;
    const PUBLISH_HEADERS = 1;
    
    private $publishMethod = BitWire_Peer::PUBLISH_INVENTORY;
    
    /* Minimum fee for transactions to be relayed */
    private $minFee = 0;
    
    /* Version to negotiate with the peer */
    private $Version = 70015;
    
    /* Network for this peer (see BitWire_Message) */
    private $Network = null;
    
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
     * @param BitWire_Controller $Controller (optional)
     * @param int $Version (optional) Negotiate this protocol-version
     * @param string $UserAgent (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Controller $Controller = null, $Version = null, $Network = null, $UserAgent = null) {
      // Store controller and version
      $this->Controller = $Controller;
      $this->Version = ($Version === null ? 70015 : $Version);
      $this->Network = ($Network === null ? BitWire_Message::BITCOIN_MAIN : $Network);
    }
    // }}}
    
    // {{{ isConnected
    /**
     * Check if this peer is conntected to Bitcoin-Network
     * 
     * @access public
     * @return bool
     **/
    public function isConnected () {
      return ($this->peerInit && ($this->peerVersion !== null) && $this->Peer && $this->Peer->isConnected ());
    }
    // }}}
    
    // {{{ validatePeer
    /**
     * Check if a given peer is valid for this stream
     * 
     * @param qcEvents_Interface_Stream $Peer
     * 
     * @access private
     * @return bool
     **/
    private function validatePeer (qcEvents_Interface_Stream $Peer) {
      return ($Peer === $this->Peer);
    }
    // }}}
    
    // {{{ getPeerSocket
    /**
     * Retrive the underlying socket-connection of this peer
     * 
     * @access public
     * @return qcEvents_Socket
     **/
    public function getPeerSocket () {
      return $this->Peer;
    }
    // }}}
    
    // {{{ getPeerAddress
    /**
     * Retrive the full address to this peer
     * 
     * @access public
     * @return string
     **/
    public function getPeerAddress () {
      if (!$this->Peer)
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
     * @return BitWire_Message_Version
     **/
    public function getPeerVersion () {
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
    public function getPeerUserAgent () {
      if ($this->peerVersion)
        return $this->peerVersion->getUserAgent ();
    }
    // }}}
    
    // {{{ consume
    /**
     * Consume a set of data
     * 
     * @param mixed $Data
     * @param qcEvents_Interface_Source $Source
     * 
     * @access public
     * @return void
     **/
    public function consume ($Data, qcEvents_Interface_Source $Source) {
      // Make sure we receive from a well-known peer
      if (!$this->validatePeer ($Source))
        return;
      
      $Length = strlen ($Data);
      
      while ($Length > 0) {
        // Make sure we have a pending message
        if (!$this->pendingMessage)
          $this->pendingMessage = new BitWire_Message (null, ($this->peerVersion ? $this->peerVersion->getVersion () : null), $this->Network);
        
        // Forward the data to next message
        if (($consumedLength = $this->pendingMessage->consume ($Data)) === false) {
          trigger_error ('Unable to parse message - discarding');
          
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
          
          return ($this->pendingMessage = null);
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
     * @param BitWire_Message $Message
     * 
     * @access private
     * @return void
     **/
    private function processMessage (BitWire_Message $Message) {
      // Retrive Payload from message
      if (!is_object ($Payload = $Message->getPayload ()))
        return trigger_error ('Message without payload received. That should not be possible.');
      
      // Check if we are negotiating
      if (!$this->peerInit || ($this->peerVersion === null)) {
        // Check if we learned peer's version
        if ($Payload instanceof BitWire_Message_Version) {
          // Store the version of the peer
          $this->peerVersion = $Payload;
        
          // Respond
          $this->sendPayload (new BitWire_Message_Version_Acknowledgement);
        
        // Check if the peer accepted our version
        } elseif ($Payload instanceof BitWire_Message_Version_Acknowledgement) {
          $this->peerInit = true;
        
        // Anything else is unwanted here
        } else
          trigger_error ('Invalid message during negotiation');
        
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
      if ($Payload instanceof BitWire_Message_SendHeaders)
        $this->publishMethod = $this::PUBLISH_HEADERS;
      
      elseif ($Payload instanceof BitWire_Message_FeeFilter)
        $this->minFee = $Payload->getFee ();
      
      // Do something automatically
      elseif ($Payload instanceof BitWire_Message_Ping)
        $this->sendPayload (new BitWire_Message_Pong ($Payload));
      
      
      # sendcmpct
      # addr
      # getaddr
      # filterload
      # filterclear
      # filteradd
      # feefilter
      # reject
      
      $this->___callback ('messageReceived', $Message);
      $this->___callback ('payloadReceived', $Payload);
    }
    // }}}
    
    // {{{ sendPayload
    /**
     * Write out payload to peer
     * 
     * @param BitWire_Message_Payload $Payload
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function sendPayload (BitWire_Message_Payload $Payload, callable $Callback = null, $Private = null) : qcEvents_Promise {
      // Create envelope
      $Message = new BitWire_Message ($Payload, $this->Version, $this->Network);
      
      // Write out to peer
      return $this->Peer->write ($Message->toBinary ())->then (
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
     * @param callable $Callback (optional) Callback to raise once the interface is closed
     * @param mixed $Private (optional) Private data to pass to the callback
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function close () : qcEvents_Promise {
      $this->___callback ('eventClosed');
      
      return qcEvents_Promise::resolve ();
    }
    // }}}
    
    // {{{ initStreamConsumer
    /**
     * Setup ourself to consume data from a stream
     * 
     * @param qcEvents_Interface_Source $Source
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function initStreamConsumer (qcEvents_Interface_Stream $Peer) : qcEvents_Promise {
      return new qcEvents_Promise (
        function (callable $Resolve, callable $Reject) use ($Peer) {
          // Store our new peer
          $this->Peer = $Peer;
          $this->peerInit = false;
          $this->peerVersion = null;
          
          // Register callbacks
          $this->peerInitCallback = array ($Resolve, $Reject);
          
          ($Peer->isConnected () ? qcEvents_Promise::resolve () : $this->Peer->once ('socketConnected'))->then (
            function () use ($Peer) {
              // Make sure the peer is still valid
              if ($Peer !== $this->Peer)
                return trigger_error ('Connect-Event for invalid peer received');
              
              // Send out version
              $Version = new BitWire_Message_Version;
              $Version->setAddress ($Peer->getLocalAddress ());
              $Version->setPort ($Peer->getLocalPort ());
              $Version->setPeerAddress ($Peer->getRemoteAddress ());
              $Version->setPeerPort ($Peer->getRemotePort ());
              
              # TODO
              # if ($UserAgent !== null)
              #  $Version->setUserAgent ($UserAgent);
              
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
     * @param qcEvents_Interface_Source $Source
     * 
     * @access public
     * @return qcEvents_Promise
     **/
    public function deinitConsumer (qcEvents_Interface_Source $Source) : qcEvents_Promise {
      return qcEvents_Promise::resolve ();
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
      $this->sendPayload (new BitWire_Message_GetData ($Inventory));
    }
    // }}}
    
    protected function bitwireConnected (BitWire_Message_Version $PeerVersion) { }
    protected function messageReceived (BitWire_Message $Message) { }
    protected function payloadReceived (BitWire_Message_Payload $Payload) { }
    protected function messageSent (BitWire_Message $Message) { }
    protected function payloadSent (BitWire_Message_Payload $Payload) { }
    protected function eventReadable () { }
    protected function eventClosed () { }
    
    // {{{ eventPipedStream
    /**
     * Callback: A stream was attached to this consumer
     * 
     * @param qcEvents_Interface_Stream $Source
     * 
     * @access protected
     * @return void
     **/
    protected function eventPipedStream (qcEvents_Interface_Stream $Source) { }
    // }}}
    
    // {{{ eventUnpiped
    /**
     * Callback: A source was removed from this consumer
     * 
     * @param qcEvents_Interface_Source $Source
     * 
     * @access protected
     * @return void
     **/
    protected function eventUnpiped (qcEvents_Interface_Source $Source) { }
    // }}}
  }

?>