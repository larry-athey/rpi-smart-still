#----------------------------------------------------------------------------------------------
# RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
#
# Don't rag on my for weird Python code, it's not my primary language. All this was originally
# in C, but the WiringPi library only supports Raspberry Pi (Broadcom) boards, not clones. I've
# had to rewrite everything in Python with the assistance of the Grok AI (x.com) to coach me on
# porting from one language to the other. I honestly detest Python and have no idea how it ever
# became a standard. Whitespace aware unencapsulated shit language, reminds me of 1980's BASIC.
#----------------------------------------------------------------------------------------------
import time
import RPi.GPIO as GPIO

# Dictionary to hold config.ini key=value pairs
config = {}
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
def disposeApp():

  return True
#----------------------------------------------------------------------------------------------
def initValveController():

  return True
#----------------------------------------------------------------------------------------------
def initHeatingController():

  return True
#----------------------------------------------------------------------------------------------
def stepperEnable(Status):

  return True
#----------------------------------------------------------------------------------------------
def stepperPulse(Direction,Steps):

  return True
#----------------------------------------------------------------------------------------------
def valveFullPosition(WhichOne,Direction):

  return True
#----------------------------------------------------------------------------------------------
def valveCalibrate(WhichOne,Direction):

  # Start of event
  start_time = time.perf_counter()

  # Simulating some delay or event 2
  time.sleep(2)

  # End of event
  end_time = time.perf_counter()

  # Calculate the difference in seconds
  time_difference_seconds = end_time - start_time

  # Convert to milliseconds
  time_difference_ms = time_difference_seconds * 1000

  print(f"Time difference in milliseconds: {time_difference_ms:.0f}")

  return True
#----------------------------------------------------------------------------------------------
def valvePulse(WhichOne,Direction,Duration):

  return True
#----------------------------------------------------------------------------------------------
def valveStatus(WhichOne):

  return True
#----------------------------------------------------------------------------------------------
