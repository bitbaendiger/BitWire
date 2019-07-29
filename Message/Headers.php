<?PHP

  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Block.php');
  
  class BitWire_Message_Headers extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'headers';
    const PAYLOAD_MIN_VERSION = 31800;
    
    /* All headers */
    private $Headers = array ();
    
    // {{{ getHeaders
    /**
     * Retrive all headers from this message
     * 
     * @access public
     * @return array
     **/
    public function getHeaders () {
      return $this->Headers;
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
      // Check the length
      $Length = strlen ($Data);
      $Offset = null;
      
      // Read number of headers
      if (($Count = BitWire_Message_Payload::readCompactSize ($Data, $Offset, $Length)) === null)
        return false;
      
      // Check the length
      if ($Length != $Offset + ($Count * 81))
        return false;
      
      // Read all headers
      $this->Headers = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Create a new block
        $Header = new BitWire_Block;
        
        // Try to parse the header
        if (!$Header->parse ($Data, $Offset, $Length))
          return false;
        
        // Push to headers
        $this->Headers [] = $Header;
      }
      
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
      $Buffer = $this::toCompactSize (count ($this->Headers));
      
      foreach ($this->Headers as $Header)
        $Buffer .= $Header->getHeader () . "\x00";
      
      return $Buffer;
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('headers', 'BitWire_Message_Headers');

?>