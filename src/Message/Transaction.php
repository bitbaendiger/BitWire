<?php

  /**
   * BitWire - Transaction-Message
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

  namespace BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class Transaction extends Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'tx';
    
    /* BitWire-Transcation */
    private $Transaction = null;
    
    // {{{ getHash
    /**
     * Retrive the hash for this payload
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      if ($this->Transaction)
        return $this->Transaction->getHash ();
      
      return new BitWire\Hash ();
    }
    // }}}
    
    // {{{ getTransaction
    /**
     * Retrive the transaction-object stored on this message
     * 
     * @access public
     * @return BitWire\Transaction
     **/
    public function getTransaction () : ?BitWire\Transaction {
      return $this->Transaction;
    }
    // }}}
    
    // {{{ setTransaction
    /**
     * Store a transaction on this payload
     * 
     * @param BitWire\Transaction $Transaction
     * 
     * @access public
     * @return void
     **/
    public function setTransaction (BitWire\Transaction $Transaction) : void {
      $this->Transaction = $Transaction;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data) : void {
      // Create a new transaction
      if (!$this->Transaction)
        $this->Transaction = new BitWire\Transaction ();
      
      // Try to parse the data
      $Length = strlen ($Data);
      $Offset = 0;
      
      $this->Transaction->parse ($Data, $Offset, $Length);
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
      if (!$this->Transaction)
        return '';
      
      return $this->Transaction->toBinary ();
    }
    // }}}
  }
