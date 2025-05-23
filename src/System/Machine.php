<?php

namespace BlueFission\System;

use BlueFission\Val;
use BlueFission\Str;
use BlueFission\Date;

/**
 * Class Machine
 *
 * A class that provides information about the system and its components.
 *
 * @package BlueFission\System
 */
class Machine
{
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
    public function __construct()
    {
        $this->_system = new System();
    }

    /**
     * Returns the operating system of the machine
     *
     * @return string The operating system name
     */
    public function getOS()
    {
        return PHP_OS_FAMILY;  // More standardized way of getting OS type
    }

    /**
     * Returns the current memory usage of the machine
     *
     * @return int The current memory usage in bytes
     */
    public function getMemoryUsage()
    {
        return memory_get_usage(true);
    }

    /**
     * Returns the peak memory usage of the machine
     *
     * @return int The peak memory usage in bytes
     */
    public function getMemoryPeakUsage()
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Returns the uptime of the machine
     *
     * @return int The uptime in seconds
     */
    public function getUptime()
    {
        if ($this->getOS() == 'Windows') {
            // Windows does not have a built-in, easy way to fetch uptime from CLI
            // Fallback to systeminfo command, parse output for uptime
            $this->_system->run('systeminfo | find "System Boot Time:"');
            $boottime = $this->_system->response();

            // extract the date from the line
            $boottimeParts = explode(":", $boottime);
            $boottime = Str::trim($boottimeParts[1] . ":" . $boottimeParts[2] . ":" . $boottimeParts[3]);

            $uptime = Date::diff($boottime, Date::now()->val(), 'seconds');

            return $uptime;
        } else {
            $uptime = explode(" ", file_get_contents("/proc/uptime"));
            return (int)$uptime[0];
        }
    }

    /**
     * Returns the CPU usage of the machine
     *
     * @return float The CPU usage as a decimal
     */
    public function getCPUUsage()
    {
        if (Str::sub(\php_uname(), 0, 7) == "Windows") {
            // Windows command for getting CPU usage
            $this->_system->run("WMIC CPU GET LoadPercentage");
            $response = $this->_system->response();

            //extract the numerical value from the response
            $cpuUsage = preg_replace("/[^0-9]/", "", $response);

            return $cpuUsage;
        } else {
            $load = \sys_getloadavg();
            return $load[0];
        }
    }

    /**
     * Returns the temperature of the machine
     *
     * @return string The temperature information
     */
    public function getTemperature()
    {
        $temperature = "";
        if (Str::sub(\php_uname(), 0, 7) == "Windows") {
            // Windows command for getting temperature
            $this->_system->run("WMIC /Namespace:\\\\root\\WMI PATH MSAcpi_ThermalZoneTemperature GET CurrentTemperature");
            $temperature = $this->_system->response();
        } else {
            // Linux command for getting temperature
            $possiblePaths = [
                "/sys/class/thermal/thermal_zone*/temp",
                "/sys/devices/virtual/thermal/thermal_zone*/temp",
                "/sys/devices/platform/coretemp.0/hwmon/hwmon*/temp*_input",
                "/sys/bus/acpi/devices/LNXTHERM:*/thermal_zone/temp",
                "/sys/class/hwmon/hwmon*/temp*_input",
                // Add more possible paths here if needed
            ];

            foreach ($possiblePaths as $path) {
                $matches = glob($path);
                if (!Val::isEmpty($matches)) {
                    $this->_system->run("cat {$matches[0]}");
                    $temperature = $this->_system->response();
                    break;
                }
            }
        }
        return $temperature . "°C";
    }

    /**
     * Returns the fan speed of the machine
     *
     * @return string The fan speed information
     */
    public function getFanSpeed()
    {
        $fanSpeed = "";
        if (Str::sub(\php_uname(), 0, 7) == "Windows") {
            // Windows command for getting fan speed
            $this->_system->run("WMIC /Node:localhost PATH Win32_Fan GET Descriptions, VariableSpeed");
            $fanSpeed = $this->_system->response();
        } else {
            // Linux command for getting fan speed
            $possiblePaths = [
                "/proc/acpi/fan/FAN*/state",
                "/sys/devices/platform/applesmc.768/fan*_input",
                "/sys/devices/virtual/thermal/cooling_device*/cur_state",
                "/sys/class/hwmon/hwmon*/pwm*_enable",
                "/sys/class/hwmon/hwmon*/fan*_input",
                // Add more possible paths here if needed
            ];

            foreach ($possiblePaths as $path) {
                $matches = glob($path);
                if (!Val::isEmpty($matches)) {
                    $this->_system->run("cat {$matches[0]}");
                    $fanSpeed = $this->_system->response();
                    break;
                }
            }
        }
        return $fanSpeed . " RPM";
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
    public function getPowerConsumption()
    {
        $powerConsumption = "";
        if (Str::sub(\php_uname(), 0, 7) == "Windows") {
            // Windows command for getting power consumption
            $this->_system->run("WMIC /Node:localhost PATH Win32_PerfFormattedData_PerfOS_System GET ProcessorQueueLength");
            $powerConsumption = $this->_system->response();
        } else {
            // Linux command for getting power consumption
            $potentialPaths = [
                "/sys/class/power_supply/BAT0/power_now",
                "/sys/class/power_supply/BAT0/energy_now",
                "/sys/class/power_supply/BAT1/power_now",
                "/sys/class/power_supply/BAT1/energy_now",
            ];

            foreach ($potentialPaths as $path) {
                $matches = glob($path);
                if (!Val::isEmpty($matches)) {
                    $this->_system->run("cat {$matches[0]}");
                    $powerConsumption = $this->_system->response();
                    break;
                }
            }
        }
        return $powerConsumption . " W";
    }
}
