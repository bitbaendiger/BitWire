<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_GetAddresses extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'getaddr';
    const PAYLOAD_HAS_DATA = false;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('getaddr', 'BitWire_Message_GetAddresses');

?>