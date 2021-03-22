<?php

  /**
   * BitWire - Wire Message
   * Copyright (C) 2017-2021 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  declare (strict_types=1);

  namespace BitBaendiger\BitWire;
  
  class Message {
    /* Debug message-parser */
    public static $debugMessages = false;
    
    /* Well known Bitcore-Networks (see their chainparams.cpp) */
    public const BITCOIN_MAIN = 0xF9BEB4D9;
    public const BITCOIN_TEST = 0x0B110907;
    public const BITCOIN_REG  = 0xFABFB5DA;
    
    public const BITMONEY_MAIN = 0xF9CD3B68;
    public const BITMONEY_TEST = 0xC7FF2D4F;
    public const BITMONEY_REG  = 0xC2FD5C1F;
    
    /* Version of this message */
    private $Version = 70015;
    
    /* Network for this message */
    private $Network = Message::BITCOIN_MAIN;
    
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
     * @param Message\Payload $Payload (optional)
     * @param int $Version (optional)
     * @param int $Network (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (Message\Payload $Payload = null, int $Version = null, int $Network = Message::BITCOIN_MAIN) {
      $this->Version = $Version ?? 70015;
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
      if (!is_object ($this->Payload = Message\Payload::fromString ($this->parsedCommand, $Payload, $this))) {
        trigger_error ('Failed to parse payload of ' . $this->parsedCommand);
        
        if (function_exists ('dump'))
          dump ($Payload);
        
        return false;
      }
      
      // Double-Check the result on debug-mode
      if ($this::$debugMessages) {
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
          if (function_exists ('dumpCompare')) {
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
    public function isReady () : bool {
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
    public function getVersion () : int {
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
    public function getCommand () : ?string {
      if (!$this->Payload)
        return null;
      
      return $this->Payload->getCommand ();
    }
    // }}}
    
    // {{{ getPayload
    /**
     * Retrive the payload of this message
     * 
     * @access public
     * @return Message\Payload
     **/
    public function getPayload () : ?Message\Payload {
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
    public function toBinary () : ?string {
      // Make sure we have a payload assigned
      if (!$this->Payload)
        return null;
      
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
