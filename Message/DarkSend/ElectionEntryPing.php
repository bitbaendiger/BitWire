<?PHP

  /**
   * BitWire - DarkSend Election-Entry-Ping Message
   * Copyright (C) 2019 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_DarkSend_ElectionEntryPing extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'dseep';
    
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
     * @return BitWire_Transaction_Input
     **/
    public function getTransactionInput () : ?BitWire_Transaction_Input {
      return $this->txIn;
    }
    // }}}
    
    // {{{ setTransactionInput
    /**
     * Set transaction-input for this message
     * 
     * @param BitWire_Transaction_Input $Input
     * 
     * @access public
     * @return void
     **/
    public function setTransactionInput (BitWire_Transaction_Input $Input) {
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
     * @return bool
     **/
    public function parse ($Data) {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      if ((($txIn = self::readCTxIn ($Data, $Offset, $Length)) === null) ||
          (($Signature = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($signatureTime = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($Stop = self::readBoolean ($Data, $Offset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->Signature = $Signature;
      $this->signatureTime = $signatureTime;
      $this->Stop = $Stop;
      
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
     * @param BitWire_Peer_Address $Peer
     * @param BitWire_Crypto_PrivateKey $PrivateKey
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire_Peer_Address $Peer, BitWire_Crypto_PrivateKey $PrivateKey) {
      // Update the timestamp
      $oTimestamp = $this->signatureTime;
      $this->signatureTime = time ();
      
      // Try to generate signature
      if (($Signature = $PrivateKey->signCompact ($this->getMessageForSignature ($Peer), false)) === false) {
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
     * @param BitWire_Peer_Address $Peer
     * @param BitWire_Crypto_PublicKey $PublicKey
     * 
     * @access public
     * @return bool
     **/
    public function verify (BitWire_Peer_Address $Peer, BitWire_Crypto_PublicKey $PublicKey) {
      return $PublicKey->verifyCompact ($this->getMessageForSignature ($Peer), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param BitWire_Peer_Address $Peer
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature (BitWire_Peer_Address $Peer) {
      return
        self::writeCompactString ("DarkNet Signed Message:\n") .
        self::writeCompactString (
          $Peer->toString () .
          $this->signatureTime .
          ($this->Stop ? 1 : 0)
        );
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dseep', 'BitWire_Message_DarkSend_ElectionEntryPing');

?>