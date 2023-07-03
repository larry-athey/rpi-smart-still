# RPI-Smart-Still
Raspberry PI and Arduino/ESP32 powered smart still controller and monitor. Designed around the Still Spirits T-500 column and boiler, but can be easily added to any other custom built still.

Let me start off by saying that I'm no master distiller and don't pretend to be one. YouTube and other social media sites are already full of those kinds of frauds *(I'm talking about you Cyrus Mason Jar, Still'n The Clear charlatan)*. I'm a software engineer with a strong emphasis in the field of automation. I'm only a hobby distiller, but I know very well what needs to be done in order to automate a still and maintain a targeted output proof. It's all a matter of temperature control and monitoring the result.

The main focus of this project is to read temperatures and control cooling water via servo controlled needle valves while monitoring the output proof. I may add control for electric boiler heating once I obtain more info from other distillers who utilize these devices. Thus far, I haven't found any need for one with the Still Spirits boiler and haven't studied them at all.

The T-500 column in its default configuration that Still Spirits designed it is a one-trick-pony. Reflux only, no pot still flavor, pretty much only good for making neutral spirits. Once a person controls the condenser and dephleg cooling separately, it's then possible to run a T-500 in pot still mode and have a more controlled reflux system. This allows you to target a higher proof than the constantly declining proof in pot still mode, while still retaining pot still flavor.

This is kind of a juggling act to do manually, no matter what still you happen to use. But it's not hard to automate this and stop your run once the still can no longer deliver the target proof. From that point, you can instantly throw the still into full reflux mode to finish stripping out all remaining ethanol which can then be added to another run.

**This project is a work in progress and components of it will be uploaded here as I feel they are ready for prime-time.**

Currently doing physical design debugging of an ESP32 powered hydrometer that uses a reference weight suspended from a load cell in a laborator overflow cup. The distillate proof changes the buoyancy of the reference weight and seems to be more accurate than the Arduino hydrometer, but still works out to be about the same price to build. It also uses a DS18B20 temperature sensor which yields a far more accurate temperature reading. The amount of code for the required libraries is too great for an Arduino Nano, so an ESP32 is used instead.
