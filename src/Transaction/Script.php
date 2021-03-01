<?php

  /**
   * BitWire - Transaction Script
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
  
  class Script {
    /* Well-known Opcodes */
    public const OP_0              = 0x00;
    public const OP_FALSE          = 0x00;
    public const OP_TRUE           = 0x51;
    public const OP_PUSHDATA_8     = 0x4C;
    public const OP_PUSHDATA_16    = 0x4D;
    public const OP_PUSHDATA_32    = 0x4E;
    public const OP_1              = 0x51;
    public const OP_2              = 0x52;
    public const OP_3              = 0x53;
    public const OP_4              = 0x54;
    public const OP_5              = 0x55;
    public const OP_6              = 0x56;
    public const OP_7              = 0x57;
    public const OP_8              = 0x58;
    public const OP_9              = 0x59;
    public const OP_10             = 0x5A;
    public const OP_11             = 0x5B;
    public const OP_12             = 0x5C;
    public const OP_13             = 0x5D;
    public const OP_14             = 0x5E;
    public const OP_15             = 0x5F;
    public const OP_16             = 0x60;
    public const OP_NOP            = 0x61;
    public const OP_IF             = 0x63;
    public const OP_VERNOTIF       = 0x66;
    public const OP_ELSE           = 0x67;
    public const OP_VERIFY         = 0x69;
    public const OP_RETURN         = 0x6A;
    public const OP_2DROP          = 0x6D;
    public const OP_2ROT           = 0x71;
    public const OP_DUP            = 0x76;
    public const OP_PICK           = 0x79;
    public const OP_LEFT           = 0x80;
    public const OP_INVERT         = 0x83;
    public const OP_OR             = 0x85;
    public const OP_EQUAL          = 0x87;
    public const OP_EQUALVERIFY    = 0x88;
    public const OP_1SUB           = 0x8C;
    public const OP_NOT            = 0x91;
    public const OP_ADD            = 0x93;
    public const OP_MOD            = 0x97;
    public const OP_BOOLOR         = 0x9B;
    public const OP_LESSTHAN       = 0x9F;
    public const OP_SHA1           = 0xA7;
    public const OP_HASH160        = 0xA9;
    public const OP_CHECKSIG       = 0xAC;
    public const OP_CHECKSIGVERIFY = 0xAD;
    public const OP_CHECKMULTISIG  = 0xAE;
    public const OP_NOP1           = 0xB0;
    public const OP_NOP6           = 0xB5;
    public const OP_NOP9           = 0xB8;
    public const OP_ZEROCOINSPEND  = 0xC2;
    
    /* Well-known Opcode-Names */
    private static $opcodeNames = [
      self::OP_0              => 'OP_0',
      self::OP_PUSHDATA_8     => 'OP_PUSHDATA_8',
      self::OP_PUSHDATA_16    => 'OP_PUSHDATA_16',
      self::OP_PUSHDATA_32    => 'OP_PUSHDATA_32',
      self::OP_1              => 'OP_1',
      self::OP_2              => 'OP_2',
      self::OP_3              => 'OP_3',
      self::OP_4              => 'OP_4',
      self::OP_5              => 'OP_5',
      self::OP_6              => 'OP_6',
      self::OP_7              => 'OP_7',
      self::OP_8              => 'OP_8',
      self::OP_9              => 'OP_9',
      self::OP_10             => 'OP_10',
      self::OP_11             => 'OP_11',
      self::OP_12             => 'OP_12',
      self::OP_13             => 'OP_13',
      self::OP_14             => 'OP_14',
      self::OP_15             => 'OP_15',
      self::OP_16             => 'OP_16',
      self::OP_NOP            => 'OP_NOP',
      self::OP_IF             => 'OP_IF',
      self::OP_VERNOTIF       => 'OP_VERNOTIF',
      self::OP_ELSE           => 'OP_ELSE',
      self::OP_VERIFY         => 'OP_VERIFY',
      self::OP_RETURN         => 'OP_RETURN',
      self::OP_2DROP          => 'OP_2DROP',
      self::OP_2ROT           => 'OP_2ROT',
      self::OP_DUP            => 'OP_DUP',
      self::OP_PICK           => 'OP_PICK',
      self::OP_LEFT           => 'OP_LEFT',
      self::OP_INVERT         => 'OP_INVERT',
      self::OP_OR             => 'OP_OR',
      self::OP_EQUAL          => 'OP_EQUAL',
      self::OP_EQUALVERIFY    => 'OP_EQUALVERIFY',
      self::OP_1SUB           => 'OP_1SUB',
      self::OP_NOT            => 'OP_NOT',
      self::OP_ADD            => 'OP_ADD',
      self::OP_MOD            => 'OP_MOD',
      self::OP_BOOLOR         => 'OP_BOOLOR',
      self::OP_LESSTHAN       => 'OP_LESSTHAN',
      self::OP_SHA1           => 'OP_SHA1',
      self::OP_HASH160        => 'OP_HASH160',
      self::OP_CHECKSIG       => 'OP_CHECKSIG',
      self::OP_CHECKSIGVERIFY => 'OP_CHECKSIGVERIFY',
      self::OP_CHECKMULTISIG  => 'OP_CHECKMULTISIG',
      self::OP_NOP1           => 'OP_NOP1',
      self::OP_NOP6           => 'OP_NOP6',
      self::OP_NOP9           => 'OP_NOP9',
      self::OP_ZEROCOINSPEND  => 'OP_ZEROCOINSPEND',
    ];
    
    /* Parsed stack of script */
    private $scriptOps = [ ];
    
    // {{{ __construct
    /**
     * Create a new transaction-script
     * 
     * @param string $Data (optional) Binary encoded transaction-script
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Data = '') {
      $this->parse ($Data);
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare debug-informations for var_dump()
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () : array {
      try {
        $scriptAddresses = $this->getAddresses ();
      } catch (\Throwable $error) {
        $scriptAddresses = null;
      }
      
      return [
        'addresses' => $scriptAddresses,
        'script' => $this->__toString (),
      ];
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
      // Generate a human readable string
      $Result = '';
      
      foreach ($this->scriptOps as $Op)
        if (($Op [0] > 0) && ($Op [0] < $this::OP_PUSHDATA_8))
          $Result .= bin2hex ($Op [1]) . ' ';
        elseif (isset ($this::$opcodeNames [$Op [0]]))
          $Result .= $this::$opcodeNames [$Op [0]] . ' ' . (isset ($Op [1]) ? bin2hex ($Op [1]) . ' ' : '');
        else
          $Result .= sprintf ('OP_%d(0x%02X) ', $Op [0], $Op [0]);
      
      // Return the result
      return rtrim ($Result);
    }
    // }}}
    
    // {{{ getAddresses
    /**
     * Try to read addresses of this script
     * 
     * @param array $addressTypeMap (optional)
     * 
     * @access public
     * @return array
     **/
    public function getAddresses (array $addressTypeMap = [ ]) : array {
      if ($this->isEmpty ())
        return [ ];
      
      if ($this->isSignatureInput ())
        throw new \Exception ('Script is not an output');
      
      $typePubkey = $addressTypeMap [BitWire\Address::TYPE_PUBKEY] ?? 0;
      $typeScript = $addressTypeMap [BitWire\Address::TYPE_SCRIPT] ?? 5;
      
      $encodeBase58 = BitWire\Address::ENCODE_BASE58;
      $encodeBech32 = BitWire\Address::ENCODE_BECH32;
      
      if ($this->isPublicKeyHashInput ())
        $outputAddresses = [[ $typePubkey, hash ('ripemd160', hash ('sha256', $this->scriptOps [1][1], true), true), $encodeBase58 ]];
      elseif ($this->isScriptHashInput ())
        $outputAddresses = [[ $typeScript, hash ('ripemd160', hash ('sha256', $this->scriptOps [1][1], true), true), $encodeBase58 ]];
      elseif ($this->isMultiSignatureScriptInput ())
        $outputAddresses = [[ $typeScript, hash ('ripemd160', hash ('sha256', $this->scriptOps [count ($this->scriptOps) - 1][1], true), true), $encodeBase58 ]];
      elseif ($this->isPublicKeyOutput ())
        $outputAddresses = [[ $typePubkey, hash ('ripemd160', hash ('sha256', $this->scriptOps [0][1], true), true), $encodeBase58 ]];
      elseif ($this->isPublicKeyHashOutput ())
        $outputAddresses = [[ $typePubkey, $this->scriptOps [2][1], $encodeBase58 ]];
      elseif ($this->isScriptHashOutput ())
        $outputAddresses = [[ $typeScript, $this->scriptOps [1][1], $encodeBase58 ]];
      elseif ($this->isWitnessProgramOutput ()) {
        // Extract witness-version
        if ($this->scriptOps [0][0] != $this::OP_0)
          $witnessVersion = $this->scriptOps [0][0] - $this::OP_1 + 1;
        else
          $witnessVersion = 0;
        
        $witnessProgram = $this->scriptOps [1][1];
        
        if ($witnessVersion == 0) {
          if (strlen ($witnessProgram) == 20)
            $outputAddresses = [[ $witnessVersion, $witnessProgram, $encodeBech32 ]];
          elseif (strlen ($witnessProgram) == 32)
            $outputAddresses = [[ $witnessVersion, $witnessProgram, $encodeBech32 ]];
          else // NON-STANDARD
            return [ ];
        } else
          $outputAddresses = [[ $witnessVersion, $witnessProgram, $encodeBech32 ]];
      } elseif ($this->isMultiSignatureOutput ()) {
        $outputAddresses = [ ];
        
        for ($i = 1; $i < count ($this->scriptOps) - 2; $i++)
          $outputAddresses [] = [ $typePubkey, hash ('ripemd160', hash ('sha256', $this->scriptOps [$i][1], true), true), $encodeBase58 ];
      } else
        throw new \exception ('Unknown Script-Type: ' . (string)$this);
      
      foreach ($outputAddresses as $addressIndex=>$addressData)
        $outputAddresses [$addressIndex] = new BitWire\Address ($addressData [0], $addressData [1], $addressData [2]);
      
      return $outputAddresses;
    }
    // }}}
    
    // {{{ isEmpty
    /**
     * Check for an empty script
     * 
     * @access public
     * @return bool
     **/
    public function isEmpty () : bool {
      return (count ($this->scriptOps) == 0);
    }
    // }}}
    
    // {{{ isSignatureInput
    /**
     * Check if the script is a signature-input
     * 
     * @access public
     * @return bool
     **/
    public function isSignatureInput () : bool {
      if ((count ($this->scriptOps) != 1) || !isset ($this->scriptOps [0][1]))
        return false;
      
      return $this->isSignature ($this->scriptOps [0][1]);
    }
    // }}}
    
    // {{{ isPublicKeyHashInput
    /**
     * Check if this is a P2PKH
     * 
     * @access public
     * @return bool
     **/
    public function isPublicKeyHashInput () : bool {
      // Retrive the stack
      $Length = count ($this->scriptOps);
      
      if ($Length != 2)
        return false;
      
      // Make sure first frame is DER-encoded signature
      if (!isset ($this->scriptOps [0][1]))
        return false;
      
      if (!$this->isSignature ($this->scriptOps [0][1]))
        return false;
      
      // Make sure second frame is public key
      if (!isset ($this->scriptOps [1][1]))
        return false;
      
      if (($Length = strlen ($this->scriptOps [1][1])) < 1)
        return false;
      
      $Version = ord ($this->scriptOps [1][1][0]);
      
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
    public function isScriptHashInput () : boll {
      // Retrive the stack
      $Length = count ($this->scriptOps);
      
      if ($Length != 2)
        return false;
      
      // Make sure first frame is DER-encoded signature
      if (!isset ($this->scriptOps [0][1]))
        return false;
      
      if (!$this->isSignature ($this->scriptOps [0][1]))
        return false;
      
      // Make sure second frame is a script
      $Script = new $this ($this->scriptOps [1][1]);
      
      if ((count ($Script->scriptOps) != 2) ||
          ($Script->scriptOps [1][0] != $this::OP_CHECKSIG))
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
    public function isMultiSignatureScriptInput () : bool {
      // Retrive the stack
      $Length = count ($this->scriptOps);
      
      // Check length and opcode of first frame
      if (($Length < 2) || ($this->scriptOps [0][0] != 0x00))
        return false;
      
      for ($i = 1; $i < $Length - 1; $i++) {
        // Make sure there is data available
        if (!isset ($this->scriptOps [$i][1])) {
          trigger_error ('No buffer on op');
          
          return false;
        }
        
        // Check if this is a DER-encoded Signature 
        if (!$this->isSignature ($this->scriptOps [$i][1]))
          return false;
      }
      
      // Create an own script from last Stack
      $Script = new $this ($this->scriptOps [$Length - 1][1]);
      
      // Check the script
      if (($sLength = count ($Script->scriptOps)) < 4)
        return false;
      
      if ($Script->scriptOps [$sLength - 1][0] != $this::OP_CHECKMULTISIG)
        return false;
      
      if ($sLength != $Script->scriptOps [$sLength - 2][0] - 77)
        return false;
      
      if ($Script->scriptOps [0][0] != 80 + $Length - 2)
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
    public function isPublicKeyOutput () : bool {
      if (count ($this->scriptOps) != 2)
        return false;
      
      if ($this->scriptOps [1][0] != $this::OP_CHECKSIG)
        return false;
      
      if (!isset ($this->scriptOps [0][1]))
        return false;
      
      if (($Length = strlen ($this->scriptOps [0][1])) < 1)
        return false;
      
      $Version = ord ($this->scriptOps [0][1][0]);
      
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
    public function isPublicKeyHashOutput () : bool {
      $Length = count ($this->scriptOps);
      
      // Work around very buggy transaction
      // See: e411dbebd2f7d64dafeef9b14b5c59ec60c36779d43f850e5e347abee1e1a455
      // See: 5492a05f1edfbd29c525a3dbf45f654d0fc45a805ccd620d0a4dff47de63f90b
      // See: f003f0c1193019db2497a675fd05d9f2edddf9b67c59e677c48d3dbd4ed5f00b
      if (($Length > 5) && ($this->scriptOps [4][0] == $this::OP_CHECKSIG))
        for ($i = 5; $i < $Length; $i++)
          if (($this->scriptOps [$i][0] != $this::OP_CHECKSIG) && ($this->scriptOps [$i][0] != $this::OP_NOP) && ($this->scriptOps [$i][0] != $this::OP_NOP1))
            return false;
      
      return
        ($Length >= 5) &&
        ($this->scriptOps [0][0] == $this::OP_DUP) &&
        ($this->scriptOps [1][0] == $this::OP_HASH160) &&
        (isset ($this->scriptOps [2][1]) && (strlen ($this->scriptOps [2][1]) == 20)) &&
        ($this->scriptOps [3][0] == $this::OP_EQUALVERIFY) &&
        ($this->scriptOps [4][0] == $this::OP_CHECKSIG);
    }
    // }}}
    
    // {{{ isScriptHashOutput
    /**
     * Check if this script defines p2sh output
     * 
     * @access public
     * @return bool
     **/
    public function isScriptHashOutput () : bool {
      return
        (count ($this->scriptOps) == 3) &&
        ($this->scriptOps [0][0] == $this::OP_HASH160) &&
        (isset ($this->scriptOps [1][1]) && (strlen ($this->scriptOps [1][1]) == 20)) &&
        ($this->scriptOps [2][0] == $this::OP_EQUAL);
    }
    // }}}
    
    // {{{ isMultiSignatureOutput
    /**
     * Check if this is a multi-signature output
     * 
     * @access public
     * @return bool
     **/
    public function isMultiSignatureOutput () : bool {
      // Check size of stack
      $Length = count ($this->scriptOps);
      
      if ($Length < 4)
        return false;
      
      if ($this->scriptOps [$Length - 2][0] != 77 + $Length)
        return false;
      
      if ($this->scriptOps [$Length - 1][0] != $this::OP_CHECKMULTISIG)
        return false;
      
      return true;
    }
    // }}}
    
    // {{{ isNullDataOutput
    /**
     * Check for an unspendable output
     * 
     * @access public
     * @return bool
     **/
    public function isNullDataOutput () : bool {
      if ($this->isEmpty ())
        return false;
      
      if ($this->scriptOps [0][0] != $this::OP_RETURN)
        return false;
      
      // Make sure the remaining script is push-only
      for ($i = 1; $i < count ($this->scriptOps); $i++)
        if ($this->scriptOps [$i][0] > $this::OP_16)
          return false;
      
      return true;
    }
    // }}}
    
    // {{{ isWitnessProgram
    /**
     * Check if this is a witness-output
     * 
     * @access public
     * @return bool
     **/
    public function isWitnessProgramOutput () : bool {
      // Check size of script
      if ((count ($this->scriptOps) != 2) ||
          (strlen ($this->scriptOps [1][1]) < 2) ||
          (strlen ($this->scriptOps [1][1]) > 40))
        return false;
      
      if (($this->scriptOps [0][0] != $this::OP_0) &&
          (($this->scriptOps [0][0] < $this::OP_1) || ($this->scriptOps [0][0] > $this::OP_16)))
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
    private function isSignature ($Data) : boll {
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
    
    // {{{ isZerocoinSpend
    /**
     * Check if this script is a zerocoin-spend
     * 
     * @access public
     * @return bool
     **/
    public function isZerocoinSpend () : bool {
      return ((count ($this->scriptOps) > 0) && ($this->scriptOps [0][0] == self::OP_ZEROCOINSPEND));
    }
    // }}}
    
    
    // {{{ parse
    /**
     * Parse script-ops from binary
     * 
     * @param string $binaryData
     * 
     * @access public
     * @return void
     **/
    public function parse ($binaryData) : void {
      // Prepare to generate stack
      $scriptOps = [ ];
      
      $dataLength = strlen ($binaryData);
      $dataOffset = 0;
      
      while ($dataOffset < $dataLength) {
        // Get next opcode
        $scriptOpcode = ord ($binaryData [$dataOffset++]);
        
        // Push plain data to script-ops
        if (($scriptOpcode > 0) && ($scriptOpcode < $this::OP_PUSHDATA_8)) {
          $scriptOps [] = [ $scriptOpcode, substr ($binaryData, $dataOffset, $scriptOpcode) ];
          $dataOffset += $scriptOpcode;
        
        // Push length-prefixed data to script-ops
        } elseif (($scriptOpcode == $this::OP_PUSHDATA_8) ||
                  ($scriptOpcode == $this::OP_PUSHDATA_16) ||
                  ($scriptOpcode == $this::OP_PUSHDATA_32)) {
          // Retrive the length of data to push
          if ($scriptOpcode == $this::OP_PUSHDATA_32)
            $oLength = (ord ($binaryData [$dataOffset++])) |
                       (ord ($binaryData [$dataOffset++]) << 8) |
                       (ord ($binaryData [$dataOffset++]) << 16) |
                       (ord ($binaryData [$dataOffset++]) << 24);
          elseif ($scriptOpcode == $this::OP_PUSHDATA_16)
            $oLength = (ord ($binaryData [$dataOffset++])) |
                       (ord ($binaryData [$dataOffset++]) << 8);
          else
            $oLength =  ord ($binaryData [$dataOffset++]);
          
          // Push to script-ops
          $scriptOps [] = [ $scriptOpcode, substr ($binaryData, $dataOffset, $oLength) ];
          $dataOffset += $oLength;
        
        // Push only one opcode
        } else
          $scriptOps [] = [ $scriptOpcode, null ];
      }
      
      // Store the result
      $this->scriptOps = $scriptOps;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create a binary representation of this script
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      $outputScript = '';
      
      foreach ($this->scriptOps as $scriptOp) {
        $outputScript .= chr ($scriptOp [0]);
        
        if (!isset ($scriptOp [1]))
          continue;
        
        $dataLength = strlen ($scriptOp [1]);
        
        if ($scriptOp [0] == $this::OP_PUSHDATA_8)
          $outputScript .= chr ($dataLength &= 0xFF);
        elseif ($scriptOp [0] == $this::OP_PUSHDATA_16)
          $outputScript .= pack ('v', $dataLength &= 0xFFFF);
        elseif ($scriptOp [0] == $this::OP_PUSHDATA_32)
          $outputScript .= pack ('V', $dataLength &= 0xFFFF);
        
        $outputScript .= substr ($scriptOp [1], 0, $dataLength);
      }
      
      return $outputScript;
    }
    // }}}
  }
