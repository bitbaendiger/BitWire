<?PHP

  /**
   * BitWire - Masternode Winner Vote
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
  
  class BitWire_Message_Masternode_Winner extends BitWire_Message_Payload_Hashable {
    const PAYLOAD_COMMAND = 'mnw';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    /* Height of block */
    private $blockHeight = null;
    
    /* Address of winning masternode */
    private $winningAddress = '';
    
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
     * @return BitWire_Message_Masternode_Winner
     **/
    public static function readString (&$Data, &$Offset, $Length = null) : ?BitWire_Message_Masternode_Winner {
      $Instance = new static;
      
      if (!$Instance->parse ($Data, $Offset, $Length))
        return null;
      
      return $Instance;
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * 
     **/
    function __debugInfo () : array {
      return array (
        'masternode-collateral' => (string)$this->txIn,
        'block-height' => $this->blockHeight,
        'winning-address' => (string)$this->winningAddress,
      );
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
      return new BitWire_Hash (
        self::writeCompactString ($this->winningAddress->toBinary ()) .
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
    public function parse ($Data, &$Offset = 0, $Length = null) {
      // Try to read all values
      if ($Length === null)
        $Length = strlen ($Data);
      
      $tOffset = $Offset;
      
      if ((($txIn = self::readCTxIn ($Data, $tOffset, $Length)) === null) ||
          (($blockHeight = self::readUInt32 ($Data, $tOffset, $Length)) === null) ||
          (($winningAddress = self::readCompactString ($Data, $tOffset, $Length)) === null) ||
          (($signatureData = self::readCompactString ($Data, $tOffset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->blockHeight = $blockHeight;
      $this->winningAddress = new BitWire_Transaction_Script ($winningAddress);
      $this->signatureData = $signatureData;
      $Offset = $tOffset;
      
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
        self::writeUInt32 ($this->blockHeight) .
        self::writeCompactString ($this->winningAddress->toBinary ()) .
        self::writeCompactString ($this->signatureData);
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire_Crypto_PrivateKey $keyPrivate
     * @param string $magicString (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire_Crypto_PrivateKey $keyPrivate, $magicString = null) {
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
     * @param BitWire_Crypto_PublicKey $keyPublic
     * @param string $magicString (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (BitWire_Crypto_PublicKey $keyPublic, $magicString = null) {
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
    private function getMessageForSignature ($magicString = null) {
      if ($magicString === null)
        $magicString = "DarkNet Signed Message:\n";
      
      #vinMasternode.prevout.ToStringShort () + std::to_string (nBlockHeight) + GetPayeeScript ().ToString ()
      
      return
        self::writeCompactString ($magicString) .
        self::writeCompactString (
          $this->txIn->toString (true) .
          (string)$this->blockHeight .
          (string)$this->winningAddress
        );
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('mnw', 'BitWire_Message_Masternode_Winner');

?>