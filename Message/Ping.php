<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Ping extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'ping';
    const PAYLOAD_MIN_VERSION = 60001;
    
    /* Number of pings sent/received */
    private static $Counter = 0;
    
    /* Nonce of this ping */
    private $Nonce = null;
    
    // {{{ __construct
    /**
     * Create a new ping-message-payload
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      $this->Nonce = self::$Counter++;
    }
    // }}}
    
    // {{{ getNonce
    /**
     * Retrive the nonce of this ping
     * 
     * @access public
     * @return int
     **/
    public function getNonce () {
      return $this->Nonce;
    }
    // }}}
    
    // {{{ setNonce
    /**
     * Set a new nonce for this ping
     * 
     * @param int $Nonce
     * 
     * @access public
     * @return void
     **/
    public function setNonce ($Nonce) {
      $this->Nonce = (int)$Nonce;
    }
    // }}}
    
    // {{{ parseData
    /**
     * Try to parse received payload for this message
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      // Don't allow payload on ping before protocol-version 60001
      if (is_object ($Message = $this->getMessage ()) && ($Message->getVersion () < self::PAYLOAD_MIN_VERSION)) {
        $this->Nonce = null;
        
        return (strlen ($Data) == 0);
      }
      
      // Check the size of payload
      if (strlen ($Data) != 8)
        return false;
      
      // Unpack nonce from payload
      $Nonce = unpack ('Pnonce', $Data);
      $this->Nonce = array_shift ($Nonce);
      
      // Indicate success
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      // There is no payload before procotol-version 60001
      if (($this->Nonce === null) || (is_object ($Message = $this->getMessage ()) && ($Message->getVersion () < self::PAYLOAD_MIN_VERSION)))
        return '';
      
      // Return payload
      return pack ('P', $this->Nonce);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('ping', 'BitWire_Message_Ping');

?>