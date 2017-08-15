<?PHP

  class BitWire_Transaction_Script {
    /* Well-known Opcodes */
    const OP_PUSHDATA_8  = 76;
    const OP_PUSHDATA_16 = 77;
    const OP_PUSHDATA_32 = 78;
    const OP_1 = 81;
    const OP_2 = 82;
    const OP_3 = 83;
    const OP_4 = 84;
    const OP_5 = 85;
    const OP_6 = 86;
    const OP_7 = 87;
    const OP_8 = 88;
    const OP_9 = 89;
    const OP_10 = 90;
    const OP_11 = 91;
    const OP_12 = 92;
    const OP_13 = 93;
    const OP_14 = 94;
    const OP_15 = 95;
    const OP_16 = 96;
    const OP_NOP = 97;
    const OP_RETURN = 106;
    const OP_DUP = 118;
    const OP_LEFT = 128;
    const OP_INVERT = 131;
    const OP_OR = 133;
    const OP_EQUAL = 135;
    const OP_EQUALVERIFY = 136;
    const OP_NOT = 145;
    const OP_HASH160 = 169;
    const OP_CHECKSIG = 172;
    const OP_CHECKMULTISIG = 174;
    const OP_NOP1 = 176;
    
    /* Well-known Opcode-Names */
    private static $opcodeNames = array (
      self::OP_PUSHDATA_8    => 'OP_PUSHDATA_8',
      self::OP_PUSHDATA_16   => 'OP_PUSHDATA_16',
      self::OP_PUSHDATA_32   => 'OP_PUSHDATA_32',
      self::OP_1             => 'OP_1',
      self::OP_2             => 'OP_2',
      self::OP_3             => 'OP_3',
      self::OP_4             => 'OP_4',
      self::OP_5             => 'OP_5',
      self::OP_6             => 'OP_6',
      self::OP_7             => 'OP_7',
      self::OP_8             => 'OP_8',
      self::OP_9             => 'OP_9',
      self::OP_10            => 'OP_10',
      self::OP_11            => 'OP_11',
      self::OP_12            => 'OP_12',
      self::OP_13            => 'OP_13',
      self::OP_14            => 'OP_14',
      self::OP_15            => 'OP_15',
      self::OP_16            => 'OP_16',
      self::OP_NOP           => 'OP_NOP',
      self::OP_RETURN        => 'OP_RETURN',
      self::OP_DUP           => 'OP_DUP',
      self::OP_LEFT          => 'OP_LEFT',
      self::OP_INVERT        => 'OP_INVERT',
      self::OP_OR            => 'OP_OR',
      self::OP_EQUAL         => 'OP_EQUAL',
      self::OP_EQUALVERIFY   => 'OP_EQUALVERIFY',
      self::OP_NOT           => 'OP_NOT',
      self::OP_HASH160       => 'OP_HASH160',
      self::OP_CHECKSIG      => 'OP_CHECKSIG',
      self::OP_CHECKMULTISIG => 'OP_CHECKMULTISIG',
      self::OP_NOP1          => 'OP_NOP1',
    );
    
    /* Binary data from script */
    private $Data = '';
    
    /* Parsed stack of script */
    private $Stack = null;
    
    /* Instance of transaction we are on */
    private $Parent = null;
    
    // {{{ base58Encode
    /**
     * Generate base58-string from a binary string
     * 
     * @param string $Data
     * 
     * @access public
     * @return string
     **/
    public static function base58Encode ($Data) {
      static $Alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
      
      // Make sure GMP is available
      if (!extension_loaded ('gmp')) {
        trigger_error ('Missing GMP-Extension for base58-encoding');
        
        return false;
      }
      
      // Initialize
      $Number = gmp_import ($Data);
      $Base = gmp_init (58);
      $Result = '';
      
      // Generate base58-encoding
      while (gmp_cmp ($Number, $Base) >= 0) {
        $r = gmp_div_qr ($Number, $Base);
        
        $Result = $Alphabet [gmp_intval ($r [1])] . $Result;
        $Number = $r [0];
      }
      
      if (($Number = gmp_intval ($Number)) > 0)
        $Result = $Alphabet [$Number] . $Result;
      
      // Process leading zeros
      $i = 0;
      
      while ($Data [$i++] == "\x00")
        $Result = '1' . $Result;
      
      return $Result;
    }
    // }}}
    
    // {{{ base58Decode
    /**
     * Convert a base-58 encoded string into its binary representation
     * 
     * @param string $Data
     * 
     * @access public
     * @return string
     **/
    public static function base58Decode ($Data) {
      static $Alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
      
      // Make sure GMP is available
      if (!extension_loaded ('gmp')) {
        trigger_error ('Missing GMP-Extension for base58-encoding');
      
        return false;
      }
      
      // Initialize
      $Result = gmp_init (0);
      $Base = gmp_init (58);
      
      // Decode
      for ($i = 0; $i < strlen ($Data); $i++) {
        if (($p = strpos ($Alphabet, $Data [$i])) === false)
          return false;
        
        $Result = gmp_add (gmp_mul ($Result, $Base), gmp_init ($p));
      }
      
      $Result = gmp_export ($Result);
      
      // Prefix with leading zeros
      for ($i = 0; $i < strlen ($Data); $i++)
        if ($Data [$i] == '1')
          $Result = "\x00" . $Result;
        else
          break;
      
      // Return the result
      return $Result;
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new transaction-script
     * 
     * @param mixed $Parent
     * @param string $Data (optional) Binary encoded transaction-script
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Parent, $Data = '') {
      if (($Parent instanceof BitWire_Transaction) || ($Parent instanceof BitWire_Transaction_Input))
        $this->Parent = $Parent;
      else
        throw new Exception ('Transaction or input required');
      
      $this->Data = $Data;
      $this->Stack = null;
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare debug-informations for var_dump()
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      return array (
        'address' => $this->getAddress (),
        'script' => $this->__toString (),
      );
    }
    // }}}
    
    // {{{ __toString
    /**
     * Create a human readable string from this object
     * 
     * @access friendly
     * @return string
     **/
    function __toString () {
      // Retrive our stack
      $Stack = $this->getStack ();
      
      // Generate a human readable string
      $Result = '';
      
      foreach ($Stack as $Op)
        if (($Op [0] > 0) && ($Op [0] < $this::OP_PUSHDATA_8))
          $Result .= bin2hex ($Op [1]) . ' ';
        elseif (isset ($this::$opcodeNames [$Op [0]]))
          $Result .= $this::$opcodeNames [$Op [0]] . ' ' . (isset ($Op [1]) ? bin2hex ($Op [1]) . ' ' : '');
        else
          $Result .= 'OP_' . $Op [0] . ' ';
      
      // Return the result
      return rtrim ($Result);
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Try to read address of this script
     * 
     * @access public
     * @return string
     **/
    public function getAddress ($forceNet = null) {
      // Find the address and prefix with type
      $Stack = $this->getStack ();
      
      if ($this->isSignatureInput ()) {
        return null;
      } elseif ($this->isPublicKeyHashInput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x00") . hash ('ripemd160', hash ('sha256', $Stack [1][1], true), true);
      elseif ($this->isScriptHashInput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x05") . hash ('ripemd160', hash ('sha256', $Stack [1][1], true), true);
      elseif ($this->isMultiSignatureScriptInput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x05") . hash ('ripemd160', hash ('sha256', $Stack [count ($Stack) - 1][1], true), true);
      elseif ($this->isPublicKeyOutput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x00") . hash ('ripemd160', hash ('sha256', $Stack [0][1], true), true);
      elseif ($this->isPublicKeyHashOutput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x00") . $Stack [2][1];
      elseif ($this->isScriptHashOutput ())
        $Address = ($forceNet !== null ? chr ($forceNet) : "\x05") . $Stack [1][1];
      elseif ($this->isMultiSignatureOutput ()) {
        $Address = array ();
        
        for ($i = 1; $i < count ($Stack) - 2; $i++)
          $Address [] = ($forceNet !== null ? chr ($forceNet) : "\x00") . hash ('ripemd160', hash ('sha256', $Stack [$i][1], true), true);
      } else
        return null;
      
      if (!is_array ($Address))
        $Address = array ($Address);
      
      foreach ($Address as $i=>$v) {
        // Generate Checksum
        $Checksum = substr (hash ('sha256', hash ('sha256', $v, true), true), 0, 4);
        
        // Generate address
        $Address [$i] = self::base58Encode ($v . $Checksum);
      }
      
      return implode (' ', $Address);
    }
    // }}}
    
    public function isSignatureInput () {
      $Stack = $this->getStack ();
      
      if (count ($Stack) != 1)
        return false;
      
      return $this->isSignature ($Stack [0][1]);
    }
    
    // {{{ isPublicKeyHashInput
    /**
     * Check if this is a P2PKH
     * 
     * @access public
     * @return bool
     **/
    public function isPublicKeyHashInput () {
      // Retrive the stack
      $Stack = $this->getStack ();
      $Length = count ($Stack);
      
      if ($Length != 2)
        return false;
      
      // Make sure first frame is DER-encoded signature
      if (!isset ($Stack [0][1]))
        return false;
      
      if (!$this->isSignature ($Stack [0][1]))
        return false;
      
      // Make sure second frame is public key
      if (!isset ($Stack [1][1]))
        return false;
      
      if (($Length = strlen ($Stack [1][1])) < 1)
        return false;
      
      $Version = ord ($Stack [1][1][0]);
      
      if ($Version < 2)
        return false;
      elseif ($Version < 4)
        return ($Length == 33);
      elseif ($Version < 8)
        return ($Length == 65);
      
      return false;
    }
    // }}}
    
    // {{{ isScriptHashInput
    /**
     * Check if this is a P2SH input
     * 
     * @access public
     * @return bool
     **/
    public function isScriptHashInput () {
      // Retrive the stack
      $Stack = $this->getStack ();
      $Length = count ($Stack);
      
      if ($Length != 2)
        return false;
      
      // Make sure first frame is DER-encoded signature
      if (!isset ($Stack [0][1]))
        return false;
      
      if (!$this->isSignature ($Stack [0][1]))
        return false;
      
      // Make sure second frame is a script
      $Script = new $this ($this->Transaction, $Stack [1][1]);
      $Stack = $Script->getStack ();
      
      if ((count ($Stack) != 2) ||
          ($Stack [1][0] != $this::OP_CHECKSIG))
        return false;
      
      // Succeed if we get here
      return true;
    }
    // }}}
    
    // {{{ isMultiSignatureScriptInput
    /**
     * Check if this is a multi-signature script input (p2sh multisig)
     * 
     * @access public
     * @return bool
     **/
    public function isMultiSignatureScriptInput () {
      // Retrive the stack
      $Stack = $this->getStack ();
      $Length = count ($Stack);
      
      // Check length and opcode of first frame
      if (($Length < 2) || ($Stack [0][0] != 0x00))
        return false;
      
      for ($i = 1; $i < $Length - 1; $i++) {
        // Make sure there is data available
        if (!isset ($Stack [$i][1])) {
          trigger_error ('No buffer on op');
          
          return false;
        }
        
        // Check if this is a DER-encoded Signature 
        if (!$this->isSignature ($Stack [$i][1]))
          return false;
      }
      
      // Create an own script from last Stack
      $Script = new $this ($this->Transaction, $Stack [$Length - 1][1]);
      $sStack = $Script->getStack ();
      
      // Check the script
      if (($sLength = count ($sStack)) < 4)
        return false;
      
      if ($sStack [$sLength - 1][0] != $this::OP_CHECKMULTISIG)
        return false;
      
      if ($sLength != $sStack [$sLength - 2][0] - 77)
        return false;
      
      if ($sStack [0][0] != 80 + $Length - 2)
        return false;
      
      // Succeed if we get here
      return true;
    }
    // }}}
    
    // {{{ isPublicKeyOutput
    /**
     * Check for a public key output
     * 
     * @access public
     * @return bool
     **/
    public function isPublicKeyOutput () {
      $Stack = $this->getStack ();
      
      if (count ($Stack) != 2)
        return false;
      
      if ($Stack [1][0] != $this::OP_CHECKSIG)
        return false;
      
      if (!isset ($Stack [0][1]))
        return false;
      
      if (($Length = strlen ($Stack [0][1])) < 1)
        return false;
      
      $Version = ord ($Stack [0][1][0]);
      
      if ($Version < 2)
        return false;
      elseif ($Version < 4)
        return ($Length == 33);
      elseif ($Version < 8)
        return ($Length == 65);
      // See: b728387a3cf1dfcff1eef13706816327907f79f9366a7098ee48fc0c00ad2726
      elseif ($Length == 64)
        return true;
      
      return false;
    }
    // }}}
    
    // {{{ isPublicKeyHashOutput
    /**
     * Check if this script defines output to a public key hash (P2PKH)
     * 
     * @access public
     * @return bool
     **/
    public function isPublicKeyHashOutput () {
      $Stack = $this->getStack ();
      $Length = count ($Stack);
      
      // Work around very buggy transaction
      // See: e411dbebd2f7d64dafeef9b14b5c59ec60c36779d43f850e5e347abee1e1a455
      // See: 5492a05f1edfbd29c525a3dbf45f654d0fc45a805ccd620d0a4dff47de63f90b
      // See: f003f0c1193019db2497a675fd05d9f2edddf9b67c59e677c48d3dbd4ed5f00b
      if (($Length > 5) && ($Stack [4][0] == $this::OP_CHECKSIG))
        for ($i = 5; $i < $Length; $i++)
          if (($Stack [$i][0] != $this::OP_CHECKSIG) && ($Stack [$i][0] != $this::OP_NOP) && ($Stack [$i][0] != $this::OP_NOP1))
            return false;
      
      return
        ($Length >= 5) &&
        ($Stack [0][0] == $this::OP_DUP) &&
        ($Stack [1][0] == $this::OP_HASH160) &&
        (isset ($Stack [2][1]) && (strlen ($Stack [2][1]) == 20)) &&
        ($Stack [3][0] == $this::OP_EQUALVERIFY) &&
        ($Stack [4][0] == $this::OP_CHECKSIG);
    }
    // }}}
    
    // {{{ isScriptHashOutput
    /**
     * Check if this script defines p2sh output
     * 
     * @access public
     * @return bool
     **/
    public function isScriptHashOutput () {
      $Stack = $this->getStack ();
      
      return
        (count ($Stack) == 3) &&
        ($Stack [0][0] == $this::OP_HASH160) &&
        (isset ($Stack [1][1]) && (strlen ($Stack [1][1]) == 20)) &&
        ($Stack [2][0] == $this::OP_EQUAL);
    }
    // }}}
    
    // {{{ isMultiSignatureOutput
    /**
     * Check if this is a multi-signature output
     * 
     * @access public
     * @return bool
     **/
    public function isMultiSignatureOutput () {
      // Retrive the stack
      $Stack = $this->getStack ();
      
      // Check size of stack
      $Length = count ($Stack);
      
      if ($Length < 4)
        return false;
      
      if ($Stack [$Length - 2][0] != 77 + $Length)
        return false;
      
      if ($Stack [$Length - 1][0] != $this::OP_CHECKMULTISIG)
        return false;
      
      return true;
    }
    // }}}
    
    // {{{ isSignature
    /**
     * Make sure input is a valid encoded DER-Signature
     * 
     * @param string $Data
     * 
     * @access private
     * @return bool
     **/
    private function isSignature ($Data) {
      // Retrive the total length of signature
      if (($Length = strlen ($Data)) < 2)
        return false;
      
      // Check for DER-Sequence
      if ($Data [0] != '0')
        return false;
      
      if (ord ($Data [1]) != $Length - 3)
        return false;
      
      // Dump DER-Sequence-Parser
      $Elements = 0;
      $Offset = 2;
      
      while ($Offset < $Length - 3) {
        // Only two elements are expected
        if ($Elements++ > 1)
          return false;
        
        // Read the type of that element (and check for Integer-Type)
        if (($Type = ord ($Data [$Offset++])) != 0x02)
          return false;
        
        // Read length of value (MUST NOT be zero)
        if (($tLength = ord ($Data [$Offset++])) == 0)
          return false;
        
        // Value MUST NOT be negative
        $hByte = ord ($Data [$Offset]);
        
        if ($hByte & 0x80)
          return false;

        // Check padding
        if (($tLength > 1) && ($hByte == 0) && !(ord ($Data [$Offset + 1]) & 0x80))
          return false;
        
        // Move ahead
        $Offset += $tLength;
      }
      
      // Make sure the offset-pointer is at right place
      if (($Offset != $Length) && ($Offset != $Length - 1))
        return false;
      
      return true;
    }
    // }}}
    
    
    // {{{ getStack
    /**
     * Retrive stack for this script
     * 
     * @access public
     * @return array
     **/
    public function getStack () {
      // Check for a cached stack
      if ($this->Stack !== null)
        return $this->Stack;
      
      // Prepare to generate stack
      $this->Stack = array ();
      $Length = strlen ($this->Data);
      $Offset = 0;
      
      while ($Offset < $Length) {
        // Get next opcode
        $Opcode = ord ($this->Data [$Offset++]);
        
        if (($Opcode > 0) && ($Opcode < $this::OP_PUSHDATA_8)) {
          $this->Stack [] = array ($Opcode, substr ($this->Data, $Offset, $Opcode));
          $Offset += $Opcode;
        } elseif (($Opcode == $this::OP_PUSHDATA_8) ||
                  ($Opcode == $this::OP_PUSHDATA_16) ||
                  ($Opcode == $this::OP_PUSHDATA_32)) {
          if ($Opcode == $this::OP_PUSHDATA_32)
            $oLength = ord ($this->Data [$Offset++]) | (ord ($this->Data [$Offset++]) >> 8) | (ord ($this->Data [$Offset++]) >> 16) | (ord ($this->Data [$Offset++]) >> 24);
          elseif ($Opcode == $this::OP_PUSHDATA_16)
            $oLength = ord ($this->Data [$Offset++]) | (ord ($this->Data [$Offset++]) >> 8);
          else
            $oLength = ord ($this->Data [$Offset++]);
          
          $this->Stack [] = array ($Opcode, substr ($this->Data, $Offset, $oLength));
          $Offset += $oLength;
        } else
          $this->Stack [] = array ($Opcode);
      }
      
      // Return the stack
      return $this->Stack;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create a binary representation of this script
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      return $this->Data;
    }
    // }}}
  }

?>