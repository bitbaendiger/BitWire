<?php

  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class Headers extends Payload {
    protected const PAYLOAD_COMMAND = 'headers';
    protected const PAYLOAD_MIN_VERSION = 31800;
    
    /* All headers */
    private $Headers = [ ];
    
    // {{{ getHeaders
    /**
     * Retrive all headers from this message
     * 
     * @access public
     * @return array
     **/
    public function getHeaders () : array {
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
     * @return void
     **/
    public function parse (string $Data) : void {
      // Check the length
      $Length = strlen ($Data);
      $Offset = null;
      
      // Read number of headers
      $Count = Payload::readCompactSize ($Data, $Offset, $Length);
      
      // Check the length
      if ($Length != $Offset + ($Count * 81))
        throw new \LengthException ('Invalid payload-size');
      
      // Read all headers
      $this->Headers = [ ];
      
      for ($i = 0; $i < $Count; $i++) {
        // Create a new block
        $Header = new BitWire\Block ();
        
        // Try to parse the header
        $Header->parse ($Data, $Offset, $Length);
        
        // Push to headers
        $this->Headers [] = $Header;
      }
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      $Buffer = $this::toCompactSize (count ($this->Headers));
      
      foreach ($this->Headers as $Header)
        $Buffer .= $Header->getHeader () . "\x00";
      
      return $Buffer;
    }
    // }}}
  }
