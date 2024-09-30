If you refer to the picture of the assembled 1.2 board here, you can see that the logic level shifters have been omitted and bypassed. The reason being, the output of the first shifter in U16 wasn't connected and the L298N was connected to the wrong pin.

Some L298N driver boards may work with 3.3 volt logic and won't require the logic level shifter, you will just need to test yours. My original boards had 5 volt zener diodes on the input and required 5 volt logic, my new ones don't have these diodes on the inputs.

Also note, you need to make sure that the grounds between the terminals U2 and U6 are connected to each other, or your Raspberry Pi won't have a ground and won't power up.
