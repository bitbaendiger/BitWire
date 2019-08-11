<?PHP

  /**
   * BitWire - Masternode Request Message
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
  
  class BitWire_Message_Masternode_Request extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'dseg';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    // {{{ getTransactionInput
    /**
     * Retrive the transaction-input of this masternode-ping
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
      
      if (($txIn = self::readCTxIn ($Data, $Offset, $Length)) === null)
        return false;
      
      // Commit to this instance
      $this->txIn = $txIn;
      
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
      return self::writeCTxIn ($this->txIn);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dseg', 'BitWire_Message_Masternode_Request');

?>