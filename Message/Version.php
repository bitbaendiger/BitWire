<?PHP

  // Check Integer-Size
  if (PHP_INT_MAX <= 0x7FFFFFFF)
    trigger_error ('BitWire requires a 64-Bit PHP to work properly and reliable');
  
  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Version extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'version';
    
    /* Well-known services */
    const SERVICE_NONE    = 0x00; // "Unnamed" on Bitcoin-reference
    const SERVICE_NETWORK = 0x01;
    
    /* Supported version */
    private $Version = 70015;
    
    /* Supported services */
    private $SupportedServices = BitWire_Message_Version::SERVICE_NONE;
    
    /* Current timestamp */
    private $Timestamp = null;
    
    /* Services expected to be supported at peer (since 106) */
    private $PeerServices = BitWire_Message_Version::SERVICE_NETWORK;
    
    /* Expected address of our peer (since 106) */
    private $PeerAddress = '::ffff:7f00:0001';
    private $PeerPort = 8333;
    
    /* Actual announced supported services */
    private $Services = BitWire_Message_Version::SERVICE_NONE;
    
    /* Expected local address */
    private $Address = '::ffff:7f00:0001';
    private $Port = 0x000;
    
    /* Nonce for this version */
    private $Nonce = 0x0000000000000000;
    
    /* Connecting User-Agent (merely since 60000) */
    private $UserAgent = '/BitWire:0.2/';
    
    /* Known height of block-chain */
    private $StartHeight = 0x00000000;
    
    /* Relaying is supported (since 70001 / BIP37) */
    private $Relay = null;
    
    // {{{ __construct
    /**
     * Create a new version-message
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version of this message
     * 
     * @access public
     * @return int
     **/
    public function getVersion () {
      return $this->Version;
    }
    // }}}
    
    // {{{ setAddress
    /**
     * Set our local address as human-readable string
     * 
     * @param string $Address
     * 
     * @access public
     * @return void
     **/
    public function setAddress ($Address) {
      $this->Address = $Address;
    }
    // }}}
    
    // {{{ setPort
    /**
     * Set our local port
     * 
     * @param int $Port
     * 
     * @access public
     * @return void
     **/
    public function setPort ($Port) {
      $this->Port = (int)$Port;
    }
    // }}}
    
    // {{{ setPeerAddress
    /**
     * Set expected peer-address as human-readable string
     * 
     * @param stirng $Address
     * 
     * @access public
     * @return void
     **/
    public function setPeerAddress ($Address) {
      $this->PeerAddress = $Address;
    }
    // }}}
    
    // {{{ setPeerPort
    /**
     * Set the expected peer-port
     * 
     * @param int $Port
     * 
     * @access public
     * @return void
     **/
    public function setPeerPort ($Port) {
      $this->PeerPort = (int)$Port;
    }
    // }}}
    
    // {{{ setStartHeight
    /**
     * Set height of known block-chain
     * 
     * @param int $Height
     * 
     * @access public
     * @return void
     **/
    public function setStartHeight ($Height) {
      $this->StartHeight = (int)$Height;
    }
    // }}}
    
    // {{{ getUserAgent
    /**
     * Retrive the useragent
     * 
     * @access public
     * @return string
     **/
    public function getUserAgent () {
      return $this->UserAgent;
    }
    // }}}
    
    // {{{ setUserAgent
    /**
     * Set a user-agent
     * 
     * @param string $Agent
     * 
     * @access public
     * @return bool
     **/
    public function setUserAgent ($Agent) {
      $this->UserAgent = $Agent;
      
      return true;
    }
    // }}}
    
    // {{{ parseData
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parseData ($Data) {
      // Retrive length of data
      $Length = strlen ($Data);
      
      // Make sure the min length is met
      if ($Length < 59)
        return false;
      
      // Parse first bits
      $Values = unpack ('Vversion/Pservices/Ptimestamp', substr ($Data, 0, 20));
      
      $this->Version = $Values ['version'];
      $this->SupportedServices = $Values ['services'];
      $this->Timestamp = $Values ['timestamp'];
      
      // Parse additional peer-addresses
      if ($this->Version >= 106) {
        // Re-Check the length
        if ($Length < 85)
          return false;
        
        $Values = unpack ('Pservices/a16address/nport', substr ($Data, 20, 26));
        $this->PeerServices = $Values ['services'];
        $this->PeerAddress = qcEvents_Socket::ip6fromBinary ($Values ['address']);
        $this->PeerPort = $Values ['port'];
        
        $Data = substr ($Data, 46);
      } else
        $Data = substr ($Data, 20);
      
      // Parse additional standard-fields
      $Values = unpack ('Pservices/a16address/nport/Pnonce', substr ($Data, 0, 34));
      $this->Services = $Values ['services'];
      $this->Address = qcEvents_Socket::ip6fromBinary ($Values ['address']);
      $this->Port = $Values ['port'];
      $this->Nonce = $Values ['nonce'];
      
      if (($this->UserAgent = $this::readCompactString ($Data, $Length, 34)) === false)
        return false;
      
      // Truncate the buffer and recheck the length
      $Data = substr ($Data, $Length + 34);
      $Length = strlen ($Data);
      
      if ($Length < 4)
        return false;
      
      if ($Length > 4)
        $Values = unpack ('Vheight/Crelay', $Data);
      else
        $Values = unpack ('Vheight', $Data);
      
      $this->StartHeight = $Values ['height'];
      
      if ($Length > 4)
        $this->Relay = ($Values ['relay'] != 0x00);
      else
        $this->Relay = null;
      
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
      # TODO: We use unsigned integers here because of speed, but spec is different
      return
        pack ('VPP', $this->Version, $this->SupportedServices, ($this->Timestamp !== null ? $this->Timestamp : time ())) .
        ($this->Version >= 106 ? pack ('Pa16n', $this->PeerServices, qcEvents_Socket::ip6toBinary ($this->PeerAddress), $this->PeerPort) : '') .
        pack ('Pa16nP', $this->Services, qcEvents_Socket::ip6toBinary ($this->Address), $this->Port, $this->Nonce) .
        $this::toCompactSize (strlen ($this->UserAgent)) . $this->UserAgent .
        pack ('V', $this->StartHeight) .
        (($this->Version >= 70001) && ($this->Relay !== null) ? pack ('C', $this->Relay ? 0x01 : 0x00) : '');
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('version', 'BitWire_Message_Version');

?>