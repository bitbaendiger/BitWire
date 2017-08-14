<?PHP

  require_once ('BitWire/Message/GetBlocks.php');
  
  class BitWire_Message_GetHeaders extends BitWire_Message_GetBlocks {
    const PAYLOAD_COMMAND = 'getheaders';
    const PAYLOAD_MIN_VERSION = 31800;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('getheaders', 'BitWire_Message_GetHeaders');

?>