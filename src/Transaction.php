<?php

  /**
   * BitWire - Transaction
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
  
  namespace BitBaendiger\BitWire;
  
  class Transaction extends Hashable {
    /* Version of transaction */
    private $Version = 1;
    
    /* Time of transaction */
    private $hasTimestamp = false;
    private $Time = 0;
        
    /* Locktime of transaction */
    private $lockTime = 0;
    
    /* Inputs of transaction */
    private $transactionInputs = [ ];
    
    /* Outputs of transaction */
    private $transactionOutputs = [ ];
    
    /* Comment of this transaction */
    private $hasComment = false;
    private $Comment = null;
    
    // {{{ fromHex
    /**
     * Try to parse a transaction from hex-data
     * 
     * @param string $hexData
     * @param bool $hasTimestamp (optional)
     * @param bool $hasComment (optional)
     * 
     * @access public
     * @return Transaction
     **/
    public static function fromHex (string $hexData, bool $hasTimestamp = null, bool $hasComment = null) : Transaction {
      return static::fromBinary (hex2bin ($hexData), $hasTimestamp, $hasComment);
    }
    // }}}
    
    // {{{ fromBinary
    /**
     * Try to parse a transaction from binary data
     *
     * @param string $binaryData
     * @param bool $hasTimestamp (optional)
     * @param bool $hasComment (optional)
     * 
     * @access public
     * @return Transaction
     **/
    public static function fromBinary (string $binaryData, bool $hasTimestamp = null, bool $hasComment = null) : Transaction {
      $newTransaction = new static ($hasTimestamp, $hasComment);
      $newTransaction->parse ($binaryData);
      
      return $newTransaction;
    }
    // }}}
    
    
    // {{{ __construct
    /**
     * Create a new transaction
     * 
     * @param bool $hasTimestamp (optional)
     * @param bool $hasComment (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (bool $hasTimestamp = null, bool $hasComment = null) {
      if ($hasTimestamp !== null)
        $this->hasTimestamp = $hasTimestamp;
      
      if ($hasComment !== null)
        $this->hasComment = $hasComment;
    }
    // }}}
    
    // {{{ __clone
    /**
     * Create a copy of this object
     * 
     * @access friendly
     * @return void
     **/
    function __clone () {
      foreach ($this->transactionInputs as $inputKey=>$transactionInput) {
        $this->transactionInputs [$inputKey] = clone $transactionInput;
        $this->transactionInputs [$inputKey]->setTransaction ($this);
      }
      
      foreach ($this->transactionOutputs as $outputKey=>$transactionOutput)
        $this->transactionOutputs [$outputKey] = clone $transactionOutput;
    }
    // }}}
    
    // {{{ isCoinbase
    /**
     * Check if this is a coinbase-transaction
     * 
     * @access public
     * @return bool
     **/
    public function isCoinbase () : bool {
      return ((count ($this->transactionInputs) == 1) && $this->transactionInputs [0]->isCoinbase ());
    }
    // }}}
    
    // {{{ isCoinStake
    /**
     * Check if this transaction is a coin-stake
     * 
     * @access public
     * @return bool
     **/
    public function isCoinStake () : bool {
      // Check number of in- and outputs
      if ((count ($this->transactionInputs) < 1) ||
          (count ($this->transactionOutputs) < 2))
        return false;
      
      // Make sure the input is not empty
      if ($this->transactionInputs [0]->getHash ()->isEmpty ())
        return false;
      
      // Coin-Stake has first output empty
      return $this->transactionOutputs [0]->getScript ()->isEmpty ();
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version of this transaction
     * 
     * @access public
     * @return int
     **/
    public function getVersion () : int {
      return $this->Version;
    }
    // }}}
    
    // {{{ setVersion
    /**
     * Set the version of this transaction
     * 
     * @param int $Version
     * 
     * @access public
     * @return bool
     **/
    public function setVersion (int $Version) {
      if (($Version < 1) || ($Version > 0xFFFFFFFF))
        return false;
      
      $this->Version = $Version;
      
      return true;
    }
    // }}}
    
    // {{{ getLockTime
    /**
     * Retrive the lock-time of this transaction
     * 
     * @access public
     * @return int
     **/
    public function getLockTime () : int {
      return $this->lockTime;
    }
    // }}}
    
    // {{{ setLockTime
    /** 
     * Set a lock-time for this transaction
     * 
     * @param int $LockTime
     * 
     * @access public
     * @return bool
     **/
    public function setLockTime (int $LockTime) {
      if (($LockTime < 1) || ($LockTime > 0xFFFFFFFF))
        return false;
      
      $this->lockTime = $LockTime;
      
      return true;
    }
    // }}}
    
    // {{{ getInputs
    /**
     * Retrive all inputs of this transaction
     * 
     * @access public
     * @return array
     **/
    public function getInputs () : array {
      return $this->transactionInputs;
    }
    // }}}
    
    // {{{ addInput
    /**
     * Add another input to this transaction
     * 
     * @param Transaction\Input $transactionInput
     * 
     * @access public
     * @return void
     **/
    public function addInput (Transaction\Input $transactionInput) : void {
      $this->transactionInputs [] = $transactionInput;
    }
    // }}}
    
    // {{{ setInputs
    /**
     * Set all inputs for this transaction
     * 
     * @param array $transactionInputs
     * 
     * @access public
     * @return void
     **/
    public function setInputs (array $transactionInputs) : void {
      // Sanatize the inputs
      foreach ($transactionInputs as $transactionInput)
        if (!($transactionInput instanceof Transaction\Input))
          throw new \ValueError ('Only inputs are allowed');
      
      // Assign the inputs
      $this->transactionInputs = $transactionInputs;
    }
    // }}}
    
    // {{{ getOutputs
    /**
     * Retrive all outputs of this transaction
     * 
     * @access public
     * @return array
     **/
    public function getOutputs () : array {
      return $this->transactionOutputs;
    }
    // }}}
    
    // {{{ addOutput
    /**
     * Add another output to this transaction
     * 
     * @param Transaction\Output $transactionOutput
     * 
     * @access public
     * @return void
     **/
    public function addOutput (Transaction\Output $transactionOutput) : void {
      $this->transactionOutputs [] = $transactionOutput;
    }
    // }}}
    
    // {{{ setOutputs
    /**
     * Set all outputs for this transaction
     * 
     * @param array $transactionOutputs
     * 
     * @access public
     * @return void
     **/
    public function setOutputs (array $transactionOutputs) : void {
      foreach ($transactionOutputs as $transactionOutput)
        if (!($transactionOutput instanceof \BitBaendiger\BitWire\Transaction\Output))
          throw new \ValueError ('Only outputs are allowed');
      
      $this->transactionOutputs = $transactionOutputs;
    }
    // }}}
    
    // {{{ shuffleOutputs
    /**
     * Randomize the order of outputs on this transaction
     * 
     * @access public
     * @return void
     **/
    public function shuffleOutputs () : void {
      shuffle ($this->transactionOutputs);
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse an transaction from an input-buffer
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return void
     **/
    public function parse (string &$Data, int &$Offset = 0, int $Length = null) : void {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Copy offset to temporary offset
      $tOffset = $Offset;
      
      // Try to read the version of this transaction
      $Version = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      
      // Read timestamp on PoS-Transaction
      if (!$this->hasTimestamp)
        $Time = null;
      else
        $Time = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      
      // Read number of transaction-inputs
      $inputCount = Message\Payload::readCompactSize ($Data, $tOffset, $Length);
      
      // Check for Witness
      if (($inputCount == 0) && !($Version & 0x00000004)) {
        $transactionFlags = ord ($Data [$tOffset++]);
        $inputCount = Message\Payload::readCompactSize ($Data, $tOffset, $Length);
      } else
        $transactionFlags = 0x00;
      
      // Try to read all inputs
      $transactionInputs = [ ];
      
      for ($i = 0; $i < $inputCount; $i++) {
        $transactionInputs [] = $Input = new Transaction\Input ($this);
        $Input->parse ($Data, $tOffset, $Length);
      }
      
      // Read number of outputs on this transaction
      $outputCount = Message\Payload::readCompactSize ($Data, $tOffset, $Length);
      
      // Try to read all outputs
      $transactionOutputs = [ ];
      
      for ($i = 0; $i < $outputCount; $i++) {
        $transactionOutputs [] = $transactionOutput = new Transaction\Output;
        $transactionOutput->parse ($Data, $tOffset, $Length);
      }
      
      // Read witness-scripts
      if ($transactionFlags & 1) {
        for ($i = 0; $i < count ($transactionInputs); $i++) {
          $stackSize = Message\Payload::readCompactSize ($Data, $tOffset, $Length);
          $witnessStack = [ ];
          
          for ($j = 0; $j < $stackSize; $j++)
            $witnessStack [] = Message\Payload::readCompactString ($Data, $tOffset, $Length);
          
          $transactionInputs [$i]->setWitnessStack ($witnessStack);
        }
        
        $transactionFlags ^= 1;
      }
      
      if ($transactionFlags)
        throw new \ValueError ('Unknown transaction-flags');
      
      // Try to read lock-time
      $lockTime = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      
      if (!$this->hasComment)
        $Comment = null;
      else
        $Comment = Message\Payload::readCompactString ($Data, $tOffset, $Length);
      
      // Commit changes to this instance
      $this->Version = $Version;
      $this->Time = $Time;
      $this->lockTime = $lockTime;
      $this->Comment = $Comment;
      $this->transactionInputs = $transactionInputs;
      $this->transactionOutputs = $transactionOutputs;
      
      $Offset = $tOffset;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      // Generate start of transaction
      if ($this->hasTimestamp)
        $Buffer = pack ('VV', $this->Version, $this->Time) . Message\Payload::toCompactSize (count ($this->transactionInputs));
      else
        $Buffer = pack ('V', $this->Version) . Message\Payload::toCompactSize (count ($this->transactionInputs));
      
      // Append Inputs
      foreach ($this->transactionInputs as $Input)
        $Buffer .= $Input->toBinary ();
      
      // Append Outputs
      $Buffer .= Message\Payload::toCompactSize (count ($this->transactionOutputs));
      
      foreach ($this->transactionOutputs as $Output)
        $Buffer .= $Output->toBinary ();
      
      // Append Locktime
      $Buffer .= pack ('V', $this->lockTime);
      
      // Append comment
      if ($this->hasComment)
        $Buffer .= Message\Payload::toCompactString ($this->Comment);
      
      return $Buffer;
    }
    // }}}
  }
