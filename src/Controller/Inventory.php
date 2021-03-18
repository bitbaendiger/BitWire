<?php

  /**
   * BitWire - Bitcoin Controller Inventory
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

  namespace BitBaendiger\BitWire\Controller;
  use \BitBaendiger\BitWire;
  
  class Inventory implements \ArrayAccess, \Countable, \IteratorAggregate {
    /* Type of this inventory */
    private $inventoryType = null;
    
    /* Classnames that represent items for this inventory */
    private $inventoryClasses = [ ];
    
    /* Items on this inventory */
    private $inventoryItems = [ ];
    
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
    function __construct (int $inventoryType, $inventoryClasses) {
      $this->inventoryType = $inventoryType;
      
      if (!is_array ($inventoryClasses))
        $inventoryClasses = [ $inventoryClasses ];
      
      foreach ($inventoryClasses as $inventoryClass)
        $this->addClassname ($inventoryClass);
    }
    // }}}
    
    // {{{ getType
    /**
     * Retrive the type-number of items stored on this inventory
     * 
     * @access public
     * @return int
     **/
    public function getType () : int {
      return $this->inventoryType;
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
     * @return \ArrayIterator
     **/
    public function getIterator () : \ArrayIterator {
      return new \ArrayIterator ($this->inventoryItems);
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
     * @return Inventory\Item
     **/
    public function offsetGet ($itemHash) : ?Inventory\Item {
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
     * @param Inventory\Item $inventoryItem
     * 
     * @access public
     * @return void
     **/
    public function offsetSet ($itemHash, $inventoryItem) {
      if (!($inventoryItem instanceof Inventory\Item))
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
    private function addClassname (string $inventoryClass) : void {
      if (!in_array ($inventoryClass, $this->inventoryClasses))
        $this->inventoryClasses [] = $inventoryClass;
    }
    // }}}
    
    // {{{ addInventory
    /**
     * Make sure an inventory-item is added here
     * 
     * @param BitWire\Message\Inventory\Item $inventoryItem
     * @param BitWire\Peer $fromPeer (optional)
     * 
     * @access public
     * @return Inventory\Item
     * @throws TypeError
     **/
    public function addInventory (BitWire\Message\Inventory\Item $inventoryItem, BitWire\Peer $fromPeer = null) : Inventory\Item {
      // Sanity-Check the inventory-type
      if ($this->inventoryType != $inventoryItem->getType ())
        throw new TypeError ('Inventory-Types do not match');
      
      // Check if we already have such inventory
      $inventoryKey = strval ($inventoryItem->getHash ());
      
      if (!isset ($this->inventoryItems [$inventoryKey]))
        $this->inventoryItems [$inventoryKey] = new Inventory\Item ($inventoryItem->getType (), $inventoryItem->getHash ());
      
      // Check wheter to add a peer
      if ($fromPeer)
        $this->inventoryItems [$inventoryKey]->addPeer ($fromPeer);
      
      // Return the inventory-item
      return $this->inventoryItems [$inventoryKey];
    }
    // }}}
    
    // {{{ getInventory
    /**
     * Retrive an inventory-item by hash from this collection
     * 
     * @param BitWire\Hash $hashNeedle
     * 
     * @access public
     * @return Inventory\Item
     **/
    public function getInventory (BitWire\Hash $hashNeedle) : ?Inventory\Item {
      return $this->inventoryItems [strval ($hashNeedle)] ?? null;
    }
    // }}}
    
    // {{{ checkInstance
    /**
     * Check if a given payload-instance matches a class of this inventory
     * 
     * @param BitWire\Message\Payload $payloadInstance
     * 
     * @access public
     * @return bool
     **/
    public function checkInstance (BitWire\Message\Payload $payloadInstance) : bool {
      if (!($payloadInstance instanceof BitWire\Message\Payload\Hashable))
        return false;
      
      foreach ($this->inventoryClasses as $inventoryClass)
        if ($payloadInstance instanceof $inventoryClass)
          return true;
      
      return false;
    }
    // }}}
    
    // {{{ hasInstance
    /**
     * Check if a payload-instance is present on this inventory
     * 
     * @param BitWire\Message\Payload\Hashable
     * 
     * @access public
     * @return bool
     **/
    public function hasInstance (BitWire\Message\Payload\Hashable $payloadInstance) : bool {
      // Sanity-Check first
      if (!$this->checkInstance ($payloadInstance))
        throw new \TypeError ('Invalid payload-instance');
      
      // Retrive the hash of that instance
      $inventoryHash = $payloadInstance->getHash ();
      $inventoryKey = strval ($inventoryHash);
      
      // Check if that instance is known and ready
      return (isset ($this->inventoryItems [$inventoryKey]) && $this->inventoryItems [$inventoryKey]->isReady ());
    }
    // }}}
    
    // {{{ addInstance
    /**
     * Add a payload-instance to this inventory
     * 
     * @param BitWire\Message\Payload\Hashable $payloadInstance
     * 
     * @access public
     * @return Inventory\Item
     * @throws \TypeError
     **/
    public function addInstance (BitWire\Message\Payload\Hashable $payloadInstance) : Inventory\Item {
      // Sanity-Check first
      if (!$this->checkInstance ($payloadInstance))
        throw new \TypeError ('Invalid payload-instance');
      
      // Retrive the hash of that instance
      $inventoryHash = $payloadInstance->getHash ();
      $inventoryKey = strval ($inventoryHash);
      
      // Make sure we have an inventory-item for this
      if (!isset ($this->inventoryItems [$inventoryKey]))
        $this->inventoryItems [$inventoryKey] = new Inventory\Item ($this->inventoryType, $inventoryHash);
      
      $this->inventoryItems [$inventoryKey]->setItem ($payloadInstance);
      
      return $this->inventoryItems [$inventoryKey];
    }
    // }}}
    
    // {{{ getInstance
    /**
     * Retrive an instance from this inventory
     * 
     * @param BitWire\Hash $hashNeedle
     * 
     * @access public
     * @return BitWire\Message\Payload\Hashable
     **/
    public function getInstance (BitWire\Hash $hashNeedle) : ?BitWire\Message\Payload\Hashable {
      $hashNeedle = strval ($hashNeedle);
      
      return (isset ($this->inventoryItems [$hashNeedle]) ? $this->inventoryItems [$hashNeedle]->getItem () : null);
    }
    // }}}
    
    // {{{ removeInstance
    /**
     * Remove an instance from this inventory
     * 
     * @param BitWire_Hash $hashNeedle
     * 
     * @access public
     * @return void
     **/
    public function removeInstance (BitWire\Hash $hashNeedle) : void {
      unset ($this->inventoryItems [strval ($hashNeedle)]);
    }
    // }}}
    
    // {{{ contains
    /**
     * Check if an instance with a given hash is present on this inventory
     * 
     * @param BitWire\Hash $hashNeedle
     * 
     * @access public
     * @return bool
     **/
    public function contains (BitWire\Hash $hashNeedle) : bool {
      $hashNeedle = strval ($hashNeedle);
      
      return
        isset ($this->inventoryItems [$hashNeedle]) &&
        $this->inventoryItems [strval ($hashNeedle)]->isReady ();
    }
    // }}}
  }
