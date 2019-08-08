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
    
    /* Time of last ping */
    private $lastPing = 0;
    
    /* Time of last dsq broadcast  */
    private $lastDSQ = 0;
    
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
    
    // {{{ verify
    /**
     * Check if the signature here is valid
     * 
     * @access public
     * @return bool
     **/
    public function verify () {
      // Make sure we have everything we need
      if (!$this->publicKeyCollateral || !$this->publicKeyMasternode || !$this->Address)
        return false;
      
      // Reconstruct the messages to verify
      $newMessage =
        self::writeCompactString ("DarkNet Signed Message:\n") .
        self::writeCompactString (
          $this->Address->toString () .
          $this->signatureTime .
          $this->publicKeyCollateral->getID () .
          $this->publicKeyMasternode->getID () .
          $this->protocolVersion
        );
      
      $oldMessage =
        self::writeCompactString ("DarkNet Signed Message:\n") .
        self::writeCompactString (
          $this->Address->toString () .
          $this->signatureTime .
          $this->publicKeyCollateral->toBinary () .
          $this->publicKeyMasternode->toBinary () .
          $this->protocolVersion
        );
      
      // Verify the message
      return
        $this->publicKeyCollateral->verifyCompact ($newMessage, $this->Signature) ||
        $this->publicKeyCollateral->verifyCompact ($oldMessage, $this->Signature);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('mnb', 'BitWire_Message_Masternode_Broadcast');

?>