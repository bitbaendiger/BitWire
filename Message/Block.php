<?PHP

  require_once ('BitWire/Block.php');
  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Block extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'block';
    
    /* Block stored on this message */
    private $Block = null;
    
    // {{{ __construct
    /**
     * Create a new block-message
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for var_dump() of this object
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      return array (
        'block' => $this->Block,
      );
    }
    // }}}
    
    // {{{ getBlock
    /**
     * Retrive the block stored on this message
     * 
     * @access public
     * @return BitWire_Block
     **/
    public function getBlock () {
      return $this->Block;
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
      $this->Block = new BitWire_Block;
      
      return $this->Block->parseData ($Data);
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
      return $this->Block->toBinary ();
    }
    // }}}
  }
  
  // Register our payload-class
  BitWire_Message_Payload::registerCommand ('block', 'BitWire_Message_Block');

?>