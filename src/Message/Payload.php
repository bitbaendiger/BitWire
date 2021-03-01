<?php

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
  
  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  use BitBaendiger\BitWire;
  
  class Payload {
    protected const PAYLOAD_COMMAND = null;
    protected const PAYLOAD_HAS_DATA = null;
    
    /* Registered Command-Classes */
    private static $Commands = [ ];
    
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
    public static function registerCommand (string $Command, string $Class) : void {
      if (!class_exists ($Class) || !is_subclass_of ($Class, __CLASS__))
        throw new \Exception ('Invalid class given');
      
      self::$Commands [$Command] = $Class;
    }
    // }}}
    
    // {{{ fromString
    /**
     * Create a new payload-object for a given command
     * 
     * @param string $Command
     * @param string $Data
     * @param BitWire\Message $Message (optional)
     * 
     * @access public
     * @return Payload
     **/
    public static function fromString (string $Command, string $Data, BitWire\Message $Message = null) : Payload {
      if (isset (self::$Commands [$Command]))
        $Class = self::$Commands [$Command];
      else
        $Class = get_called_class ();
      
      $Payload = new $Class;
      $Payload->Command = $Command;
      
      if ($Message)
        $Payload->setMessage ($Message);
      
      $Payload->parse ($Data);
      
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
    public static function readCompactSize (string &$Data, int &$Offset, int $Length = null) : int {
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
    public static function readCompactString (string &$Data, int &$Offset, int $Length = null) : string {
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
    public static function writeCompactString (string $Value) : string {
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
    public static function toCompactSize (int $Value) : string {
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
    public static function toCompactString (string $Data) : string {
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
    public static function readChar (string &$Data, int &$Offset, int $Size, int $Length = null) : string {
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
    public static function readBoolean (string &$Data, int &$Offset, int $Length = null) : bool {
      // Try to read the value
      $Value = self::readChar ($Data, $Offset, 1, $Length);
      
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
    public static function writeBoolean (bool $Value) : string {
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
    public static function readUInt16 (string &$Data, int &$Offset, int $Length = null) : int {
      // Try to read the input
      $Value = self::readChar ($Data, $Offset, 2, $Length);
      
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
    public static function writeUInt16 (int $Value) : string {
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
    public static function readUInt32 (string &$Data, int &$Offset, int $Length = null) : int {
      // Try to read the input
      $Value = self::readChar ($Data, $Offset, 4, $Length);
      
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
    public static function writeUInt32 (int $Value) : string {
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
    public static function readUInt64 (string &$Data, int &$Offset, int $Length = null) : int {
      // Try to read the input
      $Value = self::readChar ($Data, $Offset, 8, $Length);
      
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
    public static function writeUInt64 (int $Value) : string {
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
     * @return Bitwire\Hash
     **/
    public static function readHash (string &$Data, int &$Offset, int $Length = null) : Bitwire\Hash {
      // Try to read the input
      $Hash = self::readChar ($Data, $Offset, 32, $Length);
      
      // Create Hash-Instance
      return Bitwire\Hash::fromBinary ($Hash, true);
    }
    // }}}
    
    // {{{ writeHash
    /**
     * Convert a hash to binary
     * 
     * @param Bitwire\Hash $Hash (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeHash (Bitwire\Hash $Hash = null) : string {
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
     * @return BitWire\Transaction\Input
     **/
    public static function readCTxIn (string &$Data, int &$Offset, int $Length = null) : BitWire\Transaction\Input {
      $Input = new BitWire\Transaction\Input;
      $Input->parse ($Data, $Offset, $Length);
      
      return $Input;
    }
    // }}}
    
    // {{{ writeCTxIn
    /**
     * Write a transaction-input to binary
     * 
     * @param BitWire\Transaction\Input $Input (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeCTxIn (BitWire\Transaction\Input $Input = null) : string {
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
     * @return BitWire\Peer\Address
     **/
    public static function readCAddress (string &$Data, int &$Offset, int $Length = null) : BitWire\Peer\Address {
      $Address = new BitWire\Peer\Address;
      $Address->parse ($Data, $Offset, $Length);
      
      return $Address;
    }
    // }}}
    
    // {{{ writeCAddress
    /**
     * Write a CAddress-Structure to binary
     * 
     * @param BitWire\Peer\Address $Address
     * 
     * @access public
     * @return string
     **/
    public static function writeCAddress (BitWire\Peer\Address $Address) : string {
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
     * @return BitWire\Crypto\PublicKey
     **/
    public static function readCPublicKey (string &$Data, int &$Offset, int $Length = null) : BitWire\Crypto\PublicKey {
      $tOffset = $Offset;
      
      $PublicKey = self::readCompactString ($Data, $tOffset, $Length);
      $PublicKey = BitWire\Crypto\PublicKey::fromBinary ($PublicKey);
      
      $Offset = $tOffset;
      
      return $PublicKey;
    }
    // }}}
    
    // {{{ writeCPublicKey
    /**
     * Write a public key binary
     * 
     * @param BitWire\Crypto\PublicKey $PublicKey (optional)
     * 
     * @access public
     * @return string
     **/
    public static function writeCPublicKey (BitWire\Crypto\PublicKey $PublicKey = null) : string {
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
    public function getCommand () : string {
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
     * @return BitWire\Message
     **/
    public function getMessage () : BitWire\Message {
      return $this->Message;
    }
    // }}}
    
    // {{{ setMessage
    /**
     * Assign the message this payload is for
     * 
     * @param BitWire\Message $Message
     * 
     * @access public
     * @return void
     **/
    public function setMessage (BitWire\Message $Message) : void {
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
     * @return void
     **/
    public function parse ($Data) : void {
      if ((strlen ($Data) > 0) && ($this::PAYLOAD_HAS_DATA === false))
        throw new \ValueError ('Payload not expected');
      
      $this->Data = $Data;
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
      if ($this::PAYLOAD_HAS_DATA === false)
        return '';
      
      return $this->Data;
    }
    // }}}
  }
