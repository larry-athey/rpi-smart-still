For those who ordered version 1.1 of these printed circuit boards, there was a flaw in the design that needs to be fixed by cutting some foil traces and installing 5 jumper wires.

1. Cut the +5 volt supply line connecting to both ends of the J1 terminal bus (valve limit switch terminals).
2. Cut the foil to separate all 4 terminals in J1.
3. Solder in a jumper wire to reconnect the two open ends of the +5 volt supply line.
4. Solder jumper wires from each terminal to the end of R1/R2/R3/R7 that connect to the GPIO bus pins 18/19/22/23.

Refer to the photo Hacked_RPI-Smart-Still-V1.1_2024-08-17.jpg in this folder for a visual reference. I intentionally used wires of excess length to make the paths easier to follow.

You will also notice by the photo of the top of the assembled hat, I use a 5 volt buck regulator instead of the LM7805 and the filter inductor to reduce heat and bulk. This is now the standard design in the version 1.2 boards.

In version 1.2 of these boards, the red LEDs on the relays have gone away. You should install green LEDs in the place of the red relay LEDs and leave the original green LED mounting pads open. This will make version 1.1 boards functionally identical to the version 1.2 boards.
