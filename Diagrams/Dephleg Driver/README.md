# Dephleg Driver

This is an optional replacement for the motorized ball valve that manages the cooling water flow to the dephlegmator. The reason for this option is because many people don't run on public utility water pressure. When people use impeller driven pumps to run their cooling water, in every case I've seen, there isn't enough pressure to continue pushing water through the ball valves if they're choked down to 30% or less.

The problem with this is that there becomes no balance between too much reflux where you get no output from your still and simply switching right back to pot still mode. The Dephleg Driver solves this problem by replacing the ball valve with a PWM driven peristaltic pump. These naturally have a low flow rate simply due to the nature of their design but they have plenty of pressure to maintain the water flow over pretty much any distance you can throw at it.

No modifications to the RPi hat are needed beyond unplugging the logic level shifter chip closest to the L298N motor driver. The Dephleg Driver has only 7 wires, two connect to a pair of open pins on the logic level shifter socket, two to the original ball valve limit switch terminals, one to the +5 volt terminal, one to the +12 volt terminal, and one to a ground terminal. Connect the water lines and the upgrade is complete, all water flow problems solved.
