Printed circuit board design by [James "The Doc"](https://www.youtube.com/@TheDocChannel) of Ireland. This is for either a wide or standard format 38 pin ESP32 board. C1 value isn't labeled on the board, 470uf at 15 volts would be a good value here.

**HELPFUL NOTES**
- The 1 meg resistor _(R1)_ actually works better when replaced by two of them in series _(2 megs)_
- The wires to the flow sensor capacitor plates should be shielded inside of a grounded steel braid
- Choose a low profile capacitor for C1 so it doesn't block the USB port on the ESP32
- An absolutely dry flow sensor will yield erratic and unreliable readings on the controller dashboard
- You should periodically flush the flow sensor with vinegar to clean the capacitor plates
