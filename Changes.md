Changes since the version 1.04 release

---

- Created a completely 3D printable system case that simplifies system builds and caters to both headless and fully self-contained builds with local screen/tablet interfaces.

- A new optional accessory has been added called the Dephleg Driver that replaces the dephleg cooling water valve. This was added after a number of people notified me that they use recirculating cooling water tanks running on impeller driven pond pumps which don't provide enough pressure to keep water flowing when the valve is at 30% or lower.

- LIDAR Hydrometer Reader code updates that simplify the calibration and improves the accuracy by implementing temperature compensation and scientific scale measurement translation.

- All undercarriage timing parameters are now configurable from a new Configure Tuning page added to the management menu. This allows precision tuning of the controller system to the still that it's attached to.

- Improved Boilermaker API integration which allows the system to run the Boilermaker more like a digital SCR controller.

- Boilermaker fallback power setting is now a per-program setting since different power levels are needed based on whether a reflux mode or pot still run is being performed. Previously, the Boilermaker's internal fallback power setting was used.

- Dephleg cooling valve management routines optimized and simplified to make things more accurate with far fewer steps.

- Improvd valve synchronization at distillation run startup and pause/resume to compensate for potential mechanical play/slop issues.

- Heating stepper motor control updated to use 5% of total motor steps rather than using the 10% heat-jump positions for boiler temperature management during a distillation run.

- Two major bugs fixed in the timeline rendering that were preventing flow sensor and Boilermaker power level readings from displaying.

- Numerous user interface tweaks and bug fixes, more touch-screen friendly now.

- A new page has been added to the Wiki that covers all of the configuration settings in the management menu. It seems that there are some people who prefer to print manuals rather than watching how-to videos.
