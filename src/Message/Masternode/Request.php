<?php

  /**
   * BitWire - Masternode Request Message
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

  namespace BitBaendiger\BitWire\Message\Masternode;
  use \BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class Request extends Message\Payload {
    protected const PAYLOAD_COMMAND = 'dseg';
    
    /* UTXO of masternode */
    private $txIn = null;
    
    // {{{ __construct
    /**
     * Create a new masternode-request
     * 
     * @param BitWire\Transaction\Input $transactionInput (optional)
     * 
     * @access friendly
     * @return void
     **/
    public function __construct (BitWire\Transaction\Input $transactionInput = null) {
      $this->txIn = $transactionInput;
    }
    // }}}
    
    // {{{ getTransactionInput
    /**
     * Retrive the transaction-input of this masternode-ping
     * 
     * @access public
     * @return BitWire\Transaction\Input
     **/
    public function getTransactionInput () : ?BitWire\Transaction\Input {
      return $this->txIn;
    }
    // }}}
    
    // {{{ setTransactionInput
    /**
     * Set transaction-input for this message
     * 
     * @param BitWire\Transaction\Input $Input
     * 
     * @access public
     * @return void
     **/
    public function setTransactionInput (BitWire\Transaction\Input $Input) : void {
      $this->txIn = $Input;
    }
    // }}}
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * @param int $Offset (optional)
     * @param int $Length (optional)
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data, int &$Offset = 0, int $Length = null) : void {
      // Try to read all values
      if ($Length === null)
        $Length = strlen ($Data);
      
      $txIn = self::readCTxIn ($Data, $Offset, $Length);
      
      // Commit to this instance
      $this->txIn = $txIn;
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
      return self::writeCTxIn ($this->txIn);
    }
    // }}}
  }
