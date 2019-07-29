<?PHP

  /**
   * BitWire - Transaction Input
   * Copyright (C) 2017 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Transaction_Input {
    /* Transaction containing this input */
    private $Transaction = null;
    
    /* Hash of UTXO assigned to this input */
    private $Hash = null;
    
    /* Index of UTXO assigned to this input */
    private $Index = 0;
    
    /* Signature-Script */
    private $Script = null;
    
    /* Sequence of input */
    private $Sequence = 0;
    
    private static function checkCoinbase ($Hash, $Index) {
      if ($Index != 0xFFFFFFFF)
        return false;
      
      return (strcmp ($Hash, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00") == 0);
    }
    
    // {{{ __construct
    /**
     * Create a new transaction-input
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Transaction $Transaction = null) {
      $this->Transaction = $Transaction;
      $this->Hash = new BitWire_Hash;
      $this->Script = new BitWire_Transaction_Script ($this);
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for vardump()
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      return array (
        'hash' => strval ($this->Hash),
        'index' => $this->Index,
        'sequence' => $this->Sequence,
        'script' => strval ($this->Script),
      );
    }
    // }}}
    
    // {{{ isCoinbase
    /**
     * Check if this input represents coinbase-input
     * 
     * @access public
     * @return bool
     **/
    public function isCoinbase () {
      return self::checkCoinbase ($this->Hash->toBinary (), $this->Index);
    }
    // }}}
    
    // {{{ getTransaction
    /**
     * Retrive the associated transaction
     * 
     * @access public
     * @return BitWire_Transaction
     **/
    public function getTransaction () : ?BitWire_Transaction {
      return $this->Transaction;
    }
    // }}}
    
    // {{{ getIndex
    /**
     * Retrive the index of this input
     * 
     * @access public
     * @return int
     **/
    public function getIndex () {
      return $this->Index;
    }
    // }}}
    
    // {{{ getSequence
    /**
     * Retrive the sequence of this input
     * 
     * @access public
     * @return int
     **/
    public function getSequence () {
      return $this->Sequence;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash of the previous output
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () {
      return $this->Hash;
    }
    // }}}
    
    // {{{ getScript
    /**
     * Retrive the script of this input
     * 
     * @access public
     * @return BitWire_Transaction_Script
     **/
    public function getScript () {
      return $this->Script;
    }
    // }}}
    
    // {{{ getAddresses
    /**
     * Retrive addresses of this input
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () {
      return $this->Script->getAddresses ();
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse input-transaction from binary
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return bool
     **/
    public function parse (&$Data, &$Offset, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Try to read everything into our memory
      if ((($Hash = BitWire_Message_Payload::readChar ($Data, $Offset, 32, $Length)) === null) ||
          (($Index = BitWire_Message_Payload::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($Script = BitWire_Message_Payload::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($Sequence = BitWire_Message_Payload::readUInt32 ($Data, $Offset, $Length)) === null))
        return false;
      
      // Check size-constraints for script
      $scriptSize = strlen ($Script);
      
      if (self::checkCoinbase ($Hash, $Index)) {
        if ($scriptSize > 101)
          return false;
        
        # TODO: Any further checks?
      } elseif ($scriptSize > 10003)
        return false;
      
      // Store the results on this instance
      $this->Hash = BitWire_Hash::fromBinary ($Hash, true);
      $this->Index = $Index;
      $this->Script = new BitWire_Transaction_Script ($this, $Script);
      $this->Sequence = $Sequence;
      
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create binary representation of this input
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      return
        pack ('a32V', $this->Hash->toBinary (true), $this->Index) .
        BitWire_Message_Payload::toCompactString ($this->Script->toBinary ()) .
        pack ('V', $this->Sequence);
    }
    // }}}
  }

?>