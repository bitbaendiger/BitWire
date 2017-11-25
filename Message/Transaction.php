<?PHP

  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction.php');
  
  class BitWire_Message_Transaction extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'tx';
    
    /* BitWire-Transcation */
    private $Transaction = null;
    
    // {{{ getTransaction
    /**
     * Retrive the transaction-object stored on this message
     * 
     * @access public
     * @return BitWire_Transaction
     **/
    public function getTransaction () {
      return $this->Transaction;
    }
    // }}}
    
    // {{{ setTransaction
    /**
     * Store a transaction on this payload
     * 
     * @param BitWire_Transaction $Transaction
     * 
     * @access public
     * @return void
     **/
    public function setTransaction (BitWire_Transaction $Transaction) {
      $this->Transaction = $Transaction;
    }
    // }}}
    
    // {{{ parseData
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      // Create a new transaction
      if (!$this->Transaction)
        $this->Transaction = new BitWire_Transaction;
      
      // Try to parse the data
      if (!$this->Transaction->parseData ($Data, $Length))
        return false;
      
      return (strlen ($Data) == $Length);
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
      if (!$this->Transaction)
        return false;
      
      return $this->Transaction->toBinary ();
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('tx', 'BitWire_Message_Transaction');

?>