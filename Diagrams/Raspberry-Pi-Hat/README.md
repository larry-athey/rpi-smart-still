If you refer to the picture of the assembled 1.2 board here, you can see that the logic level shifters have been omitted and bypassed. The reason being, the output of the first shifter in U16 wasn't connected and the L298N was connected to the wrong pin. The version 1.1 boards also have this same problem. This is an easy fix, not a show stopper by any means.

Some L298N driver boards may work with 3.3 volt logic and won't require the logic level shifters, you will just need to test your boards. My original boards had 5 volt zener diodes on the inputs and required 5 volt logic, my new ones don't have these diodes on the inputs.

Also note, you need to make sure that the grounds between the terminals U2 and U6 are connected to each other, or your Raspberry Pi won't have a ground and won't power up. You can easily just solder a jumper wire between the two terminals if you want to.
