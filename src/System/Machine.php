<?php
namespace BlueFission\System;

/**
 * Class Machine
 *
 * A class that provides information about the system and its components.
 *
 * @package BlueFission\System
 */
class Machine {

    /**
     * Private instance of System class
     *
     * @var System $_system
     */
    private $_system;

    /**
     * Machine constructor.
     *
     * Initializes an instance of the System class.
     */
    public function __construct() {
        $this->_system = new System();
    }

    /**
     * Returns the operating system of the machine
     *
     * @return string The operating system name
     */
    public function getOS() {
      return PHP_OS;
    }

    /**
     * Returns the current memory usage of the machine
     *
     * @return int The current memory usage in bytes
     */
    public function getMemoryUsage() {
      return memory_get_usage(true);
    }

    /**
     * Returns the peak memory usage of the machine
     *
     * @return int The peak memory usage in bytes
     */
    public function getMemoryPeakUsage() {
      return memory_get_peak_usage(true);
    }

    /**
     * Returns the uptime of the machine
     *
     * @return int The uptime in seconds
     */
    public function getUptime() {
      $uptime = explode(" ", file_get_contents("/proc/uptime"));
      return $uptime[0];
    }

    /**
     * Returns the CPU usage of the machine
     *
     * @return float The CPU usage as a decimal
     */
    public function getCPUUsage() {
      $load = sys_getloadavg();
      return $load[0];
    }

    /**
     * Returns the temperature of the machine
     *
     * @return string The temperature information
     */
    public function getTemperature() {
        $temperature = "";
        if (\substr(\php_uname(), 0, 7) == "Windows") { 
            // Windows command for getting temperature
            $this->_system->run("WMIC /Namespace:\\\\root\\WMI PATH MSAcpi_ThermalZoneTemperature GET CurrentTemperature");
            $temperature = $this->_system->_response;
        } else {
            // Linux command for getting temperature
            $this->_system->run("cat /sys/class/thermal/thermal_zone*/temp");
            $temperature = $this->_system->_response;
        }
        return $temperature;
    }

    /**
     * Returns the fan speed of the machine
     *
     * @return string The fan speed information
     */
    public function getFanSpeed() {
        $fanSpeed = "";
        if (\substr(\php_uname(), 0, 7) == "Windows") { 
            // Windows command for getting fan speed
            $this->_system->run("WMIC /Node:localhost PATH Win32_Fan GET Descriptions, VariableSpeed");
            $fanSpeed = $this->_system->_response;
        } else {
            // Linux command for getting fan speed
            $this->_system->run("cat /proc/acpi/fan/FAN*/state");
            $fanSpeed = $this->_system->_response;
        }
        return $fanSpeed;
    }

    /**
     * Get the power consumption of the system.
     * 
     * This method returns the power consumption of the system by executing
     * the relevant command for the operating system. If the system is running
     * on Windows, it uses the WMIC command to get the ProcessorQueueLength.
     * If the system is running on Linux, it uses the "cat" command to get the
     * power_now value from the BAT0 power supply.
     * 
     * @return string The power consumption of the system.
     */
    public function getPowerConsumption() {
        $powerConsumption = "";
        if (\substr(\php_uname(), 0, 7) == "Windows") { 
            // Windows command for getting power consumption
            $this->_system->run("WMIC /Node:localhost PATH Win32_PerfFormattedData_PerfOS_System GET ProcessorQueueLength");
            $powerConsumption = $this->_system->_response;
        } else {
            // Linux command for getting power consumption
            $this->_system->run("cat /sys/class/power_supply/BAT0/power_now");
            $powerConsumption = $this->_system->_response;
        }
        return $powerConsumption;
    }

}
