<?PHP

  require_once ('BitWire/Message/Inventory.php');
  
  class BitWire_Message_GetData extends BitWire_Message_Inventory {
    const PAYLOAD_COMMAND = 'getdata';
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('getdata', 'BitWire_Message_GetData');

?>