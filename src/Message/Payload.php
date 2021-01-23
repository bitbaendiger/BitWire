<?PHP

  namespace BitBaendiger\BitWire\Message;
  
  /**
   * BitWire - Message Payload
   * Copyright (C) 2019-2021 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  require_once ('BitWire/src/Transaction/Input.php');
  require_once ('BitWire/src/Peer/Address.php');
  require_once ('BitWire/src/Hash.php');
  
  class Payload {
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
     * @param \BitWire_Message $Message (optional)
     * 
     * @access public
     * @return Payload
     **/
    public static function fromString ($Command, $Data, \BitWire_Message $Message = null) : ?Payload {
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
        throw new \ValueError ('Empty input');
      
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
        throw new \ValueError ('Short read');
      
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
      $Size = self::readCompactSize ($Data, $tOffset, $Length);
      
      // Read the value
      $Value = self::readChar ($Data, $tOffset, $Size, $Length);
      
      // Patch back offset
      $Offset = $tOffset;
      
      return $Value;
    }
    // }}}
    
    // {{{ writeCompactString
    /**
     * Convert a string to a binary compact string
     * 
     * @param string $Value
     * 
     * @access public
     * @return string
     **/
    public static function writeCompactString ($Value) {
      return self::toCompactSize (strlen ($Value)) . $Value;
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
        throw new \ValueError ('Short read');
      
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
    
    // {{{ writeBoolean
    /**
     * Convert a boolean-value to binary
     * 
     * @param bool $Value
     * 
     * @access public
     * @return string
     **/
    public static function writeBoolean ($Value) {
      return chr ($Value ? 0x01 : 0x00);
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
    
    // {{{ writeUInt16
    /**
     * Write an unsigned 16-bit integer
     * 
     * @param int $Value
     * 
     * @access public
     * @return string
     **/
    public static function writeUInt16 ($Value) {
      return pack ('n', $Value);
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
    
    // {{{ writeUInt32
    /**
     * Write an unsigned 32-bit integer
     * 
     * @param int $Value
     * 
     * @access public
     * @return string
     **/
    public static function writeUInt32 ($Value) {
      return pack ('V', $Value);
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
    
    // {{{ writeUInt64
    /**
     * Write an unsigned 64-bit integer
     * 
     * @param int $Value
     * 
     * @access public
     * @return string
     **/
    public static function writeUInt64 ($Value) {
      return pack ('P', $Value);
    }
    // }}}
    
    // {{{ readHash
    /**
     * Safely read a Hash from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return \BitBaendiger\Bitwire\Hash
     **/
    public static function readHash (&$Data, &$Offset, $Length = null) : ?\BitBaendiger\Bitwire\Hash {
      // Try to read the input
      if (($Hash = self::readChar ($Data, $Offset, 32, $Length)) === null)
        return null;
      
      // Create Hash-Instance
      return\BitBaendiger\Bitwire\Hash::fromBinary ($Hash, true);
    }
    // }}}
    
    // {{{ writeHash
    /**
     * Convert a hash to binary
     * 
     * @param \BitBaendiger\Bitwire\Hash $Hash (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeHash (\BitBaendiger\Bitwire\Hash $Hash = null) {
      if ($Hash)
        return $Hash->toBinary (true);
      
      trigger_error ('Writing empty hash');
      return str_repeat ("\x00", 32);
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
     * @return \BitBaendiger\BitWire\Transaction\Input
     **/
    public static function readCTxIn (&$Data, &$Offset, $Length = null) : ?\BitBaendiger\BitWire\Transaction\Input {
      $Input = new \BitBaendiger\BitWire\Transaction\Input;
      
      if (!$Input->parse ($Data, $Offset, $Length))
        return null;
      
      return $Input;
    }
    // }}}
    
    // {{{ writeCTxIn
    /**
     * Write a transaction-input to binary
     * 
     * @param \BitBaendiger\BitWire\Transaction\Input $Input (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeCTxIn (\BitBaendiger\BitWire\Transaction\Input $Input = null) {
      if ($Input)
        return $Input->toBinary ();
      
      trigger_error ('Writing empty CTxIn');
      return str_repeat ("\x00", 32) . "\xff\xff\xff\xff\x00\xff\xff\xff\xff";
    }
    // }}}
    
    // {{{ readCAddress
    /**
     * Safely read an ip-address from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return \BitBaendiger\BitWire\Peer\Address
     **/
    public static function readCAddress (&$Data, &$Offset, $Length = null) : ?\BitBaendiger\BitWire\Peer\Address {
      $Address = new \BitBaendiger\BitWire\Peer\Address;
      
      if (!$Address->parse ($Data, $Offset, $Length))
        return null;
      
      return $Address;
    }
    // }}}
    
    // {{{ writeCAddress
    /**
     * Write a CAddress-Structure to binary
     * 
     * @param \BitBaendiger\BitWire\Peer\Address $Address
     * 
     * @access public
     * @return string
     **/
    public static function writeCAddress (\BitBaendiger\BitWire\Peer\Address $Address) {
      return $Address->toBinary ();
    }
    // }}}
    
    // {{{ readCPublicKey
    /**
     * Safely read a public key from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return \BitWire_Crypto_PublicKey
     **/
    public static function readCPublicKey (&$Data, &$Offset, $Length = null) : ?\BitWire_Crypto_PublicKey {
      $tOffset = $Offset;
      
      if (($PublicKey = self::readCompactString ($Data, $tOffset, $Length)) === null)
        return null;
      
      if (($PublicKey = \BitWire_Crypto_PublicKey::fromBinary ($PublicKey)) === null)
        return null;
      
      $Offset = $tOffset;
      
      return $PublicKey;
    }
    // }}}
    
    // {{{ writeCPublicKey
    /**
     * Write a public key binary
     * 
     * @param \BitWire_Crypto_PublicKey $PublicKey (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeCPublicKey (\BitWire_Crypto_PublicKey $PublicKey = null) {
      if ($PublicKey)
        return self::writeCompactString ($PublicKey->toBinary ());
      
      trigger_error ('Writing empty (invalid) public key');
      return self::writeCompactString ('');
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
     * @param \BitWire_Message $Message
     * 
     * @access public
     * @return void
     **/
    public function setMessage (\BitWire_Message $Message) {
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
      if ((strlen ($Data) > 0) && ($this::PAYLOAD_HAS_DATA === false))
        return false;
      
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