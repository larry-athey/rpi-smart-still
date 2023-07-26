# RPi Smart Still
Raspberry PI and Arduino/ESP32 powered smart still controller and monitor. Designed around the Still Spirits T-500 column and boiler, but can be easily added to any other custom built still.

**NO, THERE IS NOTHING ILLEGAL ABOUT THIS PROJECT! IT DOESN'T MAKE YOU DO ILLEGAL THINGS OR PREVENT YOU FROM GETTING A DISTILLATION LICENSE IN THE USA! THIS IS ONLY A TOOL!**

Let me start off by saying that I'm no master distiller and don't pretend to be one. YouTube and other social media sites are already full of those kinds of self-idolizing hacks and frauds *(for example, Cyrus Mason Jar and "Windsong", the Still'n The Clear charlatans)*. I'm a software engineer with a strong emphasis in the fields of automation and remote computer management. I'm only a hobby distiller, but I know very well what needs to be done in order to automate a still and maintain a targeted output proof. It's all just a matter of temperature control and monitoring the result.

The main focus of this project is to read temperatures and control cooling water via servo controlled valves while monitoring the output proof. It's upon on the distiller themselves to take cuts because there is no way to make those determinations electronically without a gas chromatograph mass spectrometer. I don't know any hobbyists who can afford one of those. Even a small used one is $6000 on eBay.

Even though the still that I'm working with is the Still Spirits boiler and their copper T-500 column, this system will work with any other still as long as it has a dephlegmator. I don't have the room for anything larger and don't need to upgrade because of how little I actually drink. I just do distilling as a hobby, electronics and programming are also hobbies of mine. So, here we are. LOL!!!

The T-500 column in its default configuration that Still Spirits designed it is a one-trick-pony. Reflux only, no pot still flavor, pretty much only good for making neutral spirits. Once a person controls the condenser and dephleg cooling separately, it's then possible to run a T-500 in pot still mode and have a more controlled reflux system. This allows you to target a higher proof than the constantly declining proof in pot still mode, while still retaining pot still flavor.

This is a little bit of a juggling act to do manually, no matter what still you happen to use. But it's not hard to automate this and stop your run once the still can no longer deliver the target proof. From that point, you can flip the still controller over to full reflux mode to finish stripping out all of the remaining ethanol which can then be added to another run.

I have no plans of adding any heating control to the system at this time because there's a 50/50 split between people who use gas or electricity. This would require a geared stepper motor that could be attached to a gas valve or a potentiometer on an SCR power controller. I haven't studied stepper motor control any further than troubleshooting my own 3D printers, so this is definitely a wish list item for now.

**This project is a work in progress and components of it will be uploaded here as I feel they are ready for prime-time.**
