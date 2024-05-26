# RPi Smart Still
Raspberry PI and Arduino/ESP32 powered smart still controller system. Designed around the Still Spirits T-500 column and boiler, but can be easily added to any other gas or electric still with a dephlegmator. Safe to say that this is the world's first add-on smart still controller because I looked for one before I was forced to build my own. My only other options were to spend $15K on a Genio or iStill...Uh, pass - I'll build one from scratch.

**Please refer to the [Wiki](https://github.com/larry-athey/rpi-smart-still/wiki) for more information and setup/usage instructions.**

You may contact me directly at https://panhandleponics.com<br>
Subscribe to the official YouTube channel at https://www.youtube.com/@PanhandlePonics<br><br>

**NO, THERE IS NOTHING ILLEGAL ABOUT THIS PROJECT! IT DOESN'T MAKE YOU DO ILLEGAL THINGS OR PREVENT YOU FROM GETTING A DISTILLATION LICENSE IN THE USA! THIS IS ONLY A TOOL!**<br><br>

Let me start off by saying that I'm no master distiller and I don't pretend to be one. YouTube and other social media sites already have more than enough self-idolizing hacks and frauds _(often sporting a Popcorn Sutton photo as their profile picture)_ who think that they're legends in the distilling world and should be on the cast of Moonshiners _(a'la, "Cyrus Mason" and "Windsong", the Still'n The Clear charlatans)_. **I'm just a nobody hobby distiller, period. Not an internet celebrity or a deluded podcaster wannabe, and I most certainly do not idolize reality TV "stars". That kind of mentality is for juveniles and posers.**

I'm a veteran software engineer with over 43 years programming experience and a strong emphasis in the areas of automation and remote device management. Even though I'm only a hobby distiller, I know very well what needs to be done in order to automate a non-commercial dephleg reflux still and maintain a targeted output. It's all just a matter of temperature control and monitoring the results. Lather, rinse, repeat.

The main focus of this project is to read temperatures and control the heating & cooling water via servos while monitoring the results based on whether it's a pot still or reflux run. Also, be able to control these actions if a person wants to maintain a minimum proof in a pot still run. Plus, be able to shut down a pot still run once a minimum proof or output flow rate has been reached, in order to eliminate tails from the distillate.

This project is also not intended to replace things like the **AirStill Pro** or **MyVodkaMaker**, this is meant to be added to an existing higher volume still that can deliver more product per hour. If anything, it's intended to give a person most of the conveniences of a Genio or iStill using your own existing equipment and save you from a $15,000 (or more) investment.

**NOTE:** _It's upon on the distiller themselves to take cuts because there is no way to make those determinations electronically without a gas chromatograph / mass spectrometer. Seriously, ChatGPT can't even do this without tastebuds. Honestly, how could a computer know what tastes **you** prefer and find them in your distillate?_

Even though the still that I'm working with is the Still Spirits boiler and their copper T-500 column, this system will work with any other personal still as long as it has a dephlegmator. I don't have the room for anything larger and don't need to upgrade because of how little I actually drink. I just do distilling as a hobby, electronics and programming are also hobbies of mine. So, here we are. LOL!!!

Yes, contrary to what you see in YouTube videos and in that T-500 users group on Facebook, it actually can be used for more than just making flavored distilled sugar wash. I'm not on Facebook anymore, but when I was, I just had to shake my head at all of those T-500 users who thought they were actually making whiskey by dumping a bottle of essence in some 90% lighter fluid and then complaining about how bad it tastes. LOL!!!

The T-500 column in its default configuration that Still Spirits designed it is a one-trick-pony. Reflux only, no pot still flavor, pretty much only good for making neutral spirits. Once a person controls the condenser and dephleg cooling separately, it's then possible to run a T-500 in pot still mode and have a more controlled reflux system. This allows you to target a higher proof than the constantly declining proof in pot still mode, while still retaining pot still flavor.

This is a little bit of a juggling act to do manually, no matter what still you happen to use, but especially so with a T-500. But it's not hard to automate this and stop your run once the still can no longer deliver the target proof. From that point, you can flip the still controller over to maximum reflux mode to finish stripping out all of the remaining ethanol which can then be added to another run.

**NOTE:** _This is only intended for hobbyist and small business micro distillers. Commercial distilleries use continuous column stills, this system would be of no value or use in that kind of setting. The target audience for this system are those who want to have the convenience of hands-free reproducibility or just want to tame down a touchy still._

<br><img width="1024" alt="2023-08-14 16-50-42-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/99d79efd-4ef9-46cd-ae24-7db2d0185869"><br><br>
<img width="1024" alt="2023-08-15 12-07-20-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/dee64786-54f7-4628-b870-9a848907a846"><br><br>
<img width="1024" alt="2023-08-15 12-06-19-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/443844d2-ec12-4a0a-9831-3e3e8a4ffdd7"><br><br>
<img width="1024" alt="2023-08-20 14-34-02-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/03ad11e0-4699-4d16-a72b-26ed88820186"><br><br>
<img width="1024" alt="2023-05-11 00-00-00-0" src="https://github.com/larry-athey/rpi-smart-still/assets/121518798/0bb3de80-8381-46cc-891a-b38762a76548">
