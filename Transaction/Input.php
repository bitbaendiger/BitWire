<?PHP

  /**
   * BitWire - Transaction Input
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
  
  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Transaction_Input {
    /* Transaction containing this input */
    private $parentTransaction = null;
    
    /* Hash of UTXO assigned to this input */
    private $transactionHash = null;
    
    /* Index of UTXO assigned to this input */
    private $transactionIndex = 0xffffffff;
    
    /* Signature-Script */
    private $Script = null;
    
    /* Sequence of input */
    private $Sequence = 0xffffffff;
    
    // {{{ checkCoinbase
    /**
     * Check if a given hash and index might represent a coinbase
     * 
     * @param BitWire_Hash $transactionHash
     * @param int $transactionIndex
     * 
     * @access private
     * @return bool
     **/
    private static function checkCoinbase (BitWire_Hash $transactionHash, $transactionIndex) {
      if ($transactionIndex != 0xFFFFFFFF)
        return false;
      
      return $transactionHash->isEmpty ();
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new transaction-input
     * 
     * @param BitWire_Transaction $parentTransaction (optional)
     * @param BitWire_Hash $transactionHash (optional)
     * @param int $transactionIndex (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Transaction $parentTransaction = null, BitWire_Hash $transactionHash = null, $transactionIndex = null) {
      $this->parentTransaction = $parentTransaction;
      
      if ($transactionHash)
        $this->transactionHash = $transactionHash;
      else
        $this->transactionHash = new BitWire_Hash;
      
      if ($transactionIndex !== null)
        $this->transactionIndex = $transactionIndex;
      
      $this->Script = new BitWire_Transaction_Script;
    }
    // }}}
    
    // {{{ __toString
    /**
     * Convert this one into a string
     * 
     * @access public
     * @return string
     **/
    function __toString () {
      return strval ($this->transactionHash) . ':' . $this->transactionIndex;
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
        'hash' => strval ($this->transactionHash),
        'index' => $this->transactionIndex,
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
      return self::checkCoinbase ($this->transactionHash, $this->transactionIndex);
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
      return $this->parentTransaction;
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
      return $this->transactionIndex;
    }
    // }}}
    
    // {{{ setIndex
    /**
     * Set the index of out previous output
     * 
     * @param int $Index
     * 
     * @access public
     * @return void
     **/
    public function setIndex ($transactionIndex) {
      $this->transactionIndex = (int)$transactionIndex;
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
    public function getHash () : BitWire_Hash {
      return $this->transactionHash;
    }
    // }}}
    
    // {{{ setHash
    /**
     * Store the hash if the previous output
     * 
     * @param BitWire_Hash $transactionHash
     * 
     * @access public
     * @return void
     **/
    public function setHash (BitWire_Hash $transactionHash) {
      $this->transactionHash = $transactionHash;
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
    
    // {{{ toString
    /** 
     * Convert this input to a string like bitcore would do
     * 
     * @param bool $shortHash (optional) Short hash of outpoint
     * 
     * @access public
     * @return string
     **/
    public function toString ($shortHash = false) {
      $outpointHash = strval ($this->transactionHash);
      
      return
        'CTxIn(' .
          'COutPoint(' . ($shortHash ? substr ($outpointHash, 0, 10) : $outpointHash) . ', ' . $this->transactionIndex . ')' .
          # TODO: Missing support for zerocoin
          ($this->isCoinbase () ? ', coinbase ' . bin2hex ($this->Script->toBinary ()) : ', scriptSig=' . substr (strval ($this->Script), 0, 24)) .
          ($this->Sequence != 0xffffffff ? ', nSequence=' . $this->Sequence : '') .
        ')';
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
      if ((($transactionHash = BitWire_Message_Payload::readHash ($Data, $Offset, $Length)) === null) ||
          (($transactionIndex = BitWire_Message_Payload::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($Script = BitWire_Message_Payload::readCompactString ($Data, $Offset, $Length)) === null) ||
          (($Sequence = BitWire_Message_Payload::readUInt32 ($Data, $Offset, $Length)) === null))
        return false;
      
      // Check size-constraints for script
      $scriptSize = strlen ($Script);
      
      if (self::checkCoinbase ($transactionHash, $transactionIndex)) {
        if ($scriptSize > 101)
          return false;
        
        # TODO: Any further checks?
      } elseif ($scriptSize > 10003)
        return false;
      
      // Store the results on this instance
      $this->transactionHash = $transactionHash;
      $this->transactionIndex = $transactionIndex;
      $this->Script = new BitWire_Transaction_Script ($Script);
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
        BitWire_Message_Payload::writeHash ($this->transactionHash) .
        BitWire_Message_Payload::writeUInt32 ($this->transactionIndex) .
        BitWire_Message_Payload::writeCompactString ($this->Script->toBinary ()) .
        BitWire_Message_Payload::writeUInt32 ($this->Sequence);
    }
    // }}}
  }

?>