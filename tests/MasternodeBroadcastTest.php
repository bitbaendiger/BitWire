<?php

  declare (strict_types=1);

  use PHPUnit\Framework\TestCase;

  final class MasternodeBroadcastTest extends TestCase {
    public function testVerifyHashSignedBroadcasts () : void {
      $broadcasts = [
        '7abce94996a807667d32b074bd0c87a6dc17013c5752fa6f82fee63667eb2cec1c00000000ffffffff2a0104f9003a39870000000000000048341521030aa634feaccb55facd066bdc73817f152d43b103af267192b5d510b12100910e41044e60a91aa2bbcfcb2757bd650f0cbde611ea5f9ebfac8aad6050804e007c0ffd1ea2ac724618e55f2db599ed93b512d001db385b9cc74ef76d3d23de8d0b8a81411fa57684b6333b3308a55c9cc4affb6eeeaa9428301a3d488e02df357e4f20c9953af03638b960aec689fe40924c241cfea7b6796f921dcf3fa6440962a6c2ab4f60a3576000000000401901007abce94996a807667d32b074bd0c87a6dc17013c5752fa6f82fee63667eb2cec1c00000000ffffffffb417dad584fc7ed686ed61b8926e9a23d2eceaa48ad8efc559c40fbda903f24119c3576000000000411bfdc73297ff405342be0d448b4714ff970beebc40995e06867f0ad90f68f004ec616d88ea83bad3ac4e9f0ec4eb0879d95e9e46beaf186980f566aa84e7f009a90100000001000000',
        '753c4eb376ac52e508f84277069195696848ff0dd0ff0ac5278863434c33e7a00000000000ffffffff2a0104f9003a3985000000000000007f341521037548f30a83e944089252a971dc2e3dc8f15fda7566fe10df3ee6c664c8641fac410473da63f3943a5ec94ac54720dc320cf73c539f640cf459ea3beea48821de0555a207fa6472fecf0c9ef42548de3ad390fc1831a463035733924b0d1654655b814120327b11760f7b966cd8ad22740cdc763d386bde457efda110c183200eddc56daa55365eab3b59a77265165b3cdb8569797403f7405835347131e58b73b6deaf448e5356600000000040190100753c4eb376ac52e508f84277069195696848ff0dd0ff0ac5278863434c33e7a00000000000ffffffffdafb13c0bcd59750837820d695a4cdb88ced933f7ae54de133d85905404b45cdfdcb576000000000411cbee1e159baddfac5103d61ae7ea863e98c078596361218a97ec7e28978ca968853fbe36258883889b36ff98f5559ea1d645034857f797d7708cb6b23e7cf20560100000001000000',
        '2b6a02db46c0cd2b32fe9b4ec0923ebeb46c00d3490332f2b128d12d152ab1e50000000000ffffffff00000000000000000000ffffa75667a3c12421025138dd6e221b0106cd17b412536d7c5be413abbbebb02cdf6fe58677a1f55f8a410449199f40f8a8ab5dca18c66967a32e5415844a719a534f7624362c665d9109ebe678eccdcdb8dbb6be77f81ffd5d2ca980fdd8990278a10802957a0bac20cab2411f0d670c43b3fe1554031590f1e92ba256bc6e43064898804e109d665a7810fac440d4178c416a90b4049ed5327e704c09c6df7d9d27bcae179eb23daadeb3fd786e694f60000000000a1501002b6a02db46c0cd2b32fe9b4ec0923ebeb46c00d3490332f2b128d12d152ab1e50000000000ffffffff5d0a92a87252c60bd5d73c013afd52bbb7e9d4da0e8060ffd33c6b19c8e10beabdcd576000000000411b120c93267df2e1d18edde4e9741fe6050b456db3e4796ce68f2f28f72870aa7a16c347c0b951e9c033f7d1eb9675cf8b6a381ec197d5267b1c7cc6d49083805a0000000000000000',
        '04c2b2e8b1bb740d2db57143c90fa9a0b495dc8a879e1e10cbc107521a92653b0000000000ffffffff2a02c20720289856299b000000000009c12421035b198b920ca2f2c8413646ba4135df37d08c37f54223f29b78654f77601bea9941049facd9221d513b1865a785dc272aeacb0fda44bf3e89eb0c85aee61066e39f8114eceace31830351f007a4cdb602659e80215996452ab458823b525224d166c4412076993a4ed76ca87b6582051e4b59a0181d7bc0e78f45441e53872af8f982735557d5a84c4be4d58c7511a570b94ca3c31b1253c1f293ed0d8c87746ef69a6835cb015560000000000a15010004c2b2e8b1bb740d2db57143c90fa9a0b495dc8a879e1e10cbc107521a92653b0000000000ffffffff6bcd7b87a69d9685617643396ba0a44d7d9c26242d76569141b23f1f42d3b92be9cd576000000000411be16e3f559017a5aba91777f253ca376be7691a3eb2ebdc44755114d610e70a2550212e45d2a121cf3a82ad92e827806b760e49eef9db8b8380e5ff9f72cb17ff0000000000000000',
        'b6c30f9b14365186a0c3524ff67f41e85466b3fd7896e099529271624d001a1f0100000000ffffffff2a02c20720289856299b000000000013c12421030e7ee29ee416e530fd651e725bcae7b91bdce71cb8aad112e3801e823258efd04104976180be380341e288d3d49f1f69d4073a90a9b01cc40c26cf65f41ee4e51b14b7b78b2f6d1ceca061143813627e874d8cdd9352e4f47e4a977decffd6399528411fc275228b12c0d194dd4a491612b59f4986c3b36bb18edc746e41e33294b3b05a2c2a4638a33a71cbf300a3988c2a49a6825e511e04208f801c3f2f4e986abf56e3015560000000000a150100b6c30f9b14365186a0c3524ff67f41e85466b3fd7896e099529271624d001a1f0100000000ffffffff2989da1458db553b2e7cfb6b64dc4be658147466a2c2f7eda2bb1fc1e6a766878e2b596000000000411c9efcad436a794f72627ace2a2208adfad6b189a9a75a818f96270d080f370b2d6fc8ebddc596216b81aa02f3a739da490299ab3b05a471a48135f2b47db23c440000000000000000',
      ];
      
      foreach ($broadcasts as $broadcast) {
        $mnBroadcast = new \BitBaendiger\BitWire\Message\Masternode\Broadcast ();
        $mnBroadcast->parse (hex2bin ($broadcast));
        
        $this->assertTrue ($mnBroadcast->verify ());
      }
    }
  }