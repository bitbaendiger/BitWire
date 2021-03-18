<?php

  /**
   * BitWire - FeeFilter Message
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

  class FeeFilter extends Payload {
    protected const PAYLOAD_COMMAND = 'feefilter';
    protected const PAYLOAD_MIN_VERSION = 70013;
    
    /* Minimum fee for relayed transactions */
    private $Fee = 0;
    
    // {{{ getFee
    /**
     * Retrive minimum fee for relayed transactions
     * 
     * @access public
     * @return int
     **/
    public function getFee () : int {
      return $this->Fee;
    }
    // }}}
    
    // {{{ setFee
    /**
     * Set the minimum fee for relayed transactions
     * 
     * @param int $Fee
     * 
     * @access public
     * @return void
     **/
    public function setFee (int $Fee) {
      $this->Fee = $Fee;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse received payload for this message
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse ($Data) : void {
      // Check the size of payload
      if (strlen ($Data) != 8)
        throw new \LengthException ('Invalid length');
      
      // Unpack nonce from payload
      $Fee = unpack ('Pfee', $Data);
      $this->Fee = array_shift ($Fee);
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      // Return payload
      return pack ('P', $this->Fee);
    }
    // }}}
  }
