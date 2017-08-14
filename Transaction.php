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