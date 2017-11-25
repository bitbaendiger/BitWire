<?PHP

  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Hash.php');
  
  abstract class BitWire_Message_Inventory_List extends BitWire_Message_Payload {
    /* Known inventory-types */
    const TYPE_TRANSACTION = 0x01;
    const TYPE_BLOCK = 0x02;
    const TYPE_BLOCK_FILTERED = 0x03;
    
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
     * @param array $Inventory
     * 
     * @access public
     * @return void
     **/
    public function setInventory (array $Inventory) {
      // Make sure the inventory contains hash-objects
      foreach ($Inventory as $idx=>$Hash)
        if ($Hash ['hash'] instanceof BitWire_Hash)
          continue;
        elseif (strlen ($Hash ['hash']) == 32)
          $Inventory [$idx]['hash'] = BitWire_Hash::fromBinary ($Hash, true);
        elseif (strlen ($Hash ['hash']) == 64)
          $Inventory [$idx]['hash'] = BitWire_Hash::fromHex ($Hash, true);
        else
          unset ($Inventory [$idx]);
      
      // Store the new inventory
      $this->Inventory = $Inventory;
    }
    // }}}
    
    // {{{ parseData
    /** 
     * Parse binary contents for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      // Read the number of entries
      if (($Count = $this::readCompactSize ($Data, $Length)) === false)
        return false;
      
      // Truncate number of entries from data
      $Data = substr ($Data, $Length);
      
      // Check wheter to auto-detect the version
      $Length = strlen ($Data);
      
      // Sanatize length of data
      if (($Length % 36) != 0)
        return false;
      
      // Read addresses
      $this->Inventory = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Try to unpack the data
        if (!($Values = unpack ('Vtype/a32hash', substr ($Data, $i * 36, 36))))
          return false;
        
        $Values ['hash'] = BitWire_Hash::fromBinary ($Values ['hash'], true);
        
        // Push to addresses
        $this->Inventory [] = $Values;
      }
      
      // Indicate success
      return true;
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
        $Buffer .= pack ('Va32', $Inventory ['type'], $Inventory ['hash']->toBinary (true));
      
      // Return the result
      return $Buffer;
    }
    // }}}
  }

?>