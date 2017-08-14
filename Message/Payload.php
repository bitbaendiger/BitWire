<?PHP

  class BitWire_Message_Payload {
    const PAYLOAD_COMMAND = null;
    const PAYLOAD_HAS_DATA = null;
    
    /* Registered Command-Classes */
    private static $Commands = array ();
    
    /* Instance of Message this payload is for */
    private $Message = null;
    
    /* Parsed Command */
    private $Command = null;
    
    /* Unparsed Data */
    private $Data = null;
    
    // {{{ registerCommand
    /**
     * Register a classname for a command
     * 
     * @param string $Command
     * @param string $Class
     * 
     * @access public
     * @return void
     **/
    public static function registerCommand ($Command, $Class) {
      if (!class_exists ($Class) || !is_subclass_of ($Class, __CLASS__))
        return false;
      
      self::$Commands [$Command] = $Class;
    }
    // }}}
    
    // {{{ parse
    /**
     * Create a new payload-object for a given command
     * 
     * @param string $Command
     * @param string $Data
     * @param BitWire_Message $Message (optional)
     * 
     * @access public
     * @return BitWire_Message_Payload
     **/
    public static function parse ($Command, $Data, BitWire_Message $Message = null) {
      if (isset (self::$Commands [$Command]))
        $Class = self::$Commands [$Command];
      else
        $Class = get_called_class ();
      
      $Payload = new $Class;
      $Payload->Command = $Command;
      
      if ($Message)
        $Payload->setMessage ($Message);
      
      if (!$Payload->parseData ($Data))
        return false;
      
      return $Payload;
    }
    // }}}
    
    // {{{ readCompactSize
    /**
     * Read a compact size
     * 
     * @param string $Data
     * @param int &$Length (optional)
     * @param int $Offset (optional)
     * 
     * @access public
     * @return int
     **/
    public static function readCompactSize ($Data, &$Length = null, $Offset = 0) {
      // Check boundaries
      $iLength = strlen ($Data);
      
      if ($iLength < $Offset + 1)
        return false;
      
      // Try to read type of interger
      $Byte = ord ($Data [$Offset]);
      $Length = 1;
      
      // Process the value
      if ($Byte <= 252)
        return $Byte;
      
      if ($Byte == 253) {
        $Mod = 'v';
        $Length += 2;
      } elseif ($Byte == 254) {
        $Mod = 'V';
        $Length += 4;
      } elseif ($Byte == 255) {
        $Mod = 'P';
        $Length += 8;
      }
      
      if ($iLength < $Offset + $Length)
        return false;
      
      // Extract the value
      $Value = unpack ($Mod . 'value', substr ($Data, $Offset + 1, $Length - 1));
      
      return $Value ['value'];
    }
    // }}}
    
    // {{{ readCompactString
    /**
     * Read a string from data that is limited by a compact size
     * 
     * @param string $Data
     * @param int &$Length (optional)
     * @param int $Offset (optional)
     * 
     * @access public
     * @return string
     **/
    public static function readCompactString ($Data, &$Length = null, $Offset = 0) {
      if (($Size = self::readCompactSize ($Data, $Length, $Offset)) === false)
        return false;
      
      $Length += $Size;
      
      return substr ($Data, $Offset + $Length - $Size, $Size);
    }
    // }}}
    
    // {{{ toCompactSize
    /**
     * Convert an integer to compact-size
     * 
     * @param int $Value
     * 
     * @access public
     * @return string
     **/
    public static function toCompactSize ($Value) {
      if ($Value <= 252)
        return chr ($Value);
      
      if ($Value <= 0xFFFF)
        return pack ('Cv', 0xFD, $Value);
      
      if ($Value <= 0xFFFFFFFF)
        return pack ('CV', 0xFE, $Value);
      
      return pack ('CP', 0xFF, $Value);
    }
    // }}}
    
    // {{{ toCompactString
    /**
     * Convert a string into a compact string
     * 
     * @param string $Data
     * 
     * @access public
     * @return string
     **/
    public static function toCompactString ($Data) {
      return self::toCompactSize (strlen ($Data)) . $Data;
    }
    // }}}
    
    // {{{ getCommand
    /**
     * Retrive the command for this payload
     * 
     * @access public
     * @return string
     **/
    public function getCommand () {
      if ($this::PAYLOAD_COMMAND !== null)
        return $this::PAYLOAD_COMMAND;
      
      return $this->Command;
    }
    // }}}
    
    // {{{ getMessage
    /**
     * Retrive the message this payload was made for
     * 
     * @access public
     * @return BitWire_Message
     **/
    public function getMessage () {
      return $this->Message;
    }
    // }}}
    
    // {{{ setMessage
    /**
     * Assign the message this payload is for
     * 
     * @param BitWire_Message $Message
     * 
     * @access public
     * @return void
     **/
    public function setMessage (BitWire_Message $Message) {
      $this->Message = $Message;
    }
    // }}}
    
    // {{{ parseData
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      if (strlen ($Data) > 0) {
        trigger_error ('Unparsed data on payload for ' . get_class ($this));
        
        if ($this::PAYLOAD_HAS_DATA === false)
          return false;
      }
      
      $this->Data = $Data;
      
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
      if ($this::PAYLOAD_HAS_DATA === false)
        return '';
      
      return $this->Data;
    }
    // }}}
  }

?>