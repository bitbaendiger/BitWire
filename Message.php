<?PHP

  class BitWire_Message {
    /* Well known Bitcore-Networks (see their chainparams.cpp) */
    const BITCOIN_MAIN = 0xF9BEB4D9;
    const BITCOIN_TEST = 0x0B110907;
    const BITCOIN_REG  = 0xFABFB5DA;
    
    const BITMONEY_MAIN = 0xF9CD3B68;
    const BITMONEY_TEST = 0xC7FF2D4F;
    const BITMONEY_REG  = 0xC2FD5C1F;
    
    /* Version of this message */
    private $Version = 70015;
    
    /* Network for this message */
    private $Network = BitWire_Message::BITCOIN_MAIN;
    
    /* Payload of this message */
    private $Payload = null;
    
    /* Buffered data for receiving a message */
    private $Buffer = '';
    
    private $parsedNetwork = null;
    private $parsedCommand = null;
    private $parsedLength = null;
    private $parsedChecksum = null;
    
    // {{{ __construct
    /**
     * Create a new BitWire-Message
     * 
     * @param BitWire_Message_Payload $Payload (optional)
     * @param int $Version (optional)
     * @param int $Network (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Message_Payload $Payload = null, $Version = 70015, $Network = BitWire_Message::BITCOIN_MAIN) {
      $this->Version = $Version;
      $this->Network = $Network;
      
      if ($this->Payload = $Payload)
        $this->Payload->setMessage ($this);
    }
    // }}}
    
    // {{{ consume
    /**
     * Push some data to the message-parser
     * 
     * @param string $Data
     * 
     * @access public
     * @return int
     **/
    public function consume ($Data) {
      // Get the length of the new data
      $bLength = strlen ($this->Buffer);
      $dLength = strlen ($Data);
      $tLength = $bLength + $dLength;
      
      $this->Buffer .= $Data;
      
      // Check if we have the message-header ready
      if ($this->parsedNetwork === null) {
        // Consume all bytes until the buffer is large enough
        if ($tLength < 24)
          return $dLength;
        
        // Unpack the header
        $Header = unpack ('Nnetwork/a12command/Vlength/a4checksum', substr ($this->Buffer, 0, 24));
        $this->Buffer = substr ($this->Buffer, 24);
        $tLength -= 24;
        
        $this->parsedNetwork = $Header ['network'];
        $this->parsedCommand = trim ($Header ['command']);
        $this->parsedLength = $Header ['length'];
        $this->parsedChecksum = $Header ['checksum'];
        $bLength -= 24;
      }
      
      // Check if we have all bytes for the payload
      if ($tLength < $this->parsedLength)
        return $dLength;
      
      // Check the payload
      $Payload = substr ($this->Buffer, 0, $this->parsedLength);
      
      if (strcmp ($this->parsedChecksum, substr (hash ('sha256', hash ('sha256', $Payload, true), true), 0, 4)) != 0) {
        trigger_error ('Failed to validate checksum');
        
        return false;
      }
      
      // Generate Payload
      if (!is_object ($this->Payload = BitWire_Message_Payload::fromString ($this->parsedCommand, $Payload, $this))) {
        trigger_error ('Failed to parse payload');
        
        return false;
      }
      
      // Double-Check the result on debug-mode
      if (defined ('BITWIRE_DEBUG') && BITWIRE_DEBUG) {
        // Re-Convert payload to binary
        $pPayload = $this->Payload->toBinary ();
        
        // Compare payloads
        if (strcmp ($Payload, $pPayload) != 0) {
          echo
            'DEBUG: Binary of parsed Payload "', $this->parsedCommand, '" differs:', "\n",
            '  Length: in=', strlen ($Payload), ' out=', strlen ($pPayload), "\n",
            '  MD5:  in=', md5 ($Payload), "\n", 
            '       out=', md5 ($pPayload), "\n\n";
          
          // Check for dump-functions
          if (function_exists ('dump_compare')) {
            dumpCompare ($Payload, $pPayload);
            echo "\n";
          } elseif (function_exists ('dump')) {
            dump ($Payload);
            dump ($pPayload);
            echo "\n";
          }
        }
      }
      
      // Get the number of newly consumed bytes
      $Result = $this->parsedLength - $bLength;
      
      // Reset
      $this->Buffer = $this->parsedNetwork = $this->parsedCommand = $this->parsedLength = $this->parsedChecksum = null;
      
      return $Result;
    }
    // }}}
    
    // {{{ isReady
    /**
     * Check if the message is ready for processing
     * 
     * @access public
     * @return bool
     **/
    public function isReady () {
      return is_object ($this->Payload);
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version set for this message (may be NULL)
     * 
     * @access public
     * @return int
     **/
    public function getVersion () {
      return $this->Version;
    }
    // }}}
    
    // {{{ getCommand
    /**
     * Retrive the command of this message
     * 
     * @access public
     * @return string
     **/
    public function getCommand () {
      if (!$this->Payload)
        return;
      
      return $this->Payload->getCommand ();
    }
    // }}}
    
    // {{{ getPayload
    /**
     * Retrive the payload of this message
     * 
     * @access public
     * @return BitWire_Message_Payload
     **/
    public function getPayload () {
      return $this->Payload;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this message into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      // Make sure we have a payload assigned
      if (!$this->Payload)
        return false;
      
      // Retrive the payload
      $Payload = $this->Payload->toBinary ();
      
      // Generate binary message
      return
        pack ('Na12V', $this->Network, $this->Payload->getCommand (), strlen ($Payload)) .
        ($this->Version >= 209 ? substr (hash ('sha256', hash ('sha256', $Payload, true), true), 0, 4) : '') .
        $Payload;
    }
    // }}}
    
  }

?>