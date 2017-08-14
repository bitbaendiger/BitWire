<?PHP

  require_once ('BitWire/Message/Inventory/List.php');
  
  class BitWire_Message_NotFound extends BitWire_Message_Inventory_List {
    const PAYLOAD_COMMAND = 'notfound';
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('notfound', 'BitWire_Message_NotFound');

?>