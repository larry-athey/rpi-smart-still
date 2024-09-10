For those who ordered version 1.1 of these printed circuit boards, there was a flaw in the design that needs to be fixed by cutting some foil traces and installing 5 jumper wires.

1. Cut the +5 volt line connecting to both ends of the J1 terminal bus (valve limit switch terminals).
2. Cut the foil to separate all 4 terminals in J1.
3. Solder in a jumper wire to reconnect the two open ends of the +5 volt supply line.
4. Solder jumper wires from each terminal to the end of R1/R2/R3/R7 that connect to the GPIO bus pins 18/19/22/23.

In version 1.2 of these boards, the red LEDs on the relays have gone away. You should install green LEDs in the place of the red relay LEDs and leave the original greem LED mounting pads open.
