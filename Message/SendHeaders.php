<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_SendHeaders extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'sendheaders';
    const PAYLOAD_HAS_DATA = false;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('sendheaders', 'BitWire_Message_SendHeaders');

?>