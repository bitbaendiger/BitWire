<?php

  /**
   * BitWire - Transaction Input
   * Copyright (C) 2017-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  namespace BitBaendiger\BitWire\Transaction;
  use BitBaendiger\BitWire;
  
  class Input {
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
     * @param BitWire\Hash $transactionHash
     * @param int $transactionIndex
     * 
     * @access private
     * @return bool
     **/
    private static function checkCoinbase (BitWire\Hash $transactionHash, int $transactionIndex) : bool {
      if ($transactionIndex != 0xFFFFFFFF)
        return false;
      
      return $transactionHash->isEmpty ();
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new transaction-input
     * 
     * @param BitWire\Transaction $parentTransaction (optional)
     * @param BitWire\Hash $transactionHash (optional)
     * @param int $transactionIndex (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire\Transaction $parentTransaction = null, BitWire\Hash $transactionHash = null, int $transactionIndex = null) {
      $this->parentTransaction = $parentTransaction;
      $this->transactionHash = $transactionHash ?? new BitWire\Hash;
      
      if ($transactionIndex !== null)
        $this->transactionIndex = $transactionIndex;
      
      $this->Script = new Script;
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
      return [
        'hash' => strval ($this->transactionHash),
        'index' => $this->transactionIndex,
        'sequence' => $this->Sequence,
        'script' => strval ($this->Script),
      ];
    }
    // }}}
    
    // {{{ isCoinbase
    /**
     * Check if this input represents coinbase-input
     * 
     * @access public
     * @return bool
     **/
    public function isCoinbase () : bool {
      return self::checkCoinbase ($this->transactionHash, $this->transactionIndex);
    }
    // }}}
    
    // {{{ getTransaction
    /**
     * Retrive the associated transaction
     * 
     * @access public
     * @return BitWire\Transaction
     **/
    public function getTransaction () : ?BitWire\Transaction {
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
    public function getIndex () : int {
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
    public function setIndex (int $transactionIndex) : void {
      $this->transactionIndex = $transactionIndex;
    }
    // }}}
    
    // {{{ getSequence
    /**
     * Retrive the sequence of this input
     * 
     * @access public
     * @return int
     **/
    public function getSequence () : int {
      return $this->Sequence;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash of the previous output
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      return $this->transactionHash;
    }
    // }}}
    
    // {{{ setHash
    /**
     * Store the hash if the previous output
     * 
     * @param BitWire\Hash $transactionHash
     * 
     * @access public
     * @return void
     **/
    public function setHash (BitWire\Hash $transactionHash) : void {
      $this->transactionHash = $transactionHash;
    }
    // }}}
    
    // {{{ getScript
    /**
     * Retrive the script of this input
     * 
     * @access public
     * @return Script
     **/
    public function getScript () : Script {
      return $this->Script;
    }
    // }}}
    
    // {{{ setWitnessStack
    /**
     * Push witness-stack for this transaction-input
     * 
     * @param array $witnessStack
     * 
     * @access public
     * @return void
     **/
    public function setWitnessStack (array $witnessStack) {
      # Unimplemented
    }
    // }}}
    
    // {{{ getAddresses
    /**
     * Retrive addresses of this input
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () : array {
      return $this->Script->getAddresses ();
    }
    // }}}
    
    // {{{ getStakeUniqueness
    /**
     * Retrive uniqueness for proof-of-stake
     * 
     * @access public
     * @return string
     **/
    public function getStakeUniqueness () : string {
      return pack ('Va32', $this->transactionIndex, $this->transactionHash->toBinary (true));
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
    public function toString ($shortHash = false) : string {
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
     * @return void
     **/
    public function parse (string &$Data, int &$Offset, int $Length = null) : void {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Try to read everything into our memory
      $transactionHash     = BitWire\Message\Payload::readHash ($Data, $Offset, $Length);
      $transactionIndex    = BitWire\Message\Payload::readUInt32 ($Data, $Offset, $Length);
      $transactionScript   = BitWire\Message\Payload::readCompactString ($Data, $Offset, $Length);
      $transactionSequence = BitWire\Message\Payload::readUInt32 ($Data, $Offset, $Length);
      
      // Check size-constraints for script
      $scriptSize = strlen ($transactionScript);
      
      if (self::checkCoinbase ($transactionHash, $transactionIndex)) {
        if ($scriptSize > 101)
          throw new \LengthException ('Coinbase-Script too large');
        
        # TODO: Any further checks?
      } elseif ($scriptSize > 10003)
        throw new \LengthException ('Input-Script too large');
      
      // Store the results on this instance
      $this->transactionHash = $transactionHash;
      $this->transactionIndex = $transactionIndex;
      $this->Script = new Script ($transactionScript);
      $this->Sequence = $transactionSequence;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create binary representation of this input
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      return
        BitWire\Message\Payload::writeHash ($this->transactionHash) .
        BitWire\Message\Payload::writeUInt32 ($this->transactionIndex) .
        BitWire\Message\Payload::writeCompactString ($this->Script->toBinary ()) .
        BitWire\Message\Payload::writeUInt32 ($this->Sequence);
    }
    // }}}
  }
