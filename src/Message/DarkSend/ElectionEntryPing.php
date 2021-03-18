<?php

  /**
   * BitWire - DarkSend Election-Entry-Ping Message
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

  namespace BitBaendiger\BitWire\Message\DarkSend;
  use \BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class ElectionEntryPing extends Message\Payload {
    protected const PAYLOAD_COMMAND = 'dseep';
    
    /* Transaction-Input used */
    private $txIn = null;
    
    /* Signature for the ping */
    private $Signature = '';
    
    /* Timestamp of the ping */
    private $signatureTime = 0;
    
    /* Stop this election-entry ?! (unused) */
    private $Stop = false;
    
    // {{{ getTransactionInput
    /**
     * Retrive the transaction-input of this darksend-ping
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
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data)  : void {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      $txIn = self::readCTxIn ($Data, $Offset, $Length);
      $Signature = self::readCompactString ($Data, $Offset, $Length);
      $signatureTime = self::readUInt64 ($Data, $Offset, $Length);
      $Stop = self::readBoolean ($Data, $Offset, $Length);
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->Signature = $Signature;
      $this->signatureTime = $signatureTime;
      $this->Stop = $Stop;
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
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->signatureTime).
        self::writeBoolean ($this->Stop);
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire\Peer\Address $forPeer
     * @param BitWire\Crypto\PrivateKey $privateKey
     * @param string $messageMagic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire\Peer\Address $forPeer, BitWire\Crypto\PrivateKey $privateKey, string $messageMagic = null) : bool {
      // Update the timestamp
      $oTimestamp = $this->signatureTime;
      $this->signatureTime = time ();
      
      // Try to generate signature
      if (($Signature = $privateKey->signCompact ($this->getMessageForSignature ($forPeer, $messageMagic), false)) === false) {
        // Restore the old timestamp
        $this->signatureTime = $oTimestamp;
        
        return false;
      }
      
      // Set the signature
      $this->Signature = $Signature;
      
      return true;
    }
    // }}}
    
    // {{{ verify
    /**
     * Verify this ping
     * 
     * @param BitWire\Peer\Address $forPeer
     * @param BitWire\Crypto\PublicKey $publicKey
     * @param string $messageMagic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (BitWire\Peer\Address $forPeer, BitWire\Crypto\PublicKey $publicKey, string $messageMagic = null) : bool {
      return $publicKey->verifyCompact ($this->getMessageForSignature ($forPeer, $messageMagic), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param BitWire\Peer\Address $forPeer
     * @param string $messageMagic (optional)
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature (BitWire\Peer\Address $forPeer, string $messageMagic = null) : void {
      if ($messageMagic === null)
        $messageMagic = "DarkNet Signed Message:\n";
      
      return
        self::writeCompactString ($messageMagic) .
        self::writeCompactString (
          $forPeer->toString () .
          $this->signatureTime .
          ($this->Stop ? 1 : 0)
        );
    }
    // }}}
  }
