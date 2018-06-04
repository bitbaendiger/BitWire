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
    
    // {{{ parseData
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * @param int &$Length (optional)
     * @param int $Offset (optional)
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data, &$Length = null, $Offset = 0) {
      // Grep length of input
      $Start = $Offset;
      $Length = strlen ($Data);
      
      // Read start of PoS-Transaction
      if ($this->Type == $this::TYPE_POS) {
        if ($Length < $Offset + 9) {
          trigger_error ('PoS-Transaction too short');
          
          return false;
        }
        
        $Values = unpack ('Vversion/Vtime', substr ($Data, $Offset, 8));
        $Offset += 8;
        
        $this->Version = $Values ['version'];
        $this->Time = $Values ['time'];
        
      // Read start of PoW-Transaction
      } else {
        if ($Length < $Offset + 5) {
          trigger_error ('PoW-Transaction too short');
          
          return false;
        }
        
        $Values = unpack ('Vversion', substr ($Data, $Offset, 4));
        $Offset += 4;
        
        $this->Version = $Values ['version'];
        $this->Time = null;
      }
      
      // Read number of inputs on transaction
      if (($Count = BitWire_Message_Payload::readCompactSize ($Data, $Size, $Offset)) === false) {
        trigger_error ('Failed to read number of inputs');
        
        return false;
      }
      
      $Offset += $Size;
      
      // Try to read inputs
      $this->Inputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        $Input = new BitWire_Transaction_Input ($this);
        
        if (!$Input->parseData ($Data, $Size, $Offset)) {
          trigger_error ('Failed to read input');
          
          return false;
        }
        
        $Offset += $Size;
        $this->Inputs [] = $Input;
      }
      
      // Read number of outputs on transaction
      if (($Count = BitWire_Message_Payload::readCompactSize ($Data, $Size, $Offset)) === false) {
        trigger_error ('Failed to read number of outputs');
        
        return false;
      }
      
      $Offset += $Size;
      
      // Try to read outputs
      $this->Outputs = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        $Values = unpack ('Pamount', substr ($Data, $Offset, 8));
        $Offset += 8;
        
        if (($Values ['script'] = new BitWire_Transaction_Script ($this, BitWire_Message_Payload::readCompactString ($Data, $Size, $Offset))) === false) {
          trigger_error ('Failed to read output-script');
          
          return false;
        }
        
        if ($Size > 10003) {
          trigger_error ('Output-script too large: ' . $Size);
          
          return false;
        }
        
        $this->Outputs [] = $Values;
        $Offset += $Size;
      }
      
      // Parse locktime
      $Values = unpack ('Vlocktime', substr ($Data, $Offset, 4));
      $this->lockTime = array_shift ($Values);
      $Offset += 4;
      
      // Check for a comment
      if ($this->hasComment) {
        if (($Comment = BitWire_Message_Payload::readCompactString ($Data, $Size, $Offset)) === false) {
          trigger_error ('Failed to read comment from transaction');

          return false;
        } else
          $this->Comment = $Comment;

        $Offset += $Size;
      }
      
      // Write out consumed length
      $Length = $Offset - $Start;
      
      // Indicate success
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