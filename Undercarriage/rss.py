#----------------------------------------------------------------------------------------------
# RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
#
# Don't rag on my weird ass Python code, it's not my primary language. All this was originally
# in C, but the WiringPi library only supports Raspberry Pi (Broadcom) boards, not clones. I've
# had to rewrite everything in Python with the assistance of the Grok AI (x.com) to coach me on
# porting from one language to the other. I honestly detest Python and have no idea how it ever
# became a standard. Whitespace aware unencapsulated shit language, reminds me of 1980's BASIC.
#----------------------------------------------------------------------------------------------
import time
import RPi.GPIO as GPIO

config = {}
STEPPER_MS = 5 # 5 ms sleep time between Nema 17 heating stepper motor pulses
#----------------------------------------------------------------------------------------------
def readConfig():
  global config

  try:
    # Open the config file
    with open("/usr/share/rpi-smart-still/config.ini","r") as file:
      # Read and process each line
      for line in file:
        # Strip leading/trailing whitespace
        line = line.strip()

        # Skip comments and empty lines
        if not line or line.startswith(";") or line.startswith("#"):
          continue

        # Split on the first '=' to handle values that might contain '='
        try:
          key,value = line.split("=",1)
          # Strip any surrounding whitespace from key and value
          key = key.strip()
          value = value.strip()

          # Remove quotes from value if present
          if value.startswith('"') and value.endswith('"') or value.startswith("'") and value.endswith("'"):
            value = value[1:-1]

          # Convert value to appropriate type if needed (e.g., int, float, bool)
          if value.lower() == "true":
            value = True
          elif value.lower() == "false":
            value = False
          elif value.isdigit():
            value = int(value)
          elif value.replace(".","").isdigit():
            value = float(value)

          # Store in dictionary
          config[key] = value

        except ValueError:
          # Handle lines that do not contain '='
          print(f"Warning: Line '{line}' does not contain '=' and was ignored.")

  except (IOError,OSError) as e:
    print(f"An error occurred while opening config file: {e}")
    return False

  return True
#----------------------------------------------------------------------------------------------
def sleep_ms(milliseconds):
  seconds = milliseconds / 1000.0
  time.sleep(seconds)

  return True
#----------------------------------------------------------------------------------------------
def disposeApp():

  return True
#----------------------------------------------------------------------------------------------
def initValveController():
  GPIO.setmode(GPIO.BCM)
  GPIO.setwarnings(False)
  GPIO.setup(config.get("VALVE1_OPEN"),GPIO.OUT)
  GPIO.setup(config.get("VALVE1_CLOSE"),GPIO.OUT)
  GPIO.setup(config.get("VALVE1_LIMIT_OPEN"),GPIO.IN)
  GPIO.setup(config.get("VALVE1_LIMIT_CLOSE"),GPIO.IN)
  GPIO.setup(config.get("VALVE2_OPEN"),GPIO.OUT)
  GPIO.setup(config.get("VALVE2_CLOSE"),GPIO.OUT)
  GPIO.setup(config.get("VALVE2_LIMIT_OPEN"),GPIO.IN)
  GPIO.setup(config.get("VALVE2_LIMIT_CLOSE"),GPIO.IN)

  return True
#----------------------------------------------------------------------------------------------
def initHeatingController():
  GPIO.setmode(GPIO.BCM)
  GPIO.setwarnings(False)
  GPIO.setup(config.get("STEPPER_ENABLE"),GPIO.OUT)
  GPIO.setup(config.get("STEPPER_PULSE"),GPIO.OUT)
  GPIO.setup(config.get("STEPPER_DIR"),GPIO.OUT)

  return True
#----------------------------------------------------------------------------------------------
def initRelayController():
  GPIO.setmode(GPIO.BCM)
  GPIO.setwarnings(False)
  GPIO.setup(config.get("RELAY1"),GPIO.OUT)
  GPIO.setup(config.get("RELAY2"),GPIO.OUT)
  return True
#----------------------------------------------------------------------------------------------
def relayToggle(WhichOne,Status):
  if WhichOne == 1:
    if Status == 1:
      GPIO.output(config.get("RELAY1"),GPIO.HIGH)
    else:
      GPIO.output(config.get("RELAY1"),GPIO.LOW)
  else:
    if Status == 1:
      GPIO.output(config.get("RELAY2"),GPIO.HIGH)
    else:
      GPIO.output(config.get("RELAY2"),GPIO.LOW)

  return True
#----------------------------------------------------------------------------------------------
def stepperEnable(Status):
  if Status == 1:
    GPIO.output(config.get("STEPPER_ENABLE"),GPIO.HIGH)
  else:
    GPIO.output(config.get("STEPPER_ENABLE"),GPIO.LOW)

  return True
#----------------------------------------------------------------------------------------------
def stepperPulse(Direction,Steps):
  if Direction == 1:
    GPIO.output(config.get("STEPPER_DIR"),GPIO.HIGH)
  else:
    GPIO.output(config.get("STEPPER_DIR"),GPIO.LOW)
  for x in range(1,Steps + 1):
    GPIO.output(config.get("STEPPER_PULSE"),GPIO.HIGH)
    sleep_ms(STEPPER_MS)
    GPIO.output(config.get("STEPPER_PULSE"),GPIO.LOW)
    sleep_ms(STEPPER_MS)

  return True
#----------------------------------------------------------------------------------------------
def valveFullPosition(WhichOne,Direction):
  if WhichOne == 1:
    if Direction == 0:
      GPIO.output(config.get("VALVE1_CLOSE"),GPIO.HIGH)
      while GPIO.input(config.get("VALVE1_LIMIT_CLOSE")) == GPIO.HIGH:
        sleep_ms(10)
      GPIO.output(config.get("VALVE1_CLOSE"),GPIO.LOW)
    else:
      GPIO.output(config.get("VALVE1_OPEN"),GPIO.HIGH)
      while GPIO.input(config.get("VALVE1_LIMIT_OPEN")) == GPIO.HIGH:
        sleep_ms(10)
      GPIO.output(config.get("VALVE1_OPEN"),GPIO.LOW)
  else:
    if Direction == 0:
      GPIO.output(config.get("VALVE2_CLOSE"),GPIO.HIGH)
      while GPIO.input(config.get("VALVE2_LIMIT_CLOSE")) == GPIO.HIGH:
        sleep_ms(10)
      GPIO.output(config.get("VALVE2_CLOSE"),GPIO.LOW)
    else:
      GPIO.output(config.get("VALVE2_OPEN"),GPIO.HIGH)
      while GPIO.input(config.get("VALVE2_LIMIT_OPEN")) == GPIO.HIGH:
        sleep_ms(10)
      GPIO.output(config.get("VALVE2_OPEN"),GPIO.LOW)

  return True
#----------------------------------------------------------------------------------------------
def valveCalibrate(WhichOne,Direction):
  if Direction == 0:
    valveFullPosition(WhichOne,1)
    start_time = time.perf_counter()
    valveFullPosition(WhichOne,0)
    end_time = time.perf_counter()
    time_difference_seconds = end_time - start_time
    time_difference_ms = time_difference_seconds * 1000
    print(f"{time_difference_ms:.0f}")
  else:
    valveFullPosition(WhichOne,0)
    start_time = time.perf_counter()
    valveFullPosition(WhichOne,1)
    end_time = time.perf_counter()
    time_difference_seconds = end_time - start_time
    time_difference_ms = time_difference_seconds * 1000
    print(f"{time_difference_ms:.0f}")

  return True
#----------------------------------------------------------------------------------------------
def valvePulse(WhichOne,Direction,Duration):
  if WhichOne == 1:
    if Direction == 1:
      GPIO.output(config.get("VALVE1_CLOSE"),GPIO.HIGH)
      sleep_ms(Duration)
      GPIO.output(config.get("VALVE1_CLOSE"),GPIO.LOW)
    else:
      GPIO.output(config.get("VALVE1_OPEN"),GPIO.HIGH)
      sleep_ms(Duration)
      GPIO.output(config.get("VALVE1_OPEN"),GPIO.LOW)
  else:
    if Direction == 1:
      GPIO.output(config.get("VALVE2_CLOSE"),GPIO.HIGH)
      sleep_ms(Duration)
      GPIO.output(config.get("VALVE2_CLOSE"),GPIO.LOW)
    else:
      GPIO.output(config.get("VALVE2_OPEN"),GPIO.HIGH)
      sleep_ms(Duration)
      GPIO.output(config.get("VALVE2_OPEN"),GPIO.LOW)

  return True
#----------------------------------------------------------------------------------------------
def valveStatus(WhichOne):
  if WhichOne == 1:
    if GPIO.input(config.get("VALVE1_LIMIT_OPEN")) == GPIO.LOW:
      print("1")
    elif GPIO.input(config.get("VALVE1_LIMIT_CLOSE")) == GPIO.LOW:
      print("0")
    else:
      print("10")
  else:
    if GPIO.input(config.get("VALVE2_LIMIT_OPEN")) == GPIO.LOW:
      print("1")
    elif GPIO.input(config.get("VALVE2_LIMIT_CLOSE")) == GPIO.LOW:
      print("0")
    else:
      print("10")

  return True
#----------------------------------------------------------------------------------------------
