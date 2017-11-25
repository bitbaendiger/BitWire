<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Reject extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'reject';
    const PAYLOAD_MIN_VERSION = 70002;
    
    /* Reject-code describing what happened */
    private $Code = 0x00;
    
    /* The actual command that was rejected */
    private $Command = '';
    
    /* Human-readable reason of the reject-message */
    private $Reason = '';
    
    /* Extra-Data depending on code */
    private $Extra = '';
    
    // {{{ parseData
    /**
     * Try to parse input data
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      $Length = $Offset = 0;
      
      $this->Command = $this::readCompactString ($Data, $Length);
      $Offset = $Length;
      
      $this->Code = ord ($Data [$Offset++]);
      $this->Reason = $this::readCompactString ($Data, $Length, $Offset);
      $Offset += $Length;
      
      $this->Extra = substr ($Data, $Offset);
      
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create a binary representation of this payload
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      return
        $this::toCompactString ($this->Command) .
        chr ($this->Code) .
        $this::toCompactString ($this->Reason) .
        $this->Extra;
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('reject', 'BitWire_Message_Reject');

?>