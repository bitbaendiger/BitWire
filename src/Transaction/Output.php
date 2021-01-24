<?PHP

  namespace BitBaendiger\BitWire\Transaction;
  
  /**
   * BitWire - Transaction Output
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
  
  require_once ('BitWire/src/Message/Payload.php');
  require_once ('BitWire/src/Transaction/Script.php');
  
  class Output {
    const DIGITS = 8;
    
    /* Amount of coins on this output */
    private $outputAmount = 0;
    
    /* Script for this output */
    private $outputScript = null;
    
    // {{{ __construct
    /**
     * Create a new transaction-input
     * 
     * @param float $outputAmount (optional)
     * @param Script $outputScript (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($outputAmount = 0.0, Script $outputScript = null) {
      $this->outputAmount = (int)floor ($outputAmount * pow (10, $this::DIGITS));
      
      if ($outputScript)
        $this->outputScript = $outputScript;
      else
        $this->outputScript = new Script;
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
      return strval ($this->outputScript) . ': ' . $this->getAmount ();
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
        'amount' => $this->getAmount (),
        'script' => strval ($this->outputScript),
      );
    }
    // }}}
    
    // {{{ getAmount
    /**
     * Retrive the amount of this output
     * 
     * @access public
     * @return float
     **/
    public function getAmount () {
      return $this->outputAmount / pow (10, $this::DIGITS);
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
      return $this->outputScript;
    }
    // }}}
    
    // {{{ getAddresses
    /**
     * Retrive addresses of this input
     * 
     * @param array $addressTypeMap (optional)
     * 
     * @access public
     * @return array
     **/
    public function getAddresses (array $addressTypeMap = array ()) : array {
      return $this->outputScript->getAddresses ($addressTypeMap);
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
      if ((($outputAmount = \BitBaendiger\BitWire\Message\Payload::readUInt64 ($inputData, $myOffset, $dataLength)) === null) ||
          (($outputScript = \BitBaendiger\BitWire\Message\Payload::readCompactString ($inputData, $myOffset, $dataLength)) === null))
          return false;
      
      // Check size-constraints for script
      if (strlen ($outputScript) > 10003)
        return false;
        
      // Store the results on this instance
      $this->outputAmount = $outputAmount;
      $this->outputScript = new Script ($outputScript);
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
        pack ('P', $this->outputAmount) .
        \BitBaendiger\BitWire\Message\Payload::toCompactString ($this->outputScript->toBinary ());
    }
    // }}}
  }

?>