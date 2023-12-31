# RPi Smart Still
Raspberry PI and Arduino/ESP32 powered smart still controller system. Designed around the Still Spirits T-500 column and boiler, but can be easily added to any other gas or electric reflux still...Please refer to the [Wiki](https://github.com/larry-athey/rpi-smart-still/wiki) for more information and setup/usage instructions.

You may contact me at https://panhandleponics.com<br>
Subscribe to the official YouTube channel at https://www.youtube.com/@PanhandlePonics<br>

**NOTE:** _Please refrain from contacting me to request interviews or join in on podcasts. I natively speak Geek, I'm not good at explaining things verbally in laymen terminology without preparation and rehearsal._<br><br>

<img width="1024" alt="2023-08-14 16-50-42-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/99d79efd-4ef9-46cd-ae24-7db2d0185869"><br><br>
<img width="1024" alt="2023-08-15 12-07-20-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/dee64786-54f7-4628-b870-9a848907a846"><br><br>
<img width="1024" alt="2023-08-15 12-06-19-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/443844d2-ec12-4a0a-9831-3e3e8a4ffdd7"><br><br>
<img width="1024" alt="2023-08-20 14-34-02-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/03ad11e0-4699-4d16-a72b-26ed88820186"><br>

**NO, THERE IS NOTHING ILLEGAL ABOUT THIS PROJECT! IT DOESN'T MAKE YOU DO ILLEGAL THINGS OR PREVENT YOU FROM GETTING A DISTILLATION LICENSE IN THE USA! THIS IS ONLY A TOOL!**

Let me start off by saying that I'm no master distiller and I don't pretend to be one. YouTube and other social media sites already have more than enough self-idolizing hacks and frauds who think that they're legends in the distilling world and should be on the cast of Moonshiners *(for example, Cyrus Mason and "Windsong", the Still'n The Clear charlatans)*. **I'm just a nobody hobby distiller, period. Not an internet celebrity or a deluded podcaster wannabe, and I don't idolize reality TV "stars". That kind of juvenile mentality is for posers.**

I'm a veteran software engineer with over 43 years programming experience and a strong emphasis in the areas of automation and remote device management. Even though I'm only a hobby distiller, I know very well what needs to be done in order to automate a non-commercial dephleg reflux still and maintain a targeted output. It's all just a matter of temperature control and monitoring the results. Lather, rinse, repeat.

The main focus of this project is to read temperatures and control the heating & cooling water via servos while monitoring the results based on whether it's a pot still or reflux run. It's upon on the distiller themselves to take cuts because there is no way to make those determinations electronically without a gas chromatograph mass spectrometer. Seriously, ChatGPT can't even do it without tastebuds.

Even though the still that I'm working with is the Still Spirits boiler and their copper T-500 column, this system will work with any other personal still as long as it has a dephlegmator. I don't have the room for anything larger and don't need to upgrade because of how little I actually drink. I just do distilling as a hobby, electronics and programming are also hobbies of mine. So, here we are. LOL!!!

The T-500 column in its default configuration that Still Spirits designed it is a one-trick-pony. Reflux only, no pot still flavor, pretty much only good for making neutral spirits. Once a person controls the condenser and dephleg cooling separately, it's then possible to run a T-500 in pot still mode and have a more controlled reflux system. This allows you to target a higher proof than the constantly declining proof in pot still mode, while still retaining pot still flavor.

This is a little bit of a juggling act to do manually, no matter what still you happen to use, but especially so with a T-500. But it's not hard to automate this and stop your run once the still can no longer deliver the target proof. From that point, you can flip the still controller over to maximum reflux mode to finish stripping out all of the remaining ethanol which can then be added to another run.

**This project is a work in progress, this is not release ready code at this time. Components of it are uploaded and updated multiple times per day as development progresses. All I can say is that I'm hoping to have a first official release declared before the end of 2023.**
