<?php

  /**
   * BitWire - Spork Message
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
  use \BitBaendiger\BitWire;
  
  class Spork extends Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'spork';
    
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
    public function isActive () : bool {
      return (($this->sporkValue < time ()) && ($this->sporkValue != -1));
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash for this broadcast
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      # TODO
      return new BitWire\Hash (
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
    public function getID () : int {
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
    public function getValue () : int {
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
    public function getSignatureTime () : int {
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
     * @return void
     **/
    public function parse (string $payloadData) : void {
      // Try to read all values
      $payloadLength = strlen ($payloadData);
      $payloadOffset = 0;
      
      $sporkID = self::readUInt32 ($payloadData, $payloadOffset, $payloadLength);
      $sporkValue = self::readUInt64 ($payloadData, $payloadOffset, $payloadLength);
      $sporkSignatureTime = self::readUInt64 ($payloadData, $payloadOffset, $payloadLength);
      $sporkSignature = self::readCompactString ($payloadData, $payloadOffset, $payloadLength);
      
      try {
        $signerMessageVersion = self::readUInt32 ($payloadData, $payloadOffset, $payloadLength);
      } catch (\Throwable $error) {
        $signerMessageVersion = null;
      }
      
      if ($payloadOffset < $payloadLength - 1)
        throw new \LengthException ('Garbage data at the end');
      
      // Commit to this instance
      $this->sporkID = $sporkID;
      $this->sporkValue = $sporkValue;
      $this->sporkSignatureTime = $sporkSignatureTime;
      $this->sporkSignature = $sporkSignature;
      $this->signerMessageVersion = $signerMessageVersion;
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
      return
        self::writeUInt32 ($this->sporkID) .
        self::writeUInt64 ($this->sporkValue) .
        self::writeUInt64 ($this->sporkSignatureTime) .
        self::writeCompactString ($this->sporkSignature) .
        ($this->signerMessageVersion !== null ?  self::writeUInt32 ($this->signerMessageVersion) : '');
    }
    // }}}
  }
