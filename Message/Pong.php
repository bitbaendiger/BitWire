<?PHP

  require_once ('BitWire/Message/Ping.php');
  
  class BitWire_Message_Pong extends BitWire_Message_Ping {
    const PAYLOAD_COMMAND = 'pong';
    
    // {{{ __construct
    /**
     * Create a new pong
     * 
     * @param BitWire_Message_Ping $Ping (optional) The ping this pong is for
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Message_Ping $Ping = null) {
      if ($Ping)
        $this->setNonce ($Ping->getNonce ());
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('pong', 'BitWire_Message_Pong');

?>