<?php
namespace BlueFission\Tests;

use BlueFission\Utils\Machine;
use PHPUnit\Framework\TestCase;

class MachineTest extends TestCase {

  public function testGetOS() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsString($machine->getOS());
  }

  public function testGetMemoryUsage() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsInt($machine->getMemoryUsage());
  }

  public function testGetMemoryPeakUsage() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsInt($machine->getMemoryPeakUsage());
  }

  public function testGetUptime() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsInt($machine->getUptime());
  }

  public function testGetCPUUsage() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsFloat($machine->getCPUUsage());
  }

  public function testGetTemperature() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsString($machine->getTemperature());
  }

  public function testGetFanSpeed() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsString($machine->getFanSpeed());
  }

  public function testGetPowerConsumption() {
    $machine = new \BlueFission\System\Machine();
    $this->assertIsString($machine->getPowerConsumption());
  }

}
