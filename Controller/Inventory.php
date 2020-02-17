<?PHP

  /**
   * BitWire - Bitcoin Controller Inventory
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
  
  require_once ('BitWire/Controller/Inventory/Item.php');
  
  class BitWire_Controller_Inventory implements ArrayAccess, Countable, IteratorAggregate {
    /* Type of this inventory */
    private $inventoryType = null;
    
    /* Classnames that represent items for this inventory */
    private $inventoryClasses = array ();
    
    /* Items on this inventory */
    private $inventoryItems = array ();
    
    // {{{ __construct
    /**
     * Create a new inventory
     * 
     * @param int $inventoryType
     * @param mixed $inventoryClasses
     * 
     * @accesss friendly
     * @return void
     **/
    function __construct ($inventoryType, $inventoryClasses) {
      $this->inventoryType = $inventoryType;
      
      if (!is_array ($inventoryClasses))
        $inventoryClasses = array ($inventoryClasses);
      
      foreach ($inventoryClasses as $inventoryClass)
        $this->addClassname ($inventoryClass);
    }
    // }}}
    
    // {{{ count
    /**
     * Count the number of items on this inventory
     * 
     * @access public
     * @return int
     **/
    public function count () {
      return count ($this->inventoryItems);
    }
    // }}}
    
    // {{{ getIterator
    /**
     * Retrive a new iterator for this inventory
     * 
     * @access public
     * @return ArrayIterator
     **/
    public function getIterator () : ArrayIterator {
      return new ArrayIterator ($this->inventoryItems);
    }
    // }}}
    
    // {{{ offsetExists
    /**
     * Check if a given hash exists on this inventory
     * 
     * @param string $itemHash
     * 
     * @access public
     * @return bool
     **/
    public function offsetExists ($itemHash) {
      return isset ($this->inventoryItems [$itemHash]);
    }
    // }}}
    
    // {{{ offsetUnset
    /**
     * Remove an item from this inventory
     * 
     * @param string $itemHash
     * 
     * @access public
     * @return void
     **/
    public function offsetUnset ($itemHash) {
      unset ($this->inventoryItems [$itemHash]);
    }
    // }}}
    
    // {{{ offsetGet
    /**
     * Retrive an item from this inventory
     * 
     * @param string $itemHash
     * 
     * @access public
     * @return BitWire_Controller_Inventory_Item
     **/
    public function offsetGet ($itemHash) : ?BitWire_Controller_Inventory_Item {
      if (isset ($this->inventoryItems [$itemHash]))
        return $this->inventoryItems [$itemHash];
      
      return null;
    }
    // }}}
    
    // {{{ offsetSet
    /**
     * Add an item to this inventory
     * 
     * @param string $itemHash (optional)
     * @param BitWire_Controller_Inventory_Item $inventoryItem
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($itemHash, $inventoryItem) {
      if (!($inventoryItem instanceof BitWire_Controller_Inventory_Item))
        return;
      
      $this->inventoryItems [strval ($inventoryItem->getHash ())] = $inventoryItem;
    }
    // }}}
    
    // {{{ addClassname
    /**
     * Add a classname for items for this inventory
     * 
     * @param string $inventoryClass
     * 
     * @access private
     * @return void
     **/
    private function addClassname ($inventoryClass) {
      if (!in_array ($inventoryClass, $this->inventoryClasses))
        $this->inventoryClasses [] = $inventoryClass;
    }
    // }}}
    
    // {{{ addInventory
    /**
     * Make sure an inventory-item is added here
     * 
     * @param BitWire_Message_Inventory_Item $inventoryItem
     * @param BitWire_Peer $fromPeer (optional)
     * 
     * @access public
     * @return BitWire_Controller_Inventory_Item
     * @throws TypeError
     **/
    public function addInventory (BitWire_Message_Inventory_Item $inventoryItem, BitWire_Peer $fromPeer = null) : BitWire_Controller_Inventory_Item {
      // Sanity-Check the inventory-type
      if ($this->inventoryType != $inventoryItem->getType ())
        throw new TypeError ('Inventory-Types do not match');
      
      // Check if we already have such inventory
      $inventoryKey = strval ($inventoryItem->getHash ());
      
      if (!isset ($this->inventoryItems [$inventoryKey]))
        $this->inventoryItems [$inventoryKey] = new BitWire_Controller_Inventory_Item ($inventoryItem->getType (), $inventoryItem->getHash ());
      
      // Check wheter to add a peer
      if ($fromPeer)
        $this->inventoryItems [$inventoryKey]->addPeer ($fromPeer);
      
      // Return the inventory-item
      return $this->inventoryItems [$inventoryKey];
    }
    // }}}
    
    // {{{ checkInstance
    /**
     * Check if a given payload-instance matches a class of this inventory
     * 
     * @param BitWire_Message_Payload $payloadInstance
     * 
     * @access public
     * @return bool
     **/
    public function checkInstance (BitWire_Message_Payload $payloadInstance) {
      if (!($payloadInstance instanceof BitWire_Message_Payload_Hashable))
        return false;
      
      foreach ($this->inventoryClasses as $inventoryClass)
        if ($payloadInstance instanceof $inventoryClass)
          return true;
      
      return false;
    }
    // }}}
    
    // {{{ addInstance
    /**
     * Add a payload-instance to this inventory
     * 
     * @param BitWire_Message_Payload_Hashable $payloadInstance
     * 
     * @access public
     * @return void
     * @throws TypeError
     **/
    public function addInstance (BitWire_Message_Payload_Hashable $payloadInstance) {
      // Sanity-Check first
      if (!$this->checkInstance ($payloadInstance))
        throw new TypeError ('Invalid payload-instance');
      
      // Retrive the hash of that instance
      $inventoryHash = $payloadInstance->getHash ();
      $inventoryKey = strval ($inventoryHash);
      
      // Make sure we have an inventory-item for this
      if (!isset ($this->inventoryItems [$inventoryKey]))
        $this->inventoryItems [$inventoryKey] = new BitWire_Controller_Inventory_Item ($this->inventoryType, $inventoryHash);
      
      $this->inventoryItems [$inventoryKey]->setItem ($payloadInstance);
    }
    // }}}
  }

?>