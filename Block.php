<?PHP

  require_once ('BitWire/Hash.php');
  require_once ('BitWire/Transaction.php');
  require_once ('BitWire/Message/Payload.php');
  require_once ('BitWire/Interface/Hashable.php');
  
  class BitWire_Block implements BitWire_Interface_Hashable {
    /* Type of this block */
    const TYPE_POW = 0;
    const TYPE_POS = 1;
    
    private $Type = BitWire_Block::TYPE_POW;
    
    /* Version of this block */
    private $Version = 0x00000000;
    
    /* Hash of previous block */
    private $PreviousHash = null;
    
    /* Hash of the merkle root */
    private $MerkleRootHash = null;
    
    /* Timestamp when this block was created */
    private $Timestamp = 0x00000000;
    
    /* Difficulty to generate this block */
    private $TargetThreshold = 0x00000000;
    
    /* Nonce of this block */
    private $Nonce = 0x00000000;
    
    /* Signature of PoS-Blocks */
    private $Signature = '';
    
    /* Transactions stored on this block */
    private $Transactions = array ();
    
    /* Do transactions have comments */
    private $hasTxComments = false;
    
    // {{{ __construct
    /**
     * Create a new block
     * 
     * @param enum $Type (optional)
     * @param bool $hasTxComments (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Type = null, $hasTxComments = null) {
      $this->PreviousHash = new BitWire_Hash;
      $this->MerkleRootHash = new BitWire_Hash;
      
      if ($Type !== null)
        $this->Type = $Type;
      
      if ($hasTxComments !== null)
        $this->hasTxComments = $hasTxComments;
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for var_dump() of this object
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      $Info = array (
        'version' => sprintf ('0x%08x', $this->Version),
        'timestamp' => date ('Y-m-d H:i:s', $this->Timestamp),
        'hash' => strval ($this->getHash ()),
        'hash_previous' => strval ($this->getPreviousHash ()),
        'merkle_root' => strval ($this->getMerkleRootHash ()),
        'threshold' => $this->TargetThreshold,
        'nonce' => $this->Nonce,
        '#txs' => count ($this->Transactions),
      );
      
      if ($this->Type == $this::TYPE_POS)
        $Info ['Signature'] = bin2hex ($this->Signature);
      
      return $Info;
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version of this block
     * 
     * @access public
     * @return int
     **/
    public function getVersion () {
      return $this->Version;
    }
    // }}}
    
    // {{{ setVersion
    /**
     * Set the version of this block
     * 
     * @param int $Version
     * 
     * @access public
     * @return void
     **/
    public function setVersion ($Version) {
      $this->Version = (int)$Version;
    }
    // }}}
    
    // {{{ getPreviousHash
    /**
     * Retrive the hash of the previous block
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getPreviousHash () {
      return $this->PreviousHash;
    }
    // }}}
    
    // {{{ setPreviousHash
    /**
     * Set hash of previous block
     * 
     * @param BitWire_Hash $Hash
     * 
     * @access public
     * @return void
     **/
    public function setPreviousHash (BitWire_Hash $Hash) {
      $this->PreviousHash = $Hash;
    }
    // }}}
    
    // {{{ getMerkleRootHash
    /**
     * Retrive the (stored) hash of the merkle root
     * 
     * @param bool $Recalculate (optional) Recalculate the hash depending on stored transactions
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getMerkleRootHash ($Recalculate = false) {
      // Check wheter to return stored hash
      if (!$Recalculate)
        return $this->MerkleRootHash;
      
      // Prepare transactions
      $Transactions = $this->getTransactions ();
      $Count = count ($Transactions);
      
      foreach ($Transactions as $i=>$Transaction)
        $Transactions [$i] = $Transaction->getHash ();
      
      // Create merkle-root
      while ($Count > 1) {
        $Next = array ();
        
        for ($i = 0, $j = 1; $i < $Count; $i = ++$j, $j++)
          $Next [] = new BitWire_Hash ($Transactions [$i]->toBinary (true) . $Transactions [($j < $Count ? $j : $i)]->toBinary (true));
        
        $Transactions = $Next;
        $Count = ceil ($Count / 2);
      }
      
      // Return the result
      return $Transactions [0];
    }
    // }}}
    
    // {{{ setMerkleRootHash
    /**
     * Store a new merkle-root-hash
     * 
     * @param BitWire_Hash $Hash
     * 
     * @access public
     * @return void
     **/
    public function setMerkleRootHash (BitWire_Hash $Hash) {
      $this->MerkleRootHash = $Hash;
    }
    // }}}
    
    // {{{ getTimestamp
    /**
     * Retrive the timestamp when this block was created
     * 
     * @access public
     * @return int
     **/
    public function getTimestamp () {
      return $this->Timestamp;
    }
    // }}}
    
    // {{{ setTimestamp
    /**
     * Store a timestamp for this block
     * 
     * @param int $Timestamp
     * 
     * @access public
     * @return void
     **/
    public function setTimestamp ($Timestamp) {
      $this->Timestamp = (int)$Timestamp;
    }
    // }}}
    
    // {{{ getThreshold
    /** 
     * Retrive the target threshold of this block
     * 
     * @access public
     * @return int
     **/
    public function getThreshold () {
      return $this->TargetThreshold;
    }
    // }}}
    
    // {{{ setThreshold
    /**
     * Set the target threshold for this block
     * 
     * @param int $Threshold
     * 
     * @access public
     * @return void
     **/
    public function setThreshold ($Threshold) {
      $this->TargetThreshold = (int)$Threshold;
    }
    // }}}
    
    // {{{ getNonce
    /**
     * Retrive the nonce of this block
     * 
     * @access public
     * @return int
     **/
    public function getNonce () {
      return $this->Nonce;
    }
    // }}}
    
    // {{{ setNonce
    /**
     * Set the nonce for this block
     * 
     * @param int $Nonce
     * 
     * @access public
     * @return void
     **/
    public function setNonce ($Nonce) {
      $this->Nonce = (int)$Nonce;
    }
    // }}}
    
    // {{{ getTransactions
    /**
     * Retrive all transactions from this block
     * 
     * @access public
     * @return array
     **/
    public function getTransactions () {
      return $this->Transactions;
    }
    // }}}
    
    // {{{ addTransaction
    /**
     * Append a transaction to this block
     * 
     * @param BitWire_Transaction $Tx
     * 
     * @access public
     * @return void
     **/
    public function addTransaction (BitWire_Transaction $Tx) {
      // Append to transactions
      $this->Transactions [] = $Tx;
      
      // Refresh merkle-root-hash
      $this->setMerkleRootHash ($this->getMerkleRootHash (true));
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive a hash for this object
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () {
      return new BitWire_Hash ($this->getHeader ());
    }
    // }}}
    
    // {{{ getHeader
    /**
     * Retrive the binary header of this block
     * 
     * @access public
     * @return string
     **/
    public function getHeader () {
      return pack ('Va32a32VVV', $this->Version, $this->PreviousHash->toBinary (true), $this->MerkleRootHash->toBinary (true), $this->Timestamp, $this->TargetThreshold, $this->Nonce);
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Length (optional)
     * 
     * @access public
     * @return bool
     **/
    public function parse (&$Data, &$Offset, $Length = null) {
      // Check the length of input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Parse the header
      $tOffset = $Offset;
      
      if ((($Version = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null) ||
          (($Hash = BitWire_Message_Payload::readChar ($Data, $tOffset, 32, $Length)) === null) ||
          (($MerkleRoot = BitWire_Message_Payload::readChar ($Data, $tOffset, 32, $Length)) === null) ||
          (($Timestamp = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null) ||
          (($Threshold = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null) ||
          (($Nonce = BitWire_Message_Payload::readUInt32 ($Data, $tOffset, $Length)) === null) ||
          (($Count = BitWire_Message_Payload::readCompactSize ($Data, $tOffset, $Length)) === null))
        return false;
      
      // Check if there is more than just the header
      if ($tOffset != $Length) {
        // Try to read all transactions
        $Transactions = array ();
        
        for ($i = 0; $i < $Count; $i++) {
          // Create a new transaction
          $Transaction = new BitWire_Transaction ($this->Type, $this->hasTxComments);
          
          // Try to parse the transaction
          $pOffset = $tOffset;
          
          if (!$Transaction->parse ($Data, $tOffset, $Length)) {
            trigger_error ('Failed to read transaction #' . $i);
            
            return false;
          }
          
          // Double-check the transaction
          if (defined ('BITWIRE_DEBUG') && BITWIRE_DEBUG) {
            $Binary = $Transaction->toBinary ();
            $Original = substr ($Data, $pOffset, $tOffset - $pOffset);
            
            if (strcmp ($Binary, $Original) != 0) {
              echo
                'DEBUG: Binary of Transaction Payload "', $this->parsedCommand, '" differs:', "\n",
                '  Length: in=', strlen ($Original), ' out=', strlen ($Binary), "\n",
                '  MD5:  in=', md5 ($Original), "\n", 
                '       out=', md5 ($Binary), "\n\n";
              
              // Check for dump-functions
              if (function_exists ('dump_compare')) {
                dumpCompare ($Original, $Binary);
                echo "\n";
              } elseif (function_exists ('dump')) {
                dump ($Original);
                dump ($Binary);
                echo "\n";
              }
            }
          }
          
          // Push to transactions
          $Transactions [] = $Transaction;
          $tOffset += $Size;
        }
       
        // Read Signature of PoS-Blocks
        if ($this->Type != $this::TYPE_POS)
          $Signature = null;
        elseif (($Signature = BitWire_Message_Payload::readCompactString ($Data, $tOffset, $Length)) === null)
          return false;
        
      } else {
        $Transactions = array ();
        $Signature = null;
      }
      
      // Store all values read
      $this->Version = $Version;
      $this->PreviousHash = BitWire_Hash::fromBinary ($Hash, true);
      $this->MerkleRootHash = BitWire_Hash::fromBinary ($MerkleRoot, true);
      $this->Timestamp = $Timestamp;
      $this->TargetThreshold = $Threshold;
      $this->Nonce = $Nonce;
      $this->Transactions = $Transactions;
      $this->Signature = $Signature;
      
      // Push back the offset
      $Offset = $tOffset;
      
      // Check if all data was consumed
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
      // Generate the header
      $Buffer =
        $this->getHeader () .
        BitWire_Message_Payload::toCompactSize (count ($this->Transactions));
      
      // Write out transactions
      foreach ($this->Transactions as $Transaction)
        $Buffer .= $Transaction->toBinary ();
      
      // Append signature
      if ($this->Type == $this::TYPE_POS)
        $Buffer .= BitWire_Message_Payload::toCompactString ($this->Signature);
      
      return $Buffer;
    }
    // }}}
    
    // {{{ validate
    /**
     * Try to validate this block
     * 
     * @access public
     * @return bool
     **/
    public function validate () {
      // Make sure time is not too far in future
      if ($this->Timestamp > time () + 7200)
        return false;
      
      # TODO: Check the time in some other way too?!
      
      // Compare hash and threshold
      if (extension_loaded ('gmp')) {
        $Max = gmp_mul (gmp_init ($this->TargetThreshold & 0xFFFFFF), gmp_pow (gmp_init (256), ((($this->TargetThreshold >> 24) & 0xFF) - 3)));
        $Act = gmp_init (strval ($this->getHash ()), 16);
        
        if (gmp_cmp ($Act, $Max) > 0)
          return false;
      } else
        trigger_error ('GMP required to validate threshold');
      
      // Compare merkle-root
      return $this->getMerkleRootHash ()->compare ($this->getMerkleRootHash (true));
    }
    // }}}
  }

?>