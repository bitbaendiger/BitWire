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
    private $TxIn = null;
    
    /* Signature for the ping */
    private $Signature = '';
    
    /* Timestamp of the ping */
    private $Timestamp = 0;
    
    /* Stop this election-entry ?! (unused) */
    private $Stop = false;
    
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
          (($Signature = self::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($Timestamp = self::readUInt64 ($Data, $Offset, $Length)) === null) ||
          (($Stop = self::readBoolean ($data, $Offset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->TxIn = $TxIn;
      $this->Signature = $Signature;
      $this->Timestamp = $Timestamp;
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
        self::writeCTxIn ($this->TxIn) .
        self::writeCompactString ($this->Signature) .
        self::writeUInt64 ($this->Timestamp).
        self::writeBoolean ($this->Stop);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dseep', 'BitWire_Message_DarkSend_ElectionEntryPing');

?>