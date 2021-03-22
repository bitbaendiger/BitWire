<?php

  /**
   * BitWire - Masternode Ping Message
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

  namespace BitBaendiger\BitWire\Message\Masternode;
  use \BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class Ping extends Message\Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'mnp';
    
    /* Known signature-types */
    public const SIGNATURE_OLD  = 0x00;
    public const SIGNATURE_NEW  = 0xff;
    public const SIGNATURE_HASH = 0x01;
    
    /* UTXO of masternode */
    private $txIn = null;
    
    /* Hash of block */
    private $blockHash = null;
    
    /* Time of signature */
    private $signatureTime = 0;
    
    /* The signature itself */
    private $Signature = '';
    
    /* Type of the signature (optional)  */
    private $signatureType = Ping::SIGNATURE_NEW;
    
    // {{{ fromString
    /**
     * Try to read a masternode-ping from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return Ping
     **/
    public static function readString (string &$Data, int &$Offset, int $Length = null) : Ping {
      $Instance = new static ();
      $Instance->parse ($Data, $Offset, $Length);
      
      return $Instance;
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
      return new BitWire\Hash (
        self::writeCTxIn ($this->txIn) .
        ($this->signatureType == $this::SIGNATURE_HASH ? self::writeHash ($this->blockHash) : '') . 
        self::writeUInt64 ($this->signatureTime)
      );
    }
    // }}}
    
    // {{{ getTransactionInput
    /**
     * Retrive the transaction-input of this masternode-ping
     * 
     * @access public
     * @return BitWire\Transaction\Input
     **/
    public function getTransactionInput () : ?BitWire\Transaction\Input {
      return $this->txIn;
    }
    // }}}
    
    // {{{ setTransactionInput
    /**
     * Set transaction-input for this message
     * 
     * @param BitWire\Transaction\Input $Input
     * 
     * @access public
     * @return void
     **/
    public function setTransactionInput (BitWire\Transaction\Input $Input) : void {
      $this->txIn = $Input;
    }
    // }}}
    
    // {{{ getBlockHash
    /**
     * Retrive the blockhash form this ping
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getBlockHash () : ?BitWire\Hash {
      return $this->blockHash;
    }
    // }}}
    
    // {{{ setBlockHash
    /**
     * Set the blockhash contained in this ping
     * 
     * @param BitWire\Hash $blockHash
     * 
     * @access public
     * @return void
     **/
    public function setBlockHash (BitWire\Hash $blockHash) : void {
      $this->blockHash = $blockHash;
    }
    // }}}
    
    // {{{ getSignatureTime
    /**
     * Retrive timestamp of signature
     * 
     * @access public
     * @return int
     **/
    public function getSignatureTime () : int {
      return $this->signatureTime;
    }
    // }}}
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * @param int $Offset (optional)
     * @param int $Length (optional)
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data, int &$Offset = 0, int $Length = null) : void {
      // Try to read all values
      if ($Length === null)
        $Length = strlen ($Data);
      
      $tOffset = $Offset;
      
      $txIn = self::readCTxIn ($Data, $tOffset, $Length);
      $blockHash = self::readHash ($Data, $tOffset, $Length);
      $signatureTime = self::readUInt64 ($Data, $tOffset, $Length);
      $Signature = self::readCompactString ($Data, $tOffset, $Length);
      
      try {
        $signatureType = self::readUInt32 ($Data, $Offset, $Length);
      } catch (\LengthException $error) {
        $signatureType = null;
      }
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->blockHash = $blockHash;
      $this->signatureTime = $signatureTime;
      $this->Signature = $Signature;
      
      if ($signatureType !== null)
        $this->signatureType = $signatureType;
      
      $Offset = $tOffset;
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
        self::writeCTxIn ($this->txIn) .
        self::writeHash ($this->blockHash) .
        self::writeUInt64 ($this->signatureTime) .
        self::writeCompactString ($this->Signature);
        ($this->signatureType >= $this::SIGNATURE_HASH ? self::writeUInt32 ($this->signatureType) : '');
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire\Crypto\PrivateKey $privateKey
     * @param string $magicString (optional)
     * @param int $signatureType (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire\Crypto\PrivateKey $privateKey, string $magicString = null, int $signatureType = null) : bool {
      // Update the timestamp
      $oTimestamp = $this->signatureTime;
      $this->signatureTime = time ();
      
      // Try to generate signature
      if (($Signature = $privateKey->signCompact ($this->getMessageForSignature ($magicString, $signatureType))) === false) {
        // Restore the old timestamp
        $this->signatureTime = $oTimestamp;
        
        return false;
      }
      
      // Set the signature
      $this->Signature = $Signature;
      
      if ($signatureType !== null)
        $this->signatureType = $signatureType;
      
      return true;
    }
    // }}}
    
    // {{{ verify
    /**
     * Verify this ping
     * 
     * @param BitWire\Crypto\PublicKey $publicKey
     * @param string $magicString (optional)
     * @param int $signatureType (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (BitWire\Crypto\PublicKey $publicKey, string $magicString = null, int $signatureType = null) : bool {
      return $publicKey->verifyCompact ($this->getMessageForSignature ($magicString, $signatureType), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param string $magicString (optional)
     * @param int $signatureType (optional)
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature (string $magicString = null, int $signatureType = null) : string {
      if ($magicString === null)
        $magicString = "DarkNet Signed Message:\n";
      
      $signatureType = $signatureType ?? $this->signatureType;
      
      if ($signatureType == $this::SIGNATURE_HASH)
        return
          self::writeCTxIn ($this->txIn) .
          self::writeHash ($this->blockHash) . 
          self::writeUInt64 ($this->signatureTime);
      
      return
        self::writeCompactString ($magicString) .
        self::writeCompactString (
          $this->txIn->toString ($signatureType == $this::SIGNATURE_OLD) .
          (string)$this->blockHash .
          $this->signatureTime
        );
    }
    // }}}
  }
