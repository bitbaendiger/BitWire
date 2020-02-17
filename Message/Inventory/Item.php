<?PHP

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
  
  require_once ('BitWire/Hash.php');
  
  class BitWire_Message_Inventory_Item {
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
     * @return BitWire_Message_Inventory_Item
     **/
    public static function fromBinary (&$dataBinary, &$dataOffset = 0, $dataLength = null) : ?BitWire_Message_Inventory_Item {
      // Make sure we know the length of binary data
      if ($dataLength === null)
        $dataLength = strlen ($dataBinary);
      
      // Check amount of available data
      if ($dataLength < $dataOffset + 36)
        return null;
      
      // Try to unpack the data
      if (!($inventoryValues = unpack ('Vtype/a32hash', substr ($dataBinary, $dataOffset, 36))))
        return null;
      
      // Move the cursor
      $dataOffset += 36;
      
      return new static ($inventoryValues ['type'], BitWire_Hash::fromBinary ($inventoryValues ['hash'], true));
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new inventory-item
     * 
     * @param enum $inventoryType
     * @param BitWire_Hash $inventoryHash
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($inventoryType, BitWire_Hash $inventoryHash) {
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
    public function getType () {
      return $this->inventoryType;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash of this inventory-item
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () : BitWire_Hash {
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
    public function toBinary () {
      return pack ('Va32', $this->inventoryType, $this->inventoryHash->toBinary (true));
    }
    // }}}
  }

?>