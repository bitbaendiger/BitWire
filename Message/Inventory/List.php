<?PHP

  /**
   * BitWire - List of Inventory-Items
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
  
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Message/Inventory/Item.php');
  
  abstract class BitWire_Message_Inventory_List extends BitWire_Message_Payload implements IteratorAggregate, Countable {
    /* Known inventory-types (see protocol.h) */
    const TYPE_TRANSACTION = 0x01;
    const TYPE_BLOCK = 0x02;
    const TYPE_BLOCK_FILTERED = 0x03;
    const TYPE_COMPACT_BLOCK = 0x04;
    const TYPE_WITNESS_TRANSACTION = 0x40000001;
    const TYPE_WITNESS_BLOCK = 0x40000002;
    const TYPE_WITNESS_BLOCK_FILTERED = 0x40000003;
    
    /* Inventory-Types from DASH-based chains */
    const TYPE_TXLOCK_REQUEST = 0x04;
    const TYPE_TXLOCK_VOTE = 0x05;
    const TYPE_SPORK = 0x06;
    const TYPE_DSTX = 0x10;
    const TYPE_GOVERNANCE_OBJECT = 0x11;
    const TYPE_GOVERNANCE_OBJECT_VOTE = 0x12;
    const TYPE_COMPACT_BLOCK_DASH = 0x14; // BIP152, as 0x04 in Bitcoin
    const TYPE_QUORUM_FINAL_COMMITMENT = 0x15;
    const TYPE_QUORUM_CONTRIB = 0x17;
    const TYPE_QUORUM_COMPLAINT = 0x18;
    const TYPE_QUORUM_JUSTIFICATION = 0x19;
    const TYPE_QUORUM_PREMATURE_COMMITMENT = 0x1A;
    const TYPE_QUORUM_RECOVERED_SIG = 0x1C;
    const TYPE_CLSIG = 0x1D;
    const TYPE_ISLOCK = 0x1E;
    
    /* Inventory-Types from PIVX-based (older DASH-implementation) chains */
    const TYPE_MASTERNODE_WINNER = 0x07;
    const TYPE_MASTERNODE_SCANNING_ERROR = 0x08;
    const TYPE_BUDGET_VOTE = 0x09;
    const TYPE_BUDGET_PROPOSAL = 0x0A;
    const TYPE_BUDGET_FINALIZED = 0x0B;
    const TYPE_BUDGET_FINALIZED_VOTE = 0x0C;
    const TYPE_MASTERNODE_QUORUM = 0x0D;
    const TYPE_MASTERNODE_ANNOUNCE = 0x0E;
    const TYPE_MASTERNODE_PING = 0x0F;
    
    /* Inventory of this payload */
    private $Inventory = array ();
    
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
     * @return Traversable
     **/
    public function getIterator () : Traversable {
      return new ArrayIterator ($this->Inventory);
    }
    // }}}
    
    // {{{ getInventory
    /**
     * Retrive the inventory of this payload
     * 
     * @access public
     * @return array
     **/
    public function getInventory () {
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
        if ($inventoryItem instanceof BitWire_Message_Inventory_Item)
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
        if (!($inventoryItem ['hash'] instanceof BitWire_Hash)) {
          if (strlen ($inventoryItem ['hash']) == 32)
            $inventoryItem ['hash'] = BitWire_Hash::fromBinary ($inventoryItem ['hash'], (isset ($inventoryItem ['internal']) ? $inventoryItem ['internal'] : true));
          elseif (strlen ($inventoryItem ['hash']) == 64)
            $inventoryItem ['hash'] = BitWire_Hash::fromHex ($inventoryItem ['hash'], (isset ($inventoryItem ['internal']) ? $inventoryItem ['internal'] : true));
          else {
            trigger_error ('Dropping inventory-item with invalid hash');
            
            unset ($inventoryItems [$inventoryIndex]);
            
            continue;
          }
        }
        
        $inventoryItems [$inventoryIndex] = new BitWire_Message_Inventory_Item ($inventoryItem ['type'], $inventoryItem ['hash']);
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
     * @return bool
     **/
    public function parse ($Data) {
      // Read the number of entries
      $Length = strlen ($Data);
      $Offset = 0;
      
      if (($Count = $this::readCompactSize ($Data, $Offset, $Length)) === null)
        return false;
      
      // Sanatize length of data
      if ((($Length - $Offset) % 36) != 0)
        return false;
      
      // Read addresses
      $this->Inventory = array ();
      
      for ($i = 0; $i < $Count; $i++)
        // Try to unpack inventory-item
        if ($inventoryItem = BitWire_Message_Inventory_Item::fromBinary ($Data, $Offset, $Length))
          $this->Inventory [] = $inventoryItem;
      
      // Indicate success
      return true;
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
    public function toBinary () {
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

?>