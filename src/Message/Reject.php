<?php

  /**
   * BitWire - Reject Message
   * Copyright (C) 2019-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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

  namespace BitBaendiger\BitWire\Message;
  
  class Reject extends Payload {
    protected const PAYLOAD_COMMAND = 'reject';
    protected const PAYLOAD_MIN_VERSION = 70002;
    
    /* Reject-code describing what happened */
    private $Code = 0x00;
    
    /* The actual command that was rejected */
    private $Command = '';
    
    /* Human-readable reason of the reject-message */
    private $Reason = '';
    
    /* Extra-Data depending on code */
    private $Extra = '';
    
    // {{{ getCode
    /**
     * Retrive the reject-code
     * 
     * @access public
     * @return int
     **/
    public function getCode () : int {
      return $this->Code;
    }
    // }}}
    
    // {{{ setCode
    /**
     * Set the reject-code for this payload
     * 
     * @param int $Code
     * 
     * @access public
     * @return void
     **/
    public function setCode (int $Code) : void {
      if (($Code < 0) || ($Code > 255))
        throw new \ValueError ('Code is usigned char (0-255)');
      
      $this->Code = $Code;
    }
    // }}}
    
    // {{{ getReason
    /**
     * Retrive the reason-text
     * 
     * @access public
     * @return string
     **/
    public function getReason () : string {
      return $this->Reason;
    }
    // }}}
    
    // {{{ setReason
    /**
     * Set reason-text for this payload
     * 
     * @param string $Reason
     * 
     * @access public
     * @return void
     **/
    public function setReason (string $Reason) : void {
      $this->Reason = $Reason;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse input data
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse ($Data) : void {
      $Length = strlen ($Data);
      $Offset = 0;
      
      $Command = $this::readCompactString ($Data, $Offset, $Length);
      $Code = $this::readChar ($Data, $Offset, 1, $Length);
      $Reason = $this::readCompactString ($Data, $Offset, $Length);
      
      $this->Command = $Command;
      $this->Code = ord ($Code);
      $this->Reason = $Reason;
      $this->Extra = substr ($Data, $Offset);
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create a binary representation of this payload
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      return
        $this::toCompactString ($this->Command) .
        chr ($this->Code) .
        $this::toCompactString ($this->Reason) .
        $this->Extra;
    }
    // }}}
  }
