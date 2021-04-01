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
    private $inputScript = null;
    
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
      $this->transactionHash = $transactionHash ?? new BitWire\Hash ();
      
      if ($transactionIndex !== null)
        $this->transactionIndex = $transactionIndex;
      
      $this->inputScript = new Script ();
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
        'script' => (string)$this->inputScript,
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
      return !$this->isZerocoinSpend () && self::checkCoinbase ($this->transactionHash, $this->transactionIndex);
    }
    // }}}
    
    // {{{ isZerocoinSpend
    /**
     * Check if this is a zerocoin-spend
     * 
     * @access public
     * @return bool
     **/
    public function isZerocoinSpend () : bool {
      if (!self::checkCoinbase ($this->transactionHash, $this->transactionIndex))
        return false;
      
      return $this->inputScript->isZerocoinSpend ();
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
    
    // {{{ setTransaction
    /**
     * Associate a transaction with this input
     * 
     * @param BitWire\Transaction $myTransaction
     * 
     * @access public
     * @return void
     **/
    public function setTransaction (BitWire\Transaction $myTransaction) : void {
      $this->parentTransaction = $myTransaction;
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
      return $this->inputScript;
    }
    // }}}
    
    // {{{ setScript
    /**
     * Replace our script
     * 
     * @param Script $inputScript
     * 
     * @access public
     * @return void
     **/
    public function setScript (Script $inputScript) : void {
      $this->inputScript = $inputScript;
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
      return $this->inputScript->getAddresses ();
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
          ($this->isCoinbase () ? ', coinbase ' . bin2hex ($this->inputScript->toBinary ()) : ', scriptSig=' . substr (strval ($this->inputScript), 0, 24)) .
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
      $isZerocoin = (($scriptSize > 0) && (ord ($transactionScript [0]) == Script::OP_ZEROCOINSPEND));
      
      if (self::checkCoinbase ($transactionHash, $transactionIndex) && !$isZerocoin) {
        if ($scriptSize > 101)
          throw new \LengthException ('Coinbase-Script too large');
        
        # TODO: Any further checks?
      } elseif (($scriptSize > 10003) && !$isZerocoin)
        throw new \LengthException ('Input-Script too large');
      
      # TODO: Enforce length-constraint on zerocoin-transactios?
      
      // Store the results on this instance
      $this->transactionHash = $transactionHash;
      $this->transactionIndex = $transactionIndex;
      $this->inputScript = new Script ($transactionScript);
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
        BitWire\Message\Payload::writeCompactString ($this->inputScript->toBinary ()) .
        BitWire\Message\Payload::writeUInt32 ($this->Sequence);
    }
    // }}}
    
    // {{{ sign
    /**
     * Create a signature for this input
     * 
     * @todo THIS ONLY SIGNS P2PKH-TRANSACTIONS
     * 
     * @param Output $previousOutput
     * @param BitWire\Crypto\PrivateKey $privateKey
     * 
     * @access public
     * @return void
     **/
    public function sign (Output $previousOutput, BitWire\Crypto\PrivateKey $privateKey) : void {
      // Make sure we have a transaction assigned
      if (!$this->parentTransaction)
        throw new \Exception ('No transaction assigned');
      
      // Check if we are able to sign
      if (!$previousOutput->getScript ()->isPublicKeyHashOutput ())
        throw new \Exception ('Unsupported output-type');
      
      // Find my place in the original transaction
      $myPlace = null;
      
      foreach ($this->parentTransaction->getInputs () as $txIndex=>$transactionInput)
        if ($transactionInput === $this) {
          $myPlace = $txIndex;
          
          break;
        }
      
      if ($myPlace === null)
        throw new \Exception ('Failed to find myself on the transaction');
      
      // Create a copy of our transaction
      $signTransaction = clone $this->parentTransaction;
      
      foreach ($signTransaction->getInputs () as $txIndex=>$transactionInput)
        if ($txIndex === $myPlace)
          $transactionInput->setScript ($previousOutput->getScript ());
        else
          $transactionInput->getScript ()->empty ();
      
      // Convert transaction to binary
      $signBinary = $signTransaction->toBinary () . "\x01\x00\x00\x00";
      
      // Try to sign
      $inputScript = new Script ();
      $inputScript->pushData ($privateKey->sign ($signBinary) . "\x01");
      $inputScript->pushData ($privateKey->toPublicKey ()->toBinary ());
      
      // Replace the input-script
      $this->inputScript = $inputScript;
    }
    // }}}
  }
