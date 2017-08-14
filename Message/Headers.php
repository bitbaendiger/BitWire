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
      // Read number of headers
      if (($Count = $this::readCompactSize ($Data, $Size)) === false)
        return false;
      
      // Check the length
      $Length = strlen ($Data);
      
      if ($Length != $Size + ($Count * 81))
        return false;
      
      // Read all headers
      $this->Headers = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Create a new block
        $Header = new BitWire_Block;
        
        // Try to parse the header
        if (!$Header->parseData (substr ($Data, $Size + $i * 81, 81)))
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