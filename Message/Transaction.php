<?PHP

  /**
   * BitWire - Transaction-Message
   * Copyright (C) 2017-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  require_once ('BitWire/Transaction.php');
  
  class BitWire_Message_Transaction extends BitWire_Message_Payload_Hashable {
    const PAYLOAD_COMMAND = 'tx';
    
    /* BitWire-Transcation */
    private $Transaction = null;
    
    // {{{ getHash
    /**
     * Retrive the hash for this payload
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () : BitWire_Hash {
      if ($this->Transaction)
        return $this->Transaction->getHash ();
      
      return new BitWire_Hash;
    }
    // }}}
    
    // {{{ getTransaction
    /**
     * Retrive the transaction-object stored on this message
     * 
     * @access public
     * @return BitWire_Transaction
     **/
    public function getTransaction () {
      return $this->Transaction;
    }
    // }}}
    
    // {{{ setTransaction
    /**
     * Store a transaction on this payload
     * 
     * @param BitWire_Transaction $Transaction
     * 
     * @access public
     * @return void
     **/
    public function setTransaction (BitWire_Transaction $Transaction) {
      $this->Transaction = $Transaction;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      // Create a new transaction
      if (!$this->Transaction)
        $this->Transaction = new BitWire_Transaction;
      
      // Try to parse the data
      $Length = strlen ($Data);
      $Offset = 0;
      
      if (!$this->Transaction->parse ($Data, $Offset, $Length))
        return false;
      
      return ($Length == $Offset);
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
      if (!$this->Transaction)
        return false;
      
      return $this->Transaction->toBinary ();
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('tx', 'BitWire_Message_Transaction');

?>