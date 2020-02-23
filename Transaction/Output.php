<?PHP

  /**
   * BitWire - Transaction Output
   * Copyright (C) 2017-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Transaction_Output {
    /* Amount of coins on this output */
    private $outputAmount = 0.00;
    
    /* Script for this output */
    private $outputScript = null;
    
    // {{{ __construct
    /**
     * Create a new transaction-input
     * 
     * @param float $outputAmount (optional)
     * @param BitWire_Transaction_Script $outputScript (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($outputAmount = 0.0, BitWire_Transaction_Script $outputScript = null) {
      $this->outputAmount = (float)$outputAmount;
      
      if ($outputScript)
        $this->outputScript = $outputScript;
      else
        $this->outputScript = new BitWire_Transaction_Script;
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
      return strval ($this->outputScript) . ': ' . $this->outputAmount;
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
      return array (
        'amount' => $this->outputAmount,
        'script' => strval ($this->outputScript),
      );
    }
    // }}}
    
    // {{{ getScript
    /**
     * Retrive the script of this input
     * 
     * @access public
     * @return BitWire_Transaction_Script
     **/
    public function getScript () : BitWire_Transaction_Script {
      return $this->outputScript;
    }
    // }}}
    
    // {{{ getAddresses
    /**
     * Retrive addresses of this input
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () {
      return $this->Script->getAddresses ();
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse transaction-output from binary
     * 
     * @param string $inputData
     * @param int $dataOffset
     * @param int $dataLength (optional)
     * 
     * @access public
     * @return bool
     **/
    public function parse (&$inputData, &$dataOffset, $dataLength = null) {
      // Make sure we know the length of our input
      if ($dataLength === null)
        $dataLength = strlen ($inputData);
      
      // Start our own offset
      $myOffset = $dataOffset;
      
      // Try to read everything into our memory
      if ((($outputAmount = BitWire_Message_Payload::readUInt64 ($inputData, $myOffset, $dataLength)) === null) ||
          (($outputScript = BitWire_Message_Payload::readCompactString ($inputData, $myOffset, $dataLength)) === null))
          return false;
      
      // Check size-constraints for script
      if (strlen ($outputScript) > 10003)
        return false;
        
      // Store the results on this instance
      $this->outputAmount = $outputAmount / 100000000;
      $this->outputScript = new BitWire_Transaction_Script ($outputScript);
      $dataOffset = $myOffset;
      
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create binary representation of this input
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      return
        pack ('P', $this->outputAmount * 100000000) .
        BitWire_Message_Payload::toCompactString ($this->outputScript->toBinary ());
    }
    // }}}
  }

?>