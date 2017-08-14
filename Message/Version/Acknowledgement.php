<?PHP

  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Version_Acknowledgement extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'verack';
    const PAYLOAD_HAS_DATA = false;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('verack', 'BitWire_Message_Version_Acknowledgement');

?>