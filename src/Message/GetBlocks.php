<?php

  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class GetBlocks extends Payload {
    protected const PAYLOAD_COMMAND = 'getblocks';
    
    private $Version = null;
    private $Hashes = [ ];
    private $StopHash = null;
    
    // {{{ __construct
    /**
     * Create a new GetBlocks-Message-Payload
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      $this->StopHash = BitWire\Hash::fromBinary ("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }
    // }}}
    
    public function getHashes () : array {
      return $this->Hashes;
    }
    
    // {{{ addHash
    /**
     * Append a hash to this request
     * 
     * @access public
     * @return void
     **/
    public function addHash (BitWire\Hash $Hash) {
      $this->Hashes [] = $Hash;
    }
    // }}}
    
    // {{{ parse
    /** 
     * Parse binary contents for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data) : void {
      // Retrive the length of the buffer
      $Length = strlen ($Data);
      $Offset = 0;
      
      // Read initial values
      $Version = Payload::readUInt32 ($Data, $Offset, $Length);
      $Count = Payload::readCompactSize ($Data, $Offset, $Length);
      
      $this->Version = $Version;
      
      // Check the length again
      if ($Length != $Offset + $Count * 32)
        throw new \LengthException ('Invalid payload-size');
      
      // Read all hashes
      $this->Hashes = [ ];
      
      for ($i = 0; $i < $Count; $i++)
        $this->Hashes [] = BitWire\Hash::fromBinary (substr ($Data, $Offset + $i * 32, 32), true);
      
      // Read the stop-hash
      $this->StopHash = BitWire\Hash::fromBinary (substr ($Data, -32, 32), true);
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Generate binary representation from this object
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      $Result = pack ('V', $this->Version) . $this::toCompactSize (count ($this->Hashes));
      
      foreach ($this->Hashes as $Hash)
        $Result .= $Hash->toBinary (true);
      
      return $Result . $this->StopHash->toBinary (true);
    }
    // }}}
  }
