<?php

  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class SendCompact extends Payload {
    protected const PAYLOAD_COMMAND = 'sendcmpct';
    protected const PAYLOAD_HAS_DATA = true;
    
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
     * @return void
     **/
    public function parse (string $Data) : void {
      if (strlen ($Data) != 9)
        throw new \LengthException ('Invalid payload-size');
      
      $Values = unpack ('cCompact/PVersion', $Data);
      
      $this->Compact = ($Values ['Compact'] == 0x01);
      $this->Version = $Values ['Version'];
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
      return pack ('cP', ($this->Compact ? 0x01 : 0x00), $this->Version);
    }
    // }}}
  }
