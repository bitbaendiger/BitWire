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
  require_once ('BitWire/Crypto/PublicKey.php');
    
  class BitWire_Message_DarkSend_ElectionEntry extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'dsee';
    
    /* Subtype of this entry */
    const TYPE_SIMPLE = 0x00;
    const TYPE_DONATION = 0x01;
    const DSEE_FORCE_TYPE = null;
    
    private $Type = BitWire_Message_DarkSend_ElectionEntry::TYPE_DONATION;
    
    /* Transaction-Input */
    private $txIn = null;
    
    /* Address of node */
    private $Address = null;
    
    /* Signature for the entry */
    private $Signature = '';
    
    /* Time of signature */
    private $signatureTime = 0;
    
    /* Public key of Collateral-Address */
    private $publicKeyCollateral = null;
    
    /* Public key of masternode */
    private $publicKeyMasternode = null;
    
    /* Count */
    private $Count = 0;
    
    /* Current */
    private $Current = 0;
    
    /* Last Update */
    private $lastUpdate = 0;
    
    /* Protocol-Version */
    private $protocolVersion = 0;
    
    /* Donation-Address */
    private $donationAddress = null;
    
    /* Donation-Percent */
    private $donationPercent = 0;
    
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
     * Set transaction-input (collateral-transactio) for this entry
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
     * Set address for this masternode-entry
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
    
    // {{{ getSignature
    /**
     * Retrive the signature for this entry
     * 
     * @access public
     * @return string
     **/
    public function getSignature () {
      return $this->Signature;
    }
    // }}}
    
    // {{{ getSignatureTime
    /**
     * Reitrve the time of the signature
     * 
     * @access public
     * @return int
     **/
    public function getSignatureTime () {
      return $this->signatureTime;
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
     * Assign collateral-public-key
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
     * Assign a masternode-public-key
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
    
    // {{{ getCount
    /**
     * Retrive number of masternodes
     * 
     * @access public
     * @return int
     **/
    public function getCount () {
      return $this->Count;
    }
    // }}}
    
    // {{{ setCount
    /**
     * Update number of masternodes
     * 
     * @param int $Count
     * 
     * @access public
     * @return void
     **/
    public function setCount ($Count) {
      $this->Count = (int)$Count;
    }
    // }}}
    
    // {{{ getCurrent
    /**
     * Retrive the index-postion of this entry
     * 
     * @access public
     * @return int
     **/
    public function getCurrent () {
      return $this->Current;
    }
    // }}}
    
    // {{{ setCurrent
    /**
     * Update index-position of this entry
     * 
     * @param int $Current
     * 
     * @access public
     * @return void
     **/
    public function setCurrent ($Current) {
      $this->Current = (int)$Current;
    }
    // }}}
    
    // {{{ getLastUpdate
    /**
     * Retrive timestamp when this entry was last updated
     * 
     * @access public
     * @return int
     **/
    public function getLastUpdate () {
      return $this->lastUpdate;
    }
    // }}}
    
    // {{{ setLastUpdate
    /**
     * Set timestamp of last update of this entry
     * 
     * @param int $lastUpdate
     * 
     * @access public
     * @return void
     **/
    public function setLastUpdate ($lastUpdate) {
      $this->lastUpdate = (int)$lastUpdate;
    }
    // }}}
    
    // {{{ getProtocolVersion
    /**
     * Retrive the protocol-version the node is running
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
     * Set the protocol-version the node is running on
     * 
     * @param int $protocolVersion
     * 
     * @access public
     * @return void
     **/
    public function setProtocolVersion ($protocolVersion) {
      $this->protocolVersion = (int)$protocolVersion;
    }
    // }}}
    
    // {{{ getDonationAddress
    /**
     * Retive donation-address (unused)
     * 
     * @access public
     * @return string
     **/
    public function getDonationAddress () {
      return $this->donationAddress;
    }
    // }}}
    
    // {{{ getDonactionPercent
    /**
     * Retrive amount of donation (unused)
     * 
     * @access public
     * @return int
     **/
    public function getDonationPercent () {
      return $this->donationPercent;
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
      
      /**
       * There are multiple versions of dsee around.
       * It should be save to require anything up to $protocolVersion, while the rest
       * is optional e.g. should be configurable.
       **/
      if ((($txIn = self::readCTxIn ($Data, $Offset, $Length)) === null) ||
          (($Address = self::readCAddress ($Data, $Offset, $Length)) === null) ||
          (($Signature = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($signatureTime = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($publicKeyCollateral = self::readCPublicKey ($Data, $Offset, $Length)) === null) ||
          (($publicKeyMasternode = self::readCPublicKey ($Data, $Offset, $Length)) === null) ||
          (($Count = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($Current = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($lastUpdate = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($protocolVersion = self::readUInt32 ($Data, $Offset, $Length)) === null))
        return false;
      
      if (($this::DSEE_FORCE_TYPE == $this::TYPE_DONATION) ||
          (($Length != $Offset) && ($this::DSEE_FORCE_TYPE === null))) {
        if ((($donationAddress = self::readCompactString ($Data, $Offset, $Length)) === null) ||
            (($donationPercent = self::readUInt32 ($Data, $Offset, $Length)) === null))
          return false;
        
        $this->Type = $this::TYPE_DONATION;
      } else {
        $donationAddress = null;
        $donationPercent = null;
        
        $this->Type = $this::TYPE_SIMPLE;
      }
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->Address = $Address;
      $this->Signature = $Signature;
      $this->signatureTime = $signatureTime;
      $this->publicKeyCollateral = $publicKeyCollateral;
      $this->publicKeyMasternode = $publicKeyMasternode;
      $this->Count = $Count;
      $this->Current = $Current;
      $this->lastUpdate = $lastUpdate;
      $this->protocolVersion = $protocolVersion;
      $this->donationAddress = $donationAddress;
      $this->donationPercent = $donationPercent;
      
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
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->signatureTime).
        self::writeCPublicKey ($this->publicKeyCollateral) .
        self::writeCPublicKey ($this->publicKeyMasternode) .
        self::writeUInt32 ($this->Count) .
        self::writeUInt32 ($this->Current) .
        self::writeUInt64 ($this->lastUpdate) .
        self::writeUInt32 ($this->protocolVersion) .
        ($this->getType () == $this::TYPE_DONATION ? self::writeCompactString ($this->donationAddress) . self::writeUInt32 ($this->donationPercent) : '');
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
     * @access public
     * @return bool
     **/
    public function verify ($Magic = null) {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral) {
        trigger_error ('Missing collateral public key');
        
        return false;
      }
      
      // Verify the message
      return $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($Magic), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param string $Magic (optional)
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature ($Magic = null) {
      // Make sure we have everything we need
      if (!$this->Address || !$this->publicKeyCollateral || !$this->publicKeyMasternode)
        return false;
      
      if ($Magic === null)
        $Magic = "DarkNet Signed Message:\n";
      
      return
        self::writeCompactString ($Magic) .
        self::writeCompactString (
          $this->Address->toString () .
          $this->signatureTime .
          $this->publicKeyCollateral->toBinary () .
          $this->publicKeyMasternode->toBinary () .
          $this->protocolVersion .
          ($this->getType () == $this::TYPE_DONATION ? $this->donationAddress . $this->donationPercent : '')
        );
    }
    // }}}
    
    // {{{ getType
    /**
     * Retrive the actual type of this election-entry
     * 
     * @access private
     * @return enum
     **/
    private function getType () {
      if ($this::DSEE_FORCE_TYPE !== null)
        return $this::DSEE_FORCE_TYPE;
      
      return $this->Type;
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dsee', 'BitWire_Message_DarkSend_ElectionEntry');

?>