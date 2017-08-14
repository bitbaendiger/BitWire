# BitWire
Basic implementation of the Bitcoin P2P Wire Protocol. If you use it, expect
to blow up the Bitcoin Network.

## Dependencies
BitWire relies on a asynchronous PHP-Framework called `qcEvents`. Make sure
to have it in place if you try to use BitWire. The `BitWire_Peer`-Class acts
as a consumer on top of qcEvents's Sockets (or any other kind of sources, but
only sockets make sense at the moment):

~~~ {.php}
  $Base = new qcEvents_Base;
  $Socket = new qcEvents_Socket;
  $Peer = new BitWire_Peer;

  $Socket->connect ('', 8333, $Socket::TYPE_TCP);
  $Socket->pipeStream ($Peer);

  $Base->loop ();
~~~

## Debuging
There is a constant called `BITWIRE_DEBUG`. When set to `true` BitWire will
start to output debugging-information related to its work.

Most notably: Whenever Messages/Payloads are received BitWire will try to
re-encode these and compare the output with its original input to make sure
that BitWire is able to create a bitwise identical representation.

## Disclaimer
This software is completly on its own. We are not affiliated or related to
any bitcoin mining pool or bitcoin exchange. The purpose of this software is
to gather statistical data out of the bitcoin network and to use it for
personal purposes.

We are NOT affiliated or related in any way to bitwire.co or bitwire.biz.
These are completely independant projects and do not have any relation with
us.
