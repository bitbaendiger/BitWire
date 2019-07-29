<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_SendCompact extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'sendcmpct';
    const PAYLOAD_HAS_DATA = true;
    
    /* Indicator if compact messages are desired */
    private $Compact = false;
    
    /* Version of this message */
    private $Version = 0x0000000000000000;
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      if (strlen ($Data) != 9)
        return false;
      
      $Values = unpack ('cCompact/PVersion', $Data);
      
      $this->Compact = ($Values ['Compact'] == 0x01);
      $this->Version = $Values ['Version'];
      
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
      return pack ('cP', ($this->Compact ? 0x01 : 0x00), $this->Version);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('sendcmpct', 'BitWire_Message_SendCompact');

?>