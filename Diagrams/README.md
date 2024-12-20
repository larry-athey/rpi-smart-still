
![Raspberry, Orange, Banana, Banana Zero](https://github.com/user-attachments/assets/9bfcdb60-92fe-4e8e-aca0-7be427dbd868)

As of October 1, 2024 I am now only encouraging and supporting builds based on the Raspberry Pi Hat. If you are the adventurous type, you are still more than welcome to build a prototype system. I'm only maintaining that information here and the original videos on the prototype system so people can gather a better understanding of how things work at a low level. As time allows, I will be creating new videos related to builds based on the Raspberry Pi Hat.

The official hydrometer interface for the system is the Bird Brain _(LIDAR hydrometer reader)_ and capacitive flow sensor. You are still free to use the older Load Cell Hydrometer if you wish, but it is no longer considered a supported project. You will need to enable support for the Load Cell Hydrometer by changing the settings.hydro_type database field value to 0 in order to use it with the system.

**NOTE:** _While the name "Raspberry Pi" is used frequently here, please keep in mind that this system is compatible with Raspberry Pi clones as well. It's no big secret, the Raspberry Pi drought caused a lot of clones to be created that actually out-spec and out-perform the original at drastically lower prices. As of the Model 5, the company has made it clear that they are no longer concerned with the functionality of older models and Raspbian 12 has broken a lot of their capabilities. As well as rendering **years** of online documentation no longer valid._

**ALSO:** _Due to this blatantly underhanded money grab to recover their losses from the drought, I'm now only supporting Raspbian up through version 11 on pre Model 5 Raspberry Pi units and focusing more on supporting Debian for ARM (Armbian) on Raspberry Pi clones._
