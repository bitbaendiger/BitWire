<?PHP

  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Message/Inventory/List.php');
  require_once ('BitWire/Hash.php');
  
  class BitWire_Message_Inventory extends BitWire_Message_Inventory_List {
    const PAYLOAD_COMMAND = 'inv';
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('inv', 'BitWire_Message_Inventory');

?>