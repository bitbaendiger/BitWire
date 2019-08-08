<?PHP

  /**
   * BitWire - Masternode Ping Message
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
  
  class BitWire_Message_Masternode_Ping extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'mnp';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    /* Hash of block */
    private $Hash = null;
    
    /* Time of signature */
    private $signatureTime = 0;
    
    /* The signature itself */
    private $Signature = '';
    
    // {{{ fromString
    /**
     * Try to read a masternode-ping from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return BitWire_Message_Masternode_Ping
     **/
    public static function readString (&$Data, &$Offset, $Length = null) : ?BitWire_Message_Masternode_Ping {
      $Instance = new static;
      
      if (!$Instance->parse ($Data, $Offset, $Length))
        return null;
      
      return $Instance;
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
          (($Hash = self::readHash ($Data, $tOffset, $Length)) === null) ||
          (($signatureTime = self::readUInt64 ($Data, $tOffset, $Length)) === null) ||
          (($Signature = self::readCompactString ($Data, $tOffset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->txIn = $txIn;
      $this->Hash = $Hash;
      $this->signatureTime = $signatureTime;
      $this->Signature = $Signature;
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
        self::writeHash ($this->Hash) .
        self::writeUInt64 ($this->signatureTime) .
        self::writeCompactString ($this->Signature);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('mnp', 'BitWire_Message_Masternode_Ping');

?>