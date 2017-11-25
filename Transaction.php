<?PHP

  require_once ('BitWire/Hashable.php');
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction/Input.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Transaction extends BitWire_Hashable {
    /* Version of transaction */
    private $Version = 1;
    
    /* Locktime of transaction */
    private $lockTime = 0;
    
    /* Inputs of transaction */
    private $Inputs = array ();
    
    /* Outputs of transaction */
    private $Outputs = array ();
    
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
      
      if ($Length < $Offset + 5)
        return false;
      
      $Values = unpack ('Vversion', substr ($Data, $Offset, 4));
      $Offset += 4;
      $this->Version = $Values ['version'];
      
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
      
      // Write out consumed length
      $Length = $Offset + 4 - $Start;
      
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
      $Buffer = pack ('V', $this->Version) . BitWire_Message_Payload::toCompactSize (count ($this->Inputs));
      
      foreach ($this->Inputs as $Input)
        $Buffer .= $Input->toBinary ();
      
      $Buffer .= BitWire_Message_Payload::toCompactSize (count ($this->Outputs));
      
      foreach ($this->Outputs as $Output)
        $Buffer .=
          pack ('P', $Output ['amount']) .
          BitWire_Message_Payload::toCompactString ($Output ['script']->toBinary ());
      
      $Buffer .= pack ('V', $this->lockTime);
      
      return $Buffer;
    }
    // }}}
  }

?>