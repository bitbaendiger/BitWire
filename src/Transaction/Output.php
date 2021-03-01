<?php

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
  
  declare (strict_types=1);
  
  namespace BitBaendiger\BitWire\Transaction;
  use BitBaendiger\BitWire;
  
  class Output {
    public const DIGITS = 8;
    
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
    function __construct (float $outputAmount = 0.0, Script $outputScript = null) {
      $this->outputAmount = (int)floor ($outputAmount * pow (10, $this::DIGITS));
      
      if ($outputScript)
        $this->outputScript = $outputScript;
      else
        $this->outputScript = new Script ();
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
      return [
        'amount' => $this->getAmount (),
        'script' => strval ($this->outputScript),
      ];
    }
    // }}}
    
    // {{{ getAmount
    /**
     * Retrive the amount of this output
     * 
     * @remark This function may lead to precision-errros, use getRawAmount() whenever possible
     * 
     * @access public
     * @return float
     **/
    public function getAmount () : float {
      return $this->outputAmount / pow (10, $this::DIGITS);
    }
    // }}}
    
    // {{{ getRawAmount
    /**
     * Retrive the raw amount of this output (e.g. in satoshis)
     * 
     * @access public
     * @return int
     **/
    public function getRawAmount () : int {
      return $this->outputAmount;
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
    public function getAddresses (array $addressTypeMap = [ ]) : array {
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
     * @return void
     **/
    public function parse (string &$inputData, int &$dataOffset, int $dataLength = null) : void {
      // Make sure we know the length of our input
      if ($dataLength === null)
        $dataLength = strlen ($inputData);
      
      // Start our own offset
      $myOffset = $dataOffset;
      
      // Try to read everything into our memory
      $outputAmount = BitWire\Message\Payload::readUInt64 ($inputData, $myOffset, $dataLength);
      $outputScript = BitWire\Message\Payload::readCompactString ($inputData, $myOffset, $dataLength);
      
      // Check size-constraints for script
      if (strlen ($outputScript) > 10003)
        throw new \LengthException ('Output-Script too large');
      
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
    public function toBinary () : string {
      return
        pack ('P', $this->outputAmount) .
        BitWire\Message\Payload::toCompactString ($this->outputScript->toBinary ());
    }
    // }}}
  }
