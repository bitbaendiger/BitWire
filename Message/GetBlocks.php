<?PHP

  require_once ('BitWire/Message/Inventory.php');
  require_once ('BitWire/Hash.php');
  
  class BitWire_Message_GetBlocks extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'getblocks';
    
    private $Version = null;
    private $Hashes = array ();
    private $StopHash = null;
    
    // {{{ __construct
    /**
     * Create a new GetBlocks-Message-Payload
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      $this->StopHash = BitWire_Hash::fromBinary ("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");
    }
    // }}}
    
    public function getHashes () {
      return $this->Hashes;
    }
    
    // {{{ addHash
    /**
     * Append a hash to this request
     * 
     * @access public
     * @return void
     **/
    public function addHash (BitWire_Hash $Hash) {
      $this->Hashes [] = $Hash;
    }
    // }}}
    
    // {{{ parseData
    /** 
     * Parse binary contents for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      // Retrive the length of the buffer
      $Length = strlen ($Data);
      
      if ($Length < 38) {
        trigger_error ('Input too short');
        
        return false;
      }
      
      // Read the version
      $Values = unpack ('Vversion', substr ($Data, 0, 4));
      $this->Version = $Values ['version'];
      
      // Read number of hashes
      if (($Count = self::readCompactSize ($Data, $cLength, 4)) === false) {
        trigger_error ('Failed to read length');
        
        return false;
      }
      
      // Check the length again
      if ($Length != 36 + $cLength + $Count * 32) {
        trigger_error ('Invalid size');
        
        return false;
      }
      
      // Read all hashes
      $this->Hashes = array ();
      
      for ($i = 0; $i < $Count; $i++)
        $this->Hashes [] = BitWire_Hash::fromBinary (substr ($Data, $i * 32 + 4 + $cLength, 32), true);
      
      // Read the stop-hash
      $this->StopHash = BitWire_Hash::fromBinary (substr ($Data, -32, 32), true);
      
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Generate binary representation from this object
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      $Result = pack ('V', $this->Version) . $this::toCompactSize (count ($this->Hashes));
      
      foreach ($this->Hashes as $Hash)
        $Result .= $Hash->toBinary (true);
      
      return $Result . $this->StopHash->toBinary (true);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('getblocks', 'BitWire_Message_GetBlocks');

?>