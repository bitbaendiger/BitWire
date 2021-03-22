<?php

  /**
   * BitWire - Masternode Broadcast Message
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
  
  class Broadcast extends Message\Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'mnb';
    
    /* Known signature-types */
    public const SIGNATURE_OLD  = 0x00;
    public const SIGNATURE_NEW  = 0xff;
    public const SIGNATURE_HASH = 0x01;
    
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
    
    /* Message-Type */
    private $signatureType = Broadcast::SIGNATURE_NEW;
    
    // {{{ fromDarkSendEntry
    /**
     * Fake a masternode-broadcast from a dark-send-entry
     * 
     * @param Message\DarkSend\ElectionEntry $darkSendEntry
     * 
     * @access public
     * @return Broadcast
     **/
    public static function fromDarkSendEntry (Message\DarkSend\ElectionEntry $darkSendEntry) : Broadcast {
      $Broadcast = new BitWire_Message_Masternode_Broadcast ();
      $Broadcast->txIn = $darkSendEntry->getTransactionInput ();
      $Broadcast->Address = $darkSendEntry->getAddress ();
      $Broadcast->publicKeyCollateral = $darkSendEntry->getCollateralPublicKey ();
      $Broadcast->publicKeyMasternode = $darkSendEntry->getMasternodePublicKey ();
      $Broadcast->signatureTime = $darkSendEntry->getSignatureTime ();
      $Broadcast->protocolVersion = $darkSendEntry->getProtocolVersion ();
      
      return $Broadcast;
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
     * @return BitWire\Transaction\Input
     **/
    public function getTransactionInput () : ?BitWire\Transaction\Input {
      return $this->txIn;
    }
    // }}}
    
    // {{{ setTransactionInput
    /**
     * Set the transaction-input for this masternode-broadcast
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
     * Set the address for this broadcast
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
     * Set public key of collateral address
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
     * Set public key of masternode
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
    
    // {{{ getProtocolVersion
    /**
     * Retrive the protocol-version announced on this broadcast
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
     * Set the announced protocol-version of this broadcast
     * 
     * @param int $Version
     * 
     * @access public
     * @return void
     **/
    public function setProtocolVersion (int $Version) : void {
      $this->protocolVersion = $Version;
    }
    // }}}
    
    // {{{ getLastPing
    /**
     * Retirve the last received ping for this broadcast
     * 
     * @access public
     * @return Ping
     **/
    public function getLastPing () : ?Ping {
      return $this->lastPing;
    }
    // }}}
    
    // {{{ setLastPing
    /**
     * Store last send/received masternode-ping on this broadcast
     * 
     * @param Ping $Ping
     * 
     * @access public
     * @return void
     **/
    public function setLastPing (Ping $Ping) : void {
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
    public function getLastDSQ () : int {
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
    public function setLastDSQ (int $lastDSQ) : void {
      $this->lastDSQ = $lastDSQ;
    }
    // }}}
    
    // {{{ getSignatureTime
    /**
     * Retrive timestamp when this broadcast was signed
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
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data) : void {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      $txIn = self::readCTxIn ($Data, $Offset, $Length);
      $Address = self::readCAddress ($Data, $Offset, $Length);
      $publicKeyCollateral = self::readCPublicKey ($Data, $Offset, $Length);
      $publicKeyMasternode = self::readCPublicKey ($Data, $Offset, $Length);
      $Signature = self::readCompactString ($Data, $Offset, $Length);
      $signatureTime = self::readUInt64 ($Data, $Offset, $Length);
      $protocolVersion = self::readUInt32 ($Data, $Offset, $Length);
      $lastPing = Ping::readString ($Data, $Offset, $Length);
      
      try {
        $lastDSQ = self::readUInt64 ($Data, $Offset, $Length);
        $signatureType = null;
      } catch (\LengthException $error) {
        $lastDSQ = 0;
        $signatureType = self::readUInt32 ($Data, $Offset, $Length);
      }
      
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
      
      if ($signatureType !== null)
        $this->signatureType = $signatureType;
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
        self::writeCPublicKey ($this->publicKeyCollateral) .
        self::writeCPublicKey ($this->publicKeyMasternode) .
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->signatureTime) .
        self::writeUInt32 ($this->protocolVersion) .
        $this->lastPing->toBinary () .
        ($this->signatureType == $this::SIGNATURE_HASH ? self::writeUInt32 ($this->signatureType) : self::writeUInt64 ($this->lastDSQ));
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this message
     * 
     * @param BitWire\Crypto\PrivateKey $privateKey
     * @param int $signatureTimestamp (optional)
     * @param string $messageMagic (optional)
     * @param enum $signatureType (optional)
     * 
     * @access public
     * @return bool
     **/
    public function sign (BitWire\Crypto\PrivateKey $privateKey, int $signatureTimestamp = null, string $messageMagic = null, int $signatureType = null) : bool {
      // Update the timestamp
      $lastTimestamp = $this->signatureTime;
      $this->signatureTime = ($signatureTimestamp ?? time ());
      
      // Try to generate signature
      if (($Signature = $privateKey->signCompact ($this->getMessageForSignature ($messageMagic, $signatureType))) === false) {
        // Restore the old timestamp
        $this->signatureTime = $oldTimestamp;
        
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
     * Check if the signature here is valid
     * 
     * @param string $messageMagic (optional)
     * 
     * @access public
     * @return bool
     **/
    public function verify (string $messageMagic = null) : bool {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral)
        return false;
      
      // Verify the message
      return
        $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($messageMagic, $this::SIGNATURE_OLD), $this->Signature) ||
        $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($messageMagic, $this::SIGNATURE_NEW), $this->Signature) ||
        $this->publicKeyCollateral->verifyCompact ($this->getMessageForSignature ($messageMagic, $this::SIGNATURE_HASH), $this->Signature);;
    }
    // }}}
    
    // {{{ getMessageForSignature
    /**
     * Generate message used for signing this broadcast
     * 
     * @param string $messageMagic (optional)
     * @param int $signatureType (optional)
     * 
     * @access public
     * @return string
     **/
    public function getMessageForSignature (string $messageMagic = null, int $signatureType = null) : string {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral || !$this->publicKeyMasternode || !$this->Address)
        throw new \Exception ('Insufficent data to prepare signature');
      
      if ($messageMagic === null)
        $messageMagic = "DarkNet Signed Message:\n";
      
      $signatureType = $signatureType ?? $this->signatureType;
      
      if ($signatureType == $this::SIGNATURE_HASH)
        $hashedMessage =
          bin2hex (
            strrev (
              hash (
                'sha256',
                hash (
                  'sha256', 
                  self::writeUInt32 ($signatureType) .
                  self::writeCAddress ($this->Address) .
                  self::writeUInt64 ($this->signatureTime) .
                  self::writeCPublicKey ($this->publicKeyCollateral) .
                  self::writeCPublicKey ($this->publicKeyMasternode) .
                  self::writeUInt32 ($this->protocolVersion),
                  true
                ),
                true
              )
            )
          );
      else
        $hashedMessage =
          $this->Address->toString () .
          $this->signatureTime .
          ($signatureType == $this::SIGNATURE_OLD ? $this->publicKeyCollateral->toBinary () : $this->publicKeyCollateral->getID ()) .
          ($signatureType == $this::SIGNATURE_OLD ? $this->publicKeyMasternode->toBinary () : $this->publicKeyMasternode->getID ()) .
          $this->protocolVersion;
      
      return
        self::writeCompactString ($messageMagic) .
        self::writeCompactString ($hashedMessage);
    }
    // }}}
  }
