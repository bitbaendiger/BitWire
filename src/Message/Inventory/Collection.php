<?php

  /**
   * BitWire - List of Inventory-Items
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

  namespace BitBaendiger\BitWire\Message\Inventory;
  use \BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  abstract class Collection extends Message\Payload implements \IteratorAggregate, \Countable {
    /* Known inventory-types (see protocol.h) */
    public const TYPE_TRANSACTION = 0x01;
    public const TYPE_BLOCK = 0x02;
    public const TYPE_BLOCK_FILTERED = 0x03;
    public const TYPE_COMPACT_BLOCK = 0x04;
    public const TYPE_WITNESS_TRANSACTION = 0x40000001;
    public const TYPE_WITNESS_BLOCK = 0x40000002;
    public const TYPE_WITNESS_BLOCK_FILTERED = 0x40000003;
    
    /* Inventory-Types from DASH-based chains */
    public const TYPE_TXLOCK_REQUEST = 0x04;
    public const TYPE_TXLOCK_VOTE = 0x05;
    public const TYPE_SPORK = 0x06;
    public const TYPE_DSTX = 0x10;
    public const TYPE_GOVERNANCE_OBJECT = 0x11;
    public const TYPE_GOVERNANCE_OBJECT_VOTE = 0x12;
    public const TYPE_COMPACT_BLOCK_DASH = 0x14; // BIP152, as 0x04 in Bitcoin
    public const TYPE_QUORUM_FINAL_COMMITMENT = 0x15;
    public const TYPE_QUORUM_CONTRIB = 0x17;
    public const TYPE_QUORUM_COMPLAINT = 0x18;
    public const TYPE_QUORUM_JUSTIFICATION = 0x19;
    public const TYPE_QUORUM_PREMATURE_COMMITMENT = 0x1A;
    public const TYPE_QUORUM_RECOVERED_SIG = 0x1C;
    public const TYPE_CLSIG = 0x1D;
    public const TYPE_ISLOCK = 0x1E;
    
    /* Inventory-Types from PIVX-based (older DASH-implementation) chains */
    public const TYPE_MASTERNODE_WINNER = 0x07;
    public const TYPE_MASTERNODE_SCANNING_ERROR = 0x08;
    public const TYPE_BUDGET_VOTE = 0x09;
    public const TYPE_BUDGET_PROPOSAL = 0x0A;
    public const TYPE_BUDGET_FINALIZED = 0x0B;
    public const TYPE_BUDGET_FINALIZED_VOTE = 0x0C;
    public const TYPE_MASTERNODE_QUORUM = 0x0D;
    public const TYPE_MASTERNODE_ANNOUNCE = 0x0E;
    public const TYPE_MASTERNODE_PING = 0x0F;
    
    /* Inventory of this payload */
    private $Inventory = [ ];
    
    // {{{ __construct
    /**
     * Create a new inventory-payload
     * 
     * @param array $Iventory (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (array $Inventory = null) {
      if ($Inventory)
        $this->setInventory ($Inventory);
    }
    // }}}
    
    // {{{ getIterator
    /**
     * Retrive an Iterator for this list
     * 
     * @access public
     * @return \Traversable
     **/
    public function getIterator () : \Traversable {
      return new \ArrayIterator ($this->Inventory);
    }
    // }}}
    
    // {{{ getInventory
    /**
     * Retrive the inventory of this payload
     * 
     * @access public
     * @return array
     **/
    public function getInventory () : array {
      return $this->Inventory;
    }
    // }}}
    
    // {{{ setInventory
    /**
     * Set the inventory of this payload
     * 
     * @param array $inventoryItems
     * 
     * @access public
     * @return void
     **/
    public function setInventory (array $inventoryItems) {
      // Make sure the inventory contains hash-objects
      foreach ($inventoryItems as $inventoryIndex=>$inventoryItem) {
        // Check if the item is already fine
        if ($inventoryItem instanceof Item)
          continue;
        
        // Sanatize the item
        if (!is_array ($inventoryItem) ||
            !isset ($inventoryItem ['hash']) ||
            !isset ($inventoryItem ['type'])) {
          trigger_error ('Dropping malformed inventory-item');
          
          unset ($inventoryItems [$inventoryIndex]);
          
          continue;
        }
        
        // Make sure we have a valid hash
        if (!($inventoryItem ['hash'] instanceof BitWire\Hash)) {
          if (strlen ($inventoryItem ['hash']) == 32)
            $inventoryItem ['hash'] = BitWire\Hash::fromBinary ($inventoryItem ['hash'], (isset ($inventoryItem ['internal']) ? $inventoryItem ['internal'] : true));
          elseif (strlen ($inventoryItem ['hash']) == 64)
            $inventoryItem ['hash'] = BitWire\Hash::fromHex ($inventoryItem ['hash'], (isset ($inventoryItem ['internal']) ? $inventoryItem ['internal'] : true));
          else {
            trigger_error ('Dropping inventory-item with invalid hash');
            
            unset ($inventoryItems [$inventoryIndex]);
            
            continue;
          }
        }
        
        $inventoryItems [$inventoryIndex] = new Item ($inventoryItem ['type'], $inventoryItem ['hash']);
      }
      
      // Store the new inventory
      $this->Inventory = $inventoryItems;
    }
    // }}}
    
    // {{{ parse
    /** 
     * Parse binary contents for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse (string $Data) : void {
      // Read the number of entries
      $Length = strlen ($Data);
      $Offset = 0;
      
      $Count = $this::readCompactSize ($Data, $Offset, $Length);
      
      // Sanatize length of data
      if ((($Length - $Offset) % 36) != 0)
        throw new \LengthException ('Length is not a multiple of item-size');
      
      // Read addresses
      $this->Inventory = [ ];
      
      for ($i = 0; $i < $Count; $i++)
        // Try to unpack inventory-item
        if ($inventoryItem = Item::fromBinary ($Data, $Offset, $Length))
          $this->Inventory [] = $inventoryItem;
    }
    // }}}
    
    // {{{ count
    /**
     * Retrive the number of elements on this list
     * 
     * @access public
     * @return int
     **/
    public function count () {
      return count ($this->Inventory);
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
      // Output number of entries
      $Buffer = $this::toCompactSize (count ($this->Inventory));
      
      // Output each entry
      foreach ($this->Inventory as $Inventory)
        $Buffer .= $Inventory->toBinary ();
      
      // Return the result
      return $Buffer;
    }
    // }}}
  }
