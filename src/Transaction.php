<?PHP

  namespace BitBaendiger\BitWire;
  
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
  
  require_once ('BitWire/src/Hashable.php');
  require_once ('BitWire/src/Message/Payload.php');
  require_once ('BitWire/src/Transaction/Input.php');
  require_once ('BitWire/src/Transaction/Output.php');
  require_once ('BitWire/src/Transaction/Script.php');
  
  class Transaction extends Hashable {
    /* Type of transaction */
    const TYPE_POW = 0;
    const TYPE_POS = 1;
    
    private $Type = Transaction::TYPE_POW;
    
    /* Version of transaction */
    private $Version = 1;
    
    /* Time of transaction (for PoS-Transactions) */
    private $Time = 0;
        
    /* Locktime of transaction */
    private $lockTime = 0;
    
    /* Inputs of transaction */
    private $transactionInputs = array ();
    
    /* Outputs of transaction */
    private $transactionOutputs = array ();
    
    /* Comment of this transaction */
    private $hasComment = false;
    private $Comment = null;
    
    // {{{ fromHex
    /**
     * Try to parse a transaction from hex-data
     * 
     * @param string $hexData
     * 
     * @access public
     * @return Transaction
     **/
    public static function fromHex ($hexData) : ?Transaction {
      return static::fromBinary (hex2bin ($hexData));
    }
    // }}}
    
    // {{{ fromBinary
    /**
     * Try to parse a transaction from binary data
     *
     * @param string $binaryData
     * 
     * @access public
     * @return Transaction
     **/
    public static function fromBinary ($binaryData) : ?Transaction {
      $newTransaction = new static;
      
      if (!$newTransaction->parse ($binaryData))
        return null;
      
      return $newTransaction;
    }
    // }}}
    
    
    // {{{ __construct
    /**
     * Create a new transaction
     * 
     * @param enum $Type (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Type = null, $hasComment = null) {
      if ($Type !== null)
        $this->Type = $Type;
      
      if ($hasComment !== null)
        $this->hasComment = $hasComment;
    }
    // }}}
    
    // {{{ isCoinbase
    /**
     * Check if this is a coinbase-transaction
     * 
     * @access public
     * @return bool
     **/
    public function isCoinbase () {
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
    public function isCoinStake () {
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
    public function getVersion () {
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
    public function setVersion ($Version) {
      if (($Version < 1) || ($Version > 0xFFFFFFFF))
        return false;
      
      $this->Version = (int)$Version;
      
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
    public function getLockTime () {
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
    public function setLockTime ($LockTime) {
      if (($LockTime < 1) || ($LockTime > 0xFFFFFFFF))
        return false;
      
      $this->lockTime = (int)$LockTime;
      
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
    public function getInputs () {
      return $this->transactionInputs;
    }
    // }}}
    
    // {{{ addInput
    /**
     * Add another input to this transaction
     * 
     * @param BitWire_Transaction_Input $transactionInput
     * 
     * @access public
     * @return void
     **/
    public function addInput (\BitBaendiger\BitWire\Transaction\Input $transactionInput) {
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
     * @return bool
     **/
    public function setInputs (array $transactionInputs) {
      // Sanatize the inputs
      foreach ($transactionInputs as $transactionInput)
        if (!($transactionInput instanceof \BitBaendiger\BitWire\Transaction\Input))
          return false;
      
      // Assign the inputs
      $this->transactionInputs = $transactionInputs;
      
      return true;
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
     * @param BitWire_Transaction_Output $transactionOutput
     * 
     * @access public
     * @return void
     **/
    public function addOutput (\BitBaendiger\BitWire\Transaction\Output $transactionOutput) {
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
     * @return bool
     **/
    public function setOutputs (array $transactionOutputs) {
      foreach ($transactionOutputs as $transactionOutput)
        if (!($transactionOutput instanceof \BitBaendiger\BitWire\Transaction\Output))
          return false;
      
      $this->transactionOutputs = $transactionOutputs;
      
      return true;
    }
    // }}}
    
    // {{{ shuffleOutputs
    /**
     * Randomize the order of outputs on this transaction
     * 
     * @access public
     * @return void
     **/
    public function shuffleOutputs () {
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
     * @return bool
     **/
    public function parse (&$Data, &$Offset = 0, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Copy offset to temporary offset
      $tOffset = $Offset;
      
      // Try to read the version of this transaction
      if (($Version = \BitBaendiger\BitWire\Message\Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Read timestamp on PoS-Transaction
      if ($this->Type != $this::TYPE_POS)
        $Time = null;
      elseif (($Time = \BitBaendiger\BitWire\Message\Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Read number of inputs on this transaction
      if (($Count = \BitBaendiger\BitWire\Message\Payload::readCompactSize ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Try to read all inputs
      $Inputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        $Inputs [] = $Input = new \BitBaendiger\BitWire\Transaction\Input ($this);
        
        if (!$Input->parse ($Data, $tOffset, $Length))
          return false;
      }
      
      // Read number of outputs on this transaction
      if (($Count = \BitBaendiger\BitWire\Message\Payload::readCompactSize ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Try to read all outputs
      $transactionOutputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        $transactionOutputs [] = $transactionOutput = new \BitBaendiger\BitWire\Transaction\Output;
        
        if (!$transactionOutput->parse ($Data, $tOffset, $Length))
          return false;
      }
      
      // Try to read lock-time
      if (($lockTime = \BitBaendiger\BitWire\Message\Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      if (!$this->hasComment)
        $Comment = null;
      elseif (($Comment = \BitBaendiger\BitWire\Message\Payload::readCompactString ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Commit changes to this instance
      $this->Version = $Version;
      $this->Time = $Time;
      $this->lockTime = $lockTime;
      $this->Comment = $Comment;
      $this->transactionInputs = $Inputs;
      $this->transactionOutputs = $transactionOutputs;
      
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
      // Generate start of transaction
      if ($this->Type == $this::TYPE_POS)
        $Buffer = pack ('VV', $this->Version, $this->Time) . \BitBaendiger\BitWire\Message\Payload::toCompactSize (count ($this->transactionInputs));
      else
        $Buffer = pack ('V', $this->Version) . \BitBaendiger\BitWire\Message\Payload::toCompactSize (count ($this->transactionInputs));
      
      // Append Inputs
      foreach ($this->transactionInputs as $Input)
        $Buffer .= $Input->toBinary ();
      
      // Append Outputs
      $Buffer .= \BitBaendiger\BitWire\Message\Payload::toCompactSize (count ($this->transactionOutputs));
      
      foreach ($this->transactionOutputs as $Output)
        $Buffer .= $Output->toBinary ();
      
      // Append Locktime
      $Buffer .= pack ('V', $this->lockTime);
      
      // Append comment
      if ($this->hasComment)
        $Buffer .= \BitBaendiger\BitWire\Message\Payload::toCompactString ($this->Comment);
      
      return $Buffer;
    }
    // }}}
  }

?>