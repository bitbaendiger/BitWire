<?PHP

  require_once ('BitWire/Hashable.php');
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction/Input.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Transaction extends BitWire_Hashable {
    /* Type of transaction */
    const TYPE_POW = 0;
    const TYPE_POS = 1;
    
    private $Type = BitWire_Transaction::TYPE_POW;
    
    /* Version of transaction */
    private $Version = 1;
    
    /* Time of transaction (for PoS-Transactions) */
    private $Time = 0;
        
    /* Locktime of transaction */
    private $lockTime = 0;
    
    /* Inputs of transaction */
    private $Inputs = array ();
    
    /* Outputs of transaction */
    private $Outputs = array ();
    
    /* Comment of this transaction */
    private $hasComment = false;
    private $Comment = null;
    
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
      return ((count ($this->Inputs) == 1) && $this->Inputs [0]->isCoinbase ());
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
      return $this->Inputs;
    }
    // }}}
    
    // {{{ setInputs
    /**
     * Set all inputs for this transaction
     * 
     * @param array $Inputs
     * 
     * @access public
     * @return bool
     **/
    public function setInputs (array $Inputs) {
      $this->Inputs = $Inputs;
      
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
    public function getOutputs () {
      return $this->Outputs;
    }
    // }}}
    
    // {{{ setOutputs
    /**
     * Set all outputs for this transaction
     * 
     * @param array $Outputs
     * 
     * @access public
     * @return bool
     **/
    public function setOutputs (array $Outputs) {
      $this->Outputs = $Outputs;
      
      return true;
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
    public function parse (&$Data, &$Offset, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Copy offset to temporary offset
      $tOffset = $Offset;
      
      // Try to read the version of this transaction
      if (($Version = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Read timestamp on PoS-Transaction
      if ($this->Type != $this::TYPE_POS)
        $Time = null;
      elseif (($Time = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Read number of inputs on this transaction
      if (($Count = BitWire_Message_Payload::readCompactSize ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Try to read all inputs
      $Inputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        $Inputs [] = $Input = new BitWire_Transaction_Input ($this);
        
        if (!$Input->parse ($Data, $tOffset, $Length))
          return false;
      }
      
      // Read number of outputs on this transaction
      if (($Count = BitWire_Message_Payload::readCompactSize ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Try to read all outputs
      $Outputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Read values of the output
        if ((($Amount = BitWire_Message_Payload::readUInt64 ($Data, $tOffset, $Length)) === null) ||
            (($Script = BitWire_Message_Payload::readCompactString ($Data, $tOffset, $Length)) === null))
          return false;
        
        // Check size-constraints for script
        if (strlen ($Script) > 10003)
          return false;
        
        $Outputs [] = array (
          'amount' => $Amount,
          'script' => new BitWire_Transaction_Script ($this, $Script),
        );
      }
      
      // Try to read lock-time
      if (($lockTime = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null)
        return false;
      
      if (!$this->hasComment)
        $Comment = null;
      elseif (($Comment = BitWire_Message_Payload::readCompactString ($Data, $tOffset, $Length)) === null)
        return false;
      
      // Commit changes to this instance
      $this->Version = $Version;
      $this->Time = $Time;
      $this->lockTime = $lockTime;
      $this->Comment = $Comment;
      $this->Inputs = $Inputs;
      $this->Outputs = $Outputs;
      
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
        $Buffer = pack ('VV', $this->Version, $this->Time) . BitWire_Message_Payload::toCompactSize (count ($this->Inputs));
      else
        $Buffer = pack ('V', $this->Version) . BitWire_Message_Payload::toCompactSize (count ($this->Inputs));
      
      // Append Inputs
      foreach ($this->Inputs as $Input)
        $Buffer .= $Input->toBinary ();
      
      // Append Outputs
      $Buffer .= BitWire_Message_Payload::toCompactSize (count ($this->Outputs));
      
      foreach ($this->Outputs as $Output)
        $Buffer .=
          pack ('P', $Output ['amount']) .
          BitWire_Message_Payload::toCompactString ($Output ['script']->toBinary ());
      
      // Append Locktime
      $Buffer .= pack ('V', $this->lockTime);
      
      // Append comment
      if ($this->hasComment)
        $Buffer .= BitWire_Message_Payload::toCompactString ($this->Comment);
      
      return $Buffer;
    }
    // }}}
  }

?>