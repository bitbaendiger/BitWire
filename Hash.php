<?PHP

  class BitWire_Hash {
    /* Internally stored hash */
    private $Data = '';
    
    // {{{ fromBinary
    /**
     * Create a hash from its binary representation
     * 
     * @param string $Data
     * @param bool $Internal (optional)
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public static function fromBinary ($Data, $Internal = false) {
      $Instance = new static;
      $Instance->Data = ($Internal ? strrev ($Data) : $Data);
      
      return $Instance;
    }
    // }}}
    
    // {{{ fromHex
    /**
     * Create a hash from its hex represenation
     * 
     * @param string $Data
     * @param bool $Internal (optional)
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public static function fromHex ($Data, $Internal = false) {
      return static::fromBinary (hex2bin ($Data), $Internal);
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new Hash
     * 
     * @param string $Data (optional) Generate hash from this data
     * @param bool $Internal (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Data = null, $Internal = true) {
      if ($Data === null)
        return;
      
      $this->Data = hash ('sha256', hash ('sha256', $Data, true), true);
      
      if ($Internal)
        $this->Data = strrev ($this->Data);
    }
    // }}}
    
    // {{{ __toString
    /**
     * Output this hash in human readable form
     * 
     * @access friendly
     * @return string
     **/
    function __toString () {
      return bin2hex ($this->Data);
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for vardump()
     * 
     * @access public
     * @return array
     **/
    function __debugInfo () {
      return array (
        'hash' => $this->__toString (),
      );
    }
    // }}}
    
    // {{{ isEmpty
    /**
     * Check for an empty hash
     * 
     * @access public
     * @return bool
     **/
    public function isEmpty () {
      return (strcmp ($this->Data, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00") == 0);
    }
    // }}}
    
    // {{{ toBinary
    /** 
     * Convert this hash to binary representation
     * 
     * @access public
     * @return string
     **/
    public function toBinary ($Internal = false) {
      return ($Internal ? strrev ($this->Data) : $this->Data);
    }
    // }}}
    
    // {{{ compare
    /**
     * Compare this hash with another one
     * 
     * @param BitWire_Hash $With
     * 
     * @access public
     * @return bool
     **/
    public function compare (BitWire_Hash $With) {
      return (strcmp ($this->Data, $With->Data) == 0);
    }
    // }}}
  }

?>