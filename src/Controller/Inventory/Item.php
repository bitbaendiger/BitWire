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

  namespace BitBaendiger\BitWire\Controller\Inventory;
  use \BitBaendiger\BitWire;
  
  class Item extends BitWire\Message\Inventory\Item {
    /* The item itself */
    private $inventoryItem = null;
    
    /* Peers that have this item available */
    private $inventoryPeers = [ ];
    
    /* List of requests for this inventory */
    private $inventoryRequests = [ ];
    
    // {{{ isReady
    /**
     * Check if this inventory-item is ready
     * 
     * @access public
     * @return bool
     **/
    public function isReady () : bool {
      return is_object ($this->inventoryItem);
    }
    // }}}
    
    // {{{ getItem
    /**
     * Retrive the actual item of this inventory
     * 
     * @access public
     * @return BitWire\ABI\Hashable
     **/
    public function getItem () : ?BitWire\ABI\Hashable {
      return $this->inventoryItem;
    }
    // }}}
    
    // {{{ setItem
    /**
     * Store the actual item for this inventory
     * 
     * @param BitWire\ABI\Hashable $inventoryItem
     * 
     * @access public
     * @return bool
     **/
    public function setItem (BitWire\ABI\Hashable $inventoryItem) : bool {
      // Compare the hashes
      if (!$this->getHash ()->compare ($inventoryItem->getHash ()))
        return false;
      
      // Store the item
      $this->inventoryItem = $inventoryItem;
      
      return true;
    }
    // }}}
    
    // {{{ unsetItem
    /**
     * Remove the stored item
     * 
     * @access public
     * @return void
     **/
    public function unsetItem () : void {
      $this->inventoryItem = null;
    }
    // }}}
    
    // {{{ addPeer
    /**
     * Register a peer that is aware of this item
     * 
     * @param BitWire\Peer $Peer
     * 
     * @access public
     * @return void
     **/
    public function addPeer (BitWire\Peer $Peer) : void {
      if (!in_array ($Peer, $this->inventoryPeers, true))
        $this->inventoryPeers [] = $Peer;
    }
    // }}}
    
    // {{{ removePeer
    /**
     * Unregister a peer from this inventory
     * 
     * @param BitWire\Peer $Peer
     * 
     * @access public
     * @return void
     **/
    public function removePeer (BitWire\Peer $Peer) : void {
      if (($Key = array_search ($Peer, $this->inventoryPeers, true)) !== false)
        unset ($this->inventoryPeers [$Key]);
    }
    // }}}
    
    // {{{ getPeers
    /**
     * Retrive all peers that seem to know about this
     * 
     * @access public
     * @return array
     **/
    public function getPeers () : array {
      return $this->inventoryPeers;
    }
    // }}}
    
    // {{{ shouldRequest
    /** 
     * Check if this item should be requested from any peer
     * 
     * @access public
     * @return bool
     **/
    public function shouldRequest () : bool {
      // Never request if we are already ready
      if ($this->isReady ())
        return false;
      
      // Check if there was a recent request
      $Now = time ();
      
      foreach ($this->inventoryRequests as $inventoryRequest)
        if ($Now - $inventoryRequest ['timestamp'] < 30)
          return false;
      
      // Request a request
      return true;
    }
    // }}}
    
    // {{{ setRequested
    /**
     * Set requested-state for a given peer
     * 
     * @param BitWire\Peer $fromPeer
     * @param bool $setState (optional)
     * 
     * @access public
     * @return void
     **/
    public function setRequested (BitWire\Peer $fromPeer, bool $setState = true) : void {
      // Clean up the inventory
      $Now = time ();
      
      foreach ($this->inventoryRequests as $inventoryIndex=>$inventoryRequest)
        if (($inventoryRequest ['peer'] === $fromPeer) ||
            ($Now - $inventoryRequest ['timestamp'] > 60))
          unset ($this->inventoryRequests [$inventoryIndex]);
      
      // Check wheter to add new entry
      if ($setState)
        $this->inventoryRequests [] = [
          'peer' => $fromPeer,
          'timestamp' => $Now,
        ];
    }
    // }}}
  }
