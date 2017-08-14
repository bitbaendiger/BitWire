<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_MemPool extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'mempool';
    const PAYLOAD_MIN_VERSION = 60002;
    const PAYLOAD_HAS_DATA = false;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('mempool', 'BitWire_Message_MemPool');

?>