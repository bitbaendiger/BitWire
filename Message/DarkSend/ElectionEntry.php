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
    
    /* Transaction-Input */
    private $TxIn = null;
    
    /* Address of node */
    private $Address = null;
    
    /* Signature for the entry */
    private $Signature = '';
    
    /* Time of signature */
    private $sigTime = 0;
    
    /* Public key */
    private $pubKey1 = null;
    
    /* Second public key */
    private $pubKey2 = null;
    
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
      return $this->sigTime;
    }
    // }}}
    
    // {{{ getPublicKey
    /**
     * Retrive one of the public keys
     * 
     * @param int $Num (optional)
     * 
     * @access public
     * @return BitWire_Crypto_PublicKey
     **/
    public function getPublicKey ($Num = 1) : ?BitWire_Crypto_PublicKey {
      if ($Num == 1)
        return $this->pubKey1;
      elseif ($Num == 2)
        return $this->pubKey2;
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
    
    // {{{ getDonationAddress
    /**
     * Retive donation-address (unused)
     * 
     * @access public
     * @return string
     **/
    public function getDonationAddress  () {
      return $this->donationAddress
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
      
      if ((($TxIn = self::readCTxIn ($Data, $Offset, $Length)) === null) ||
          (($Address = self::readCAddress ($Data, $Offset, $Length)) === null) ||
          (($Signature = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($sigTime = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($pubKey1 = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($pubKey2 = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($Count = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($Current = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($lastUpdate = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($protocolVersion = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($donationAddress = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($donationPercent = self::readUInt32 ($Data, $Offset, $Length)) === null))
        return false;
      
      if ((($pubKey1 = BitWire_Crypto_PublicKey::fromBinary ($pubKey1)) === null) ||
          (($pubKey2 = BitWire_Crypto_PublicKey::fromBinary ($pubKey2)) === null))
        return false;
      
      // Commit to this instance
      $this->TxIn = $TxIn;
      $this->Address = $Address;
      $this->Signature = $Signature;
      $this->sigTime = $sigTime;
      $this->pubKey1 = $pubKey1;
      $this->pubKey2 = $pubKey2;
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
        self::writeCTxIn ($this->TxIn) .
        self::writeCAddress ($this->Address) .
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->sigTime).
        self::writeCompactString ($this->pubKey1->toBinary ()) .
        self::writeCompactString ($this->pubKey2->toBinary ()) .
        self::writeUInt32 ($this->Count) .
        self::writeUInt32 ($this->Current) .
        self::writeUInt64 ($this->lastUpdate) .
        self::writeUInt32 ($this->protocolVersion) .
        self::writeCompactString ($this->donationAddress) .
        self::writeUInt32 ($this->donationPercent);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dsee', 'BitWire_Message_DarkSend_ElectionEntry');

?>