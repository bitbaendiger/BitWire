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
  
  class ElectionEntry extends Message\Payload {
    protected const PAYLOAD_COMMAND = 'dsee';
    
    /* Subtype of this entry */
    public const TYPE_SIMPLE = 0x00;
    public const TYPE_DONATION = 0x01;
    
    protected const DSEE_FORCE_TYPE = null;
    
    private $Type = ElectionEntry::TYPE_DONATION;
    
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
     * @return BitWire\Transaction\Input
     **/
    public function getTransactionInput () : ?BitWire\Transaction\Input {
      return $this->txIn;
    }
    // }}}
    
    // {{{ setTransactionInput
    /**
     * Set transaction-input (collateral-transactio) for this entry
     * 
     * @param BitWire\Transaction\Input $txIn
     * 
     * @access public
     * @return void
     **/
    public function setTransactionInput (BitWire\Transaction\Input $txIn) : void {
      $this->txIn = $txIn;
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Retrive the peer-address of this entry
     * 
     * @access public
     * @return BitWire\Peer\Address
     **/
    public function getAddress () : ?BitWire\Peer\Address {
      return $this->Address;
    }
    // }}}
    
    // {{{ setAddress
    /**
     * Set address for this masternode-entry
     * 
     * @param BitWire\Peer\Address $Address
     * 
     * @access public
     * @return void
     **/
    public function setAddress (BitWire\Peer\Address $Address) : void {
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
    public function getSignature () : string {
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
     * @return BitWire\Crypto\PublicKey
     **/
    public function getCollateralPublicKey () : ?BitWire\Crypto\PublicKey {
      return $this->publicKeyCollateral;
    }
    // }}}
    
    // {{{ setCollateralPublicKey
    /**
     * Assign collateral-public-key
     * 
     * @param BitWire\Crypto\PublicKey $PublicKey
     * 
     * @access public
     * @return void
     **/
    public function setCollateralPublicKey (BitWire\Crypto\PublicKey $PublicKey) : void {
      $this->publicKeyCollateral = $PublicKey;
    }
    // }}}
    
    // {{{ getMasternodePublicKey
    /**
     * Retrive the public key of the masternode
     * 
     * @access public
     * @return BitWire\Crypto\PublicKey
     **/
    public function getMasternodePublicKey () : ?BitWire\Crypto\PublicKey {
      return $this->publicKeyMasternode;
    }
    // }}}
    
    // {{{ setMasternodePublicKey
    /**
     * Assign a masternode-public-key
     * 
     * @param BitWire\Crypto\PublicKey $PublicKey
     * 
     * @access public
     * @return void
     **/
    public function setMasternodePublicKey (BitWire\Crypto\PublicKey $PublicKey) : void {
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
    public function getCount () : int {
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
    public function setCount (int $Count) : void {
      $this->Count = $Count;
    }
    // }}}
    
    // {{{ getCurrent
    /**
     * Retrive the index-postion of this entry
     * 
     * @access public
     * @return int
     **/
    public function getCurrent () : int {
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
    public function setCurrent (int $Current) : void {
      $this->Current = $Current;
    }
    // }}}
    
    // {{{ getLastUpdate
    /**
     * Retrive timestamp when this entry was last updated
     * 
     * @access public
     * @return int
     **/
    public function getLastUpdate () : int {
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
    public function setLastUpdate (int $lastUpdate) : void {
      $this->lastUpdate = $lastUpdate;
    }
    // }}}
    
    // {{{ getProtocolVersion
    /**
     * Retrive the protocol-version the node is running
     * 
     * @access public
     * @return int
     **/
    public function getProtocolVersion () : int {
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
    public function setProtocolVersion (int $protocolVersion) : void {
      $this->protocolVersion = $protocolVersion;
    }
    // }}}
    
    // {{{ getDonationAddress
    /**
     * Retive donation-address (unused)
     * 
     * @access public
     * @return string
     **/
    public function getDonationAddress () : string {
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
    public function getDonationPercent () : int {
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
     * @return void
     **/
    public function parse ($Data) : void {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      /**
       * There are multiple versions of dsee around.
       * It should be save to require anything up to $protocolVersion, while the rest
       * is optional e.g. should be configurable.
       **/
      $txIn = self::readCTxIn ($Data, $Offset, $Length);
      $Address = self::readCAddress ($Data, $Offset, $Length);
      $Signature = self::readCompactString ($Data, $Offset, $Length);
      $signatureTime = self::readUInt64 ($Data, $Offset, $Length);
      $publicKeyCollateral = self::readCPublicKey ($Data, $Offset, $Length);
      $publicKeyMasternode = self::readCPublicKey ($Data, $Offset, $Length);
      $Count = self::readUInt32 ($Data, $Offset, $Length);
      $Current = self::readUInt32 ($Data, $Offset, $Length);
      $lastUpdate = self::readUInt64 ($Data, $Offset, $Length);
      $protocolVersion = self::readUInt32 ($Data, $Offset, $Length);
      
      if (($this::DSEE_FORCE_TYPE == $this::TYPE_DONATION) ||
          (($Length != $Offset) && ($this::DSEE_FORCE_TYPE === null))) {
        $donationAddress = self::readCompactString ($Data, $Offset, $Length);
        $donationPercent = self::readUInt32 ($Data, $Offset, $Length);
        
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
     * @param BitWire\Crypto\PrivateKey $PrivateKey
     * @param int $Timestamp (optional)
     * @param string $Magic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire\Crypto\PrivateKey $PrivateKey, int $Timestamp = null, string $Magic = null) : bool {
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
     * @param string $messageMagic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (string $messageMagic = null) : bool {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral) {
        trigger_error ('Missing collateral public key');
        
        return false;
      }
      
      // Verify the message
      return $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($messageMagic), $this->Signature);
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Prepare the message for our signature
     * 
     * @param string $messageMagic (optional)
     * 
     * @access private
     * @return string
     **/
    private function getMessageForSignature (string $messageMagic = null) : string {
      // Make sure we have everything we need
      if (!$this->Address || !$this->publicKeyCollateral || !$this->publicKeyMasternode)
        return false;
      
      if ($messageMagic === null)
        $messageMagic = "DarkNet Signed Message:\n";
      
      return
        self::writeCompactString ($messageMagic) .
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
    private function getType () : int{
      if ($this::DSEE_FORCE_TYPE !== null)
        return $this::DSEE_FORCE_TYPE;
      
      return $this->Type;
    }
    // }}}
  }
