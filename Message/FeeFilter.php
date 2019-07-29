<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_FeeFilter extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'feefilter';
    const PAYLOAD_MIN_VERSION = 70013;
    
    /* Minimum fee for relayed transactions */
    private $Fee = 0;
    
    // {{{ getFee
    /**
     * Retrive minimum fee for relayed transactions
     * 
     * @access public
     * @return int
     **/
    public function getFee () {
      return $this->Fee;
    }
    // }}}
    
    // {{{ setFee
    /**
     * Set the minimum fee for relayed transactions
     * 
     * @param int $Fee
     * 
     * @access public
     * @return void
     **/
    public function setFee ($Fee) {
      $this->Fee = (int)$Fee;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse received payload for this message
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      // Check the size of payload
      if (strlen ($Data) != 8)
        return false;
      
      // Unpack nonce from payload
      $Fee = unpack ('Pfee', $Data);
      $this->Fee = array_shift ($Fee);
      
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
      // Return payload
      return pack ('P', $this->Fee);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('feefilter', 'BitWire_Message_FeeFilter');

?>