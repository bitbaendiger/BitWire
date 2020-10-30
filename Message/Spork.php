<?PHP

  /**
   * BitWire - Spork Message
   * Copyright (C) 2019-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Message/Payload/Hashable.php');
  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Message/Masternode/Ping.php');
  
  class BitWire_Message_Spork extends BitWire_Message_Payload_Hashable {
    const PAYLOAD_COMMAND = 'spork';
    
    private $sporkID = 0x00;
    private $sporkValue = 0x00;
    private $sporkSiganture = '';
    private $sporkSignatureTime = 0x00;
    private $signerMessageVersion = null;
    
    // {{{ __debugInfo
    /**
     * Prepare output for var_dump()
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () : array {
      return array (
        'nSporkID' => $this->sporkID,
        'nValue' => $this->sporkValue,
        'nTimeSigned' => $this->sporkSiganture,
        'active' => $this->isActive (),
      );
    }
    // }}}
    
    // {{{ isActive
    /**
     * Check if this spork is active
     * 
     * @access public
     * @return bool
     **/
    public function isActive () {
      return $this->sporkValue < time ();
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash for this broadcast
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () : BitWire_Hash {
      # TODO
      return new BitWire_Hash (
        self::writeUInt32 ($this->sporkID) .
        self::writeUInt64 ($this->sporkSignatureTime)
      );
    }
    // }}}
    
    // {{{ getID
    /**
     * Retrive the ID of this spork
     * 
     * @access public
     * @return int
     **/
    public function getID () {
      return $this->sporkID;
    }
    // }}}
    
    //{{{ getValue
    /**
     * Retrive the value set for this spork
     *
     * @access public
     * @return int
     **/
    public function getValue () {
      return $this->sporkValue;
    }
    // }}}
    
    // {{{ getSignatureTime
    /**
     * Retrive timestamp when this spork was signed
     * 
     * @access public
     * @return int
     **/
    public function getSignatureTime () {
      return $this->sporkSignatureTime;
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
    public function parse ($payloadData) {
      // Try to read all values
      $payloadLength = strlen ($payloadData);
      $payloadOffset = 0;
      
      if ((($sporkID = self::readUInt32 ($payloadData, $payloadOffset, $payloadLength)) === null) ||
          (($sporkValue = self::readUInt64 ($payloadData, $payloadOffset, $payloadLength)) === null) ||
          (($sporkSignatureTime = self::readUInt64 ($payloadData, $payloadOffset, $payloadLength)) === null) ||
          (($sporkSignature = self::readCompactString ($payloadData, $payloadOffset, $payloadLength)) === null))
        return alse;
      
      $signerMessageVersion = null;
      
      if (($payloadOffset + 4 <= $payloadLength) &&
          (($signerMessageVersion = self::readUInt32 ($payloadData, $payloadOffset, $payloadLength)) === null))
        return false;
      
      // Commit to this instance
      $this->sporkID = $sporkID;
      $this->sporkValue = $sporkValue;
      $this->sporkSignatureTime = $sporkSignatureTime;
      $this->sporkSignature = $sporkSignature;
      $this->signerMessageVersion = $signerMessageVersion;
      
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
      return
        self::writeUInt32 ($this->sporkID) .
        self::writeUInt64 ($this->sporkValue) .
        self::writeUInt64 ($this->sporkSignatureTime) .
        self::writeCompactString ($this->sporkSignature) .
        ($this->signerMessageVersion !== null ?  self::writeUInt32 ($this->signerMessageVersion) : '');
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('spork', 'BitWire_Message_Spork');

?>