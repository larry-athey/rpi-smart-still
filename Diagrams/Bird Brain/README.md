Printed circuit board design by [James "The Doc"](https://www.youtube.com/@TheDocChannel) of Ireland. This is for either a wide or standard format 38 pin ESP32 board. C1 value isn't labeled on the board, 470uf at 15 volts would be a good value here.

**SIDE NOTES**
- The 1 meg resistor _(R1)_ actually works better when replaced by two of them in series _(2 megs)_
- The two wires to the capacitor plates should be contained inside of a grounded steel braid to shield them
- Choose a low profile capacitor for C1 so it doesn't block the USB port on the ESP32
