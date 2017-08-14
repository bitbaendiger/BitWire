<?PHP

  require_once ('qcEvents/Socket.php');
  
  class BitWire_Message_Addresses extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'addr';
    
    /* Known addresses */
    private $Addresses = array ();
    
    // {{{ getAddresses
    /**
     * Retrive all addresses stored here
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () {
      return $this->Addresses;
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
      // Try to read the version
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : null);
      
      // Read the number of entries
      if (($Count = $this::readCompactSize ($Data, $Length)) === false)
        return false;
      
      // Truncate number of entries from data
      $Data = substr ($Data, $Length);
      
      // Check wheter to auto-detect the version
      $Length = strlen ($Data);
      
      if ($Version === null) {
        if (($Length % 30) == 0)
          $Version = 70015;
        else
          $Version = 31401;
      }
      
      // Sanatize length of data
      if (($Length % ($Version >= 31402 ? 30 : 26)) != 0)
        return false;
      
      // Read addresses
      $this->Addresses = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Try to unpack the data
        if ($Version >= 31402)
          $Values = unpack ('Vtimestamp/Pservices/a16address/vport', substr ($Data, $i * 30, 30));
        else
          $Values = unpack ('Pservices/a16address/vport', substr ($Data, $i * 26, 26));
        
        if (!$Values)
          return false;
        
        // Convert IP-Address into something human readable
        $Values ['address'] = qcEvents_Socket::ip6fromBinary ($Values ['address']);
        
        // Push to addresses
        $this->Addresses [] = $Values;
      }
      
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
      // Check the version we are running with
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : 700015);
      
      // Output number of entries
      $Buffer = $this::toCompactSize (count ($this->Addresses));
      
      // Output each entry
      foreach ($this->Addresses as $Address)
        if ($Version >= 31402)
          $Buffer .= pack ('VPa16v', $Address ['timestamp'], $Address ['services'], qcEvents_Socket::ip6toBinary ($Address ['address']), $Address ['port']);
        else
          $Buffer .= pack ('Pa16v', $Address ['services'], qcEvents_Socket::ip6toBinary ($Address ['address']), $Address ['port']);
      
      // Return the result
      return $Buffer;
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('addr', 'BitWire_Message_Addresses');

?>