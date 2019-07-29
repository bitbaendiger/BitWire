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
    
    // {{{ fromString
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
    public static function fromString ($Command, $Data, BitWire_Message $Message = null) : ?BitWire_Message_Payload {
      if (isset (self::$Commands [$Command]))
        $Class = self::$Commands [$Command];
      else
        $Class = get_called_class ();
      
      $Payload = new $Class;
      $Payload->Command = $Command;
      
      if ($Message)
        $Payload->setMessage ($Message);
      
      if (!$Payload->parse ($Data))
        return null;
      
      return $Payload;
    }
    // }}}
    
    // {{{ readCompactSize
    /**
     * Read a compact size
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return int
     **/
    public static function readCompactSize (&$Data, &$Offset, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Make sure there is something to read
      if ($Length <= $Offset)
        return null;
      
      // Try to read type of interger
      $Byte = ord ($Data [$Offset]);
      
      // Process the value
      if ($Byte <= 252) {
        $Offset++;
        
        return $Byte;
      }
      
      if ($Byte == 253) {
        $Mod = 'v';
        $rLength = 2;
      } elseif ($Byte == 254) {
        $Mod = 'V';
        $rLength = 4;
      } elseif ($Byte == 255) {
        $Mod = 'P';
        $rLength = 8;
      }
      
      // Make sure we have enough bytes to read
      if ($Length < $Offset + $rLength + 1)
        return null;
      
      // Extract the value
      $Value = unpack ($Mod . 'value', substr ($Data, $Offset + 1, $Length - 1));
      $Offset += $rLength + 1;
      
      return $Value ['value'];
    }
    // }}}
    
    // {{{ readCompactString
    /**
     * Read a string from data that is limited by a compact size
     * 
     * @param string $Data
     * @param int $Offset
     * @param int &$Length (optional)
     * 
     * @access public
     * @return string
     **/
    public static function readCompactString (&$Data, &$Offset, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Read the size of the string
      $tOffset = $Offset;
      
      if (($Size = self::readCompactSize ($Data, $tOffset, $Length)) === null)
        return null;
      
      // Read the value
      if (($Value = self::readChar ($Data, $tOffset, $Size, $Length)) === null)
        return null;
      
      // Patch back offset
      $Offset = $tOffset;
      
      return $Value;
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
    
    // {{{ readChar
    /**
     * Safely read a set of charaters from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Size
     * @param int $Length (optional)
     * 
     * @access public
     * @return string
     **/
    public static function readChar (&$Data, &$Offset, $Size, $Length = null) {
      // Make sure we know the length of our data
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Check if there is enough data to read
      if ($Length < $Offset + $Size)
        return null;
      
      // Generate the result
      $Result = substr ($Data, $Offset, $Size);
      $Offset += $Size;
      
      return $Result;
    }
    // }}}
    
    // {{{ readBoolean
    /**
     * Safely read a boolean from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return boolean
     **/
    public static function readBoolean (&$Data, &$Offset, $Length = null) {
      // Try to read the value
      if (($Value = self::readChar ($Data, $Offset, 1, $Length)) === null)
        return null;
      
      // Return the value
      return (strcmp ($Value, "\x00") != 0);
    }
    // }}}
    
    // {{{ readUInt16
    /**
     * Safely read an unsigned 16-bit Integer from an input-buffer
     * 
     * @param string $Data
     + @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return int
     **/
    public static function readUInt16 (&$Data, &$Offset, $Length = null) {
      // Try to read the input
      if (($Value = self::readChar ($Data, $Offset, 2, $Length)) === null)
        return null;
      
      // Convert to uint16
      $Value = unpack ('nvalue', $Value);
      
      return $Value ['value'];
    }
    // }}}
    
    // {{{ readUInt32
    /**
     * Safely read an unsigned 32-bit Integer from an input-buffer
     * 
     * @param string $Data
     + @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return int
     **/
    public static function readUInt32 (&$Data, &$Offset, $Length = null) {
      // Try to read the input
      if (($Value = self::readChar ($Data, $Offset, 4, $Length)) === null)
        return null;
      
      // Convert to uint32
      $Value = unpack ('Vvalue', $Value);
      
      return $Value ['value'];
    }
    // }}}
    
    // {{{ readUInt64
    /**
     * Safely read an unsigned 64-bit Integer from an input-buffer
     * 
     * @param string $Data
     + @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return int
     **/
    public static function readUInt64 (&$Data, &$Offset, $Length = null) {
      // Try to read the input
      if (($Value = self::readChar ($Data, $Offset, 8, $Length)) === null)
        return null;
      
      // Convert to uint64
      $Value = unpack ('Pvalue', $Value);
      
      return $Value ['value'];
    }
    // }}}
    
    // {{{ readCTxIn
    /**
     * Safely read an Transaction-Input from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return BitWire_Transaction_Input
     **/
    public static function readCTxIn (&$Data, &$Offset, $Length = null) : ?BitWire_Transaction_Input {
      $Input = new BitWire_Transaction_Input;
      
      if (!$Input->parse ($Data, $Offset, $Length))
        return null;
      
      return $Input;
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
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      if (strlen ($Data) > 0) {
        trigger_error ('Unparsed data on payload for ' . get_class ($this) . '/' . $this->Command);
        
        if (function_exists ('dump'))
          dump ($Data);
        
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