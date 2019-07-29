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
    
    // {{{ getCode
    /**
     * Retrive the reject-code
     * 
     * @access public
     * @return int
     **/
    public function getCode () {
      return $this->Code;
    }
    // }}}
    
    // {{{ setCode
    /**
     * Set the reject-code for this payload
     * 
     * @param int $Code
     * 
     * @access public
     * @return bool
     **/
    public function setCode ($Code) {
      if (($Code < 0) || ($Code > 255))
        return false;
      
      $this->Code = (int)$Code;
      
      return true;
    }
    // }}}
    
    // {{{ getReason
    /**
     * Retrive the reason-text
     * 
     * @access public
     * @return string
     **/
    public function getReason () {
      return $this->Reason;
    }
    // }}}
    
    // {{{ setReason
    /**
     * Set reason-text for this payload
     * 
     * @param string $Reason
     * 
     * @access public
     * @return bool
     **/
    public function setReason ($Reason) {
      $this->Reason = $Reason;
      
      return true;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse input data
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      $Length = strlen ($Data);
      $Offset = 0;
      
      if ((($Command = $this::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($Code = $this::readChar ($Data, $Offset, 1, $Length)) === null) ||
          (($Reason = $this::readCompactString ($Data, $Offset, $Length)) === null))
        return false;
      
      $this->Command = $Command;
      $this->Code = $Code;
      $this->Reason = $Reason;
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