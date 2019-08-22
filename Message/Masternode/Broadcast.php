<?PHP

  /**
   * BitWire - Masternode Broadcast Message
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
  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Message/Masternode/Ping.php');
  
  class BitWire_Message_Masternode_Broadcast extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'mnb';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    /* Address of node */
    private $Address = null;
    
    /* Public key of collateral-address */
    private $publicKeyCollateral = null;
    
    /* Public key of masternode */
    private $publicKeyMasternode = null;
    
    /* The signature itself */
    private $Signature = '';
    
    /* Time of signature */
    private $signatureTime = 0;
    
    /* Used protocol-version */
    private $protocolVersion = 0;
    
    /* Last masternode ping */
    private $lastPing = null;
    
    /* Time of last dsq broadcast  */
    private $lastDSQ = 0;
    
    // {{{ fromDarkSendEntry
    /**
     * Fake a masternode-broadcast from a dark-send-entry
     * 
     * @param BitWire_Message_DarkSend_ElectionEntry $darkSendEntry
     * 
     * @access public
     * @return BitWire_Message_Masternode_Broadcast
     **/
    public static function fromDarkSendEntry (BitWire_Message_DarkSend_ElectionEntry $darkSendEntry) : BitWire_Message_Masternode_Broadcast {
      $Broadcast = new BitWire_Message_Masternode_Broadcast;
      $Broadcast->txIn = $darkSendEntry->getTransactionInput ();
      $Broadcast->Address = $darkSendEntry->getAddress ();
      $Broadcast->publicKeyCollateral = $darkSendEntry->getCollateralPublicKey ();
      $Broadcast->publicKeyMasternode = $darkSendEntry->getMasternodePublicKey ();
      $Broadcast->signatureTime = $darkSendEntry->getSigantureTime ();
      $Broadcast->protocolVersion = $darkSendEntry->getProtocolVersion ();
      
      return $Broadcast;
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
        self::writeUInt64 ($this->signatureTime) .
        self::writeCPublicKey ($this->publicKeyCollateral)
      );
    }
    // }}}
    
    // {{{ getTransactionInput
    /**
     * Retrive CTxIn of this election-entry
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
     * Set the transaction-input for this masternode-broadcast
     * 
     * @param BitWire_Transaction_Input $txIn
     * 
     * @access public
     * @return void
     **/
    public function setTransactionInput (BitWire_Transaction_Input $txIn) {
      $this->txIn = $txIn;
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Retrive the peer-address of this entry
     * 
     * @access public
     * @return BitWire_Peer_Address
     **/
    public function getAddress () : ?BitWire_Peer_Address {
      return $this->Address;
    }
    // }}}
    
    // {{{ setAddress
    /**
     * Set the address for this broadcast
     * 
     * @param BitWire_Peer_Address $Address
     * 
     * @access public
     * @return void
     **/
    public function setAddress (BitWire_Peer_Address $Address) {
      $this->Address = $Address;
    }
    // }}}
    
    // {{{ getCollateralPublicKey
    /**
     * Retrive the public key of the collateral-address used
     * 
     * @access public
     * @return BitWire_Crypto_PublicKey
     **/
    public function getCollateralPublicKey () : ?BitWire_Crypto_PublicKey {
      return $this->publicKeyCollateral;
    }
    // }}}
    
    // {{{ setCollateralPublicKey
    /**
     * Set public key of collateral address
     * 
     * @param BitWire_Crypto_PublicKey $PublicKey
     * 
     * @access public
     * @return void
     **/
    public function setCollateralPublicKey (BitWire_Crypto_PublicKey $PublicKey) {
      $this->publicKeyCollateral = $PublicKey;
    }
    // }}}
    
    // {{{ getMasternodePublicKey
    /**
     * Retrive the public key of the masternode
     * 
     * @access public
     * @return BitWire_Crypto_PublicKey
     **/
    public function getMasternodePublicKey () : ?BitWire_Crypto_PublicKey {
      return $this->publicKeyMasternode;
    }
    // }}}
    
    // {{{ setMasternodePublicKey
    /**
     * Set public key of masternode
     * 
     * @param BitWire_Crypto_PublicKey $PublicKey
     * 
     * @access public
     * @return void
     **/
    public function setMasternodePublicKey (BitWire_Crypto_PublicKey $PublicKey) {
      $this->publicKeyMasternode = $PublicKey;
    }
    // }}}
    
    // {{{ getProtocolVersion
    /**
     * Retrive the protocol-version announced on this broadcast
     * 
     * @access public
     * @return int
     **/
    public function getProtocolVersion () {
      return $this->protocolVersion;
    }
    // }}}
    
    // {{{ setProtocolVersion
    /**
     * Set the announced protocol-version of this broadcast
     * 
     * @param int $Version
     * 
     * @access public
     * @return void
     **/
    public function setProtocolVersion ($Version) {
      $this->protocolVersion = (int)$Version;
    }
    // }}}
    
    // {{{ getLastPing
    /**
     * Retirve the last received ping for this broadcast
     * 
     * @access public
     * @return BitWire_Message_Masternode_Ping
     **/
    public function getLastPing () : ?BitWire_Message_Masternode_Ping {
      return $this->lastPing;
    }
    // }}}
    
    // {{{ setLastPing
    /**
     * Store last send/received masternode-ping on this broadcast
     * 
     * @param BitWire_Message_Masternode_Ping $Ping
     * 
     * @access public
     * @return void
     **/
    public function setLastPing (BitWire_Message_Masternode_Ping $Ping) {
      $this->lastPing = $Ping;
    }
    // }}}
    
    // {{{ getLastDSQ
    /**
     * Get last DarkSend-Queue
     * 
     * @access public
     * @return int
     **/
    public function getLastDSQ () {
      return $this->lastDSQ;
    }
    // }}}
    
    // {{{ setLastDSQ
    /**
     * Set last DarkSend-Queue
     * 
     * @param int $lastDSQ
     * 
     * @access public
     * @return void
     **/
    public function setLastDSQ ($lastDSQ) {
      $this->lastDSQ = (int)$lastDSQ;
    }
    // }}}
    
    // {{{ getSignatureTime
    /**
     * Retrive timestamp when this broadcast was signed
     * 
     * @access public
     * @return int
     **/
    public function getSignatureTime () {
      return $this->signatureTime;
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
          (($Address = self::readCAddress ($Data, $Offset, $Length)) === null) ||
          (($publicKeyCollateral = self::readCPublicKey ($Data, $Offset, $Length)) === null) ||
          (($publicKeyMasternode = self::readCPublicKey ($Data, $Offset, $Length)) === null) ||
          (($Signature = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($signatureTime = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($protocolVersion = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($lastPing = BitWire_Message_Masternode_Ping::readString ($Data, $Offset, $Length)) === null) ||
          (($lastDSQ = self::readUInt64 ($Data, $Offset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->Address = $Address;
      $this->publicKeyCollateral = $publicKeyCollateral;
      $this->publicKeyMasternode = $publicKeyMasternode;
      $this->Signature = $Signature;
      $this->signatureTime = $signatureTime;
      $this->protocolVersion = $protocolVersion;
      $this->lastPing = $lastPing;
      $this->lastDSQ = $lastDSQ;
      
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
        self::writeCAddress ($this->Address) .
        self::writeCPublicKey ($this->publicKeyCollateral) .
        self::writeCPublicKey ($this->publicKeyMasternode) .
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->signatureTime) .
        self::writeUInt32 ($this->protocolVersion) .
        $this->lastPing->toBinary () .
        self::writeUInt64 ($this->lastDSQ);
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire_Crypto_PrivateKey $PrivateKey
     * @param int $Timestamp (optional)
     * @param string $Magic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire_Crypto_PrivateKey $PrivateKey, $Timestamp = null, $Magic = null) {
      // Update the timestamp
      $oTimestamp = $this->signatureTime;
      $this->signatureTime = ($Timestamp !== null ? $Timestamp : time ());
      
      require_once ('dump.php');
      dump ($this->getMessageForSignature ($Magic));
      
      // Try to generate signature
      if (($Signature = $PrivateKey->signCompact ($this->getMessageForSignature ($Magic))) === false) {
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
     * Check if the signature here is valid
     * 
     * @param string $Magic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify ($Magic = null) {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral)
        return false;
      
      // Verify the message
      return
        $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($Magic, false), $this->Signature) ||
        $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($Magic, true), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Generate message used for signing this broadcast
     * 
     * @param string $Magic (optional)
     * @param bool $Old (optional)
     * 
     * @access public
     * @return string
     **/
    public function getMessageForSignature ($Magic = null, $Old = false) {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral || !$this->publicKeyMasternode || !$this->Address)
        return false;
      
      if ($Magic === null)
        $Magic = "DarkNet Signed Message:\n";
      
      return
        self::writeCompactString ($Magic) .
        self::writeCompactString (
          $this->Address->toString () .
          $this->signatureTime .
          ($Old ? $this->publicKeyCollateral->toBinary () : $this->publicKeyCollateral->getID ()) .
          ($Old ? $this->publicKeyMasternode->toBinary () : $this->publicKeyMasternode->getID ()) .
          $this->protocolVersion
        );
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('mnb', 'BitWire_Message_Masternode_Broadcast');

?>