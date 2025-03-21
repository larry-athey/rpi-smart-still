# RPi Smart Still
Raspberry Pi (or clone) and Arduino/ESP32 powered smart still controller system. Designed around the Still Spirits T500 column and boiler, but can be easily added to any other gas or electric still with a dephlegmator. Safe to say that this is the world's first add-on smart still controller because I looked for one before I was forced to build my own. My only other option was to spend $15K on a Genio or iStill...Uh, pass - I'll build one from scratch.

**Please refer to the [Wiki](https://github.com/larry-athey/rpi-smart-still/wiki) for more information and setup/usage instructions.**

You may contact me directly at https://panhandleponics.com<br>
Subscribe to the official YouTube channel at https://www.youtube.com/@PanhandlePonics

_If you have an Air Still (or clone) be sure to check out my [Airhead](https://github.com/larry-athey/airhead) project if you'd like to upgrade that appliance and make it more useful. Or check out my [Boilermaker](https://github.com/larry-athey/boilermaker) project if you'd like a stand-alone version of its boiler power and temperature controller to use with the RPi Smart Still controller system._<br><br>

**NO, THERE IS NOTHING ILLEGAL ABOUT THIS PROJECT! IT DOESN'T MAKE YOU DO ILLEGAL THINGS OR PREVENT YOU FROM GETTING A DISTILLATION LICENSE IN THE USA! THIS IS ONLY A TOOL!**<br><br>

**Facebook Idiot:** _Why would you bother?_<br>
**Me:** It's only a "bother" for somebody who couldn't do it on their best day. I'd rather have nicer things.

Let me start off by saying that I'm no master distiller and I don't pretend to be one. YouTube and other social media sites already have more than enough self-idolizing hacks and frauds _(usually sporting a Popcorn Sutton photo as their profile picture)_ who think that they're legends in the distilling world and should be on the cast of Moonshiners _(a'la, "Cyrus Mason" and "Windsong", the Still'n The Clear charlatans)_. **I'm just a nobody hobby distiller. Not an internet celebrity or a deluded podcaster wannabe, and I most certainly do not idolize reality TV "stars". That kind of mentality is for juveniles and posers. I'm simply a hacker, period.**

I'm a veteran software engineer with over 45 years programming experience and a strong emphasis in the areas of automation and remote device management. Even though I'm only a hobby distiller, I know very well what needs to be done in order to automate a non-commercial dephleg reflux still and maintain a targeted output. It's all just a matter of temperature control and monitoring results, same things that a human does.

The main focus of this project is to read temperatures and control the heating & cooling water via servos while monitoring the results based on whether it's a pot still or reflux run. Also, be able to control these actions if a person wants to maintain a minimum proof in a pot still run. Plus, be able to shut down a pot still run once a minimum proof or output flow rate has been reached, in order to eliminate tails from the distillate.

This project is also not intended to replace things like the **AirStill Pro** or **MyVodkaMaker**, this is meant to be added to an existing higher volume still that can deliver more product per hour. If anything, it's intended to provide a person most of the conveniences of a Genio or iStill using your own existing equipment and save you from a $15,000 (or more) investment. This system costs less than $500 and works with your cell phone.

_**NOTE:** It's upon on the distiller themselves to take cuts because there is no way to make flavor determinations electronically without the use of gas chromatography. Please see my [Cutting Board](https://github.com/larry-athey/cutting-board) project for a more simplified alternative that automatically swaps out jars once they are full._

Even though the still that I'm working with is the Still Spirits boiler and their copper T500 column, this system will work with any other personal still as long as it has a dephlegmator. I don't have the room for anything larger and don't need to upgrade because of how little I actually drink. I just do distilling as a hobby and for this project. Electronics, robotics, and programming are also hobbies of mine. So, here we are. LOL!!!

Yes, contrary to what you see in YouTube videos and in that T500 users group on Facebook, it actually can be used for more than just making flavored distilled sugar wash. I'm not on Facebook anymore, but when I was, I just had to shake my head at all of those T500 users who thought they were actually making whiskey by dumping a bottle of essence in some 90% lighter fluid and then complaining about how bad it tastes. LOL!!!

The T500 column in its default configuration that Still Spirits designed it is a one-trick-pony. Reflux only, no pot still flavor, pretty much only good for making neutral spirits. Once a person controls the condenser and dephleg cooling separately, it's then possible to run a T500 in pot still mode and have a more controlled reflux system. This allows you to target a higher proof than the constantly declining proof in pot still mode, while still retaining pot still flavor. Best of all, this can be accomplished totally hands free.

This is a little bit of a juggling act to do manually, no matter what still you happen to use, but especially so with a T500. But it's not hard to automate this and stop your run once the still can no longer deliver the target proof. From that point, you can flip the still controller over to maximum reflux mode to finish stripping out all of the remaining ethanol as a totally neutral spirit, which can then be added to another run.

_**FYI:** This is only intended for hobbyist and small business micro distillers. Commercial distilleries use continuous column stills, this system would be of no value or use in that kind of setting. The target audience for this system are those who want to have the convenience of hands-free reproducibility or just want to tame down a touchy still._

<br><img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/7c329bd6-2925-471e-895e-508ff79dc1e5"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/b32f3226-20d1-4fcc-816d-5b6759123e04"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/d018d39a-29b2-4b84-b7fc-e1e11e623ff0"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/4bfd6090-3d98-42cd-8d09-7a9dec18ca1d"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/fbde1696-560b-4bf5-b7ab-8872883ebedf"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/5cc6f707-35e3-481b-8e75-02739034dc1a"><br><br>
<img width="1024" alt="2024-09-25 14-34-02-0" src="https://github.com/user-attachments/assets/e691e82d-7098-4b75-84cb-c16d310b94a7">
