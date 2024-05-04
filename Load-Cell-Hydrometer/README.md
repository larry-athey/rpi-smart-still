# Load Cell Hydrometer

I am currently doing a physical re-design of this device to make it easier to assemble and operate. The PDF file in the /Diagrams/ directory will be replaced, so I wouldn't put too much focus on it.

The code here works, but is still a work in progress since the flow monitor is still a bit buggy and is being replaced with a hall effect water flow sensor. https://www.amazon.com/gp/product/B07RF57QF8/

The rest of the re-design plan is to make it easier to service the unit and flash the ESP32 without having to disassemble it.

The whole physical structure will also be 3D printed in order to simplify the build process.

**NOTE:** _As requested, I am also developing a device to electronically read the floating height of a glass hydrometer in a normal parrot, this will be done with a VL53L0X LIDAR sensor and a lightweight reflector at the top of the hydrometer. I realize that George Duncan already did something similar years ago as closed source project, but I'm not real keen on his design which requires the hydrometer to be hidden inside of a PVC tube. I believe that I can do this with the hydrometer out in the open and the LIDAR sensor at the bottom with the unit attached to the side of the parrot._
