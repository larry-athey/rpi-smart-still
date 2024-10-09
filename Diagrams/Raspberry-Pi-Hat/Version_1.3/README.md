This version of the Pi Hat is a perfect fit for Model 3 B+ and Model 4 boards, as well as clones of those models. You would also use this Pi Hat if you are using a Banana Pi or Orange Pi "Zero" board.

If you're actually going to waste a Model 5 board (or clone) on this, you will need to use the v1.4 board instead.

The issue with the logic level shifters in the v1.1 and v1.2 boards is fixed in this version.

Again, keep in mind that **MOST** L298N driver boards will work with 3.3 volt logic and the logic level shifters may not be necessary. You should test your driver boards to see if they only work with 5 volt logic.

**NOTE:** _The ground pins on the U4 and U6 power terminals must be connected together or your Pi won't boot up._
