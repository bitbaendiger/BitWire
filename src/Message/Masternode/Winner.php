<?php

  /**
   * BitWire - Masternode Winner Vote
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
  
  class Winner extends Message\Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'mnw';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    /* Height of block */
    private $blockHeight = null;
    
    /* Address-Script of winning masternode */
    private $winningScript = null;
    
    /* The signature itself */
    private $signatureData = '';
    
    // {{{ fromString
    /**
     * Try to read a masternode-winner from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return Winner
     **/
    public static function readString (string &$Data, int &$Offset, int $Length = null) : Winner {
      $Instance = new static;
      $Instance->parse ($Data, $Offset, $Length);
      
      return $Instance;
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * 
     **/
    function __debugInfo () : array {
      return [
        'masternode-collateral' => (string)$this->txIn,
        'block-height' => $this->blockHeight,
        'winning-script' => (string)$this->getWinningScript (),
      ];
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
        self::writeCompactString ($this->getWinningScript ()->toBinary ()) .
        self::writeUInt32 ($this->blockHeight) .
        self::writeCTxIn ($this->txIn)
      );
    }
    // }}}
    
    // {{{ getTransactionInput
    /**
     * Retrive the transaction-input of the masternode voting
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
    
    // {{{ getBlockHeight
    /** 
     * Retrive the block-height for this vote
     * 
     * @access public
     * @return int
     **/
    public function getBlockHeight () : int {
      return $this->blockHeight;
    }
    // }}}
    
    // {{{ getWinningScript
    /**
     * Retrive script for winning masternode
     * 
     * @access public
     * @return BitWire\Transaction\Script
     **/
    public function getWinningScript () : BitWire\Transaction\Script {
      if (!$this->winningScript)
        return new BitWire\Transaction\Script ('');
      
      return $this->winningScript;
    }
    // }}}
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $inputData
     * @param int $inputOffset (optional)
     * @param int $inputLength (optional)
     * 
     * @access public
     * @return void
     **/
    public function parse (string $inputData, int &$inputOffset = 0, int $inputLength = null) : void {
      // Try to read all values
      if ($inputLength === null)
        $inputLength = strlen ($inputData);
      
      $tOffset = $inputOffset;
      
      $txIn = self::readCTxIn ($inputData, $tOffset, $inputLength);
      $blockHeight = self::readUInt32 ($inputData, $tOffset, $inputLength);
      $winningScript = self::readCompactString ($inputData, $tOffset, $inputLength);
      $signatureData = self::readCompactString ($inputData, $tOffset, $inputLength);
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->blockHeight = $blockHeight;
      $this->winningScript = new BitWire\Transaction\Script ($winningScript);
      $this->signatureData = $signatureData;
      $inputOffset = $tOffset;
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
        self::writeUInt32 ($this->blockHeight) .
        self::writeCompactString ($this->getWinningScript ()->toBinary ()) .
        self::writeCompactString ($this->signatureData);
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire\Crypto\PrivateKey $keyPrivate
     * @param string $magicString (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire\Crypto\PrivateKey $keyPrivate, string $magicString = null) : bool {
      // Try to generate signature
      if (($signatureData = $keyPrivate->signCompact ($this->getMessageForSignature ($magicString))) === false)
        return false;
      
      // Set the signature
      $this->signatureData = $signatureData;
      
      return true;
    }
    // }}}
    
    // {{{ verify
    /**
     * Verify this winner-vote
     * 
     * @param BitWire\Crypto\PublicKey $keyPublic
     * @param string $magicString (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (BitWire\Crypto\PublicKey $keyPublic, string $magicString = null) : bool {
      return $PublicKey->verifyCompact ($this->getMessageForSignature ($magicString), $this->signatureData);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param string $magicString (optional)
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature (string $magicString = null) : string {
      if ($magicString === null)
        $magicString = "DarkNet Signed Message:\n";
      
      #vinMasternode.prevout.ToStringShort () + std::to_string (nBlockHeight) + GetPayeeScript ().ToString ()
      
      return
        self::writeCompactString ($magicString) .
        self::writeCompactString (
          $this->txIn->toString (true) .
          (string)$this->blockHeight .
          (string)$this->getWinningScript ()
        );
    }
    // }}}
  }
