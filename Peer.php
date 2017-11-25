<?PHP

  require_once ('qcEvents/Interface/Stream/Consumer.php');
  require_once ('qcEvents/Hookable.php');
  
  // BitWire Messages / Payloads
  require_once ('BitWire/Message.php');
  require_once ('BitWire/Message/Version.php');
  require_once ('BitWire/Message/Version/Acknowledgement.php');
  require_once ('BitWire/Message/Ping.php');
  require_once ('BitWire/Message/Pong.php');
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
     * @param int $Version (optional) Negotiate this protocol-version
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Controller $Controller = null, $Version = 70015) {
      // Store controller and version
      $this->Controller = $Controller;
      $this->Version = $Version;
      
      // Create Callback for Connected-Event at peer
      $this->peerCallbackConnected = function (qcEvents_Interface_Stream $Peer) {
        // Make sure the peer is known
        if (!$this->validatePeer ($Peer)) {
          trigger_error ('Connect-Event for invalid peer received');
          
          $Peer->removeHook ('socketConnected', $this->peerCallbackConnected);
          
          return;
        }
        
        // Send out version
        $Version = new BitWire_Message_Version;
        $Version->setAddress ($Peer->getLocalAddress ());
        $Version->setPort ($Peer->getLocalPort ());
        $Version->setPeerAddress ($Peer->getRemoteAddress ());
        $Version->setPeerPort ($Peer->getRemotePort ());
        
        $this->sendPayload ($Version);
      };
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
      return ($this->peerInit && ($this->peerVersion !== null) && $this->Peer->isConnected ());
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
          $this->pendingMessage = new BitWire_Message (null, $this->peerVersion);
        
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
          $this->peerVersion = $Payload->getVersion ();
        
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
            $this->___raiseCallback ($this->peerInitCallback [0], $this->Peer, $this, true, $this->peerInitCallback [1]);
            $this->peerInitCallback = null;
          }
          
          // Run the generic callback
          $this->___callback ('eventPipedStream', $this->Peer);
          $this->___callback ('bitwireConnected');
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
     * @param callable $Callback (optional)
     * @param mixed $Private (optional)
     * 
     * @access public
     * @return void
     **/
    public function sendPayload (BitWire_Message_Payload $Payload, callable $Callback = null, $Private = null) {
      // Create envelope
      $Message = new BitWire_Message ($Payload, $this->Version);
      
      // Write out to peer
      $this->Peer->write ($Message->toBinary (), function ($Peer, $Status) use ($Message, $Payload, $Callback, $Private) {
        // Raise the specific callback if there is one
        $this->___raiseCallback ($Callback, $Status, $Private);
        
        // Only proceed on success
        if (!$Status)
          return;
        
        // Raise unspecific callbacks
        $this->___callback ('payloadSent', $Payload);
        $this->___callback ('messageSent', $Message);
      });
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
     * @return void
     **/
    public function close (callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $this, $Private);
    }
    // }}}
    
    // {{{ initStreamConsumer
    /**
     * Setup ourself to consume data from a stream
     * 
     * @param qcEvents_Interface_Source $Source
     * @param callable $Callback (optional) Callback to raise once the pipe is ready
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised in the form of
     *  
     *   function (qcEvents_Interface_Stream $Source, qcEvents_Interface_Stream_Consumer $Destination, bool $Status, mixed $Private = null) { }
     * 
     * @access public
     * @return callable
     **/
    public function initStreamConsumer (qcEvents_Interface_Stream $Peer, callable $Callback = null, $Private = null) {
      // Store our new peer
      $this->Peer = $Peer;
      $this->peerInit = false;
      $this->peerVersion = null;
      
      // Register callbacks
      if ($Callback)
        $this->peerInitCallback = array ($Callback, $Private);
      
      $this->Peer->addHook ('socketConnected', $this->peerCallbackConnected);
      
      # if ($Peer->isConnected ())
      #   call_user_func ($this->peerCallbackConnected, $this->Peer);
    }
    // }}}
    
    // {{{ deinitConsumer
    /**
     * Callback: A source was removed from this consumer
     * 
     * @param qcEvents_Interface_Source $Source
     * @param callable $Callback (optional) Callback to raise once the pipe is ready
     * @param mixed $Private (optional) Any private data to pass to the callback
     * 
     * The callback will be raised in the form of 
     * 
     *   function (qcEvents_Interface_Source $Source, qcEvents_Interface_Stream_Consumer $Destination, bool $Status, mixed $Private = null) { }
     * 
     * @access public
     * @return void
     **/
    public function deinitConsumer (qcEvents_Interface_Source $Source, callable $Callback = null, $Private = null) {
      if ($Callback)
        call_user_func ($Callback, $Source, $this, true, $Private);
    }
    // }}}
    
    public function requestInventory (array $Inventory) {
      $this->sendPayload (new BitWire_Message_GetData ($Inventory));
    }
    
    protected function bitwireConnected () { }
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