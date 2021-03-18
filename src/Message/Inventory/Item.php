<?php

  /**
   * BitWire - Inventory Item
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
  
  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message\Inventory;
  use \BitBaendiger\BitWire;
  
  class Item {
    /* Type of this inventory item */
    private $inventoryType = 0x00;
    
    /* Hash of this inventory item */
    private $inventoryHash = null;
    
    // {{{ fromBinary
    /**
     * Try to read inventory-item from binary data
     * 
     * @param string $dataBinary
     * @param int $dataOffset (optional)
     * @apram int $dataLength (optional)
     * 
     * @access public
     * @return Item
     **/
    public static function fromBinary (string &$dataBinary, int &$dataOffset = 0, int $dataLength = null) : Item {
      // Make sure we know the length of binary data
      if ($dataLength === null)
        $dataLength = strlen ($dataBinary);
      
      // Check amount of available data
      if ($dataLength < $dataOffset + 36)
        throw new \LengthException ('Input-Data too short');
      
      // Try to unpack the data
      if (!($inventoryValues = unpack ('Vtype/a32hash', substr ($dataBinary, $dataOffset, 36))))
        throw new \ValueError ('Unable to unpack item');
      
      // Move the cursor
      $dataOffset += 36;
      
      return new static ($inventoryValues ['type'], BitWire\Hash::fromBinary ($inventoryValues ['hash'], true));
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new inventory-item
     * 
     * @param enum $inventoryType
     * @param BitWire\Hash $inventoryHash
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($inventoryType, BitWire\Hash $inventoryHash) {
      $this->inventoryType = (int)$inventoryType;
      $this->inventoryHash = $inventoryHash;
    }
    // }}}
    
    // {{{ getType
    /**
     * Retrive the type of this inventory-item
     * 
     * @access public
     * @return enum
     **/
    public function getType () : int {
      return $this->inventoryType;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash of this inventory-item
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      return $this->inventoryHash;
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
      return pack ('Va32', $this->inventoryType, $this->inventoryHash->toBinary (true));
    }
    // }}}
  }
