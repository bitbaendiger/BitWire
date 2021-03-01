<?php

  /**
   * BitWire - Block
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
  
  class Block implements ABI\Hashable {
    /* Type of this block */
    public const TYPE_POW = 0;
    public const TYPE_POS = 1;
    
    private $Type = Block::TYPE_POW;
    
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
    private $Transactions = [ ];
    
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
    function __construct (int $Type = null, bool $hasTxComments = null) {
      $this->PreviousHash = new Hash;
      $this->MerkleRootHash = new Hash;
      
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
      $Info = [
        'version' => sprintf ('0x%08x', $this->Version),
        'timestamp' => date ('Y-m-d H:i:s', $this->Timestamp),
        'hash' => strval ($this->getHash ()),
        'hash_previous' => strval ($this->getPreviousHash ()),
        'merkle_root' => strval ($this->getMerkleRootHash ()),
        'threshold' => $this->TargetThreshold,
        'nonce' => $this->Nonce,
        '#txs' => count ($this->Transactions),
      ];
      
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
    public function getVersion () : int {
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
    public function setVersion (int $Version) : void {
      $this->Version = $Version;
    }
    // }}}
    
    // {{{ getPreviousHash
    /**
     * Retrive the hash of the previous block
     * 
     * @access public
     * @return Hash
     **/
    public function getPreviousHash () : Hash {
      return $this->PreviousHash;
    }
    // }}}
    
    // {{{ setPreviousHash
    /**
     * Set hash of previous block
     * 
     * @param Hash $Hash
     * 
     * @access public
     * @return void
     **/
    public function setPreviousHash (Hash $Hash) : void {
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
     * @return Hash
     **/
    public function getMerkleRootHash (bool $Recalculate = false) : Hash {
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
          $Next [] = new Hash ($Transactions [$i]->toBinary (true) . $Transactions [($j < $Count ? $j : $i)]->toBinary (true));
        
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
     * @param Hash $Hash
     * 
     * @access public
     * @return void
     **/
    public function setMerkleRootHash (Hash $Hash) : void {
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
    public function getTimestamp () : int {
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
    public function setTimestamp (int $Timestamp) : void {
      $this->Timestamp = $Timestamp;
    }
    // }}}
    
    // {{{ getThreshold
    /** 
     * Retrive the target threshold of this block
     * 
     * @access public
     * @return int
     **/
    public function getThreshold () : int {
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
    public function setThreshold (int $Threshold) : void {
      $this->TargetThreshold = $Threshold;
    }
    // }}}
    
    // {{{ getNonce
    /**
     * Retrive the nonce of this block
     * 
     * @access public
     * @return int
     **/
    public function getNonce () : int {
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
    public function setNonce (int $Nonce) : void {
      $this->Nonce = $Nonce;
    }
    // }}}
    
    // {{{ getTransactions
    /**
     * Retrive all transactions from this block
     * 
     * @access public
     * @return array
     **/
    public function getTransactions () : array {
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
    public function addTransaction (BitWire_Transaction $Tx) : void {
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
     * @return Hash
     **/
    public function getHash () : Hash {
      return new Hash ($this->getHeader ());
    }
    // }}}
    
    // {{{ getHeader
    /**
     * Retrive the binary header of this block
     * 
     * @access public
     * @return string
     **/
    public function getHeader () : string {
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
     * @return void
     **/
    public function parse (string &$Data, int &$Offset, int $Length = null) : void {
      // Check the length of input
      if ($Length === null)
        $Length = strlen ($Data);
      
      // Parse the header
      $tOffset = $Offset;
      
      $Version    = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      $Hash       = Message\Payload::readHash ($Data, $tOffset, $Length);
      $MerkleRoot = Message\Payload::readHash ($Data, $tOffset, $Length);
      $Timestamp  = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      $Threshold  = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      $Nonce      = Message\Payload::readUInt32 ($Data, $tOffset, $Length);
      $Count      = Message\Payload::readCompactSize ($Data, $tOffset, $Length);
      
      // Check if there is more than just the header
      if ($tOffset != $Length) {
        // Try to read all transactions
        $Transactions = [ ];
        
        for ($i = 0; $i < $Count; $i++) {
          // Create a new transaction
          $Transaction = new Transaction ($this->Type, $this->hasTxComments);
          
          // Try to parse the transaction
          $pOffset = $tOffset;
          
          $Transaction->parse ($Data, $tOffset, $Length);
          
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
        else
          $Signature = Message\Payload::readCompactString ($Data, $tOffset, $Length);
      
      } else {
        $Transactions = [ ];
        $Signature = null;
      }
      
      // Store all values read
      $this->Version = $Version;
      $this->PreviousHash = $Hash;
      $this->MerkleRootHash = $MerkleRoot;
      $this->Timestamp = $Timestamp;
      $this->TargetThreshold = $Threshold;
      $this->Nonce = $Nonce;
      $this->Transactions = $Transactions;
      $this->Signature = $Signature;
      
      // Push back the offset
      $Offset = $tOffset;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      // Generate the header
      $Buffer =
        $this->getHeader () .
        Message\Payload::toCompactSize (count ($this->Transactions));
      
      // Write out transactions
      foreach ($this->Transactions as $Transaction)
        $Buffer .= $Transaction->toBinary ();
      
      // Append signature
      if ($this->Type == $this::TYPE_POS)
        $Buffer .= Message\Payload::toCompactString ($this->Signature);
      
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
    public function validate () : bool {
      // Make sure time is not too far in future
      if ($this->Timestamp > time () + 7200)
        return false;
      
      # TODO: Check the time in some other way too?!
      
      // Compare hash and threshold
      $workTarget = Numeric::fromCompact ($this->TargetThreshold);
      $workActual = Numeric::fromHash ($this->getHash ());
      
      if ($workActual > $workTarget)
        return false;
      
      // Compare merkle-root
      return $this->getMerkleRootHash ()->compare ($this->getMerkleRootHash (true));
    }
    // }}}
  }
