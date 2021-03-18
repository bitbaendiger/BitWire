<?php

  /**
   * BitWire - Block Message
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
  
  class Block extends Payload\Hashable {
    protected const PAYLOAD_COMMAND = 'block';
    
    /* Block stored on this message */
    private $Block = null;
    
    // {{{ __debugInfo
    /**
     * Prepare output for var_dump() of this object
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () : array {
      return [
        'block' => $this->Block,
      ];
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash for this payload
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      if ($this->Block)
        return $this->Block->getHash ();

      return new BitWire\Hash ();
    }
    // }}}
    
    // {{{ getBlock
    /**
     * Retrive the block stored on this message
     * 
     * @access public
     * @return BitWire\Block
     **/
    public function getBlock () : ?BitWire\Block {
      return $this->Block;
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
      $Block = new BitWire\Block ();
      $Offset = 0;
      
      $Block->parse ($Data, $Offset);
      
      $this->Block = $Block;
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
      if ($this->Block)
        return $this->Block->toBinary ();
    }
    // }}}
  }
